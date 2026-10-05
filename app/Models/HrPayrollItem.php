<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrPayrollItem extends Model
{
    protected $table = 'hr_payroll_items';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['net' => 'decimal:2', 'basic' => 'decimal:2', 'allowances' => 'decimal:2', 'bonus' => 'decimal:2', 'deductions' => 'decimal:2', 'loan_deduction' => 'decimal:2', 'absence_deduction' => 'decimal:2', 'absent_days' => 'decimal:1'];
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

}
