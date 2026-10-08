<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HallBooking extends Model
{
    protected $table = 'hall_bookings';

    protected $guarded = [];

    public const STATUSES = ['tentative' => 'Tentative', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2', 'paid' => 'decimal:2'];
    }

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    public function seatPlan()
    {
        return $this->belongsTo(HallSeatPlan::class, 'seat_plan_id');
    }

    public function payments()
    {
        return $this->hasMany(HallPayment::class, 'booking_id')->with('account');
    }

    public function getDueAttribute(): float
    {
        return round((float) $this->total - (float) $this->paid, 2);
    }
}
