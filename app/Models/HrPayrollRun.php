<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrPayrollRun extends Model
{
    protected $table = 'hr_payroll_runs';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['paid_on' => 'date', 'total_expense' => 'decimal:2', 'total_net' => 'decimal:2', 'total_deductions' => 'decimal:2', 'total_loans' => 'decimal:2'];
    }

    public function journalEntry(): JournalEntry
    {
        return JournalEntry::findOrFail($this->journal_entry_id);
    }

    public function items()
    {
        return $this->hasMany(HrPayrollItem::class, 'run_id')->with('employee');
    }
}
