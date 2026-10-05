<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeBenifit extends Model
{
    protected $table = 'employee_benifit';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
