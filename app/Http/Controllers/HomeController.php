<?php

namespace App\Http\Controllers;

use App\Models\Roomdetails;
use App\Models\RoomImage;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function index()
    {
        $rooms = Roomdetails::query()
            ->where('roomactive', 1)
            ->whereIn('roomid', fn ($q) => $q->select('roomid')->from('tbl_roomnofloorassign'))
            ->orderBy('roomid')
            ->limit(6)
            ->get();

        return view('home', ['rooms' => $rooms, 'images' => $this->firstImages($rooms->pluck('roomid'))]);
    }

    /** @return Collection<int,string> room id => first image path */
    private function firstImages($roomIds)
    {
        return RoomImage::query()
            ->whereIn('room_id', $roomIds)
            ->ordered()
            ->get()
            ->unique('room_id')
            ->pluck('room_imagename', 'room_id');
    }
}
