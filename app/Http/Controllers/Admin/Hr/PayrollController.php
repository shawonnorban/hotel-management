<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrPayrollItem;
use App\Models\HrPayrollRun;
use App\Models\LedgerAccount;
use App\Services\Hr\PayrollService;
use App\Support\Money;
use App\Support\Settings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payroll) {}

    public function index()
    {
        return view('admin.hr.payroll.index', ['runs' => HrPayrollRun::withCount('items')->orderByDesc('period')->paginate(20)]);
    }

    public function generate(Request $request)
    {
        $period = $request->validate(['period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']])['period'];

        try {
            $run = $this->payroll->generate($period, auth('admin')->id());
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withErrors(['payroll' => $e->getMessage()]);
        }

        return redirect()->route('admin.hr.payroll.show', $run)->with('status', 'Payroll for '.$period.' generated. Review it, then finalize.');
    }

    public function show(HrPayrollRun $run)
    {
        $run->load('items');

        return view('admin.hr.payroll.show', [
            'run' => $run,
            'accounts' => LedgerAccount::where('is_cash', true)->where('is_group', false)->where('is_active', true)->orderBy('code')->get(),
            'month' => Carbon::createFromFormat('Y-m-d', $run->period.'-01'),
        ]);
    }

    public function finalize(HrPayrollRun $run)
    {
        return $this->act(fn () => $this->payroll->finalize($run, auth('admin')->id()), 'Payroll finalized and booked.', $run);
    }

    public function reopen(HrPayrollRun $run)
    {
        return $this->act(fn () => $this->payroll->reopen($run, auth('admin')->id()), 'Payroll reopened as a draft.', $run);
    }

    public function pay(Request $request, HrPayrollRun $run)
    {
        $d = $request->validate(['account' => ['required', 'integer', 'exists:ledger_accounts,id'], 'paid_on' => ['required', 'date']]);

        return $this->act(fn () => $this->payroll->pay($run, (int) $d['account'], Carbon::parse($d['paid_on']), auth('admin')->id()), 'Salaries marked as paid.', $run);
    }

    public function destroy(HrPayrollRun $run)
    {
        if ($run->status !== 'draft') {
            return back()->withErrors(['payroll' => 'Only a draft payroll can be deleted. Reopen it first.']);
        }
        $run->delete();

        return redirect()->route('admin.hr.payroll.index')->with('status', 'Draft payroll deleted.');
    }

    public function payslip(HrPayrollRun $run, HrPayrollItem $item)
    {
        abort_unless($item->run_id === $run->id && $run->status !== 'draft', 404);

        return Pdf::loadView('pdf.payslip', ['run' => $run, 'item' => $item->load('employee.position', 'employee.department'), 'hotel' => Settings::hotelName(), 'money' => fn ($v) => Money::format($v)])
            ->setPaper('a5', 'portrait')->stream('payslip-'.$run->period.'-'.$item->employee->code.'.pdf');
    }

    public function export(HrPayrollRun $run)
    {
        $run->load('items');

        return response()->streamDownload(function () use ($run) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee no.', 'Employee', 'Basic', 'Allowances', 'Bonus', 'Absence', 'Deductions', 'Loan', 'Net']);
            foreach ($run->items as $i) {
                fputcsv($out, [$i->employee->code, $i->employee->full_name, $i->basic, $i->allowances, $i->bonus, $i->absence_deduction, $i->deductions, $i->loan_deduction, $i->net]);
            }
            fclose($out);
        }, 'payroll-'.$run->period.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function act(\Closure $action, string $message, HrPayrollRun $run)
    {
        try {
            $action();
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withErrors(['payroll' => $e->getMessage()]);
        }

        return redirect()->route('admin.hr.payroll.show', $run)->with('status', $message);
    }
}
