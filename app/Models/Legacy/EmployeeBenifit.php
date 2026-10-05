<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class EmployeeBenifit extends Model
{
    protected $table = 'employee_benifit';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
