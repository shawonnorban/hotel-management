<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'stock' => 'decimal:3', 'avg_cost' => 'decimal:4', 'reorder_level' => 'decimal:3'];
    }

    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'item_id')->orderByDesc('id');
    }

    public function getValueAttribute(): float
    {
        return round((float) $this->stock * (float) $this->avg_cost, 2);
    }

    public function getIsLowAttribute(): bool
    {
        return (float) $this->reorder_level > 0 && (float) $this->stock <= (float) $this->reorder_level;
    }
}
