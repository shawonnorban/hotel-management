<?php

namespace App\Http\Controllers\Admin\Housekeeping;

use App\Http\Controllers\Controller;
use App\Models\BookedInfo;
use App\Models\HkLaundryCost;
use App\Models\HkLaundryOrder;
use App\Models\HkLaundryPayment;
use App\Models\HkLaundryProduct;
use App\Models\LedgerAccount;
use App\Services\LaundryService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class LaundryController extends Controller
{
    public function __construct(private LaundryService $laundry) {}

    public function index(Request $request)
    {
        $f = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:'.implode(',', array_keys(HkLaundryOrder::STATUSES))], 'unpaid' => ['nullable', 'boolean']]);

        $orders = HkLaundryOrder::query()
            ->when($f['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('guest_name', 'like', $like)->orWhere('room_no', 'like', $like));
            })
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($request->boolean('unpaid'), fn ($q) => $q->where('status', '!=', 'cancelled')->whereColumn('paid', '<', 'total'))
            ->orderByDesc('order_date')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.housekeeping.laundry.index', ['orders' => $orders, 'f' => $f]);
    }

    public function create()
    {
        $costs = HkLaundryCost::with('product')->whereHas('product', fn ($q) => $q->where('is_active', true))->get();

        return view('admin.housekeeping.laundry.create', [
            'products' => HkLaundryProduct::where('is_active', true)->orderBy('name')->get(),
            'costs' => $costs->groupBy('product_id')->map(fn ($g) => $g->pluck('cost', 'service')),
            'services' => HkLaundryCost::SERVICES,
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'booking_number' => ['nullable', 'string', 'max:30'], 'guest_name' => ['nullable', 'string', 'max:150'], 'room_no' => ['nullable', 'string', 'max:20'],
            'order_date' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'max:60'], 'lines.*.product' => ['nullable', 'integer', 'exists:hk_laundry_products,id'],
            'lines.*.service' => ['nullable', 'string'], 'lines.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $booking = ! empty($d['booking_number']) ? BookedInfo::with('customer')->where('booking_number', trim($d['booking_number']))->first() : null;
        if (! empty($d['booking_number']) && ! $booking) {
            return back()->withInput()->withErrors(['booking_number' => 'No booking with that number.']);
        }
        $name = $d['guest_name'] ?? null ?: $booking?->customer?->full_name;
        if (blank($name)) {
            return back()->withInput()->withErrors(['guest_name' => 'Enter the guest name or a booking number.']);
        }

        try {
            $order = $this->laundry->create($name, $d['room_no'] ?? $booking?->room_no, $booking?->bookedid, $d['order_date'], $d['lines'], $d['notes'] ?? null, auth('admin')->id());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()->route('admin.laundry.show', $order)->with('status', 'Laundry order '.$order->number.' created.');
    }

    public function show(HkLaundryOrder $order)
    {
        $order->load('lines', 'payments');

        return view('admin.housekeeping.laundry.show', [
            'order' => $order, 'services' => HkLaundryCost::SERVICES,
            'accounts' => LedgerAccount::where('is_cash', true)->where('is_group', false)->where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function status(Request $request, HkLaundryOrder $order)
    {
        $d = $request->validate(['status' => ['required', 'in:received,washing,ready,delivered']]);
        if ($order->status === 'cancelled') {
            return back()->withErrors(['order' => 'This order was cancelled.']);
        }
        $order->update(['status' => $d['status']]);

        return back()->with('status', 'Order marked '.strtolower(HkLaundryOrder::STATUSES[$d['status']]).'.');
    }

    public function cancel(HkLaundryOrder $order)
    {
        try {
            $this->laundry->cancel($order);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Order cancelled.');
    }

    public function pay(Request $request, HkLaundryOrder $order)
    {
        $d = $request->validate(['amount' => ['required', 'numeric', 'gt:0'], 'account' => ['required', 'integer', 'exists:ledger_accounts,id'], 'paid_on' => ['required', 'date'], 'reference' => ['nullable', 'string', 'max:80']]);
        try {
            $this->laundry->pay($order, (float) $d['amount'], (int) $d['account'], $d['paid_on'], $d['reference'] ?? null, auth('admin')->id());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Payment recorded.');
    }

    public function payments(Request $request)
    {
        $f = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $payments = HkLaundryPayment::with('order', 'account')
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('paid_on', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('paid_on', '<=', $v))
            ->orderByDesc('paid_on')->orderByDesc('id')->paginate(30)->withQueryString();
        $total = round((float) HkLaundryPayment::query()
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('paid_on', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('paid_on', '<=', $v))->sum('amount'), 2);

        return view('admin.housekeeping.laundry.payments', ['payments' => $payments, 'f' => $f, 'total' => $total]);
    }
}
