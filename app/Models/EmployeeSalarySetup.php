<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalarySetup extends Model
{
    protected $table = 'employee_salary_setup';

    protected $primaryKey = 'e_s_s_id';

    public $timestamps = false;

    protected $guarded = [];
}
