<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrHoliday extends Model
{
    protected $table = 'hr_holidays';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['holiday_date' => 'date'];
    }
}
