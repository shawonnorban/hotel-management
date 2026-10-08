<?php

namespace App\Services;

use App\Models\BookedInfo;
use App\Models\Customerinfo;
use App\Models\FolioCharge;
use App\Models\PaymentMethod;
use App\Models\Promocode;
use App\Models\Roomdetails;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Front-desk workflow: creating and changing reservations, check-in, check-out, cancellation and extra charges.
 * Money movements are posted to the ledger; every step is written to the reservation's audit trail.
 */
class ReservationService
{
    public function __construct(
        private BookingService $bookings,
        private PaymentService $payments,
        private LedgerService $ledger,
        private ReservationLog $log,
    ) {}

    public function create(
        Customerinfo $guest,
        Roomdetails $room,
        Carbon $checkin,
        Carbon $checkout,
        int $rooms,
        int $adults,
        int $children,
        ?string $guestName,
        ?string $special,
        ?Promocode $promo,
        string $source,
        ?int $userId,
        ?float $deposit = null,
        ?PaymentMethod $method = null,
        array $extras = [],
        array $wantedRooms = [],
    ): BookedInfo {
        return DB::transaction(function () use ($guest, $room, $checkin, $checkout, $rooms, $adults, $children, $guestName, $special, $promo, $source, $userId, $deposit, $method, $extras, $wantedRooms) {
            $advance = app(AdvanceBookingService::class);
            // With an advance rule the booking stays pending until enough has been paid.
            $booking = $this->bookings->createBooking($guest, $room, $checkin, $checkout, $rooms, $adults, $children, $guestName, $special, $promo, $advance->percent() > 0 ? '0' : '2', $source, $extras, $wantedRooms);
            $this->log->add($booking, 'created', 'Created by staff ('.$source.')'.($advance->percent() > 0 ? ', awaiting advance' : ' and confirmed'), $userId);

            if ($deposit && $deposit > 0) {
                $this->payments->receive($booking, $deposit, $method ?? throw new InvalidArgumentException('Choose a payment method for the deposit.'), $userId, 'Advance at booking');
                $advance->settle($booking, $userId);
            }

            return $booking->fresh();
        });
    }

    public function modify(BookedInfo $booking, Roomdetails $room, Carbon $checkin, Carbon $checkout, int $rooms, int $adults, int $children, ?Promocode $promo, ?int $userId): BookedInfo
    {
        $updated = $this->bookings->modify($booking, $room, $checkin, $checkout, $rooms, $adults, $children, $promo);
        $this->log->add($updated, 'modified', $checkin->format('d M').' → '.$checkout->format('d M Y').', '.$rooms.' room(s), rooms '.$updated->room_no, $userId);

        return $updated;
    }

    public function confirm(BookedInfo $booking, ?int $userId): BookedInfo
    {
        $this->assertStatus($booking, ['0'], 'Only pending bookings can be confirmed.');
        $booking->update(['bookingstatus' => '2']);
        $this->log->add($booking, 'confirmed', null, $userId);

        return $booking;
    }

    public function checkIn(BookedInfo $booking, ?int $userId): BookedInfo
    {
        $this->assertStatus($booking, ['0', '2'], 'Only pending or confirmed bookings can be checked in.');
        if ($booking->checkindate->copy()->startOfDay()->gt(today())) {
            throw new RuntimeException('This booking arrives on '.$booking->checkindate->format('d M Y').'; it cannot be checked in earlier.');
        }

        $booking->update(['bookingstatus' => '4']);
        $this->log->add($booking, 'checked_in', 'Room '.$booking->room_no, $userId);

        return $booking;
    }

    /**
     * Check the guest out and recognise the revenue for the stay.
     * An unpaid balance is only allowed when explicitly left on account (it becomes a receivable).
     */
    public function checkOut(BookedInfo $booking, ?int $userId, bool $leaveBalanceOnAccount = false): BookedInfo
    {
        return DB::transaction(function () use ($booking, $userId, $leaveBalanceOnAccount) {
            $booking = BookedInfo::query()->lockForUpdate()->findOrFail($booking->bookedid);
            $this->assertStatus($booking, ['4'], 'Only guests who are checked in can be checked out.');

            if ($booking->balance > 0.004 && ! $leaveBalanceOnAccount) {
                throw new RuntimeException('There is an unpaid balance of '.number_format($booking->balance, 2).'. Take the payment first, or leave it on account.');
            }

            $total = round((float) $booking->total_price, 2);
            $paid = min(round((float) $booking->paid_amount, 2), $total);
            $due = round($total - $paid, 2);

            // Split the total into revenue, tax and service charge (older bookings only know the total).
            $extras = round((float) $booking->extras_amount, 2);
            $tax = $booking->subtotal === null ? 0.0 : round((float) $booking->tax_amount, 2);
            $service = $booking->subtotal === null ? 0.0 : round((float) $booking->service_amount, 2);
            $room = round($total - $extras - $tax - $service, 2);

            $entry = $this->ledger->post('booking', today(), [
                ['account' => 'guest_deposits', 'debit' => $paid],
                ['account' => 'receivable', 'debit' => $due],
                ['account' => 'room_revenue', 'credit' => $room],
                ['account' => 'tax_payable', 'credit' => $tax],
                ['account' => 'service_income', 'credit' => $service],
                ['account' => 'extras_revenue', 'credit' => $extras],
            ], "Revenue for booking #{$booking->booking_number} ({$booking->nights} night(s))", $booking, $userId);

            $booking->update(['bookingstatus' => '5', 'revenue_entry_id' => $entry->id]);
            $this->log->add($booking, 'checked_out', $due > 0 ? 'Balance of '.number_format($due, 2).' left on account' : 'Settled in full', $userId);

            return $booking;
        });
    }

