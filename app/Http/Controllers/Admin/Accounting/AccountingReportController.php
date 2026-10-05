<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\LedgerAccount;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AccountingReportController extends Controller
{
    public function __construct(private LedgerService $ledger) {}

    public function ledger(Request $request)
    {
        [$from, $to] = $this->period($request);
        $accounts = LedgerAccount::where('is_group', false)->orderBy('code')->get();
        $account = $request->integer('account') ? $accounts->firstWhere('id', $request->integer('account')) : null;
        $data = $account ? $this->ledger->ledger($account, $from, $to) : null;

        if ($data && $request->query('export') === 'csv') {
            return $this->csv('ledger-'.$account->code, ['Date', 'Voucher', 'Narration', 'Debit', 'Credit', 'Balance'], $data['rows']->map(fn ($r) => [
                $r['entry']->entry_date->format('Y-m-d'), $r['entry']->number, $r['entry']->narration, $r['debit'], $r['credit'], $r['balance'],
            ])->all());
        }

        return view('admin.accounting.reports.ledger', compact('accounts', 'account', 'data', 'from', 'to'));
    }

    public function cashBook(Request $request)
    {
        [$from, $to] = $this->period($request);
        $cash = LedgerAccount::where('is_cash', true)->where('is_group', false)->orderBy('code')->get();
        $books = $cash->map(fn ($a) => ['account' => $a, 'data' => $this->ledger->ledger($a, $from, $to)]);

        return view('admin.accounting.reports.cash-book', compact('books', 'from', 'to'));
    }

    public function trialBalance(Request $request)
    {
        $asOf = $this->asOf($request);
        $tb = $this->ledger->trialBalance($asOf);

        if ($request->query('export') === 'csv') {
            return $this->csv('trial-balance-'.$asOf->format('Ymd'), ['Code', 'Account', 'Debit', 'Credit'], $tb['rows']->map(fn ($r) => [$r['account']->code, $r['account']->name, $r['debit'], $r['credit']])->all());
        }

        return view('admin.accounting.reports.trial-balance', ['tb' => $tb, 'asOf' => $asOf]);
    }

    public function incomeStatement(Request $request)
    {
        [$from, $to] = $this->period($request, true);
        $is = $this->ledger->incomeStatement($from, $to);

        if ($request->query('export') === 'csv') {
            $rows = [];
            foreach ($is['income'] as $r) {
                $rows[] = ['Income', $r['account']->name, $r['amount']];
            }
            foreach ($is['expenses'] as $r) {
                $rows[] = ['Expense', $r['account']->name, $r['amount']];
            }
            $rows[] = ['Net result', '', $is['net']];

            return $this->csv('income-statement', ['Section', 'Account', 'Amount'], $rows);
        }

        return view('admin.accounting.reports.income-statement', ['is' => $is, 'from' => $from, 'to' => $to]);
    }

    public function balanceSheet(Request $request)
    {
        $asOf = $this->asOf($request);

        return view('admin.accounting.reports.balance-sheet', ['bs' => $this->ledger->balanceSheet($asOf), 'asOf' => $asOf]);
    }

    /** @return array{0:Carbon,1:Carbon} */
    private function period(Request $request, bool $yearToDate = false): array
    {
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $to = isset($data['to']) ? Carbon::parse($data['to']) : today();
        $from = isset($data['from']) ? Carbon::parse($data['from']) : ($yearToDate ? $to->copy()->startOfYear() : $to->copy()->startOfMonth());

        return [$from->startOfDay(), $to->startOfDay()];
    }

    private function asOf(Request $request): Carbon
    {
        $data = $request->validate(['as_of' => ['nullable', 'date']]);

        return isset($data['as_of']) ? Carbon::parse($data['as_of'])->startOfDay() : today();
    }

    private function csv(string $name, array $header, array $rows)
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v, $row));
            }
            fclose($out);
        }, $name.'.csv', ['Content-Type' => 'text/csv']);
    }
}
