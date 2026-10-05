<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HrHoliday;

class HrHolidayResource extends Resource
{
    public static string $model = HrHoliday::class;

    public static string $slug = 'hr-holidays';

    public static string $label = 'Public holidays';

    public static string $singular = 'Holiday';

    public static string $icon = 'bi-balloon';

    public static string $group = 'Human resources';

    public static ?string $orderBy = 'holiday_date';

    public function fields(): array
    {
        return [
            Field::text('name', 'Holiday')->required()->rules('max:120')->listed(),
            Field::date('holiday_date', 'Date')->required()->unique()->listed(),
        ];
    }
}
