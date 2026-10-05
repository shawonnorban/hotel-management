<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

class VoucherController extends Controller
{
    public function __construct(private LedgerService $ledger)
    {
    }

    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:'.implode(',', array_keys(JournalEntry::TYPES))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $entries = JournalEntry::query()
            ->withSum('lines as total', 'debit')
            ->when($f['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('entry_date', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('entry_date', '<=', $v))
            ->when($f['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('narration', 'like', $like));
            })
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('admin.accounting.vouchers.index', ['entries' => $entries, 'f' => $f]);
    }

    public function create(Request $request)
    {
        return view('admin.accounting.vouchers.create', [
            'type' => in_array($request->query('type'), ['receipt', 'payment', 'contra', 'journal'], true) ? $request->query('type') : 'receipt',
            'accounts' => $this->postable(),
            'cashAccounts' => $this->postable()->where('is_cash', true),
        ]);
    }

    public function store(Request $request)
    {
        $type = $request->validate(['type' => ['required', 'in:receipt,payment,contra,journal']])['type'];
        $common = $request->validate([
            'entry_date' => ['required', 'date'],
            'narration' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $lines = $type === 'journal' ? $this->journalLines($request) : $this->simpleLines($request, $type);
            $entry = $this->ledger->post($type, Carbon::parse($common['entry_date']), $lines, $common['narration'] ?? null, null, auth('admin')->id());
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->withErrors(['voucher' => $e->getMessage()]);
        }

        return redirect()->route('admin.vouchers.show', $entry)->with('status', 'Voucher '.$entry->number.' posted.');
    }

    public function show(JournalEntry $voucher)
    {
        return view('admin.accounting.vouchers.show', ['entry' => $voucher->load('lines', 'creator')]);
    }

    public function void(JournalEntry $voucher)
    {
        try {
            $this->ledger->void($voucher, auth('admin')->id());
        } catch (RuntimeException $e) {
            return back()->withErrors(['voucher' => $e->getMessage()]);
        }

        return redirect()->route('admin.vouchers.show', $voucher)->with('status', 'Voucher '.$voucher->number.' was voided.');
    }

    private function postable()
    {
        return LedgerAccount::where('is_group', false)->where('is_active', true)->orderBy('code')->get();
    }

    /** Receipt / payment / contra: one cash-or-bank account against one other account. */
    private function simpleLines(Request $request, string $type): array
    {
        $data = $request->validate([
            'cash_account' => ['required', 'integer', 'exists:ledger_accounts,id'],
            'other_account' => ['required', 'integer', 'exists:ledger_accounts,id', 'different:cash_account'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999'],
        ]);
        $amount = (float) $data['amount'];

        return match ($type) {
            'receipt' => [['account' => (int) $data['cash_account'], 'debit' => $amount], ['account' => (int) $data['other_account'], 'credit' => $amount]],
            'payment' => [['account' => (int) $data['other_account'], 'debit' => $amount], ['account' => (int) $data['cash_account'], 'credit' => $amount]],
            default => [['account' => (int) $data['other_account'], 'debit' => $amount], ['account' => (int) $data['cash_account'], 'credit' => $amount]],
        };
    }

    private function journalLines(Request $request): array
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:2', 'max:50'],
            'lines.*.account' => ['nullable', 'integer', 'exists:ledger_accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ]);

        return collect($data['lines'])->filter(fn ($l) => ! empty($l['account']))->map(fn ($l) => [
            'account' => (int) $l['account'], 'debit' => $l['debit'] ?? 0, 'credit' => $l['credit'] ?? 0, 'memo' => $l['memo'] ?? null,
        ])->values()->all();
    }
}
