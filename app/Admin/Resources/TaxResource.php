<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\TblTaxmgt;

class TaxResource extends Resource
{
    public static string $model = TblTaxmgt::class;

    public static string $slug = 'taxes';

    public static string $label = 'Taxes';

    public static string $singular = 'Tax';

    public static string $icon = 'bi-percent';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('taxname', 'Tax name')->required()->rules('max:100')->listed(),
            Field::decimal('rate', 'Rate (%)')->required()->rules('min:0|max:100')->listed(),
            Field::text('reg_no', 'Registration no.')->rules('max:100')->listed(),
            Field::toggle('isactive', 'Applied to bookings')->listed(),
        ];
    }
}
