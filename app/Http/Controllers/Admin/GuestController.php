<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookedInfo;
use App\Models\Customerinfo;
use App\Models\TblOtherguest;
use App\Services\WhatsAppService;
use InvalidArgumentException;

/** Read-only guest profile: details, ID photos, stay history and totals. */
class GuestController extends Controller
{
    public function show(Customerinfo $customer, WhatsAppService $whatsapp)
    {
        $bookings = BookedInfo::where('cutomerid', $customer->customerid)->orderByDesc('checkindate')->get();
        $live = $bookings->where('bookingstatus', '!=', '1');
        $stats = [
            'stays' => $bookings->where('bookingstatus', '5')->count(),
            'bookings' => $bookings->count(),
            'nights' => $live->whereIn('bookingstatus', ['4', '5'])->sum(fn ($b) => $b->nights * max(1, (int) $b->total_room)),
            'spent' => round((float) $live->whereIn('bookingstatus', ['4', '5'])->sum('total_price'), 2),
            'due' => round((float) $live->sum(fn ($b) => max(0, $b->balance)), 2),
        ];
        $companions = TblOtherguest::whereIn('booking_id', $bookings->pluck('bookedid'))->orderByDesc('otherguest_id')->get();

        try {
            $chat = $customer->cust_phone ? $whatsapp->link($customer->cust_phone) : null;
        } catch (InvalidArgumentException) {
            $chat = null;
        }

        return view('admin.guests.show', compact('customer', 'bookings', 'stats', 'companions', 'chat'));
    }
}
