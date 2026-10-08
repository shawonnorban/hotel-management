<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookedInfo;
use App\Models\Customerinfo;
use App\Models\FolioCharge;
use App\Models\TblOtherguest;
use App\Services\WhatsAppService;
use App\Support\AppSettings;
use App\Support\Settings;
use App\Support\Uploads;
use App\Models\PaymentMethod;
use App\Models\Promocode;
use App\Models\Roomdetails;
use App\Services\BookingService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\ReservationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

class ReservationController extends Controller
{
    public const SOURCES = ['phone' => 'Phone', 'walk-in' => 'Walk-in', 'email' => 'Email', 'agent' => 'Travel agent', 'other' => 'Other'];

    public function __construct(
        private ReservationService $reservations,
        private BookingService $bookings,
        private PaymentService $payments,
        private InvoiceService $invoices,
    ) {}

    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(BookedInfo::STATUS_LABELS))],
            'view' => ['nullable', 'in:arrivals,departures,inhouse,unpaid'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $bookings = BookedInfo::with('customer')
            ->when($f['q'] ?? null, function ($query, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn ($q) => $q->where('booking_number', 'like', $like)
                    ->orWhere('full_guest_name', 'like', $like)
                    ->orWhere('room_no', $term)
                    ->orWhereHas('customer', fn ($c) => $c->where('email', 'like', $like)->orWhere('cust_phone', 'like', $like)->orWhere('firstname', 'like', $like)->orWhere('lastname', 'like', $like)));
            })
            ->when(($f['status'] ?? '') !== '', fn ($q) => $q->where('bookingstatus', $f['status']))
            ->when($f['view'] ?? null, fn ($q, $v) => match ($v) {
                'arrivals' => $q->whereDate('checkindate', today())->whereIn('bookingstatus', ['0', '2']),
                'departures' => $q->whereDate('checkoutdate', today())->where('bookingstatus', '4'),
                'inhouse' => $q->where('bookingstatus', '4'),
                'unpaid' => $q->whereNotIn('bookingstatus', ['1'])->whereColumn('paid_amount', '<', 'total_price'),
            })
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('checkindate', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('checkindate', '<=', $v))
            ->orderByDesc('bookedid')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reservations.index', ['bookings' => $bookings, 'f' => $f]);
    }

    public function create(Request $request)
    {
        return view('admin.reservations.form', $this->formData(null) + [
            'prefill' => $request->only(['checkin', 'checkout', 'room', 'guest']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateBooking($request, true);

        try {
            $guest = $this->resolveGuest($data);
            $room = Roomdetails::findOrFail($data['room']);
            $checkin = Carbon::parse($data['checkin']);
            $promo = $this->promo($data, $room, $checkin);
            $method = ! empty($data['deposit_method']) ? PaymentMethod::find($data['deposit_method']) : null;

            $booking = $this->reservations->create(
                $guest, $room, $checkin, Carbon::parse($data['checkout']), (int) $data['rooms'], (int) $data['adults'], (int) ($data['children'] ?? 0),
                $data['guest_name'] ?? null, $data['special'] ?? null, $promo, $data['source'], auth('admin')->id(),
                isset($data['deposit']) ? (float) $data['deposit'] : null, $method,
            );
            $this->saveGuests($request, $booking);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()->route('admin.reservations.show', $booking->booking_number)->with('status', 'Reservation #'.$booking->booking_number.' created.');
    }

    public function show(BookedInfo $booking)
    {
        $booking->load('customer', 'payments', 'charges', 'events.user', 'guests');

        return view('admin.reservations.show', [
            'booking' => $booking,
            'methods' => PaymentMethod::where('is_active', 1)->orderBy('payment_method_id')->get(),
            'allMethods' => PaymentMethod::orderBy('payment_method_id')->get(),
            'roomType' => Roomdetails::find((int) explode(',', (string) $booking->roomid)[0]),
            'lines' => $this->invoices->lines($booking),
            'wa' => $this->whatsappLink($booking),
        ]);
    }

    private function whatsappLink(BookedInfo $booking): ?string
    {
        $phone = $booking->customer?->cust_phone;
        if (! $phone) {
            return null;
        }
        $text = strtr((string) AppSettings::get('whatsapp.greeting', 'Hello {guest}, this is {hotel}. Regarding your booking {booking}: '), ['{guest}' => $booking->customer->firstname, '{hotel}' => Settings::hotelName(), '{booking}' => '#'.$booking->booking_number]);

        try {
            return app(WhatsAppService::class)->link($phone, $text);
        } catch (InvalidArgumentException) {
            return null; // phone number too short to be dialled internationally
        }
    }

    public function edit(BookedInfo $booking)
    {
        abort_unless(in_array((string) $booking->bookingstatus, ['0', '2'], true), 403, 'Only pending or confirmed bookings can be changed.');

        return view('admin.reservations.form', $this->formData($booking));
    }

    public function update(Request $request, BookedInfo $booking)
    {
        $data = $this->validateBooking($request, false);

        try {
            $room = Roomdetails::findOrFail($data['room']);
            $checkin = Carbon::parse($data['checkin']);
            $this->reservations->modify($booking, $room, $checkin, Carbon::parse($data['checkout']), (int) $data['rooms'], (int) $data['adults'], (int) ($data['children'] ?? 0), $this->promo($data, $room, $checkin, $booking), auth('admin')->id());
            $booking->update(['full_guest_name' => $data['guest_name'] ?? $booking->full_guest_name, 'special_request' => $data['special'] ?? null]);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()->route('admin.reservations.show', $booking->booking_number)->with('status', 'Reservation updated.');
    }

    /** JSON price & availability preview for the booking form. */
    public function quote(Request $request)
    {
        $d = $request->validate([
            'room' => ['required', 'integer'],
            'checkin' => ['required', 'date'],
            'checkout' => ['required', 'date', 'after:checkin'],
            'rooms' => ['required', 'integer', 'min:1', 'max:20'],
            'promo' => ['nullable', 'string', 'max:50'],
            'booking' => ['nullable', 'string', 'max:30'],
        ]);

        $room = Roomdetails::findOrFail($d['room']);
        $in = Carbon::parse($d['checkin']);
        $out = Carbon::parse($d['checkout']);
        $current = ! empty($d['booking']) ? BookedInfo::where('booking_number', $d['booking'])->value('bookedid') : null;
        $promo = ! empty($d['promo']) ? $this->bookings->findPromo($d['promo'], $room, $in) : null;
        $quote = $this->bookings->quote($room, $in, $out, (int) $d['rooms'], $promo);

        return response()->json($quote + [
            'available' => count($this->bookings->availableRoomNumbers((int) $room->roomid, $in, $out, $current ? (int) $current : null)),
            'promo_valid' => ! empty($d['promo']) ? (bool) $promo : null,
            'capacity' => (int) $room->capacity * (int) $d['rooms'],
        ]);
    }

    public function confirm(BookedInfo $booking)
    {
        return $this->act(fn () => $this->reservations->confirm($booking, auth('admin')->id()), 'Booking confirmed.', $booking);
    }

    public function checkIn(BookedInfo $booking)
    {
        return $this->act(fn () => $this->reservations->checkIn($booking, auth('admin')->id()), 'Guest checked in.', $booking);
    }

    public function checkOut(Request $request, BookedInfo $booking)
    {
        return $this->act(fn () => $this->reservations->checkOut($booking, auth('admin')->id(), $request->boolean('on_account')), 'Guest checked out.', $booking);
    }

    public function cancel(Request $request, BookedInfo $booking)
    {
        $d = $request->validate([
            'refund' => ['nullable', 'numeric', 'min:0'],
            'method' => ['nullable', 'integer', 'exists:payment_method,payment_method_id'],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        return $this->act(fn () => $this->reservations->cancel($booking, auth('admin')->id(), (float) ($d['refund'] ?? 0), ! empty($d['method']) ? PaymentMethod::find($d['method']) : null, $d['reason'] ?? null), 'Booking cancelled.', $booking);
    }

    public function storePayment(Request $request, BookedInfo $booking)
    {
        $d = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'method' => ['required', 'integer', 'exists:payment_method,payment_method_id'],
            'reference' => ['nullable', 'string', 'max:60'],
            'details' => ['nullable', 'string', 'max:100'],
        ]);

        return $this->act(fn () => $this->payments->receive($booking, (float) $d['amount'], PaymentMethod::findOrFail($d['method']), auth('admin')->id(), $d['details'] ?? null, $d['reference'] ?? null), 'Payment recorded.', $booking);
    }

    public function storeRefund(Request $request, BookedInfo $booking)
    {
        $d = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'method' => ['required', 'integer', 'exists:payment_method,payment_method_id'],
            'reason' => ['nullable', 'string', 'max:150'],
        ]);

        return $this->act(fn () => $this->payments->refund($booking, (float) $d['amount'], PaymentMethod::findOrFail($d['method']), auth('admin')->id(), $d['reason'] ?? null), 'Refund recorded.', $booking);
    }

    public function storeCharge(Request $request, BookedInfo $booking)
    {
        $d = $request->validate(['description' => ['required', 'string', 'max:150'], 'amount' => ['required', 'numeric', 'gt:0', 'max:99999999']]);

        return $this->act(fn () => $this->reservations->addCharge($booking, $d['description'], (float) $d['amount'], auth('admin')->id()), 'Charge added to the bill.', $booking);
    }

    public function destroyCharge(BookedInfo $booking, FolioCharge $charge)
    {
        abort_unless((int) $charge->bookedid === (int) $booking->bookedid, 404);

        return $this->act(fn () => $this->reservations->removeCharge($charge, auth('admin')->id()), 'Charge removed.', $booking);
    }

    public function addGuest(Request $request, BookedInfo $booking)
    {
        $request->validate($this->guestRules('guest.'), [], $this->guestAttributes('guest.'));
        $files = $request->allFiles()['guest'] ?? [];
        $this->storeGuest($booking, $request->input('guest', []), $files);

        return redirect()->route('admin.reservations.show', $booking->booking_number)->with('status', 'Guest added to the booking.');
    }

    public function removeGuest(BookedInfo $booking, TblOtherguest $guest)
    {
        abort_unless((int) $guest->booking_id === (int) $booking->bookedid, 404);
        foreach (['front_image', 'back_image', 'occupant_image'] as $column) {
            Uploads::delete($guest->{$column});
        }
        $guest->delete();

        return redirect()->route('admin.reservations.show', $booking->booking_number)->with('status', 'Guest removed.');
    }

    public function invoice(BookedInfo $booking)
    {
        return $this->invoices->pdf($booking)->stream('invoice-'.$booking->booking_number.'.pdf');
    }

    private function act(Closure $action, string $message, BookedInfo $booking)
    {
        try {
            $action();
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }

        return redirect()->route('admin.reservations.show', $booking->booking_number)->with('status', $message);
    }

    private function formData(?BookedInfo $booking): array
    {
        return [
            'booking' => $booking,
            'rooms' => Roomdetails::where('roomactive', 1)->orderBy('roomtype')->get(),
            'guests' => Customerinfo::orderBy('firstname')->get(['customerid', 'firstname', 'lastname', 'cust_phone', 'email']),
            'methods' => PaymentMethod::where('is_active', 1)->orderBy('payment_method_id')->get(),
            'sources' => self::SOURCES,
            'prefill' => [],
        ];
    }

    private function validateBooking(Request $request, bool $creating): array
    {
        $rules = [
            'room' => ['required', 'integer', 'exists:roomdetails,roomid'],
            'checkin' => ['required', 'date'],
            'checkout' => ['required', 'date', 'after:checkin'],
            'rooms' => ['required', 'integer', 'min:1', 'max:20'],
            'adults' => ['required', 'integer', 'min:1', 'max:60'],
            'children' => ['nullable', 'integer', 'min:0', 'max:60'],
            'guest_name' => ['nullable', 'string', 'max:255'],
            'special' => ['nullable', 'string', 'max:1000'],
            'promo' => ['nullable', 'string', 'max:50'],
        ];

        if ($creating) {
            $rules += [
                'guest_id' => ['nullable', 'integer', 'exists:customerinfo,customerid'],
                'new_title' => ['nullable', 'string', 'max:20'],
                'new_firstname' => ['required_without:guest_id', 'nullable', 'string', 'max:100'],
                'new_lastname' => ['nullable', 'string', 'max:100'],
                'new_fathername' => ['nullable', 'string', 'max:150'],
                'new_gender' => ['nullable', 'in:Male,Female,Other'],
                'new_profession' => ['nullable', 'string', 'max:100'],
                'new_dob' => ['nullable', 'date'],
                'new_anniversary' => ['nullable', 'date'],
                'new_nationality' => ['nullable', 'string', 'max:100'],
                'new_is_vip' => ['nullable', 'boolean'],
                'new_country_code' => ['nullable', 'string', 'max:10'],
                'new_email' => ['nullable', 'email', 'max:255', 'unique:customerinfo,email'],
                'new_phone' => ['required_without:guest_id', 'nullable', 'string', 'max:30', 'unique:customerinfo,cust_phone'],
                'new_country' => ['nullable', 'string', 'max:100'],
                'new_state' => ['nullable', 'string', 'max:100'],
                'new_city' => ['nullable', 'string', 'max:100'],
                'new_zipcode' => ['nullable', 'string', 'max:100'],
                'new_address' => ['nullable', 'string', 'max:255'],
                'new_id_type' => ['nullable', 'string', 'max:40'],
                'new_id_no' => ['nullable', 'string', 'max:100'],
                'new_comments' => ['nullable', 'string', 'max:1000'],
                'new_front' => ['nullable', 'image', 'max:4096'],
                'new_back' => ['nullable', 'image', 'max:4096'],
                'new_photo' => ['nullable', 'image', 'max:4096'],
                'source' => ['required', 'in:'.implode(',', array_keys(self::SOURCES))],
                'deposit' => ['nullable', 'numeric', 'gt:0'],
                'deposit_method' => ['nullable', 'required_with:deposit', 'integer', 'exists:payment_method,payment_method_id'],
                'guests' => ['nullable', 'array', 'max:30'],
            ] + $this->guestRules('guests.*.');
        }

        return $request->validate($rules);
    }

    private function resolveGuest(array $data): Customerinfo
    {
        if (! empty($data['guest_id'])) {
            return Customerinfo::findOrFail($data['guest_id']);
        }

        $request = request();
        $guest = Customerinfo::create([
            'title' => $data['new_title'] ?? null,
            'country_code' => $data['new_country_code'] ?? null,
            'firstname' => $data['new_firstname'],
            'lastname' => $data['new_lastname'] ?? '',
            'fathername' => $data['new_fathername'] ?? null,
            'gender' => $data['new_gender'] ?? null,
            'profession' => $data['new_profession'] ?? null,
            'dob' => $data['new_dob'] ?? null,
            'anniversary' => $data['new_anniversary'] ?? null,
            'nationality' => $data['new_nationality'] ?? null,
            'is_vip' => ! empty($data['new_is_vip']),
            'email' => ! empty($data['new_email']) ? strtolower($data['new_email']) : null,
            'cust_phone' => $data['new_phone'],
            'country' => $data['new_country'] ?? null,
            'state' => $data['new_state'] ?? null,
            'city' => $data['new_city'] ?? null,
            'zipcode' => $data['new_zipcode'] ?? null,
            'address' => $data['new_address'] ?? null,
            'pitype' => $data['new_id_type'] ?? null,
            'pid' => $data['new_id_no'] ?? null,
            'comments' => $data['new_comments'] ?? null,
            'imgfront' => Uploads::store($request->file('new_front'), 'customers'),
            'imgback' => Uploads::store($request->file('new_back'), 'customers'),
            'imgguest' => Uploads::store($request->file('new_photo'), 'customers'),
            'balance' => 0,
            'active' => 1,
            'signupdate' => today()->toDateString(),
        ]);
        $guest->update(['customernumber' => str_pad((string) $guest->customerid, 4, '0', STR_PAD_LEFT)]);

        return $guest;
    }

    /** @return array<string,list<string>> */
    private function guestRules(string $prefix): array
    {
        return [
            $prefix.'name' => ['nullable', 'string', 'max:150'],
            $prefix.'gender' => ['nullable', 'in:Male,Female,Other'],
            $prefix.'mobile' => ['nullable', 'string', 'max:30'],
            $prefix.'email' => ['nullable', 'email', 'max:150'],
            $prefix.'id_type' => ['nullable', 'string', 'max:40'],
            $prefix.'id_no' => ['nullable', 'string', 'max:100'],
            $prefix.'front' => ['nullable', 'image', 'max:4096'],
            $prefix.'back' => ['nullable', 'image', 'max:4096'],
            $prefix.'photo' => ['nullable', 'image', 'max:4096'],
        ];
    }

    private function guestAttributes(string $prefix): array
    {
        return [$prefix.'name' => 'guest name', $prefix.'front' => 'ID front image', $prefix.'back' => 'ID back image', $prefix.'photo' => 'guest photo'];
    }

    /** Save the "additional guests" repeater of the booking form. */
    private function saveGuests(Request $request, BookedInfo $booking): void
    {
        $files = $request->allFiles()['guests'] ?? [];
        foreach ((array) $request->input('guests', []) as $i => $row) {
            $this->storeGuest($booking, $row, $files[$i] ?? []);
        }
    }

    private function storeGuest(BookedInfo $booking, array $row, array $files): void
    {
        if (blank($row['name'] ?? null)) {
            return;
        }

        TblOtherguest::create([
            'bookedid' => (string) $booking->bookedid,
            'booking_id' => $booking->bookedid,
            'customerid' => $booking->cutomerid,
            'guestname' => $row['name'],
            'gender' => $row['gender'] ?? null,
            'mobile' => $row['mobile'] ?? null,
            'email' => $row['email'] ?? null,
            'photo_id_type' => $row['id_type'] ?? null,
            'photo_id' => $row['id_no'] ?? null,
            'front_image' => Uploads::store($files['front'] ?? null, 'guests'),
            'back_image' => Uploads::store($files['back'] ?? null, 'guests'),
            'occupant_image' => Uploads::store($files['photo'] ?? null, 'guests'),
            'type' => 0,
        ]);
    }

    private function promo(array $data, Roomdetails $room, Carbon $checkin, ?BookedInfo $booking = null)
    {
        if (empty($data['promo'])) {
            return null;
        }
        // A booking that already uses the code may keep it.
        if ($booking && strcasecmp((string) $booking->promocode, $data['promo']) === 0) {
            return Promocode::whereRaw('UPPER(promocode) = ?', [strtoupper($data['promo'])])->first();
        }

        return $this->bookings->findPromo($data['promo'], $room, $checkin)
            ?? throw new InvalidArgumentException('That promo code is not valid for this room and stay.');
    }
}
