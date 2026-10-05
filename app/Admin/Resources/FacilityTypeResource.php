<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Roomfacilitytype;

class FacilityTypeResource extends Resource
{
    public static string $model = Roomfacilitytype::class;

    public static string $slug = 'facility-types';

    public static string $label = 'Facility types';

    public static string $singular = 'Facility type';

    public static string $icon = 'bi-grid';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('facilitytypetitle', 'Facility type')->required()->rules('max:255')->unique()->listed(),
        ];
    }
}
