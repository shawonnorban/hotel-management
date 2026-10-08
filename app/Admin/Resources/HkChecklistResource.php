<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HkChecklistItem;

class HkChecklistResource extends Resource
{
    public static string $model = HkChecklistItem::class;

    public static string $slug = 'hk-checklist';

    public static string $label = 'Checklist';

    public static string $singular = 'Checklist item';

    public static string $icon = 'bi-ui-checks';

    public static string $group = 'House keeping';

    public static ?string $orderBy = 'sort';

    public static string $orderDirection = 'asc';

    public function searchable(): array
    {
        return ['name'];
    }

    public function fields(): array
    {
        return [
            Field::text('name', 'Task (e.g. Change bed linen)')->required()->rules('max:150')->listed(),
            Field::number('sort', 'Order')->default(0)->rules('integer|min:0|max:9999')->listed()->help('Items are copied to every new cleaning task, in this order.'),
            Field::toggle('is_active', 'Active')->listed(),
        ];
    }
}
