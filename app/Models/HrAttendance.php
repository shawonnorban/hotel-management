<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrAttendance extends Model
{
    protected $table = 'hr_attendance';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['work_date' => 'date'];
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
