<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class StockController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request)
    {
        $f = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'integer'], 'low' => ['nullable', 'boolean']]);

        $items = InventoryItem::with('unit', 'category')
            ->where('is_active', true)
            ->when($f['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like));
            })
            ->when($f['category'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->boolean('low'), fn ($q) => $q->where('reorder_level', '>', 0)->whereColumn('stock', '<=', 'reorder_level'))
            ->orderBy('name')
            ->paginate(30)->withQueryString();

        $totals = InventoryItem::where('is_active', true)->selectRaw('count(*) as n, coalesce(sum(stock * avg_cost),0) as value')->first();
        $low = InventoryItem::where('is_active', true)->where('reorder_level', '>', 0)->whereColumn('stock', '<=', 'reorder_level')->count();

        return view('admin.stock.index', ['items' => $items, 'f' => $f, 'categories' => ItemCategory::orderBy('name')->pluck('name', 'id'), 'totals' => $totals, 'low' => $low]);
    }

    public function movements(Request $request)
    {
        $f = $request->validate(['item' => ['nullable', 'integer'], 'type' => ['nullable', 'in:'.implode(',', array_keys(StockMovement::TYPES))], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);

        $movements = StockMovement::with('item.unit', 'user')
            ->when($f['item'] ?? null, fn ($q, $v) => $q->where('item_id', $v))
            ->when($f['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('moved_at', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('moved_at', '<=', $v))
            ->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.stock.movements', ['movements' => $movements, 'f' => $f, 'items' => InventoryItem::orderBy('name')->pluck('name', 'id')]);
    }

    /** Stock that was thrown away or written off (wastage), valued at what it cost. */
    public function destroyed(Request $request)
    {
        $f = $request->validate(['item' => ['nullable', 'integer'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);

        $query = StockMovement::with('item.unit', 'user')->where('type', 'waste')
            ->when($f['item'] ?? null, fn ($q, $v) => $q->where('item_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('moved_at', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('moved_at', '<=', $v));
        $loss = round((clone $query)->get()->sum(fn ($m) => abs((float) $m->quantity) * (float) $m->unit_cost), 2);

        return view('admin.stock.destroyed', ['movements' => $query->orderByDesc('id')->paginate(30)->withQueryString(), 'f' => $f, 'loss' => $loss, 'items' => InventoryItem::orderBy('name')->pluck('name', 'id')]);
    }

    public function issue(Request $request)
    {
        $d = $request->validate(['item' => ['required', 'integer', 'exists:inventory_items,id'], 'quantity' => ['required', 'numeric', 'gt:0'], 'reason' => ['required', 'string', 'max:200']]);

        return $this->act(fn () => $this->inventory->issue(InventoryItem::findOrFail($d['item']), (float) $d['quantity'], $d['reason'], auth('admin')->id()), 'Stock issued.');
    }

    public function waste(Request $request)
    {
        $d = $request->validate(['item' => ['required', 'integer', 'exists:inventory_items,id'], 'quantity' => ['required', 'numeric', 'gt:0'], 'reason' => ['required', 'string', 'max:200']]);

        return $this->act(fn () => $this->inventory->waste(InventoryItem::findOrFail($d['item']), (float) $d['quantity'], $d['reason'], auth('admin')->id()), 'Write-off recorded.');
    }

    public function adjust(Request $request)
    {
        $d = $request->validate(['item' => ['required', 'integer', 'exists:inventory_items,id'], 'counted' => ['required', 'numeric', 'min:0'], 'reason' => ['required', 'string', 'max:200']]);

        return $this->act(fn () => $this->inventory->adjust(InventoryItem::findOrFail($d['item']), (float) $d['counted'], $d['reason'], auth('admin')->id()), 'Stock count saved.');
    }

    private function act(\Closure $action, string $message)
    {
        try {
            $action();
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->withErrors(['action' => $e->getMessage()]);
        }

        return back()->with('status', $message);
    }
}
