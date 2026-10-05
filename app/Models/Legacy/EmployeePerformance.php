<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class EmployeePerformance extends Model
{
    protected $table = 'employee_performance';

    protected $primaryKey = 'emp_per_id';

    public $timestamps = false;

    protected $guarded = [];
}
