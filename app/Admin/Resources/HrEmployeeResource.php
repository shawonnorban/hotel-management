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
        $yn = fn (array $v) => array_combine($v, $v);

        return [
            Field::heading('Personal information'),
            Field::image('photo', 'Employee photo'),
            Field::text('first_name', 'First name')->required()->rules('max:80')->listed(),
            Field::text('last_name', 'Last name')->rules('max:80')->listed(),
            Field::select('gender', 'Gender', ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other']),
            Field::date('birth_date', 'Date of birth')->rules('before:today'),
            Field::text('father_name', "Father's name")->rules('max:150'),
            Field::text('mother_name', "Mother's name")->rules('max:150'),
            Field::select('marital_status', 'Marital status', $yn(['Single', 'Married', 'Divorced', 'Widowed'])),
            Field::text('spouse_name', 'Spouse name')->rules('max:150'),
            Field::select('blood_group', 'Blood group', $yn(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])),
            Field::text('religion', 'Religion')->rules('max:50'),
            Field::text('nationality', 'Nationality')->rules('max:80'),

            Field::heading('Contact'),
            Field::email('email', 'Email'),
            Field::text('phone', 'Phone')->rules('max:40'),
            Field::text('alt_phone', 'Alternative phone')->rules('max:40'),
            Field::text('present_address', 'Present address')->rules('max:255')->col(12),
            Field::text('address', 'Permanent address')->rules('max:255')->col(12),
            Field::text('emergency_name', 'Emergency contact')->rules('max:120'),
            Field::text('emergency_relation', 'Relation')->rules('max:60'),
            Field::text('emergency_phone', 'Emergency phone')->rules('max:40'),

            Field::heading('Identity'),
            Field::text('national_id', 'National ID (NID)')->rules('max:60'),
            Field::text('passport_no', 'Passport no.')->rules('max:60'),
            Field::text('tin_no', 'TIN')->rules('max:60'),
            Field::image('id_front', 'NID photo — front'),
            Field::image('id_back', 'NID photo — back'),

            Field::heading('Employment'),
            Field::text('code', 'Employee no.')->rules('max:30')->unique()->help('Leave blank to generate one.')->listed(),
            Field::select('department_id', 'Department', fn () => HrDepartment::where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())->listed(),
            Field::select('position_id', 'Position', fn () => HrPosition::where('is_active', true)->orderBy('title')->pluck('title', 'id')->all())->listed(),
            Field::select('employment_type', 'Employment type', $yn(['Full time', 'Part time', 'Contract', 'Intern', 'Casual'])),
            Field::text('work_location', 'Work location')->rules('max:120'),
            Field::date('join_date', 'Joined on')->required(),
            Field::date('probation_end', 'Probation ends'),
            Field::date('leave_date', 'Left on')->rules('after_or_equal:join_date')->help('Fill in when the employee leaves; payroll stops after this date.'),
            Field::toggle('is_active', 'Currently employed')->listed(),

            Field::heading('Pay & bank'),
            Field::money('basic_salary', 'Basic salary (per month)')->required()->rules('min:0')->default(0)->listed(),
            Field::text('bank_name', 'Bank')->rules('max:120'),
            Field::text('bank_branch', 'Branch')->rules('max:120'),
            Field::text('bank_account', 'Account no.')->rules('max:80'),
            Field::textarea('notes', 'Notes')->col(12),
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
        return [['label' => 'Profile', 'icon' => 'bi-person-vcard', 'url' => route('admin.hr.employees.profile', $row), 'permission' => 'hr-employees.view'], ['label' => 'Salary', 'icon' => 'bi-cash-stack', 'url' => route('admin.hr.employees.salary', $row), 'permission' => 'hr-payroll.view']];
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return HrPayrollItem::where('employee_id', $model->id)->exists() ? 'This employee has payroll history. Mark them as no longer employed instead.' : null;
    }
}
