<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrVehicle extends Model
{
    protected $table = 'tr_vehicles';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
