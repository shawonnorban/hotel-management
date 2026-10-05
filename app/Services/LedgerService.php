<?php

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Double-entry bookkeeping core. Every financial event in the system (guest payments, check-out revenue,
 * purchases, payroll, manual vouchers) becomes a balanced journal entry posted through here.
 */
class LedgerService
{
    /** @var array<string,LedgerAccount> */
    private array $byKey = [];

    /** Resolve an account by system key (e.g. "cash"), id or model. */
    public function account(string|int|LedgerAccount $ref): LedgerAccount
    {
        if ($ref instanceof LedgerAccount) {
            return $ref;
        }

        if (is_int($ref)) {
            return LedgerAccount::findOrFail($ref);
        }

        return $this->byKey[$ref] ??= LedgerAccount::where('system_key', $ref)->first()
            ?? throw new RuntimeException("Ledger account \"{$ref}\" is not set up. Run `php artisan db:seed`.");
    }

    /**
     * Post a balanced journal entry.
     *
     * @param  list<array{account:string|int|LedgerAccount,debit?:float|int|string,credit?:float|int|string,memo?:?string}>  $lines
     */
    public function post(string $type, CarbonInterface|string $date, array $lines, ?string $narration = null, ?Model $source = null, ?int $userId = null): JournalEntry
    {
        $date = Carbon::parse($date)->startOfDay();
        $prepared = [];
        $debit = $credit = 0;

        foreach ($lines as $line) {
            $account = $this->account($line['account']);
            if ($account->is_group) {
                throw new InvalidArgumentException("\"{$account->name}\" is a group account; post to one of its sub-accounts.");
            }
            if (! $account->is_active) {
                throw new InvalidArgumentException("Account \"{$account->name}\" is inactive.");
            }

            $d = (int) round(((float) ($line['debit'] ?? 0)) * 100);
            $c = (int) round(((float) ($line['credit'] ?? 0)) * 100);
            if ($d < 0 || $c < 0 || ($d > 0 && $c > 0)) {
                throw new InvalidArgumentException('Each line must have either a debit or a credit amount.');
            }
            if ($d === 0 && $c === 0) {
                continue;
            }

            $debit += $d;
            $credit += $c;
            $prepared[] = ['ledger_account_id' => $account->id, 'debit' => $d / 100, 'credit' => $c / 100, 'memo' => $line['memo'] ?? null];
        }

        if (count($prepared) < 2) {
            throw new InvalidArgumentException('A journal entry needs at least two lines with amounts.');
        }
        if ($debit !== $credit) {
            throw new InvalidArgumentException(sprintf('The entry does not balance: debits %.2f, credits %.2f.', $debit / 100, $credit / 100));
        }

        $this->assertPeriodOpen($date);

        return DB::transaction(function () use ($type, $date, $prepared, $narration, $source, $userId) {
            $entry = JournalEntry::create([
                'number' => 'PENDING-'.bin2hex(random_bytes(6)),
                'entry_date' => $date,
                'type' => $type,
                'narration' => $narration,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'created_by' => $userId,
                'status' => 'posted',
            ]);
            $entry->update(['number' => 'JE-'.str_pad((string) $entry->id, 6, '0', STR_PAD_LEFT)]);
            $entry->lines()->createMany($prepared);

            return $entry->load('lines');
        });
    }

    /** Cancel an entry (kept for the audit trail, excluded from every balance). */
    public function void(JournalEntry $entry, ?int $userId = null): JournalEntry
    {
        if ($entry->status === 'void') {
            return $entry;
        }
        $this->assertPeriodOpen($entry->entry_date);
        $entry->update(['status' => 'void', 'narration' => trim($entry->narration.' [voided'.($userId ? ' by #'.$userId : '').' on '.now()->format('Y-m-d').']')]);

        return $entry;
    }

    public function assertPeriodOpen(CarbonInterface $date): void
    {
        $closed = FinancialYear::where('is_closed', true)->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first();
        if ($closed) {
            throw new RuntimeException("The financial year \"{$closed->title}\" is closed; entries dated {$date->format('Y-m-d')} cannot be changed.");
        }
    }

