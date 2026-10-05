<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookedInfo;
use App\Models\Customerinfo;
use App\Models\FolioCharge;
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
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()->route('admin.reservations.show', $booking->booking_number)->with('status', 'Reservation #'.$booking->booking_number.' created.');
    }

    public function show(BookedInfo $booking)
    {
        $booking->load('customer', 'payments', 'charges', 'events.user');

        return view('admin.reservations.show', [
            'booking' => $booking,
            'methods' => PaymentMethod::where('is_active', 1)->orderBy('payment_method_id')->get(),
            'allMethods' => PaymentMethod::orderBy('payment_method_id')->get(),
            'roomType' => Roomdetails::find((int) explode(',', (string) $booking->roomid)[0]),
            'lines' => $this->invoices->lines($booking),
        ]);
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
                'new_firstname' => ['required_without:guest_id', 'nullable', 'string', 'max:100'],
                'new_lastname' => ['nullable', 'string', 'max:100'],
                'new_email' => ['nullable', 'email', 'max:255', 'unique:customerinfo,email'],
                'new_phone' => ['required_without:guest_id', 'nullable', 'string', 'max:30', 'unique:customerinfo,cust_phone'],
                'source' => ['required', 'in:'.implode(',', array_keys(self::SOURCES))],
                'deposit' => ['nullable', 'numeric', 'gt:0'],
                'deposit_method' => ['nullable', 'required_with:deposit', 'integer', 'exists:payment_method,payment_method_id'],
            ];
        }

        return $request->validate($rules);
    }

    private function resolveGuest(array $data): Customerinfo
    {
        if (! empty($data['guest_id'])) {
            return Customerinfo::findOrFail($data['guest_id']);
        }

        $guest = Customerinfo::create([
            'firstname' => $data['new_firstname'],
            'lastname' => $data['new_lastname'] ?? '',
            'email' => ! empty($data['new_email']) ? strtolower($data['new_email']) : null,
            'cust_phone' => $data['new_phone'],
            'balance' => 0,
            'active' => 1,
            'signupdate' => today()->toDateString(),
        ]);
        $guest->update(['customernumber' => str_pad((string) $guest->customerid, 4, '0', STR_PAD_LEFT)]);

        return $guest;
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
