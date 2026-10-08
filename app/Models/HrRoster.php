<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrRoster extends Model
{
    protected $table = 'hr_rosters';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['work_date' => 'date'];
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function shift()
    {
        return $this->belongsTo(HrShift::class, 'shift_id');
    }
}
