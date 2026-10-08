<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\LedgerAccount;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Models\PurchaseReturn;
use App\Support\Money;
use App\Support\Settings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchases) {}

    public function index(Request $request)
    {
        $f = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'supplier' => ['nullable', 'integer'], 'unpaid' => ['nullable', 'boolean']]);

        $purchases = Purchase::with('supplier', 'returns')
            ->when($f['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('reference', 'like', $like)->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $like)));
            })
            ->when($f['supplier'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($request->boolean('unpaid'), fn ($q) => $q->whereColumn('paid', '<', 'total'))
            ->orderByDesc('purchase_date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        $owed = (float) Purchase::query()->selectRaw('coalesce(sum(total - paid),0) as due')->value('due');

        return view('admin.purchases.index', ['purchases' => $purchases, 'f' => $f, 'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'), 'owed' => $owed]);
    }

    public function returns(Request $request)
    {
        $f = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);

        $returns = PurchaseReturn::with('purchase.supplier')
            ->when($f['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhereHas('purchase', fn ($p) => $p->where('number', 'like', $like)->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $like))));
            })
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('return_date', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('return_date', '<=', $v))
            ->orderByDesc('return_date')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.purchases.returns', ['returns' => $returns, 'f' => $f]);
    }

    public function returnInvoice(PurchaseReturn $return)
    {
        $return->load('purchase.supplier', 'movements');

        return Pdf::loadView('pdf.purchase-return', ['return' => $return, 'hotel' => Settings::hotelName(), 'money' => fn ($v) => Money::pdf($v)])
            ->download('return-'.$return->number.'.pdf');
    }

    public function create()
    {
        return view('admin.purchases.create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'items' => InventoryItem::with('unit')->where('is_active', true)->orderBy('name')->get(),
            'accounts' => LedgerAccount::where('is_cash', true)->where('is_group', false)->where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1', 'max:200'],
            'lines.*.item' => ['required', 'integer', 'exists:inventory_items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'pay_amount' => ['nullable', 'numeric', 'gt:0'],
            'pay_account' => ['nullable', 'required_with:pay_amount', 'integer', 'exists:ledger_accounts,id'],
            'pay_reference' => ['nullable', 'string', 'max:80'],
        ]);

        try {
            $purchase = $this->purchases->receive(
                Supplier::findOrFail($d['supplier_id']), $d['purchase_date'], array_values($d['lines']), (float) ($d['discount'] ?? 0), $d['reference'] ?? null, $d['notes'] ?? null,
                ! empty($d['pay_amount']) ? ['amount' => (float) $d['pay_amount'], 'account' => (int) $d['pay_account'], 'reference' => $d['pay_reference'] ?? null] : null,
                auth('admin')->id(),
            );
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->withErrors(['purchase' => $e->getMessage()]);
        }

        return redirect()->route('admin.purchases.show', $purchase)->with('status', 'Purchase '.$purchase->number.' recorded and stock updated.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'items', 'payments', 'returns');

        return view('admin.purchases.show', [
            'purchase' => $purchase,
            'accounts' => LedgerAccount::where('is_cash', true)->where('is_group', false)->where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function pay(Request $request, Purchase $purchase)
    {
        $d = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'account' => ['required', 'integer', 'exists:ledger_accounts,id'],
            'paid_on' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:80'],
        ]);

        return $this->act(fn () => $this->purchases->pay($purchase, (float) $d['amount'], (int) $d['account'], $d['paid_on'], $d['reference'] ?? null, auth('admin')->id()), 'Payment recorded.', $purchase);
    }

    public function returnGoods(Request $request, Purchase $purchase)
    {
        $d = $request->validate([
            'return_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:250'],
            'qty' => ['required', 'array'],
            'qty.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        return $this->act(fn () => $this->purchases->returnGoods($purchase, $d['qty'], $d['return_date'], $d['reason'] ?? null, auth('admin')->id()), 'Return recorded and stock reduced.', $purchase);
    }

    private function act(\Closure $action, string $message, Purchase $purchase)
    {
        try {
            $action();
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }

        return redirect()->route('admin.purchases.show', $purchase)->with('status', $message);
    }
}
