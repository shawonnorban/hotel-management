<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HrRoster;
use App\Models\HrShift;
use Illuminate\Database\Eloquent\Model;

class HrShiftResource extends Resource
{
    public static string $model = HrShift::class;

    public static string $slug = 'hr-shifts';

    public static string $label = 'Shift list';

    public static string $singular = 'Shift';

    public static string $icon = 'bi-hourglass-split';

    public static string $group = 'Duty roster';

    public static ?string $orderBy = 'starts_at';

    public static string $orderDirection = 'asc';

    public function searchable(): array
    {
        return ['name'];
    }

    public function fields(): array
    {
        return [
            Field::text('name', 'Shift name')->required()->rules('max:80')->unique()->listed(),
            Field::time('starts_at', 'Starts')->required()->listed(),
            Field::time('ends_at', 'Ends')->required()->listed()->help('An end earlier than the start means the shift runs past midnight.'),
            Field::text('color', 'Colour')->rules('regex:/^#[0-9a-fA-F]{6}$/')->default('#0f766e')->help('Hex colour such as #0f766e, used on the roster.'),
            Field::toggle('is_active', 'Active')->listed(),
        ];
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return HrRoster::where('shift_id', $model->id)->exists() ? 'This shift is used in the roster. Mark it inactive instead.' : null;
    }
}
