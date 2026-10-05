<?php

namespace App\Http\Controllers;

use App\Models\RoomImage;
use App\Models\Roomdetails;
use App\Models\Subscriber;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function gallery()
    {
        $images = RoomImage::query()->orderByDesc('room_img_id')->limit(60)->get();
        $rooms = Roomdetails::whereIn('roomid', $images->pluck('room_id'))->pluck('roomtype', 'roomid');

        return view('pages.gallery', ['images' => $images, 'rooms' => $rooms]);
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:190'], 'website' => ['max:0']]);
        Subscriber::firstOrCreate(['email' => strtolower($data['email'])]);

        // Same answer whether or not the address was already subscribed.
        return back()->with('status', 'Thanks for subscribing!');
    }
}
