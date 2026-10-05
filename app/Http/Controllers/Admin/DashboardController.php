<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookedInfo;
use App\Models\Customerinfo;
use App\Models\Roomdetails;
use App\Models\TblRoomnofloorassign;
use App\Support\Money;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();
        $days = collect(range(13, 0))->map(fn ($i) => $today->copy()->subDays($i));

        $perDay = BookedInfo::query()
            ->where('date_time', '>=', $days->first()->startOfDay())
            ->get(['date_time', 'total_price', 'bookingstatus'])
            ->groupBy(fn ($b) => Carbon::parse($b->date_time)->toDateString());

        $statusCounts = BookedInfo::query()->selectRaw('bookingstatus, count(*) as n')->groupBy('bookingstatus')->pluck('n', 'bookingstatus');
        $roomsTotal = TblRoomnofloorassign::count();
        $inHouse = BookedInfo::where('bookingstatus', '4')->sum('total_room');

        return view('admin.dashboard', [
            'stats' => [
                ['label' => 'Pending bookings', 'value' => BookedInfo::where('bookingstatus', '0')->count(), 'icon' => 'bi-hourglass-split', 'class' => 'accent'],
                ['label' => 'Arriving today', 'value' => BookedInfo::whereDate('checkindate', $today)->whereIn('bookingstatus', ['0', '2'])->count(), 'icon' => 'bi-box-arrow-in-right', 'class' => ''],
                ['label' => 'Departing today', 'value' => BookedInfo::whereDate('checkoutdate', $today)->where('bookingstatus', '4')->count(), 'icon' => 'bi-box-arrow-right', 'class' => 'info'],
                ['label' => 'Occupancy', 'value' => $roomsTotal ? round($inHouse / $roomsTotal * 100).'%' : '—', 'icon' => 'bi-door-open', 'class' => ''],
                ['label' => 'Room types', 'value' => Roomdetails::count(), 'icon' => 'bi-grid', 'class' => 'info'],
                ['label' => 'Guests', 'value' => Customerinfo::count(), 'icon' => 'bi-people', 'class' => ''],
                ['label' => 'Collected', 'value' => Money::format(BookedInfo::where('bookingstatus', '!=', '1')->sum('paid_amount')), 'icon' => 'bi-cash-stack', 'class' => 'accent'],
                ['label' => 'Outstanding', 'value' => Money::format(BookedInfo::whereNotIn('bookingstatus', ['1', '5'])->selectRaw('coalesce(sum(total_price - paid_amount),0) as due')->value('due')), 'icon' => 'bi-exclamation-circle', 'class' => 'danger'],
            ],
            'chart' => [
                'labels' => $days->map->format('d M')->all(),
                'bookings' => $days->map(fn ($d) => ($perDay[$d->toDateString()] ?? collect())->where('bookingstatus', '!=', '1')->count())->all(),
                'revenue' => $days->map(fn ($d) => round((float) ($perDay[$d->toDateString()] ?? collect())->where('bookingstatus', '!=', '1')->sum('total_price'), 2))->all(),
            ],
            'statusChart' => collect(BookedInfo::STATUS_LABELS)->map(fn ($label, $code) => ['label' => $label, 'count' => (int) ($statusCounts[(string) $code] ?? 0)])->values(),
            'latest' => BookedInfo::with('customer')->orderByDesc('bookedid')->limit(8)->get(),
        ]);
    }
}
