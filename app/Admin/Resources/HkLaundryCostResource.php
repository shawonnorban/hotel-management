<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HkLaundryCost;
use App\Models\HkLaundryProduct;

class HkLaundryCostResource extends Resource
{
    public static string $model = HkLaundryCost::class;

    public static string $slug = 'hk-laundry-costs';

    public static string $label = 'Laundry item cost';

    public static string $singular = 'Item cost';

    public static string $icon = 'bi-tag';

    public static string $group = 'House keeping';

    public function with(): array
    {
        return ['product'];
    }

    public function fields(): array
    {
        return [
            Field::select('product_id', 'Item', fn () => HkLaundryProduct::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())->required()->listed(),
            Field::select('service', 'Service', HkLaundryCost::SERVICES)->required()->listed(),
            Field::money('cost', 'Cost per item')->required()->rules('min:0')->listed(),
        ];
    }

    public function grouped(): ?array
    {
        $products = HkLaundryProduct::pluck('name', 'id');

        return [
            'key' => 'product_id', 'heading' => 'Item', 'parent' => fn ($id) => $products[$id] ?? 'Item #'.$id,
            'item' => fn ($row) => ['text' => HkLaundryCost::SERVICES[$row->service] ?? $row->service, 'sub' => \App\Support\Money::format($row->cost), 'url' => route('admin.resource.edit', ['hk-laundry-costs', $row->getKey()])],
        ];
    }

    public function beforeSave(array $data, ?\Illuminate\Database\Eloquent\Model $model): array
    {
        $dupe = HkLaundryCost::where('product_id', $data['product_id'])->where('service', $data['service'])->when($model, fn ($q) => $q->where('id', '!=', $model->id))->exists();
        if ($dupe) {
            throw \Illuminate\Validation\ValidationException::withMessages(['service' => 'This item already has a cost for that service.']);
        }

        return $data;
    }
}
