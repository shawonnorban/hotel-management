<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Roomfacilitydetails;
use App\Models\Roomfacilitytype;

class FacilityResource extends Resource
{
    public static string $model = Roomfacilitydetails::class;

    public static string $slug = 'facilities';

    public static string $label = 'Facilities';

    public static string $singular = 'Facility';

    public static string $icon = 'bi-wifi';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('facilitytitle', 'Facility')->required()->rules('max:255')->listed(),
            Field::select('facilitytypeid', 'Type', fn () => Roomfacilitytype::orderBy('facilitytypetitle')->pluck('facilitytypetitle', 'facilitytypeid')->all())->required()->listed(),
            Field::image('image', 'Icon')->listed(),
        ];
    }
}
