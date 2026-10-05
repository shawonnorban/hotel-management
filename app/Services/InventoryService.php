<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Stock levels with weighted-average costing. Every change is a row in stock_movements and,
 * where it changes the value of stock, a balanced ledger entry.
 */
class InventoryService
{
    public function __construct(private LedgerService $ledger)
    {
    }

    /**
     * Apply a movement to an item inside the caller's transaction. Positive quantity brings stock in.
     * $unitCost is only used for purchases (it feeds the average); other movements are valued at the current average.
     */
    public function move(InventoryItem $item, string $type, float $quantity, ?float $unitCost = null, ?Model $source = null, ?string $note = null, ?int $userId = null): StockMovement
    {
        return DB::transaction(function () use ($item, $type, $quantity, $unitCost, $source, $note, $userId) {
            $item = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);
            $quantity = round($quantity, 3);
            $newStock = round((float) $item->stock + $quantity, 3);

            if ($newStock < 0) {
                throw new InvalidArgumentException("Not enough stock of {$item->name}: {$item->stock} {$item->unit->short_code} on hand.");
            }

            $avg = (float) $item->avg_cost;
            if ($type === 'purchase' && $quantity > 0 && $unitCost !== null) {
                $avg = $newStock > 0 ? ((float) $item->stock * $avg + $quantity * $unitCost) / $newStock : $unitCost;
            }
            $cost = $type === 'purchase' && $unitCost !== null ? $unitCost : (float) $item->avg_cost;

            $item->update(['stock' => $newStock, 'avg_cost' => round($avg, 4)]);

            return StockMovement::create([
                'item_id' => $item->id,
                'type' => $type,
                'quantity' => $quantity,
                'unit_cost' => round($cost, 4),
                'balance' => $newStock,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'note' => $note,
                'created_by' => $userId,
                'moved_at' => now(),
            ]);
        });
    }

    /** Stock used up (kitchen, housekeeping, a room…): expensed at average cost. */
    public function issue(InventoryItem $item, float $quantity, string $reason, ?int $userId): StockMovement
    {
        return $this->writeOff($item, $quantity, 'issue', 'consumables', 'Stock issued: '.$reason, $reason, $userId);
    }

    /** Stock lost, broken or expired. */
    public function waste(InventoryItem $item, float $quantity, string $reason, ?int $userId): StockMovement
    {
        return $this->writeOff($item, $quantity, 'waste', 'stock_loss', 'Stock written off: '.$reason, $reason, $userId);
    }

    /** Set the counted quantity; the difference is booked as stock gain/loss. */
    public function adjust(InventoryItem $item, float $counted, string $reason, ?int $userId): ?StockMovement
    {
        if ($counted < 0) {
            throw new InvalidArgumentException('The counted quantity cannot be negative.');
        }

        return DB::transaction(function () use ($item, $counted, $reason, $userId) {
            $item = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);
            $diff = round($counted - (float) $item->stock, 3);
            if (abs($diff) < 0.0005) {
                return null;
            }

            $movement = $this->move($item, 'adjustment', $diff, null, null, $reason, $userId);
            $value = round(abs($diff) * (float) $item->avg_cost, 2);
            if ($value >= 0.01) {
                $this->ledger->post('adjustment', today(), [
                    ['account' => 'inventory', $diff > 0 ? 'debit' : 'credit' => $value],
                    ['account' => 'stock_loss', $diff > 0 ? 'credit' : 'debit' => $value],
                ], 'Stock count adjustment: '.$item->name.' ('.$reason.')', $movement, $userId);
            }

            return $movement;
        });
    }

    private function writeOff(InventoryItem $item, float $quantity, string $type, string $expenseAccount, string $narration, string $reason, ?int $userId): StockMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('The quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($item, $quantity, $type, $expenseAccount, $narration, $reason, $userId) {
            $movement = $this->move($item, $type, -$quantity, null, null, $reason, $userId);
            $value = round($quantity * (float) $movement->unit_cost, 2);

            if ($value >= 0.01) {
                $this->ledger->post('adjustment', today(), [
                    ['account' => $expenseAccount, 'debit' => $value],
                    ['account' => 'inventory', 'credit' => $value],
                ], $narration.' – '.$item->name, $movement, $userId);
            }

            return $movement;
        });
    }
}
