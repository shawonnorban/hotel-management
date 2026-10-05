<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Roomdetails;
use App\Models\TblFloor;
use App\Models\TblRoomnofloorassign;

class RoomNumberResource extends Resource
{
    public static string $model = TblRoomnofloorassign::class;

    public static string $slug = 'rooms';

    public static string $label = 'Rooms';

    public static string $singular = 'Room';

    public static string $icon = 'bi-key';

    public static string $group = 'Hotel setup';

    public static ?string $orderBy = 'roomno';

    public static string $orderDirection = 'asc';

    public function fields(): array
    {
        return [
            Field::number('roomno', 'Room number')->required()->rules('min:1')->unique()->listed(),
            Field::select('roomid', 'Room type', fn () => Roomdetails::orderBy('roomtype')->pluck('roomtype', 'roomid')->all())->required()->listed(),
            Field::select('floorid', 'Floor', fn () => TblFloor::orderBy('floorname')->pluck('floorname', 'floorid')->all())->required()->listed(),
            Field::toggle('status', 'Available for booking')->listed(),
        ];
    }
}
