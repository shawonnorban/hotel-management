<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookedInfo;
use App\Models\Purchase;
use App\Models\InventoryItem;
use App\Models\Roomdetails;
use App\Models\StockMovement;
use App\Models\TblGuestpayments;
use App\Models\TblRoomnofloorassign;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function bookings(Request $request)
    {
        [$from, $to] = $this->period($request);
        $status = $request->validate(['status' => ['nullable', 'in:'.implode(',', array_keys(BookedInfo::STATUS_LABELS))]])['status'] ?? null;

        $rows = BookedInfo::with('customer')->whereDate('checkindate', '>=', $from)->whereDate('checkindate', '<=', $to)
            ->when($status !== null && $status !== '', fn ($q) => $q->where('bookingstatus', $status))
            ->orderBy('checkindate')->get();

        $live = $rows->where('bookingstatus', '!=', '1');
        $totals = ['count' => $rows->count(), 'nights' => $live->sum(fn ($b) => $b->nights * $b->total_room), 'total' => round($live->sum('total_price'), 2), 'paid' => round($live->sum('paid_amount'), 2)];

        if ($request->query('export') === 'csv') {
            return $this->csv('bookings-'.$from->format('Ymd').'-'.$to->format('Ymd'), ['Booking', 'Guest', 'Check-in', 'Check-out', 'Rooms', 'Source', 'Status', 'Total', 'Paid'],
                $rows->map(fn ($b) => [$b->booking_number, $b->full_guest_name ?: $b->customer?->full_name, $b->checkindate->format('Y-m-d'), $b->checkoutdate->format('Y-m-d'), $b->room_no, $b->source, $b->status_label, $b->total_price, $b->paid_amount])->all());
        }

        return view('admin.reports.bookings', compact('rows', 'totals', 'from', 'to', 'status'));
    }

    public function receipts(Request $request)
    {
        [$from, $to] = $this->period($request);

        $payments = TblGuestpayments::with('booking.customer')->whereDate('paydate', '>=', $from)->whereDate('paydate', '<=', $to)->orderBy('paydate')->get();
        $byMethod = $payments->groupBy('paymenttype')->map(fn ($g) => ['count' => $g->count(), 'amount' => round($g->sum('paymentamount'), 2)])->sortKeys();

        if ($request->query('export') === 'csv') {
            return $this->csv('receipts-'.$from->format('Ymd').'-'.$to->format('Ymd'), ['Receipt', 'Date', 'Booking', 'Guest', 'Method', 'Amount'],
                $payments->map(fn ($p) => [$p->invoice, $p->paydate->format('Y-m-d H:i'), $p->booking?->booking_number, $p->booking?->customer?->full_name, $p->paymenttype, $p->paymentamount])->all());
        }

        return view('admin.reports.receipts', ['payments' => $payments, 'byMethod' => $byMethod, 'total' => round($payments->sum('paymentamount'), 2), 'from' => $from, 'to' => $to]);
    }

    public function occupancy(Request $request)
    {
        [$from, $to] = $this->period($request);
        if ($from->diffInDays($to) > 92) {
            return back()->withErrors(['period' => 'Please choose a period of up to three months.']);
        }

        $roomTypes = Roomdetails::orderBy('roomtype')->get();
        $inventory = TblRoomnofloorassign::selectRaw('roomid, count(*) as n')->groupBy('roomid')->pluck('n', 'roomid');
        $bookings = BookedInfo::whereNotIn('bookingstatus', ['1'])->whereDate('checkindate', '<=', $to)->whereDate('checkoutdate', '>', $from)->get(['roomid', 'total_room', 'room_no', 'nuofpeople', 'children', 'roomrate', 'checkindate', 'checkoutdate']);

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $sold = [];
            foreach ($bookings as $b) {
                if ($b->checkindate->copy()->startOfDay()->lte($d) && $b->checkoutdate->copy()->startOfDay()->gt($d)) {
                    foreach ($b->roomLines() as $rl) {
                        $sold[$rl['room_id']] = ($sold[$rl['room_id']] ?? 0) + $rl['rooms'];
                    }
                }
            }
            $days[] = ['date' => $d->copy(), 'sold' => $sold, 'total' => array_sum($sold)];
        }
        $capacity = (int) $inventory->sum();
        $roomNights = collect($days)->sum('total');
        $summary = ['capacity' => $capacity, 'room_nights' => $roomNights, 'occupancy' => $capacity > 0 ? round($roomNights / ($capacity * count($days)) * 100, 1) : 0.0];

        if ($request->query('export') === 'csv') {
            return $this->csv('occupancy-'.$from->format('Ymd'), ['Date', 'Rooms sold', 'Rooms available', 'Occupancy %'],
                collect($days)->map(fn ($r) => [$r['date']->format('Y-m-d'), $r['total'], max(0, $capacity - $r['total']), $capacity ? round($r['total'] / $capacity * 100, 1) : 0])->all());
        }

        return view('admin.reports.occupancy', compact('days', 'summary', 'roomTypes', 'inventory', 'from', 'to'));
    }

    public function purchases(Request $request)
    {
        [$from, $to] = $this->period($request);

        $rows = Purchase::with('supplier', 'returns')->whereDate('purchase_date', '>=', $from)->whereDate('purchase_date', '<=', $to)->orderBy('purchase_date')->get();
        $bySupplier = $rows->groupBy(fn ($p) => $p->supplier->name)->map(fn ($g) => ['count' => $g->count(), 'total' => round($g->sum('total'), 2), 'due' => round($g->sum(fn ($p) => $p->due), 2)])->sortKeys();

        if ($request->query('export') === 'csv') {
            return $this->csv('purchases-'.$from->format('Ymd').'-'.$to->format('Ymd'), ['Purchase', 'Date', 'Supplier', 'Invoice', 'Total', 'Paid', 'Due'],
                $rows->map(fn ($p) => [$p->number, $p->purchase_date->format('Y-m-d'), $p->supplier->name, $p->reference, $p->total, $p->paid, $p->due])->all());
        }

        return view('admin.reports.purchases', ['rows' => $rows, 'bySupplier' => $bySupplier, 'from' => $from, 'to' => $to, 'total' => round($rows->sum('total'), 2), 'due' => round($rows->sum(fn ($p) => $p->due), 2)]);
    }

    public function stock(Request $request)
    {
        [$from, $to] = $this->period($request);

        $moves = StockMovement::whereDate('moved_at', '>=', $from)->whereDate('moved_at', '<=', $to)->get()->groupBy('item_id');
        $rows = InventoryItem::with('unit', 'category')->orderBy('name')->get()->map(function ($item) use ($moves) {
            $m = $moves->get($item->id, collect());
            $sum = fn (string $type) => round((float) $m->where('type', $type)->sum('quantity'), 3);

            return [
                'item' => $item, 'purchased' => $sum('purchase'), 'returned' => abs($sum('return')), 'issued' => abs($sum('issue')),
                'wasted' => abs($sum('waste')), 'adjusted' => $sum('adjustment'),
                'on_hand' => (float) $item->stock, 'value' => round((float) $item->stock * (float) $item->avg_cost, 2),
            ];
        });
        $totalValue = round($rows->sum('value'), 2);

        if ($request->query('export') === 'csv') {
            return $this->csv('stock-'.$from->format('Ymd').'-'.$to->format('Ymd'), ['Item', 'Unit', 'Purchased', 'Returned', 'Issued', 'Wasted', 'Adjusted', 'On hand', 'Value'],
                $rows->map(fn ($r) => [$r['item']->name, $r['item']->unit->short_code, $r['purchased'], $r['returned'], $r['issued'], $r['wasted'], $r['adjusted'], $r['on_hand'], $r['value']])->all());
        }

        return view('admin.reports.stock', ['rows' => $rows, 'totalValue' => $totalValue, 'from' => $from, 'to' => $to]);
    }

    /** @return array{0:Carbon,1:Carbon} */
    private function period(Request $request): array
    {
        $d = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $to = isset($d['to']) ? Carbon::parse($d['to']) : today()->endOfMonth();
        $from = isset($d['from']) ? Carbon::parse($d['from']) : $to->copy()->startOfMonth();

        return [$from->startOfDay(), $to->startOfDay()];
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
