<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrEmployee;
use App\Models\HrEmployeeDocument;
use App\Models\HrEmployeeEducation;
use App\Models\HrEmployeeExperience;
use App\Support\Uploads;
use Illuminate\Http\Request;

/** Read-only employee profile plus the add/remove lists hanging off it (documents, education, experience). */
class EmployeeProfileController extends Controller
{
    private const KINDS = [
        'documents' => HrEmployeeDocument::class,
        'education' => HrEmployeeEducation::class,
        'experience' => HrEmployeeExperience::class,
    ];

    public function show(HrEmployee $employee)
    {
        return view('admin.hr.employees.profile', [
            'employee' => $employee->load('department', 'position', 'documents', 'education', 'experience', 'components'),
        ]);
    }

    public function store(Request $request, HrEmployee $employee, string $kind)
    {
        $model = self::KINDS[$kind] ?? abort(404);

        $data = match ($kind) {
            'documents' => $request->validate([
                'title' => ['required', 'string', 'max:150'], 'type' => ['nullable', 'string', 'max:50'],
                'expires_on' => ['nullable', 'date'], 'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:8192'],
            ]),
            'education' => $request->validate([
                'degree' => ['required', 'string', 'max:150'], 'institute' => ['nullable', 'string', 'max:191'], 'field' => ['nullable', 'string', 'max:150'],
                'result' => ['nullable', 'string', 'max:60'], 'passing_year' => ['nullable', 'integer', 'between:1950,2100'],
            ]),
            'experience' => $request->validate([
                'company' => ['required', 'string', 'max:191'], 'title' => ['nullable', 'string', 'max:150'], 'from_date' => ['nullable', 'date'],
                'to_date' => ['nullable', 'date', 'after_or_equal:from_date'], 'responsibilities' => ['nullable', 'string', 'max:2000'],
            ]),
        };

        if ($kind === 'documents') {
            $data['file'] = Uploads::store($request->file('file'), 'employees/documents');
        }
        $employee->hasMany($model, 'employee_id')->create($data);

        return redirect()->to(route('admin.hr.employees.profile', $employee).'#'.$kind)->with('status', 'Saved.');
    }

    public function destroy(HrEmployee $employee, string $kind, int $record)
    {
        $model = self::KINDS[$kind] ?? abort(404);
        $row = $employee->hasMany($model, 'employee_id')->findOrFail($record);
        if ($kind === 'documents') {
            Uploads::delete($row->file);
        }
        $row->delete();

        return redirect()->to(route('admin.hr.employees.profile', $employee).'#'.$kind)->with('status', 'Removed.');
    }
}
