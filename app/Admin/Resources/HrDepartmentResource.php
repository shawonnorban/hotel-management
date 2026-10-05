<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HrDepartment;
use Illuminate\Database\Eloquent\Model;

class HrDepartmentResource extends Resource
{
    public static string $model = HrDepartment::class;

    public static string $slug = 'hr-departments';

    public static string $label = 'Departments';

    public static string $singular = 'Department';

    public static string $icon = 'bi-diagram-2';

    public static string $group = 'Human resources';

    public function fields(): array
    {
        return [
            Field::text('name', 'Department')->required()->rules('max:120')->unique()->listed(),
            Field::toggle('is_active', 'Active')->listed(),
        ];
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return $model->employees()->exists() ? 'Employees belong to this department.' : null;
    }
}
