<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrEmployee;
use App\Models\HrLoan;
use App\Models\LedgerAccount;
use App\Services\Hr\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class LoanController extends Controller
{
    public function __construct(private LoanService $loans)
    {
    }

    public function index()
    {
        return view('admin.hr.loans.index', [
            'loans' => HrLoan::with('employee', 'schedule')->orderByDesc('id')->paginate(20),
            'employees' => HrEmployee::where('is_active', true)->orderBy('first_name')->get(),
            'accounts' => LedgerAccount::where('is_cash', true)->where('is_group', false)->where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:hr_employees,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'installments' => ['required', 'integer', 'between:1,120'],
            'issued_on' => ['required', 'date'],
            'first_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'account' => ['required', 'integer', 'exists:ledger_accounts,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->loans->issue(HrEmployee::findOrFail($d['employee_id']), (float) $d['amount'], (int) $d['installments'], Carbon::parse($d['issued_on']), $d['first_month'], (int) $d['account'], $d['reason'] ?? null, auth('admin')->id());
        } catch (InvalidArgumentException|\RuntimeException $e) {
            return back()->withInput()->withErrors(['loan' => $e->getMessage()]);
        }

        return back()->with('status', 'Loan issued.');
    }
}
