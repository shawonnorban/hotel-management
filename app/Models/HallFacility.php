<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HallFacility extends Model
{
    protected $table = 'hall_facilities';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
