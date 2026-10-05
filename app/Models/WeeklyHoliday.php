<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyHoliday extends Model
{
    protected $table = 'weekly_holiday';

    protected $primaryKey = 'wk_id';

    public $timestamps = false;

    protected $guarded = [];
}
