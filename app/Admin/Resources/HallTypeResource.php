<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HallType;
use Illuminate\Database\Eloquent\Model;

class HallTypeResource extends Resource
{
    public static string $model = HallType::class;

    public static string $slug = 'hall-types';

    public static string $label = 'Hall type';

    public static string $singular = 'Hall type';

    public static string $icon = 'bi-tags';

    public static string $group = 'Hall room';

    public function searchable(): array
    {
        return ['name'];
    }

    public function fields(): array
    {
        return [
            Field::text('name', 'Type (e.g. Banquet hall)')->required()->rules('max:100')->unique()->listed(),
            Field::toggle('is_active', 'Active')->listed(),
        ];
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return \App\Models\Hall::where('type_id', $model->id)->exists() ? 'Halls use this type.' : null;
    }
}
