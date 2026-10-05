<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrLeaveType extends Model
{
    protected $table = 'hr_leave_types';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_paid' => 'boolean'];
    }
}
