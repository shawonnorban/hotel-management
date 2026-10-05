<?php

namespace App\Services\Hr;

use App\Models\HrEmployee;
use App\Models\HrLoan;
use App\Models\LedgerAccount;
use App\Services\LedgerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoanService
{
    public function __construct(private LedgerService $ledger)
    {
    }

    /** Lend money to an employee; it is taken back in equal monthly instalments through payroll. */
    public function issue(HrEmployee $employee, float $amount, int $installments, Carbon $issuedOn, string $firstMonth, int $payFromAccount, ?string $reason, ?int $userId): HrLoan
    {
        $amount = round($amount, 2);
        if ($amount <= 0 || $installments < 1 || $installments > 120) {
            throw new InvalidArgumentException('Enter an amount above zero and between 1 and 120 instalments.');
        }
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $firstMonth)) {
            throw new InvalidArgumentException('The first deduction month must look like 2026-11.');
        }
        $account = LedgerAccount::findOrFail($payFromAccount);
        if (! $account->is_cash || $account->is_group) {
            throw new InvalidArgumentException('Choose a cash or bank account to pay the loan from.');
        }

        return DB::transaction(function () use ($employee, $amount, $installments, $issuedOn, $firstMonth, $account, $reason, $userId) {
            $loan = HrLoan::create(['employee_id' => $employee->id, 'amount' => $amount, 'installments' => $installments, 'issued_on' => $issuedOn, 'first_month' => $firstMonth, 'reason' => $reason, 'status' => 'active', 'created_by' => $userId]);

            $each = floor($amount / $installments * 100) / 100;
            $month = Carbon::createFromFormat('Y-m-d', $firstMonth.'-01');
            $remaining = $amount;
            for ($i = 1; $i <= $installments; $i++) {
                $part = $i === $installments ? round($remaining, 2) : $each;
                $loan->schedule()->create(['period' => $month->format('Y-m'), 'amount' => $part, 'deducted' => false]);
                $remaining = round($remaining - $part, 2);
                $month->addMonth();
            }

            $entry = $this->ledger->post('payment', $issuedOn, [
                ['account' => 'staff_loans', 'debit' => $amount],
                ['account' => $account, 'credit' => $amount],
            ], "Loan to {$employee->full_name}", $loan, $userId);
            $loan->update(['journal_entry_id' => $entry->id]);

            return $loan->load('schedule');
        });
    }
}
