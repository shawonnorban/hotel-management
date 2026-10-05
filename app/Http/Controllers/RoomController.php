<?php

namespace App\Http\Controllers;

use App\Models\Legacy\Roomdetails;
use App\Models\Legacy\RoomImage;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(private BookingService $booking) {}

    public function index(Request $request)
    {
        $search = $request->validate([
            'checkin' => ['nullable', 'date', 'after_or_equal:today'],
            'checkout' => ['nullable', 'date', 'after:checkin'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        $checkin = isset($search['checkin']) ? Carbon::parse($search['checkin']) : null;
        $checkout = isset($search['checkout']) ? Carbon::parse($search['checkout']) : null;
        $rooms = Roomdetails::query()
            ->where('roomactive', 1)
            ->whereIn('roomid', fn ($q) => $q->select('roomid')->from('tbl_roomnofloorassign'))
            ->orderBy('roomid')
            ->get();

        $available = [];
        if ($checkin && $checkout) {
            foreach ($rooms as $room) {
                $available[$room->roomid] = count($this->booking->availableRoomNumbers((int) $room->roomid, $checkin, $checkout));
            }
        }

        return view('rooms.index', [
            'rooms' => $rooms,
            'available' => $available,
            'search' => $search,
            'searched' => $checkin && $checkout,
            'images' => RoomImage::whereIn('room_id', $rooms->pluck('roomid'))->orderBy('room_img_id')->get()->unique('room_id')->pluck('room_imagename', 'room_id'),
        ]);
    }

    public function show(Request $request, int $room)
    {
        $room = Roomdetails::where('roomactive', 1)->findOrFail($room);
        $search = $request->validate([
            'checkin' => ['nullable', 'date', 'after_or_equal:today'],
            'checkout' => ['nullable', 'date', 'after:checkin'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:20'],
            'children' => ['nullable', 'integer', 'min:0', 'max:20'],
            'rooms' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $quote = null;
        $free = null;
        if (isset($search['checkin'], $search['checkout'])) {
            $checkin = Carbon::parse($search['checkin']);
            $checkout = Carbon::parse($search['checkout']);
            $free = count($this->booking->availableRoomNumbers((int) $room->roomid, $checkin, $checkout));
            $quote = $this->booking->quote($room, $checkin, $checkout, (int) ($search['rooms'] ?? 1));
        }

        return view('rooms.show', [
            'room' => $room,
            'images' => RoomImage::where('room_id', $room->roomid)->orderBy('room_img_id')->pluck('room_imagename'),
            'search' => $search,
            'quote' => $quote,
            'free' => $free,
        ]);
    }
}
