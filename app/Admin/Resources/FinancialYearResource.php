<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\FinancialYear;

class FinancialYearResource extends Resource
{
    public static string $model = FinancialYear::class;

    public static string $slug = 'financial-years';

    public static string $label = 'Financial years';

    public static string $singular = 'Financial year';

    public static string $icon = 'bi-calendar-range';

    public static string $group = 'Accounting';

    public static ?string $orderBy = 'start_date';

    public function fields(): array
    {
        return [
            Field::text('title', 'Title')->required()->rules('max:60')->listed(),
            Field::date('start_date', 'Starts')->required()->listed(),
            Field::date('end_date', 'Ends')->required()->rules('after:start_date')->listed(),
            Field::toggle('is_closed', 'Closed (no entries can be posted or changed inside this period)')->default(0)->listed(),
        ];
    }
}