    /** Net movement per account over a period: [account_id => [debit, credit]]. */
    private function movements(?CarbonInterface $from, ?CarbonInterface $to): Collection
    {
        return JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->when($from, fn ($q) => $q->whereDate('journal_entries.entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('journal_entries.entry_date', '<=', $to))
            ->groupBy('journal_lines.ledger_account_id')
            ->selectRaw('journal_lines.ledger_account_id as id, sum(journal_lines.debit) as debit, sum(journal_lines.credit) as credit')
            ->get()
            ->keyBy('id');
    }

    /** Balance in the account's normal direction (positive = normal), including its opening balance, up to a date. */
    public function balance(LedgerAccount|string $account, ?CarbonInterface $upTo = null): float
    {
        $account = $this->account($account);
        $m = $this->movements(null, $upTo)->get($account->id);
        $net = (float) ($m->debit ?? 0) - (float) ($m->credit ?? 0);

        return round((float) $account->opening_balance + ($account->isDebitNormal() ? $net : -$net), 2);
    }

    /**
     * Trial balance as at a date, one row per postable account that has a balance.
     *
     * @return array{rows:Collection,debit:float,credit:float}
     */
    public function trialBalance(CarbonInterface $asOf): array
    {
        $moves = $this->movements(null, $asOf);
        $rows = collect();
        $totalDebit = $totalCredit = 0.0;

        foreach (LedgerAccount::where('is_group', false)->orderBy('code')->get() as $account) {
            $m = $moves->get($account->id);
            $net = (float) ($m->debit ?? 0) - (float) ($m->credit ?? 0);
            $signed = ($account->isDebitNormal() ? 1 : -1) * (float) $account->opening_balance + $net; // debit-positive
            if (abs($signed) < 0.005) {
                continue;
            }
            $debit = $signed > 0 ? $signed : 0.0;
            $credit = $signed < 0 ? -$signed : 0.0;
            $totalDebit += $debit;
            $totalCredit += $credit;
            $rows->push(['account' => $account, 'debit' => round($debit, 2), 'credit' => round($credit, 2)]);
        }

        return ['rows' => $rows, 'debit' => round($totalDebit, 2), 'credit' => round($totalCredit, 2)];
    }

    /**
     * Account ledger for a period with a running balance.
     *
     * @return array{opening:float,rows:Collection,closing:float}
     */
    public function ledger(LedgerAccount $account, CarbonInterface $from, CarbonInterface $to): array
    {
        $sign = $account->isDebitNormal() ? 1 : -1;
        $opening = $this->balance($account, $from->copy()->subDay());

        $running = $opening;
        $rows = JournalLine::query()
            ->with('entry')
            ->where('ledger_account_id', $account->id)
            ->whereHas('entry', fn ($q) => $q->where('status', 'posted')->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to))
            ->get()
            ->sortBy(fn ($l) => $l->entry->entry_date->format('Ymd').str_pad((string) $l->entry->id, 10, '0', STR_PAD_LEFT))
            ->values()
            ->map(function ($line) use (&$running, $sign) {
                $running = round($running + $sign * ((float) $line->debit - (float) $line->credit), 2);

                return ['line' => $line, 'entry' => $line->entry, 'debit' => (float) $line->debit, 'credit' => (float) $line->credit, 'balance' => $running];
            });

        return ['opening' => $opening, 'rows' => $rows, 'closing' => $running];
    }

    /**
     * Income statement for a period.
     *
     * @return array{income:Collection,expenses:Collection,total_income:float,total_expenses:float,net:float}
     */
    public function incomeStatement(CarbonInterface $from, CarbonInterface $to): array
    {
        $moves = $this->movements($from, $to);
        $build = function (string $type) use ($moves) {
            return LedgerAccount::where('type', $type)->where('is_group', false)->orderBy('code')->get()->map(function ($a) use ($moves) {
                $m = $moves->get($a->id);
                $net = (float) ($m->debit ?? 0) - (float) ($m->credit ?? 0);

                return ['account' => $a, 'amount' => round($a->isDebitNormal() ? $net : -$net, 2)];
            })->filter(fn ($r) => abs($r['amount']) >= 0.005)->values();
        };

        $income = $build('income');
        $expenses = $build('expense');
        $ti = round($income->sum('amount'), 2);
        $te = round($expenses->sum('amount'), 2);

        return ['income' => $income, 'expenses' => $expenses, 'total_income' => $ti, 'total_expenses' => $te, 'net' => round($ti - $te, 2)];
    }

    /**
     * Balance sheet as at a date. Profit not yet closed into equity is shown as "Current period result".
     *
     * @return array{assets:Collection,liabilities:Collection,equity:Collection,total_assets:float,total_liabilities:float,total_equity:float,result:float}
     */
    public function balanceSheet(CarbonInterface $asOf): array
    {
        $moves = $this->movements(null, $asOf);
        $section = function (string $type) use ($moves) {
            return LedgerAccount::where('type', $type)->where('is_group', false)->orderBy('code')->get()->map(function ($a) use ($moves) {
                $m = $moves->get($a->id);
                $net = (float) ($m->debit ?? 0) - (float) ($m->credit ?? 0);

                return ['account' => $a, 'amount' => round((float) $a->opening_balance + ($a->isDebitNormal() ? $net : -$net), 2)];
            })->filter(fn ($r) => abs($r['amount']) >= 0.005)->values();
        };

        $assets = $section('asset');
        $liabilities = $section('liability');
        $equity = $section('equity');
        $income = $section('income')->sum('amount');
        $expense = $section('expense')->sum('amount');
        $result = round($income - $expense, 2);

        return [
            'assets' => $assets, 'liabilities' => $liabilities, 'equity' => $equity,
            'total_assets' => round($assets->sum('amount'), 2),
            'total_liabilities' => round($liabilities->sum('amount'), 2),
            'total_equity' => round($equity->sum('amount') + $result, 2),
            'result' => $result,
        ];
    }
}
