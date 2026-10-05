<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

class InventoryItemResource extends Resource
{
    public static string $model = InventoryItem::class;

    public static string $slug = 'inv-items';

    public static string $label = 'Stock items';

    public static string $singular = 'Item';

    public static string $icon = 'bi-box-seam';

    public static string $group = 'Purchasing';

    public function with(): array
    {
        return ['unit', 'category'];
    }

    public function searchable(): array
    {
        return ['sku', 'name'];
    }

    public function fields(): array
    {
        return [
            Field::text('name', 'Item')->required()->rules('max:160')->listed(),
            Field::text('sku', 'SKU')->rules('max:40')->unique()->help('Leave blank to generate one.')->listed(),
            Field::select('category_id', 'Category', fn () => ItemCategory::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())->listed(),
            Field::select('unit_id', 'Unit', fn () => Unit::where('is_active', true)->orderBy('name')->get()->mapWithKeys(fn ($u) => [$u->id => $u->name.' ('.$u->short_code.')'])->all())->required()->listed(),
            Field::decimal('reorder_level', 'Reorder level')->rules('min:0')->default(0)->help('You are warned when stock falls to this level.'),
            Field::toggle('is_active', 'Active')->listed(),
            Field::decimal('stock', 'On hand')->listOnly(),
            Field::money('avg_cost', 'Avg. cost')->listOnly(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        if (empty($data['sku'])) {
            $data['sku'] = $model?->sku ?: 'ITM-'.str_pad((string) (((int) InventoryItem::max('id')) + 1), 4, '0', STR_PAD_LEFT);
        }
        $data['reorder_level'] = $data['reorder_level'] ?? 0;

        return $data;
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return $model->movements()->exists() ? 'This item has stock history. Mark it inactive instead.' : null;
    }
}
