<?php

namespace App\Services;

use App\Models\HkLaundryCost;
use App\Models\HkLaundryOrder;
use App\Models\HkLaundryPayment;
use App\Models\LedgerAccount;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Guest laundry: priced from the item-cost list; revenue is recognised when money is received. */
class LaundryService
{
    public function __construct(private LedgerService $ledger) {}

    /** @param list<array{product:int|string,service:string,quantity:int|string}> $lines */
    public function create(string $guestName, ?string $roomNo, ?int $bookingId, string $date, array $lines, ?string $notes, ?int $userId): HkLaundryOrder
    {
        $priced = [];
        foreach ($lines as $line) {
            $qty = (int) ($line['quantity'] ?? 0);
            if (blank($line['product'] ?? null) || $qty <= 0) {
                continue;
            }
            $cost = HkLaundryCost::where('product_id', $line['product'])->where('service', $line['service'] ?? '')->first();
            if (! $cost) {
                throw new InvalidArgumentException('One of the items has no cost for the chosen service. Add it under Laundry item cost first.');
            }
            $priced[] = ['product_id' => $cost->product_id, 'service' => $cost->service, 'quantity' => $qty, 'unit_cost' => $cost->cost, 'line_total' => round($qty * (float) $cost->cost, 2)];
        }
        if (! $priced) {
            throw new InvalidArgumentException('Add at least one laundry item.');
        }

        return DB::transaction(function () use ($guestName, $roomNo, $bookingId, $date, $priced, $notes, $userId) {
            $order = HkLaundryOrder::create([
                'number' => 'PENDING-'.bin2hex(random_bytes(5)), 'order_date' => $date, 'booking_id' => $bookingId, 'guest_name' => $guestName, 'room_no' => $roomNo,
                'total' => round(array_sum(array_column($priced, 'line_total')), 2), 'notes' => $notes, 'created_by' => $userId,
            ]);
            $order->update(['number' => 'LDY-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);
            $order->lines()->createMany($priced);

            return $order;
        });
    }

    public function pay(HkLaundryOrder $order, float $amount, int $accountId, string $date, ?string $reference, ?int $userId): HkLaundryPayment
    {
        $amount = round($amount, 2);
        $account = LedgerAccount::findOrFail($accountId);
        if (! $account->is_cash || $account->is_group) {
            throw new InvalidArgumentException('Choose a cash or bank account to receive the money in.');
        }

        return DB::transaction(function () use ($order, $amount, $account, $date, $reference, $userId) {
            $order = HkLaundryOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->status === 'cancelled') {
                throw new InvalidArgumentException('This order was cancelled.');
            }
            if ($amount <= 0 || $amount > $order->due + 0.004) {
                throw new InvalidArgumentException('The amount must be between 0 and what is still due ('.number_format($order->due, 2).').');
            }

            $payment = $order->payments()->create(['paid_on' => $date, 'amount' => $amount, 'ledger_account_id' => $account->id, 'reference' => $reference, 'created_by' => $userId]);
            $entry = $this->ledger->post('receipt', $date, [
                ['account' => $account, 'debit' => $amount],
                ['account' => 'service_income', 'credit' => $amount],
            ], "Laundry {$order->number}".($reference ? " ({$reference})" : ''), $order, $userId);
            $payment->update(['journal_entry_id' => $entry->id]);
            $order->increment('paid', $amount);

            return $payment;
        });
    }

    public function cancel(HkLaundryOrder $order): void
    {
        if ((float) $order->paid > 0) {
            throw new InvalidArgumentException('An order with payments cannot be cancelled.');
        }
        $order->update(['status' => 'cancelled']);
    }
}
