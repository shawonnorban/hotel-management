<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['purchase_date' => 'date', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2', 'paid' => 'decimal:2'];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseLine::class)->with('item.unit');
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class)->with('account');
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /** Amount owed to the supplier after returns and payments. */
    public function getDueAttribute(): float
    {
        return max(0.0, round((float) $this->total - (float) $this->returns->sum('total') - (float) $this->paid, 2));
    }
}
