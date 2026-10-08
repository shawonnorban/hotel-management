<?php

namespace App\Services;

use App\Models\Hall;
use App\Models\HallBooking;
use App\Models\HallPayment;
use App\Models\LedgerAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Hall bookings: conflict-free scheduling, server-side pricing and payments into the ledger. */
class HallService
{
    public function __construct(private LedgerService $ledger) {}

    /** Hours between two H:i times; an end earlier than the start runs past midnight. */
    public static function hours(string $start, string $end): float
    {
        $s = Carbon::createFromFormat('H:i', substr($start, 0, 5));
        $e = Carbon::createFromFormat('H:i', substr($end, 0, 5));
        if ($e->lte($s)) {
            $e->addDay();
        }

        return round($s->diffInMinutes($e) / 60, 2);
    }

    /** @return array{hours:float,subtotal:float} */
    public function quote(Hall $hall, string $start, string $end): array
    {
        $hours = self::hours($start, $end);
        $subtotal = round($hours * (float) $hall->rate_per_hour, 2);
        if ((float) $hall->rate_per_day > 0) {
            $subtotal = min($subtotal, (float) $hall->rate_per_day);
        }

        return ['hours' => $hours, 'subtotal' => $subtotal];
    }

    /** The booking that clashes with the requested slot, if any. */
    public function conflict(int $hallId, string $date, string $start, string $end, ?int $ignoreId = null): ?HallBooking
    {
        $window = $this->window($date, $start, $end);

        return HallBooking::where('hall_id', $hallId)->where('status', '!=', 'cancelled')
            ->whereDate('event_date', '>=', Carbon::parse($date)->subDay())->whereDate('event_date', '<=', Carbon::parse($date)->addDay())
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->get()
            ->first(function ($b) use ($window) {
                $other = $this->window($b->event_date->toDateString(), $b->starts_at, $b->ends_at);

                return $other[0]->lt($window[1]) && $other[1]->gt($window[0]);
            });
    }

    public function create(array $data, ?int $userId): HallBooking
    {
        $hall = Hall::findOrFail($data['hall_id']);
        if (! $hall->is_active) {
            throw new InvalidArgumentException('This hall is not available.');
        }
        if ((int) $data['guests'] > $hall->capacity) {
            throw new InvalidArgumentException("{$hall->name} holds at most {$hall->capacity} people.");
        }
        if ($data['starts_at'] === $data['ends_at']) {
            throw new InvalidArgumentException('The end time must differ from the start time.');
        }
        $quote = $this->quote($hall, $data['starts_at'], $data['ends_at']);
        $discount = round(min((float) ($data['discount'] ?? 0), $quote['subtotal']), 2);

        return DB::transaction(function () use ($data, $hall, $quote, $discount, $userId) {
            // Serialise bookings of the same hall so two clerks can't take the same slot.
            Hall::query()->lockForUpdate()->find($hall->id);
            if ($data['status'] !== 'cancelled' && ($clash = $this->conflict($hall->id, $data['event_date'], $data['starts_at'], $data['ends_at']))) {
                throw new InvalidArgumentException("{$hall->name} is already booked for {$clash->event_name} ({$clash->event_date->format('d M')}, ".substr($clash->starts_at, 0, 5).'–'.substr($clash->ends_at, 0, 5).').');
            }

            $booking = HallBooking::create([
                'number' => 'PENDING-'.bin2hex(random_bytes(5)), 'hall_id' => $hall->id, 'seat_plan_id' => $data['seat_plan_id'] ?? null,
                'customer_name' => $data['customer_name'], 'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null, 'booking_number' => $data['booking_number'] ?? null,
                'event_name' => $data['event_name'], 'event_date' => $data['event_date'], 'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'], 'guests' => $data['guests'],
                'status' => $data['status'], 'subtotal' => $quote['subtotal'], 'discount' => $discount, 'total' => round($quote['subtotal'] - $discount, 2),
                'notes' => $data['notes'] ?? null, 'created_by' => $userId,
            ]);
            $booking->update(['number' => 'HALL-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT)]);

            return $booking;
        });
    }

    public function setStatus(HallBooking $booking, string $status): void
    {
        if ($booking->status === 'cancelled') {
            throw new InvalidArgumentException('This booking was cancelled.');
        }
        if ($status === 'cancelled' && (float) $booking->paid > 0) {
            throw new InvalidArgumentException('A booking with payments cannot be cancelled.');
        }
        $booking->update(['status' => $status]);
    }

    public function pay(HallBooking $booking, float $amount, int $accountId, string $date, ?string $reference, ?int $userId): HallPayment
    {
        $amount = round($amount, 2);
        $account = LedgerAccount::findOrFail($accountId);
        if (! $account->is_cash || $account->is_group) {
            throw new InvalidArgumentException('Choose a cash or bank account to receive the money in.');
        }

        return DB::transaction(function () use ($booking, $amount, $account, $date, $reference, $userId) {
            $booking = HallBooking::query()->lockForUpdate()->findOrFail($booking->id);
            if ($booking->status === 'cancelled') {
                throw new InvalidArgumentException('This booking was cancelled.');
            }
            if ($amount <= 0 || $amount > $booking->due + 0.004) {
                throw new InvalidArgumentException('The amount must be between 0 and what is still due ('.number_format($booking->due, 2).').');
            }

            $payment = $booking->payments()->create(['paid_on' => $date, 'amount' => $amount, 'ledger_account_id' => $account->id, 'reference' => $reference, 'created_by' => $userId]);
            $entry = $this->ledger->post('receipt', $date, [
                ['account' => $account, 'debit' => $amount],
                ['account' => 'service_income', 'credit' => $amount],
            ], "Hall {$booking->number}".($reference ? " ({$reference})" : ''), $booking, $userId);
            $payment->update(['journal_entry_id' => $entry->id]);
            $booking->increment('paid', $amount);

            return $payment;
        });
    }

    /** @return array{0:Carbon,1:Carbon} */
    private function window(string $date, string $start, string $end): array
    {
        $s = Carbon::parse($date.' '.substr($start, 0, 5));
        $e = Carbon::parse($date.' '.substr($end, 0, 5));
        if ($e->lte($s)) {
            $e->addDay();
        }

        return [$s, $e];
    }
}
