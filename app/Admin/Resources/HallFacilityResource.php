<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HallFacility;
use Illuminate\Database\Eloquent\Model;

class HallFacilityResource extends Resource
{
    public static string $model = HallFacility::class;

    public static string $slug = 'hall-facilities';

    public static string $label = 'Hall facility';

    public static string $singular = 'Hall facility';

    public static string $icon = 'bi-projector';

    public static string $group = 'Hall room';

    public function searchable(): array
    {
        return ['name'];
    }

    public function fields(): array
    {
        return [
            Field::text('name', 'Facility (e.g. Projector)')->required()->rules('max:100')->unique()->listed(),
            Field::toggle('is_active', 'Active')->listed(),
        ];
    }
}
