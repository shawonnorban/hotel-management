<?php

namespace App\Services\Hr;

use App\Models\HrAttendance;
use App\Models\HrAward;
use App\Models\HrEmployee;
use App\Models\HrLeaveRequest;
use App\Models\HrLoan;
use App\Models\HrLoanInstallment;
use App\Models\HrPayrollItem;
use App\Models\HrPayrollRun;
use App\Models\LedgerAccount;
use App\Services\LedgerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Monthly payroll: draft → finalized (expense booked, loan instalments taken) → paid.
 *
 * net = basic (pro-rated for joiners/leavers) + allowances + bonuses − absence − deductions − loan instalments
 */
class PayrollService
{
    public function __construct(private LedgerService $ledger)
    {
    }

    public function generate(string $period, ?int $userId): HrPayrollRun
    {
        $this->assertPeriod($period);
        $start = Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth()->startOfDay();

        return DB::transaction(function () use ($period, $start, $end, $userId) {
            $run = HrPayrollRun::firstOrCreate(['period' => $period], ['status' => 'draft', 'created_by' => $userId]);
            if ($run->status !== 'draft') {
                throw new InvalidArgumentException("Payroll for {$period} is already {$run->status} and can no longer be regenerated.");
            }
            $run->items()->delete();

            $employees = HrEmployee::with('components')
                ->whereDate('join_date', '<=', $end)
                ->where(fn ($q) => $q->whereNull('leave_date')->orWhereDate('leave_date', '>=', $start))
                ->get();

            foreach ($employees as $employee) {
                HrPayrollItem::create($this->compute($employee, $run, $period, $start, $end));
            }

            return $this->totals($run);
        });
    }

