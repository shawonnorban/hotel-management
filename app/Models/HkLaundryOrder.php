<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HkLaundryOrder extends Model
{
    protected $table = 'hk_laundry_orders';

    protected $guarded = [];

    public const STATUSES = ['received' => 'Received', 'washing' => 'In process', 'ready' => 'Ready', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];

    protected function casts(): array
    {
        return ['order_date' => 'date', 'total' => 'decimal:2', 'paid' => 'decimal:2'];
    }

    public function lines()
    {
        return $this->hasMany(HkLaundryLine::class, 'order_id')->with('product');
    }

    public function payments()
    {
        return $this->hasMany(HkLaundryPayment::class, 'order_id')->with('account');
    }

    public function getDueAttribute(): float
    {
        return round((float) $this->total - (float) $this->paid, 2);
    }
}
