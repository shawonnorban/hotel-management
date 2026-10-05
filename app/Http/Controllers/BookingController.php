<?php

namespace App\Http\Controllers;

use App\Models\BookedInfo;
use App\Models\PaymentMethod;
use App\Models\Roomdetails;
use App\Services\BookingNotifier;
use App\Services\BookingService;
use App\Services\InvoiceService;
use App\Services\OnlinePaymentService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $booking,
        private InvoiceService $invoices,
        private OnlinePaymentService $online,
        private BookingNotifier $notifier,
        private ReservationService $reservations,
    ) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'room' => ['required', 'integer'],
            'checkin' => ['required', 'date', 'after_or_equal:today'],
            'checkout' => ['required', 'date', 'after:checkin'],
            'rooms' => ['required', 'integer', 'min:1', 'max:10'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'guest' => ['nullable', 'string', 'max:255'],
            'special' => ['nullable', 'string', 'max:1000'],
            'promo' => ['nullable', 'string', 'max:50'],
        ]);

        $room = Roomdetails::where('roomactive', 1)->findOrFail($data['room']);
        $checkin = Carbon::parse($data['checkin']);

        $promo = null;
        if (! empty($data['promo'])) {
            $promo = $this->booking->findPromo($data['promo'], $room, $checkin);
            if (! $promo) {
                return back()->withInput()->withErrors(['promo' => 'That promo code is not valid for this room and stay.']);
            }
        }

        try {
            $booking = $this->booking->createBooking(
                Auth::guard('customer')->user(),
                $room,
                $checkin,
                Carbon::parse($data['checkout']),
                (int) $data['rooms'],
                (int) $data['adults'],
                (int) ($data['children'] ?? 0),
                $data['guest'] ?? null,
                $data['special'] ?? null,
                $promo,
            );
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()->route('booking.checkout', $booking->booking_number);
    }

    public function checkout(string $booking)
    {
        $booking = $this->ownBooking($booking);

        return view('booking.checkout', [
            'booking' => $booking,
            'methods' => PaymentMethod::where('is_active', 1)->orderBy('payment_method_id')->get()
                ->each(fn ($m) => $m->online = (bool) $this->online->gatewayForMethod($m)),
        ]);
    }

    public function pay(Request $request, string $booking)
    {
        $booking = $this->ownBooking($booking);
        $data = $request->validate(['method' => ['required', 'integer']]);

        $method = PaymentMethod::where('is_active', 1)->find($data['method']);
        if (! $method) {
            return back()->withErrors(['method' => 'That payment method is not available.']);
        }

        if ($gateway = $this->online->gatewayForMethod($method)) {
            if ($booking->balance <= 0.004) {
                return redirect()->route('booking.show', $booking->booking_number)->with('status', 'This booking is already paid.');
            }
            try {
                return redirect()->away($this->online->start($booking, $gateway));
            } catch (InvalidArgumentException|RuntimeException $e) {
                return back()->withErrors(['method' => $e->getMessage()]);
            }
        }

        // Pay at the hotel / bank transfer: the booking stays pending until staff confirm it.
        $booking->details()->update(['payment_method' => $method->payment_method]);
        $this->notifier->received($booking);

        return redirect()->route('booking.show', $booking->booking_number)
            ->with('status', 'Thank you! We have received your booking. Payment: '.$method->payment_method.'.');
    }

    public function index()
    {
        $bookings = BookedInfo::where('cutomerid', Auth::guard('customer')->id())->orderByDesc('bookedid')->paginate(15);

        return view('booking.index', ['bookings' => $bookings]);
    }

    public function show(string $booking)
    {
        return view('booking.show', ['booking' => $this->ownBooking($booking)]);
    }

    /** A guest may cancel their own booking while it is pending and nothing has been paid. */
    public function cancel(string $booking)
    {
        $booking = $this->ownBooking($booking);

        if ((string) $booking->bookingstatus !== '0' || (float) $booking->paid_amount > 0) {
            return back()->withErrors(['booking' => 'This booking can no longer be cancelled online. Please contact the hotel.']);
        }

        $this->reservations->cancel($booking, null, 0, null, 'Cancelled by the guest');

        return redirect()->route('booking.show', $booking->booking_number)->with('status', 'Your booking has been cancelled.');
    }

    public function invoice(string $booking)
    {
        $booking = $this->ownBooking($booking);

        return $this->invoices->pdf($booking)->download('invoice-'.$booking->booking_number.'.pdf');
    }

    private function ownBooking(string $number): BookedInfo
    {
        return BookedInfo::where('booking_number', $number)
            ->where('cutomerid', Auth::guard('customer')->id())
            ->firstOrFail();
    }
}
