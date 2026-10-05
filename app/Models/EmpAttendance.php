<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpAttendance extends Model
{
    protected $table = 'emp_attendance';

    protected $primaryKey = 'att_id';

    public $timestamps = false;

    protected $guarded = [];
}
