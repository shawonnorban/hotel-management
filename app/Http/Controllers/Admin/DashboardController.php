<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Legacy\BookedInfo;
use App\Models\Legacy\Customerinfo;
use App\Models\Legacy\Roomdetails;
use App\Models\Legacy\TblRoomnofloorassign;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        return view('admin.dashboard', [
            'stats' => [
                'Pending bookings' => BookedInfo::where('bookingstatus', '0')->count(),
                'Arriving today' => BookedInfo::whereDate('checkindate', $today)->whereIn('bookingstatus', ['0', '2'])->count(),
                'In house' => BookedInfo::where('bookingstatus', '4')->count(),
                'Room types' => Roomdetails::count(),
                'Rooms' => TblRoomnofloorassign::count(),
                'Guests' => Customerinfo::count(),
                'Total paid' => number_format((float) BookedInfo::whereNotIn('bookingstatus', ['1'])->sum('paid_amount'), 2),
            ],
            'latest' => BookedInfo::with('customer')->orderByDesc('bookedid')->limit(8)->get(),
        ]);
    }
}
