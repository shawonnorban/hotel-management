<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Promocode;
use App\Models\Roomdetails;
use Illuminate\Database\Eloquent\Model;

class PromoCodeResource extends Resource
{
    public static string $model = Promocode::class;

    public static string $slug = 'promo-codes';

    public static string $label = 'Promo codes';

    public static string $singular = 'Promo code';

    public static string $icon = 'bi-ticket-perforated';

    public static string $group = 'Guests & sales';

    public function fields(): array
    {
        return [
            Field::text('promocode', 'Code')->required()->rules('max:50|alpha_dash')->unique()->listed(),
            Field::select('roomid', 'Room type', fn () => [0 => 'All room types'] + Roomdetails::orderBy('roomtype')->pluck('roomtype', 'roomid')->all())->required()->listed(),
            Field::number('discount', 'Discount (%)')->required()->rules('min:1|max:100')->listed(),
            Field::date('startdate', 'Starts')->required()->listed(),
            Field::date('enddate', 'Ends')->required()->rules('after_or_equal:startdate')->listed(),
            Field::toggle('status', 'Active')->listed(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['promocode'] = strtoupper($data['promocode']);

        return $data;
    }
}
