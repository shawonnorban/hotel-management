<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeHistory extends Model
{
    protected $table = 'employee_history';

    protected $primaryKey = 'emp_his_id';

    public $timestamps = false;

    protected $guarded = [];
}
