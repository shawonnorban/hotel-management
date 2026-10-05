<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Roomdetails;
use App\Models\TblRoomOffer;

class RoomOfferResource extends Resource
{
    public static string $model = TblRoomOffer::class;

    public static string $slug = 'offers';

    public static string $label = 'Room offers';

    public static string $singular = 'Offer';

    public static string $icon = 'bi-tag';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('offertitle', 'Title')->required()->rules('max:255')->listed(),
            Field::select('roomid', 'Room type', fn () => Roomdetails::orderBy('roomtype')->pluck('roomtype', 'roomid')->all())->required()->listed(),
            Field::number('offer', 'Discount (%)')->required()->rules('min:0|max:100')->listed(),
            Field::date('offer_date', 'Valid until')->required()->listed(),
            Field::textarea('offertext', 'Details'),
        ];
    }
}
