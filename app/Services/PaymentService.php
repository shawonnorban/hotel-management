<?php

namespace App\Services;

use App\Models\BookedInfo;
use App\Models\PaymentMethod;
use App\Models\TblGuestpayments;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Guest payments and refunds, each posted to the ledger. */
class PaymentService
{
    public function __construct(private LedgerService $ledger, private ReservationLog $log)
    {
    }

    /** Record money received from the guest. */
    public function receive(BookedInfo $booking, float $amount, PaymentMethod $method, ?int $userId = null, ?string $details = null, ?string $reference = null): TblGuestpayments
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('The amount must be greater than zero.');
        }
        if (! $booking->is_open && ! $booking->is_closed) {
            throw new InvalidArgumentException('Payments cannot be taken on a cancelled booking.');
        }

        return DB::transaction(function () use ($booking, $amount, $method, $userId, $details, $reference) {
            $booking = BookedInfo::query()->lockForUpdate()->findOrFail($booking->bookedid);
            if ($amount > $booking->balance + 0.004) {
                throw new InvalidArgumentException('The amount is more than the balance due ('.number_format($booking->balance, 2).').');
            }

            $payment = $this->store($booking, $amount, $method, $userId, $details, $reference);
            $booking->increment('paid_amount', $amount);

            $entry = $this->ledger->post('receipt', today(), [
                ['account' => $this->methodAccount($method), 'debit' => $amount],
                // After check-out the revenue is already booked against receivables; before it, money is a deposit.
                ['account' => $booking->is_closed ? 'receivable' : 'guest_deposits', 'credit' => $amount],
            ], "Payment {$payment->invoice} for booking #{$booking->booking_number} ({$method->payment_method})", $booking, $userId);
            $payment->update(['journal_entry_id' => $entry->id]);

            $this->log->add($booking, 'payment', number_format($amount, 2).' by '.$method->payment_method.' (receipt '.$payment->invoice.')', $userId);

            return $payment;
        });
    }

    /** Give money back to the guest. */
    public function refund(BookedInfo $booking, float $amount, PaymentMethod $method, ?int $userId = null, ?string $reason = null): TblGuestpayments
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('The refund must be greater than zero.');
        }

        return DB::transaction(function () use ($booking, $amount, $method, $userId, $reason) {
            $booking = BookedInfo::query()->lockForUpdate()->findOrFail($booking->bookedid);
            if ($amount > (float) $booking->paid_amount + 0.004) {
                throw new InvalidArgumentException('The refund is more than the amount paid ('.number_format($booking->paid_amount, 2).').');
            }

            $payment = $this->store($booking, -$amount, $method, $userId, $reason ? 'Refund: '.$reason : 'Refund');
            $booking->decrement('paid_amount', $amount);

            $entry = $this->ledger->post('payment', today(), [
                ['account' => $booking->is_closed ? 'receivable' : 'guest_deposits', 'debit' => $amount],
                ['account' => $this->methodAccount($method), 'credit' => $amount],
            ], "Refund {$payment->invoice} for booking #{$booking->booking_number} ({$method->payment_method})", $booking, $userId);
            $payment->update(['journal_entry_id' => $entry->id]);

            $this->log->add($booking, 'refund', number_format($amount, 2).' by '.$method->payment_method.($reason ? ' – '.$reason : ''), $userId);

            return $payment;
        });
    }

    private function store(BookedInfo $booking, float $signedAmount, PaymentMethod $method, ?int $userId, ?string $details, ?string $reference = null): TblGuestpayments
    {
        $next = ((int) TblGuestpayments::query()->max('payid')) + 1;

        return TblGuestpayments::create([
            'bookedid' => (string) $booking->bookedid,
            'invoice' => str_pad((string) $next, 6, '0', STR_PAD_LEFT),
            'paydate' => Carbon::now(),
            'paymenttype' => $method->payment_method,
            'paymentamount' => $signedAmount,
            'details' => trim(($details ?? '').($reference ? ' ['.$reference.']' : '')) ?: null,
            'book_type' => 0,
            'created_by' => $userId,
        ]);
    }

    private function methodAccount(PaymentMethod $method): int|string
    {
        return $method->ledger_account_id ?: 'cash';
    }
}
