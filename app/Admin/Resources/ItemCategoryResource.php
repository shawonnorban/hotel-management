<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use Illuminate\Database\Eloquent\Model;

class ItemCategoryResource extends Resource
{
    public static string $model = ItemCategory::class;

    public static string $slug = 'inv-categories';

    public static string $label = 'Item categories';

    public static string $singular = 'Category';

    public static string $icon = 'bi-tags';

    public static string $group = 'Units & products';

    public function fields(): array
    {
        return [
            Field::text('name', 'Category')->required()->rules('max:120')->unique()->listed(),
            Field::toggle('is_active', 'Active')->listed(),
        ];
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return InventoryItem::where('category_id', $model->id)->exists() ? 'Items use this category.' : null;
    }
}
