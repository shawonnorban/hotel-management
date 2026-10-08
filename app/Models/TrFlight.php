<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrFlight extends Model
{
    protected $table = 'tr_flights';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['flight_at' => 'datetime'];
    }
}
