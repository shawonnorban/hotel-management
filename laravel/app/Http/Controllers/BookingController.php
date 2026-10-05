<?php

namespace App\Http\Controllers;

use App\Models\Legacy\BookedInfo;
use App\Models\Legacy\PaymentMethod;
use App\Models\Legacy\Roomdetails;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

class BookingController extends Controller
{
    /** Payment methods that need no online gateway (card / cash at hotel / bank transfer). */
    private const OFFLINE_METHODS = [1, 4, 6];

    public function __construct(private BookingService $booking) {}

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
        ]);

        $room = Roomdetails::where('roomactive', 1)->findOrFail($data['room']);

        try {
            $booking = $this->booking->createBooking(
                Auth::guard('customer')->user(),
                $room,
                Carbon::parse($data['checkin']),
                Carbon::parse($data['checkout']),
                (int) $data['rooms'],
                (int) $data['adults'],
                (int) ($data['children'] ?? 0),
                $data['guest'] ?? null,
                $data['special'] ?? null,
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
            'methods' => PaymentMethod::where('is_active', 1)->orderBy('payment_method_id')->get(),
            'offline' => self::OFFLINE_METHODS,
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
        if (! in_array((int) $method->payment_method_id, self::OFFLINE_METHODS, true)) {
            // Online gateways (PayPal, SSLCommerz, Stripe) are ported in a later phase.
            return back()->withErrors(['method' => $method->payment_method.' is not available yet in the new site.']);
        }

        $booking->details()->update(['payment_method' => $method->payment_method]);

        return redirect()->route('booking.show', $booking->booking_number)
            ->with('status', 'Your booking is confirmed. Payment: '.$method->payment_method.'.');
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

    private function ownBooking(string $number): BookedInfo
    {
        return BookedInfo::where('booking_number', $number)
            ->where('cutomerid', Auth::guard('customer')->id())
            ->firstOrFail();
    }
}
