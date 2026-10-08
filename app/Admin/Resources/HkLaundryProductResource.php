<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HkLaundryLine;
use App\Models\HkLaundryProduct;
use Illuminate\Database\Eloquent\Model;

class HkLaundryProductResource extends Resource
{
    public static string $model = HkLaundryProduct::class;

    public static string $slug = 'hk-laundry-products';

    public static string $label = 'Laundry product list';

    public static string $singular = 'Laundry product';

    public static string $icon = 'bi-basket';

    public static string $group = 'House keeping';

    public static ?string $orderBy = 'name';

    public static string $orderDirection = 'asc';

    public function searchable(): array
    {
        return ['name'];
    }

    public function fields(): array
    {
        return [
            Field::text('name', 'Item (e.g. Shirt)')->required()->rules('max:120')->unique()->listed(),
            Field::toggle('is_active', 'Active')->listed(),
        ];
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return HkLaundryLine::where('product_id', $model->id)->exists() ? 'This item is on laundry orders. Mark it inactive instead.' : null;
    }
}
