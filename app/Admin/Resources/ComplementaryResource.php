<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Roomdetails;
use App\Models\TblComplementary;

/** Extra services (breakfast, airport pick-up…) that can be added to a stay. */
class ComplementaryResource extends Resource
{
    public static string $model = TblComplementary::class;

    public static string $slug = 'services';

    public static string $label = 'Extra services';

    public static string $singular = 'Service';

    public static string $icon = 'bi-cup-hot';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('complementaryname', 'Service')->required()->rules('max:255')->listed(),
            Field::select('roomtype', 'Room type', fn () => Roomdetails::orderBy('roomtype')->pluck('roomtype', 'roomtype')->all())->required()->listed(),
            Field::money('rate', 'Price')->required()->rules('min:0')->listed(),
            Field::toggle('status', 'Active')->listed(),
        ];
    }
}
