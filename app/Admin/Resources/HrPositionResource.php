<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrPosition;
use Illuminate\Database\Eloquent\Model;

class HrPositionResource extends Resource
{
    public static string $model = HrPosition::class;

    public static string $slug = 'hr-positions';

    public static string $label = 'Positions';

    public static string $singular = 'Position';

    public static string $icon = 'bi-award';

    public static string $group = 'Human resources';

    public function fields(): array
    {
        return [
            Field::text('title', 'Position')->required()->rules('max:120')->listed(),
            Field::select('department_id', 'Department', fn () => HrDepartment::orderBy('name')->pluck('name', 'id')->all())->listed(),
            Field::toggle('is_active', 'Active')->listed(),
        ];
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return HrEmployee::where('position_id', $model->id)->exists() ? 'Employees hold this position.' : null;
    }
}
