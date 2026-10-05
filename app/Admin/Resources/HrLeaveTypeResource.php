<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveType;
use Illuminate\Database\Eloquent\Model;

class HrLeaveTypeResource extends Resource
{
    public static string $model = HrLeaveType::class;

    public static string $slug = 'hr-leave-types';

    public static string $label = 'Leave types';

    public static string $singular = 'Leave type';

    public static string $icon = 'bi-calendar2-week';

    public static string $group = 'Human resources';

    public function fields(): array
    {
        return [
            Field::text('name', 'Leave type')->required()->rules('max:80')->unique()->listed(),
            Field::number('days_per_year', 'Days per year')->required()->rules('min:0|max:366')->default(0)->help('0 means unlimited.')->listed(),
            Field::toggle('is_paid', 'Paid leave')->listed()->help('Unpaid leave is deducted from the month\'s salary.'),
            Field::toggle('is_active', 'Active')->listed(),
        ];
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return HrLeaveRequest::where('leave_type_id', $model->id)->exists() ? 'Leave requests use this type.' : null;
    }
}
