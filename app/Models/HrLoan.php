<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrLoan extends Model
{
    protected $table = 'hr_loans';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['issued_on' => 'date', 'amount' => 'decimal:2'];
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function schedule()
    {
        return $this->hasMany(HrLoanInstallment::class, 'loan_id')->orderBy('period');
    }

    public function getOutstandingAttribute(): float
    {
        return round((float) $this->schedule->where('deducted', false)->sum('amount'), 2);
    }
}
