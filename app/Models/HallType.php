<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HallType extends Model
{
    protected $table = 'hall_types';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
