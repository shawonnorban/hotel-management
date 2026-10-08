<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrVehicleBooking extends Model
{
    protected $table = 'tr_vehicle_bookings';

    protected $guarded = [];

    public const STATUSES = ['booked' => 'Booked', 'on_trip' => 'On trip', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

    protected function casts(): array
    {
        return ['pickup_at' => 'datetime', 'return_at' => 'datetime'];
    }

    public function vehicle()
    {
        return $this->belongsTo(TrVehicle::class, 'vehicle_id');
    }
}
