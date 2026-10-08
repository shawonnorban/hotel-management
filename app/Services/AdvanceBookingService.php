<?php

namespace App\Services;

use App\Models\BookedInfo;
use App\Support\AppSettings;
use Illuminate\Support\Facades\DB;

/**
 * Advance booking rule: with "advance percent" set, a reservation only becomes confirmed once that share of the
 * total has been paid; with "hold days" set, pending reservations that still have no advance are released.
 * Both default to 0 (off), which keeps the old behaviour of confirming immediately.
 */
class AdvanceBookingService
{
    public function __construct(private ReservationService $reservations) {}

    public function percent(): float
    {
        return max(0.0, min(100.0, (float) AppSettings::get('advance.percent', 0)));
    }

    public function holdDays(): int
    {
        return max(0, (int) AppSettings::get('advance.hold_days', 0));
    }

    /** The advance the booking must have received before it is confirmed. */
    public function required(BookedInfo $booking): float
    {
        return round((float) $booking->total_price * $this->percent() / 100, 2);
    }

    public function shortfall(BookedInfo $booking): float
    {
        return max(0.0, round($this->required($booking) - (float) $booking->paid_amount, 2));
    }

    /** Confirm a pending booking whose advance is now in. Returns true when it was confirmed. */
    public function settle(BookedInfo $booking, ?int $userId): bool
    {
        $booking = $booking->fresh();
        if ((string) $booking->bookingstatus !== '0' || $this->percent() <= 0 || $this->shortfall($booking) > 0) {
            return false;
        }
        $this->reservations->confirm($booking, $userId);

        return true;
    }

    /** Cancel pending bookings that got no advance within the hold period. @return int number released */
    public function releaseUnpaid(): int
    {
        $days = $this->holdDays();
        if ($days <= 0) {
            return 0;
        }

        $released = 0;
        BookedInfo::where('bookingstatus', '0')->where('date_time', '<', now()->subDays($days))->get()->each(function (BookedInfo $b) use (&$released) {
            if ($this->shortfall($b) <= 0 && $this->percent() > 0) {
                return;
            }
            if ((float) $b->paid_amount > 0) {
                return; // never auto-cancel a booking that already holds money
            }
            DB::transaction(fn () => $this->reservations->cancel($b, null, 0, null, 'Released: no advance received within '.$this->holdDays().' day(s)'));
            $released++;
        });

        return $released;
    }
}
