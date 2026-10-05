<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HrAward;
use App\Models\HrEmployee;
use Illuminate\Database\Eloquent\Model;

class HrAwardResource extends Resource
{
    public static string $model = HrAward::class;

    public static string $slug = 'hr-awards';

    public static string $label = 'Awards & bonuses';

    public static string $singular = 'Award';

    public static string $icon = 'bi-trophy';

    public static string $group = 'Human resources';

    public static ?string $orderBy = 'awarded_on';

    public function with(): array
    {
        return ['employee'];
    }

    public function fields(): array
    {
        return [
            Field::select('employee_id', 'Employee', fn () => HrEmployee::where('is_active', true)->orderBy('first_name')->get()->mapWithKeys(fn ($e) => [$e->id => $e->full_name.' ('.$e->code.')'])->all())->required()->listed(),
            Field::text('title', 'Award')->required()->rules('max:150')->listed(),
            Field::money('cash_amount', 'Cash bonus')->rules('min:0')->default(0)->help('Paid with the salary of the month it is awarded in.')->listed(),
            Field::date('awarded_on', 'Date')->required()->listed(),
            Field::text('note', 'Note')->rules('max:255')->col(12),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['cash_amount'] = $data['cash_amount'] ?? 0;

        return $data;
    }
}
