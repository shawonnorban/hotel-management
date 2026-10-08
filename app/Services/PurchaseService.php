<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\LedgerAccount;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Buying stock from suppliers: receipt, supplier payments and returns, all posted to the ledger. */
class PurchaseService
{
    public function __construct(private InventoryService $inventory, private LedgerService $ledger) {}

    /**
     * @param  list<array{item:int,quantity:float|int|string,unit_cost:float|int|string}>  $lines
     * @param  array{amount:float,account:int,reference?:?string}|null  $payment
     */
    public function receive(Supplier $supplier, string $date, array $lines, float $discount = 0, ?string $reference = null, ?string $notes = null, ?array $payment = null, ?int $userId = null): Purchase
    {
        $clean = [];
        foreach ($lines as $line) {
            $qty = round((float) $line['quantity'], 3);
            $cost = round((float) $line['unit_cost'], 4);
            if ($qty <= 0 || $cost < 0) {
                throw new InvalidArgumentException('Every line needs a quantity above zero and a cost of zero or more.');
            }
            $clean[] = ['item' => (int) $line['item'], 'quantity' => $qty, 'unit_cost' => $cost, 'line_total' => round($qty * $cost, 2)];
        }
        if (! $clean) {
            throw new InvalidArgumentException('Add at least one item to the purchase.');
        }

        $subtotal = round(array_sum(array_column($clean, 'line_total')), 2);
        $discount = round($discount, 2);
        if ($discount < 0 || $discount > $subtotal) {
            throw new InvalidArgumentException('The discount cannot be more than the purchase total.');
        }
        $total = round($subtotal - $discount, 2);
        $factor = $subtotal > 0 ? $total / $subtotal : 0;

        return DB::transaction(function () use ($supplier, $date, $clean, $discount, $reference, $notes, $payment, $userId, $subtotal, $total, $factor) {
            $purchase = Purchase::create([
                'number' => 'PENDING-'.bin2hex(random_bytes(5)), 'supplier_id' => $supplier->id, 'purchase_date' => $date, 'reference' => $reference,
                'subtotal' => $subtotal, 'discount' => $discount, 'total' => $total, 'paid' => 0, 'status' => 'received', 'notes' => $notes, 'created_by' => $userId,
            ]);
            $purchase->update(['number' => 'PO-'.str_pad((string) $purchase->id, 6, '0', STR_PAD_LEFT)]);

            foreach ($clean as $line) {
                $item = InventoryItem::findOrFail($line['item']);
                $purchase->items()->create(['item_id' => $item->id, 'quantity' => $line['quantity'], 'unit_cost' => $line['unit_cost'], 'line_total' => $line['line_total']]);
                // The discount is spread over the goods, so stock is valued at what it really cost.
                $this->inventory->move($item, 'purchase', $line['quantity'], round($line['unit_cost'] * $factor, 4), $purchase, 'Purchase '.$purchase->number, $userId);
            }

            $entry = $this->ledger->post('purchase', $date, [
                ['account' => 'inventory', 'debit' => $total],
                ['account' => 'payable', 'credit' => $total],
            ], "Purchase {$purchase->number} from {$supplier->name}", $purchase, $userId);
            $purchase->update(['journal_entry_id' => $entry->id]);

            if ($payment && $payment['amount'] > 0) {
                $this->pay($purchase->fresh(), (float) $payment['amount'], (int) $payment['account'], $date, $payment['reference'] ?? null, $userId);
            }

            return $purchase->fresh();
        });
    }

    public function pay(Purchase $purchase, float $amount, int $accountId, string $date, ?string $reference, ?int $userId): PurchasePayment
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('The amount must be greater than zero.');
        }
        $account = LedgerAccount::findOrFail($accountId);
        if (! $account->is_cash || $account->is_group) {
            throw new InvalidArgumentException('Choose a cash or bank account to pay from.');
        }

        return DB::transaction(function () use ($purchase, $amount, $account, $date, $reference, $userId) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            if ($amount > $purchase->due + 0.004) {
                throw new InvalidArgumentException('The amount is more than is owed ('.number_format($purchase->due, 2).').');
            }

            $payment = $purchase->payments()->create(['paid_on' => $date, 'amount' => $amount, 'ledger_account_id' => $account->id, 'reference' => $reference, 'created_by' => $userId]);
            $entry = $this->ledger->post('payment', $date, [
                ['account' => 'payable', 'debit' => $amount],
                ['account' => $account, 'credit' => $amount],
            ], "Payment for {$purchase->number}".($reference ? " ({$reference})" : ''), $purchase, $userId);
            $payment->update(['journal_entry_id' => $entry->id]);
            $purchase->increment('paid', $amount);

            return $payment;
        });
    }

    /**
     * Send goods back to the supplier.
     *
     * @param  array<int,float|int|string>  $quantities  purchase_items.id => quantity returned
     */
    public function returnGoods(Purchase $purchase, array $quantities, string $date, ?string $reason, ?int $userId): PurchaseReturn
    {
        $quantities = array_filter(array_map(fn ($q) => round((float) $q, 3), $quantities), fn ($q) => $q > 0);
        if (! $quantities) {
            throw new InvalidArgumentException('Enter the quantity to return for at least one item.');
        }

        return DB::transaction(function () use ($purchase, $quantities, $date, $reason, $userId) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            $factor = (float) $purchase->subtotal > 0 ? (float) $purchase->total / (float) $purchase->subtotal : 0;

            $return = PurchaseReturn::create(['number' => 'PENDING-'.bin2hex(random_bytes(5)), 'purchase_id' => $purchase->id, 'return_date' => $date, 'total' => 0, 'reason' => $reason, 'created_by' => $userId]);
            $return->update(['number' => 'PR-'.str_pad((string) $return->id, 6, '0', STR_PAD_LEFT)]);

            $credit = 0.0;      // what the supplier credits us
            $inventory = 0.0;   // what the stock was carried at
            foreach ($quantities as $lineId => $qty) {
                /** @var PurchaseLine $line */
                $line = PurchaseLine::where('purchase_id', $purchase->id)->lockForUpdate()->findOrFail($lineId);
                if ($qty > $line->returnable + 0.0004) {
                    throw new InvalidArgumentException("Only {$line->returnable} of {$line->item->name} can still be returned.");
                }

                $movement = $this->inventory->move($line->item, 'return', -$qty, null, $return, 'Return '.$return->number, $userId);
                $inventory += round($qty * (float) $movement->unit_cost, 2);
                $credit += round($qty * (float) $line->unit_cost * $factor, 2);
                $line->increment('returned', $qty);
            }

            $credit = round($credit, 2);
            $inventory = round($inventory, 2);
            $lines = [['account' => 'payable', 'debit' => $credit], ['account' => 'inventory', 'credit' => $inventory]];
            // Stock carried at a different average than the purchase price: book the difference.
            if ($credit > $inventory) {
                $lines[] = ['account' => 'other_income', 'credit' => round($credit - $inventory, 2)];
            } elseif ($inventory > $credit) {
                $lines[] = ['account' => 'consumables', 'debit' => round($inventory - $credit, 2)];
            }

            $entry = $this->ledger->post('purchase', $date, $lines, "Return {$return->number} of {$purchase->number}", $return, $userId);
            $return->update(['total' => $credit, 'journal_entry_id' => $entry->id]);

            return $return;
        });
    }
}
