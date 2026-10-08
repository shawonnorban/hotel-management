<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookedInfo;
use App\Models\PaymentMethod;
use App\Services\AdvanceBookingService;
use App\Services\PaymentService;
use App\Support\AppSettings;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AdvanceBookingController extends Controller
{
    public function __construct(private AdvanceBookingService $advance, private PaymentService $payments) {}

    public function index(Request $request)
    {
        $f = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'due' => ['nullable', 'boolean'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);

        $bookings = BookedInfo::with('customer')->whereIn('bookingstatus', ['0', '2'])->whereDate('checkindate', '>=', today())
            ->when($f['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('booking_number', 'like', $like)->orWhere('full_guest_name', 'like', $like)->orWhereHas('customer', fn ($c) => $c->where('cust_phone', 'like', $like)->orWhere('firstname', 'like', $like)));
            })
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('checkindate', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('checkindate', '<=', $v))
            ->orderBy('checkindate')->orderBy('bookedid')->get();

        $rows = $bookings->map(fn ($b) => ['booking' => $b, 'required' => $this->advance->required($b), 'shortfall' => $this->advance->shortfall($b), 'days' => (int) today()->diffInDays($b->checkindate->copy()->startOfDay())]);
        if ($request->boolean('due')) {
            $rows = $rows->filter(fn ($r) => $r['shortfall'] > 0 || (string) $r['booking']->bookingstatus === '0')->values();
        }

        return view('admin.advance.index', [
            'rows' => $rows, 'f' => $f, 'percent' => $this->advance->percent(), 'holdDays' => $this->advance->holdDays(),
            'methods' => PaymentMethod::where('is_active', 1)->orderBy('payment_method_id')->get(),
            'totals' => ['paid' => round($rows->sum(fn ($r) => (float) $r['booking']->paid_amount), 2), 'balance' => round($rows->sum(fn ($r) => $r['booking']->balance), 2)],
        ]);
    }

    public function updateRule(Request $request)
    {
        $d = $request->validate(['percent' => ['required', 'numeric', 'min:0', 'max:100'], 'hold_days' => ['required', 'integer', 'min:0', 'max:365']]);
        AppSettings::set('advance.percent', (string) $d['percent']);
        AppSettings::set('advance.hold_days', (string) $d['hold_days']);

        return back()->with('status', 'Advance rule saved.');
    }

    public function receive(Request $request, BookedInfo $booking)
    {
        $d = $request->validate(['amount' => ['required', 'numeric', 'gt:0', 'max:99999999'], 'method' => ['required', 'integer', 'exists:payment_method,payment_method_id'], 'reference' => ['nullable', 'string', 'max:60']]);

        try {
            $this->payments->receive($booking, (float) $d['amount'], PaymentMethod::findOrFail($d['method']), auth('admin')->id(), 'Advance payment', $d['reference'] ?? null);
            $confirmed = $this->advance->settle($booking, auth('admin')->id());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['advance' => $e->getMessage()]);
        }

        return back()->with('status', 'Advance recorded'.($confirmed ? ' and the booking is now confirmed.' : '.'));
    }
}
