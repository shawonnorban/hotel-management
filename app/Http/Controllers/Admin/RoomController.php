<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Legacy\Roomdetails;
use App\Models\Legacy\TblRoomnofloorassign;

class RoomController extends Controller
{
    public function index()
    {
        $numbers = TblRoomnofloorassign::orderBy('roomno')->get()->groupBy('roomid');

        return view('admin.rooms.index', ['rooms' => Roomdetails::orderBy('roomid')->get(), 'numbers' => $numbers]);
    }
}
