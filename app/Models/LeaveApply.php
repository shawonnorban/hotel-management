<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApply extends Model
{
    protected $table = 'leave_apply';

    protected $primaryKey = 'leave_appl_id';

    public $timestamps = false;

    protected $guarded = [];
}
