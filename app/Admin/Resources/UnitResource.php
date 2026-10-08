<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

class UnitResource extends Resource
{
    public static string $model = Unit::class;

    public static string $slug = 'inv-units';

    public static string $label = 'Units of measure';

    public static string $singular = 'Unit';

    public static string $icon = 'bi-rulers';

    public static string $group = 'Units & products';

    public function fields(): array
    {
        return [
            Field::text('name', 'Name (e.g. Kilogram)')->required()->rules('max:80')->unique()->listed(),
            Field::text('short_code', 'Short code (e.g. kg)')->required()->rules('max:12')->listed(),
            Field::toggle('is_active', 'Active')->listed(),
        ];
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return InventoryItem::where('unit_id', $model->id)->exists() ? 'Items use this unit.' : null;
    }
}
