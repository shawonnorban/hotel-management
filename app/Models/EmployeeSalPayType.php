<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalPayType extends Model
{
    protected $table = 'employee_sal_pay_type';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
