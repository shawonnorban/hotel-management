<?php

namespace App\Http\Controllers\Admin\Hall;

use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\HallBooking;
use App\Models\HallPayment;
use App\Models\HallSeatPlan;
use App\Models\LedgerAccount;
use App\Services\HallService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class HallBookingController extends Controller
{
    public function __construct(private HallService $halls) {}

    public function index(Request $request)
    {
        $f = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:'.implode(',', array_keys(HallBooking::STATUSES))], 'hall' => ['nullable', 'integer'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);

        $bookings = HallBooking::with('hall')
            ->when($f['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('customer_name', 'like', $like)->orWhere('event_name', 'like', $like));
            })
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['hall'] ?? null, fn ($q, $v) => $q->where('hall_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('event_date', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('event_date', '<=', $v))
            ->orderByDesc('event_date')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.hall.index', ['bookings' => $bookings, 'f' => $f, 'halls' => Hall::orderBy('name')->pluck('name', 'id')]);
    }

    public function create()
    {
        return view('admin.hall.create', ['halls' => Hall::where('is_active', true)->with('seatPlans')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'hall_id' => ['required', 'integer', 'exists:halls,id'], 'seat_plan_id' => ['nullable', 'integer', 'exists:hall_seat_plans,id'],
            'customer_name' => ['required', 'string', 'max:150'], 'phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:150'],
            'booking_number' => ['nullable', 'string', 'max:30'], 'event_name' => ['required', 'string', 'max:150'], 'event_date' => ['required', 'date', 'after_or_equal:today'],
            'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i'], 'guests' => ['required', 'integer', 'min:1', 'max:100000'],
            'status' => ['required', 'in:tentative,confirmed'], 'discount' => ['nullable', 'numeric', 'min:0'], 'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        if (! empty($d['seat_plan_id']) && ! HallSeatPlan::where('id', $d['seat_plan_id'])->where('hall_id', $d['hall_id'])->exists()) {
            return back()->withInput()->withErrors(['seat_plan_id' => 'That seat plan belongs to a different hall.']);
        }

        try {
            $booking = $this->halls->create($d, auth('admin')->id());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['hall_id' => $e->getMessage()]);
        }

        return redirect()->route('admin.hall-bookings.show', $booking)->with('status', 'Booking '.$booking->number.' created.');
    }

    public function show(HallBooking $booking)
    {
        $booking->load('hall.facilities', 'seatPlan', 'payments');

        return view('admin.hall.show', [
            'booking' => $booking,
            'accounts' => LedgerAccount::where('is_cash', true)->where('is_group', false)->where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function status(Request $request, HallBooking $booking)
    {
        $d = $request->validate(['status' => ['required', 'in:tentative,confirmed,completed,cancelled']]);
        try {
            $this->halls->setStatus($booking, $d['status']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Booking marked '.strtolower(HallBooking::STATUSES[$d['status']]).'.');
    }

    public function pay(Request $request, HallBooking $booking)
    {
        $d = $request->validate(['amount' => ['required', 'numeric', 'gt:0'], 'account' => ['required', 'integer', 'exists:ledger_accounts,id'], 'paid_on' => ['required', 'date'], 'reference' => ['nullable', 'string', 'max:80']]);
        try {
            $this->halls->pay($booking, (float) $d['amount'], (int) $d['account'], $d['paid_on'], $d['reference'] ?? null, auth('admin')->id());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back()->with('status', 'Payment recorded.');
    }

    /** Which halls are free or taken on a day. */
    public function board(Request $request)
    {
        $date = Carbon::parse($request->validate(['date' => ['nullable', 'date']])['date'] ?? today())->startOfDay();
        $halls = Hall::with('type')->orderBy('name')->get();
        $bookings = HallBooking::where('status', '!=', 'cancelled')->whereDate('event_date', $date)->orderBy('starts_at')->get()->groupBy('hall_id');

        return view('admin.hall.board', compact('date', 'halls', 'bookings'));
    }

    public function report(Request $request)
    {
        $d = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $to = Carbon::parse($d['to'] ?? today()->endOfMonth())->startOfDay();
        $from = Carbon::parse($d['from'] ?? $to->copy()->startOfMonth())->startOfDay();

        $rows = HallBooking::with('hall')->where('status', '!=', 'cancelled')->whereBetween('event_date', [$from->toDateString(), $to->toDateString()])->orderBy('event_date')->get();
        $byHall = $rows->groupBy(fn ($b) => $b->hall->name)->map(fn ($g) => [
            'count' => $g->count(), 'hours' => round($g->sum(fn ($b) => HallService::hours($b->starts_at, $b->ends_at)), 2),
            'total' => round($g->sum('total'), 2), 'paid' => round($g->sum('paid'), 2),
        ])->sortKeys();
        $received = round((float) HallPayment::whereBetween('paid_on', [$from->toDateString(), $to->toDateString()])->sum('amount'), 2);

        return view('admin.hall.report', ['from' => $from, 'to' => $to, 'rows' => $rows, 'byHall' => $byHall, 'received' => $received]);
    }
}
