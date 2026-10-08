<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

class SupplierResource extends Resource
{
    public static string $model = Supplier::class;

    public static string $slug = 'inv-suppliers';

    public static string $label = 'Suppliers';

    public static string $singular = 'Supplier';

    public static string $icon = 'bi-truck';

    public static string $group = 'Units & products';

    public function searchable(): array
    {
        return ['code', 'name', 'contact_person', 'email', 'phone'];
    }

    public function fields(): array
    {
        return [
            Field::text('name', 'Supplier')->required()->rules('max:150')->listed(),
            Field::text('code', 'Code')->rules('max:30')->unique()->help('Leave blank to generate one.')->listed(),
            Field::text('contact_person', 'Contact person')->rules('max:120')->listed(),
            Field::text('phone', 'Phone')->rules('max:40')->listed(),
            Field::email('email', 'Email'),
            Field::text('address', 'Address')->rules('max:255')->col(12),
            Field::toggle('is_active', 'Active')->listed(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        if (empty($data['code'])) {
            $data['code'] = $model?->code ?: 'SUP-'.str_pad((string) (((int) Supplier::max('id')) + 1), 4, '0', STR_PAD_LEFT);
        }

        return $data;
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return Purchase::where('supplier_id', $model->id)->exists() ? 'This supplier has purchases. Mark it inactive instead.' : null;
    }
}