    /** @return array<string,mixed> */
    private function compute(HrEmployee $e, HrPayrollRun $run, string $period, Carbon $start, Carbon $end): array
    {
        $daysInMonth = $start->daysInMonth;

        // Pro-rate for people who joined or left during the month.
        $from = $e->join_date->gt($start) ? $e->join_date : $start;
        $to = $e->leave_date && $e->leave_date->lt($end) ? $e->leave_date : $end;
        $employedDays = max(0, $from->diffInDays($to) + 1);
        $factor = $employedDays / $daysInMonth;

        $basic = round((float) $e->basic_salary * $factor, 2);
        [$allowances, $deductions] = $e->componentTotals();
        $allowances = round($allowances * $factor, 2);
        $deductions = round($deductions * $factor, 2);

        $bonus = round((float) HrAward::where('employee_id', $e->id)->whereBetween('awarded_on', [$start->toDateString(), $end->toDateString()])->sum('cash_amount'), 2);

        // Absent days (half days count 0.5) plus unpaid approved leave.
        $absent = (float) HrAttendance::where('employee_id', $e->id)->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])->where('status', 'absent')->count();
        $absent += 0.5 * HrAttendance::where('employee_id', $e->id)->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])->where('status', 'half_day')->count();
        $absent += $this->unpaidLeaveDays($e, $start, $end);
        $absent = min($absent, (float) $daysInMonth);
        $absence = min($basic, round((float) $e->basic_salary / $daysInMonth * $absent, 2));

        $available = round($basic + $allowances + $bonus - $absence, 2);
        $deductions = min($deductions, $available);

        // Whole instalments only, oldest first, as far as the pay covers.
        $loan = 0.0;
        $room = round($available - $deductions, 2);
        foreach ($this->dueInstallments($e, $period) as $installment) {
            if ($loan + (float) $installment->amount <= $room + 0.004) {
                $loan = round($loan + (float) $installment->amount, 2);
            }
        }

        return [
            'run_id' => $run->id, 'employee_id' => $e->id, 'basic' => $basic, 'allowances' => $allowances, 'bonus' => $bonus,
            'absence_deduction' => $absence, 'deductions' => $deductions, 'loan_deduction' => $loan,
            'net' => round($available - $deductions - $loan, 2), 'absent_days' => $absent,
        ];
    }

    private function unpaidLeaveDays(HrEmployee $e, Carbon $start, Carbon $end): float
    {
        $days = 0.0;
        $requests = HrLeaveRequest::with('type')->where('employee_id', $e->id)->where('status', 'approved')
            ->whereDate('from_date', '<=', $end)->whereDate('to_date', '>=', $start)->get()->filter(fn ($r) => ! $r->type->is_paid);
        $calendar = app(WorkCalendar::class);

        foreach ($requests as $r) {
            if ((float) $r->days === 0.5) {
                $days += $r->from_date->between($start, $end) ? 0.5 : 0;

                continue;
            }
            $from = $r->from_date->gt($start) ? $r->from_date : $start;
            $to = $r->to_date->lt($end) ? $r->to_date : $end;
            $days += $calendar->workingDays($from, $to);
        }

        return $days;
    }

    /** Instalments that are due up to this month and not yet taken, oldest first. */
    private function dueInstallments(HrEmployee $e, string $period)
    {
        return HrLoanInstallment::query()
            ->whereIn('loan_id', HrLoan::where('employee_id', $e->id)->where('status', 'active')->select('id'))
            ->where('deducted', false)->where('period', '<=', $period)
            ->orderBy('period')->orderBy('id')->get();
    }

    private function totals(HrPayrollRun $run): HrPayrollRun
    {
        $items = $run->items()->get();
        $run->update([
            'total_expense' => round($items->sum(fn ($i) => (float) $i->basic + (float) $i->allowances + (float) $i->bonus - (float) $i->absence_deduction), 2),
            'total_deductions' => round($items->sum('deductions'), 2),
            'total_loans' => round($items->sum('loan_deduction'), 2),
            'total_net' => round($items->sum('net'), 2),
        ]);

        return $run->fresh();
    }

    /** Lock the figures, book the expense and take the loan instalments. */
    public function finalize(HrPayrollRun $run, ?int $userId): HrPayrollRun
    {
        return DB::transaction(function () use ($run, $userId) {
            $run = HrPayrollRun::query()->lockForUpdate()->findOrFail($run->id);
            if ($run->status !== 'draft') {
                throw new InvalidArgumentException('Only a draft payroll can be finalized.');
            }
            if ($run->items()->count() === 0) {
                throw new InvalidArgumentException('There are no employees in this payroll.');
            }

            $date = Carbon::createFromFormat('Y-m-d', $run->period.'-01')->endOfMonth()->startOfDay();
            $entry = $this->ledger->post('payroll', $date, [
                ['account' => 'salary_expense', 'debit' => $run->total_expense],
                ['account' => 'payroll_tax_payable', 'credit' => $run->total_deductions],
                ['account' => 'staff_loans', 'credit' => $run->total_loans],
                ['account' => 'salary_payable', 'credit' => $run->total_net],
            ], "Payroll {$run->period}", $run, $userId);

            foreach ($run->items as $item) {
                $this->takeInstallments($item, $run->period);
            }

            $run->update(['status' => 'finalized', 'journal_entry_id' => $entry->id]);

            return $run;
        });
    }

    private function takeInstallments(HrPayrollItem $item, string $period): void
    {
        $left = (float) $item->loan_deduction;
        $loanIds = [];
        foreach ($this->dueInstallments($item->employee, $period) as $installment) {
            if ($left + 0.004 >= (float) $installment->amount) {
                $installment->update(['deducted' => true]);
                $left = round($left - (float) $installment->amount, 2);
                $loanIds[$installment->loan_id] = true;
            }
        }
        foreach (array_keys($loanIds) as $id) {
            if (! HrLoanInstallment::where('loan_id', $id)->where('deducted', false)->exists()) {
                HrLoan::whereKey($id)->update(['status' => 'repaid']);
            }
        }
    }

    /** Undo a finalized (but unpaid) payroll so it can be corrected. */
    public function reopen(HrPayrollRun $run, ?int $userId): HrPayrollRun
    {
        return DB::transaction(function () use ($run, $userId) {
            $run = HrPayrollRun::query()->lockForUpdate()->findOrFail($run->id);
            if ($run->status !== 'finalized') {
                throw new InvalidArgumentException('Only a finalized, unpaid payroll can be reopened.');
            }

            $this->ledger->void($run->journalEntry(), $userId);
            // Put back the instalments this run took: the most recently taken ones up to the deducted amount.
            foreach ($run->items as $item) {
                $left = (float) $item->loan_deduction;
                foreach (HrLoanInstallment::whereIn('loan_id', HrLoan::where('employee_id', $item->employee_id)->select('id'))->where('deducted', true)->where('period', '<=', $run->period)->orderByDesc('period')->orderByDesc('id')->get() as $i) {
                    if ($left + 0.004 >= (float) $i->amount) {
                        $i->update(['deducted' => false]);
                        HrLoan::whereKey($i->loan_id)->update(['status' => 'active']);
                        $left = round($left - (float) $i->amount, 2);
                    }
                }
            }
            $run->update(['status' => 'draft', 'journal_entry_id' => null]);

            return $run;
        });
    }

    /** Pay the net salaries out of a cash or bank account. */
    public function pay(HrPayrollRun $run, int $accountId, Carbon $date, ?int $userId): HrPayrollRun
    {
        $account = LedgerAccount::findOrFail($accountId);
        if (! $account->is_cash || $account->is_group) {
            throw new InvalidArgumentException('Choose a cash or bank account to pay from.');
        }

        return DB::transaction(function () use ($run, $account, $date, $userId) {
            $run = HrPayrollRun::query()->lockForUpdate()->findOrFail($run->id);
            if ($run->status !== 'finalized') {
                throw new InvalidArgumentException('Only a finalized payroll can be paid.');
            }

            $entry = $this->ledger->post('payment', $date, [
                ['account' => 'salary_payable', 'debit' => $run->total_net],
                ['account' => $account, 'credit' => $run->total_net],
            ], "Salaries paid for {$run->period}", $run, $userId);
            $run->update(['status' => 'paid', 'paid_on' => $date, 'payment_entry_id' => $entry->id]);

            return $run;
        });
    }

    private function assertPeriod(string $period): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
            throw new InvalidArgumentException('The period must look like 2026-11.');
        }
    }
}
