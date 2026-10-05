<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Roomsizemesurement;

class RoomSizeUnitResource extends Resource
{
    public static string $model = Roomsizemesurement::class;

    public static string $slug = 'size-units';

    public static string $label = 'Size units';

    public static string $singular = 'Size unit';

    public static string $icon = 'bi-rulers';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('roommesurementitle', 'Unit (e.g. sqft, m²)')->required()->rules('max:255')->unique()->listed(),
        ];
    }
}
