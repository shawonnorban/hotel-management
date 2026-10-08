<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrEmployee extends Model
{
    protected $table = 'hr_employees';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['join_date' => 'date', 'leave_date' => 'date', 'birth_date' => 'date', 'is_active' => 'boolean', 'basic_salary' => 'decimal:2'];
    }

    public function department()
    {
        return $this->belongsTo(HrDepartment::class, 'department_id');
    }

    public function position()
    {
        return $this->belongsTo(HrPosition::class, 'position_id');
    }

    public function components()
    {
        return $this->hasMany(HrSalaryComponent::class, 'employee_id');
    }

    public function documents()
    {
        return $this->hasMany(HrEmployeeDocument::class, 'employee_id');
    }

    public function education()
    {
        return $this->hasMany(HrEmployeeEducation::class, 'employee_id')->orderByDesc('passing_year');
    }

    public function experience()
    {
        return $this->hasMany(HrEmployeeExperience::class, 'employee_id')->orderByDesc('from_date');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** Salary components as money for the given basic: [allowances, deductions]. */
    public function componentTotals(): array
    {
        $basic = (float) $this->basic_salary;
        $sum = fn (string $kind) => round($this->components->where('kind', $kind)->sum(fn ($c) => $c->is_percent ? $basic * (float) $c->amount / 100 : (float) $c->amount), 2);

        return [$sum('allowance'), $sum('deduction')];
    }
}
