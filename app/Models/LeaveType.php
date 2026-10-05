<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $table = 'leave_type';

    protected $primaryKey = 'leave_type_id';

    public $timestamps = false;

    protected $guarded = [];
}
