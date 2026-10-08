<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HallSeatPlan;
use Illuminate\Database\Eloquent\Model;

class HallSeatPlanResource extends Resource
{
    public static string $model = HallSeatPlan::class;

    public static string $slug = 'hall-seat-plans';

    public static string $label = 'Seat plan';

    public static string $singular = 'Seat plan';

    public static string $icon = 'bi-grid-3x3-gap';

    public static string $group = 'Hall room';

    public function with(): array
    {
        return ['hall'];
    }

    public function fields(): array
    {
        return [
            Field::select('hall_id', 'Hall', fn () => \App\Models\Hall::orderBy('name')->pluck('name', 'id')->all())->required()->listed(),
            Field::text('name', 'Plan name')->required()->rules('max:100')->listed(),
            Field::select('layout', 'Layout', \App\Models\HallSeatPlan::LAYOUTS)->required()->listed(),
            Field::number('seats', 'Seats')->required()->rules('integer|min:1|max:100000')->listed(),
            Field::number('tables', 'Tables')->default(0)->rules('integer|min:0|max:10000'),
            Field::textarea('notes', 'Notes')->col(12),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['tables'] = $data['tables'] ?? 0;
        $hall = \App\Models\Hall::find($data['hall_id']);
        if ($hall && (int) $data['seats'] > $hall->capacity) {
            throw \Illuminate\Validation\ValidationException::withMessages(['seats' => "{$hall->name} holds at most {$hall->capacity} people."]);
        }

        return $data;
    }
}
