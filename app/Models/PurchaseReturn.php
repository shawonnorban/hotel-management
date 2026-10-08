<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['return_date' => 'date', 'total' => 'decimal:2'];
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /** The stock movements this return created (one per returned line). */
    public function movements()
    {
        return $this->morphMany(StockMovement::class, 'source')->with('item.unit');
    }
}
