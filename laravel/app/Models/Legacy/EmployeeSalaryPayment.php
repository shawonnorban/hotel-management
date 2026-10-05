<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryPayment extends Model
{
    protected $table = 'employee_salary_payment';

    protected $primaryKey = 'emp_sal_pay_id';

    public $timestamps = false;

    protected $guarded = [];
}
