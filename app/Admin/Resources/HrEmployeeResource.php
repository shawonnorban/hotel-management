<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrPayrollItem;
use App\Models\HrPosition;
use Illuminate\Database\Eloquent\Model;

class HrEmployeeResource extends Resource
{
    public static string $model = HrEmployee::class;

    public static string $slug = 'hr-employees';

    public static string $label = 'Employees';

    public static string $singular = 'Employee';

    public static string $icon = 'bi-people-fill';

    public static string $group = 'Human resources';

    public function with(): array
    {
        return ['department', 'position'];
    }

    public function searchable(): array
    {
        return ['code', 'first_name', 'last_name', 'email', 'phone'];
    }

    public function fields(): array
    {
        return [
            Field::text('first_name', 'First name')->required()->rules('max:80')->listed(),
            Field::text('last_name', 'Last name')->rules('max:80')->listed(),
            Field::text('code', 'Employee no.')->rules('max:30')->unique()->help('Leave blank to generate one.')->listed(),
            Field::select('department_id', 'Department', fn () => HrDepartment::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())->listed(),
            Field::select('position_id', 'Position', fn () => HrPosition::where('is_active', true)->orderBy('title')->pluck('title', 'id')->all())->listed(),
            Field::money('basic_salary', 'Basic salary (per month)')->required()->rules('min:0')->default(0)->listed(),
            Field::email('email', 'Email'),
            Field::text('phone', 'Phone')->rules('max:40'),
            Field::select('gender', 'Gender', ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other']),
            Field::date('birth_date', 'Date of birth')->rules('before:today'),
            Field::date('join_date', 'Joined on')->required(),
            Field::date('leave_date', 'Left on')->rules('after_or_equal:join_date')->help('Fill in when the employee leaves; payroll stops after this date.'),
            Field::text('national_id', 'National ID')->rules('max:60'),
            Field::text('bank_account', 'Bank account')->rules('max:80'),
            Field::text('address', 'Address')->rules('max:255')->col(12),
            Field::toggle('is_active', 'Currently employed')->listed(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        if (empty($data['code'])) {
            $data['code'] = $model?->code ?: 'EMP-'.str_pad((string) (((int) HrEmployee::max('id')) + 1), 4, '0', STR_PAD_LEFT);
        }
        $data['basic_salary'] = $data['basic_salary'] ?? 0;

        return $data;
    }

    public function rowActions(Model $row): array
    {
        return [['label' => 'Salary', 'icon' => 'bi-cash-stack', 'url' => route('admin.hr.employees.salary', $row), 'permission' => 'hr-payroll.view']];
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return HrPayrollItem::where('employee_id', $model->id)->exists() ? 'This employee has payroll history. Mark them as no longer employed instead.' : null;
    }
}
