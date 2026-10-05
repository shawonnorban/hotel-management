<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrLeaveRequest extends Model
{
    protected $table = 'hr_leave_requests';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['from_date' => 'date', 'to_date' => 'date', 'days' => 'decimal:1', 'decided_at' => 'datetime'];
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function type()
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }
}
