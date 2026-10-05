<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public const TYPES = ['purchase' => 'Purchase', 'return' => 'Purchase return', 'issue' => 'Issued', 'waste' => 'Wastage', 'adjustment' => 'Adjustment'];

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:4', 'balance' => 'decimal:3', 'moved_at' => 'datetime'];
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
