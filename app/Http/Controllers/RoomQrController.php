<?php

namespace App\Http\Controllers;

use App\Models\Roomdetails;
use App\Models\TblRoomnofloorassign;
use App\Services\HousekeepingService;

/** Public page a guest reaches by scanning the QR code in their room. */
class RoomQrController extends Controller
{
    public function show(int $room)
    {
        $room = TblRoomnofloorassign::findOrFail($room);

        return view('rooms.qr', ['room' => $room, 'type' => Roomdetails::find($room->roomid)?->roomtype]);
    }

    public function requestCleaning(int $room, HousekeepingService $housekeeping)
    {
        $room = TblRoomnofloorassign::findOrFail($room);
        $made = $housekeeping->assign([$room->roomassignid], null, today()->toDateString(), 'Requested by the guest via QR code.', 'qr');

        return back()->with('status', $made ? 'Thank you — housekeeping has been notified.' : 'Housekeeping already knows about this room today.');
    }
}