    /**
     * Cancel a pending/confirmed booking. Of the money already paid, $refund goes back to the guest and the
     * rest is kept as cancellation income.
     */
    public function cancel(BookedInfo $booking, ?int $userId, float $refund = 0, ?PaymentMethod $method = null, ?string $reason = null): BookedInfo
    {
        return DB::transaction(function () use ($booking, $userId, $refund, $method, $reason) {
            $booking = BookedInfo::query()->lockForUpdate()->findOrFail($booking->bookedid);
            $this->assertStatus($booking, ['0', '2'], 'Only pending or confirmed bookings can be cancelled.');

            $paid = round((float) $booking->paid_amount, 2);
            $refund = round($refund, 2);
            if ($refund < 0 || $refund > $paid + 0.004) {
                throw new InvalidArgumentException('The refund must be between 0 and the amount paid ('.number_format($paid, 2).').');
            }
            if ($refund > 0) {
                $this->payments->refund($booking, $refund, $method ?? throw new InvalidArgumentException('Choose how the refund is paid.'), $userId, $reason ?: 'Booking cancelled');
                $booking = $booking->fresh();
            }

            $kept = round((float) $booking->paid_amount, 2);
            if ($kept > 0) {
                $this->ledger->post('adjustment', today(), [
                    ['account' => 'guest_deposits', 'debit' => $kept],
                    ['account' => 'other_income', 'credit' => $kept],
                ], "Cancellation income for booking #{$booking->booking_number}", $booking, $userId);
            }

            $booking->update(['bookingstatus' => '1']);
            $this->log->add($booking, 'cancelled', trim(($reason ?: 'Cancelled').($kept > 0 ? ' – '.number_format($kept, 2).' kept' : '')), $userId);

            return $booking;
        });
    }

    public function addCharge(BookedInfo $booking, string $description, float $amount, ?int $userId): FolioCharge
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('The charge must be greater than zero.');
        }

        return DB::transaction(function () use ($booking, $description, $amount, $userId) {
            $booking = BookedInfo::query()->lockForUpdate()->findOrFail($booking->bookedid);
            $this->assertStatus($booking, ['0', '2', '4'], 'Charges can only be added to open bookings.');

            $charge = FolioCharge::create(['bookedid' => $booking->bookedid, 'description' => $description, 'amount' => $amount, 'charged_on' => today(), 'created_by' => $userId]);
            $booking->update([
                'extras_amount' => round((float) $booking->extras_amount + $amount, 2),
                'total_price' => round((float) $booking->total_price + $amount, 2),
            ]);
            $this->log->add($booking, 'charge', $description.' '.number_format($amount, 2), $userId);

            return $charge;
        });
    }

    public function removeCharge(FolioCharge $charge, ?int $userId): void
    {
        DB::transaction(function () use ($charge, $userId) {
            $booking = BookedInfo::query()->lockForUpdate()->findOrFail($charge->bookedid);
            $this->assertStatus($booking, ['0', '2', '4'], 'Charges can only be removed from open bookings.');

            $booking->update([
                'extras_amount' => max(0, round((float) $booking->extras_amount - (float) $charge->amount, 2)),
                'total_price' => max(0, round((float) $booking->total_price - (float) $charge->amount, 2)),
            ]);
            $this->log->add($booking, 'charge_removed', $charge->description.' '.number_format($charge->amount, 2), $userId);
            $charge->delete();
        });
    }

    private function assertStatus(BookedInfo $booking, array $allowed, string $message): void
    {
        if (! in_array((string) $booking->bookingstatus, $allowed, true)) {
            throw new RuntimeException($message);
        }
    }
}
