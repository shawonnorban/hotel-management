<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'returned' => 'decimal:3', 'unit_cost' => 'decimal:4', 'line_total' => 'decimal:2'];
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function getReturnableAttribute(): float
    {
        return round((float) $this->quantity - (float) $this->returned, 3);
    }
}
