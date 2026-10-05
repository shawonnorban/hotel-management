<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollHoliday extends Model
{
    protected $table = 'payroll_holiday';

    protected $primaryKey = 'payrl_holi_id';

    public $timestamps = false;

    protected $guarded = [];
}
