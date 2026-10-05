<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrCandidate;
use App\Models\HrEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function salary(HrEmployee $employee)
    {
        return view('admin.hr.employees.salary', ['employee' => $employee->load('components', 'position', 'department')]);
    }

    public function updateSalary(Request $request, HrEmployee $employee)
    {
        $d = $request->validate([
            'basic_salary' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'components' => ['nullable', 'array', 'max:30'],
            'components.*.name' => ['nullable', 'string', 'max:80'],
            'components.*.kind' => ['required_with:components.*.name', 'in:allowance,deduction'],
            'components.*.amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'components.*.is_percent' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($employee, $d) {
            $employee->update(['basic_salary' => $d['basic_salary']]);
            $employee->components()->delete();
            foreach ($d['components'] ?? [] as $row) {
                if (! empty($row['name']) && isset($row['amount']) && $row['amount'] !== '') {
                    $employee->components()->create(['name' => $row['name'], 'kind' => $row['kind'], 'amount' => $row['amount'], 'is_percent' => ! empty($row['is_percent'])]);
                }
            }
        });

        return back()->with('status', 'Salary set-up saved.');
    }

    /** Turn a selected candidate into an employee. */
    public function hire(HrCandidate $candidate)
    {
        if ($candidate->employee_id) {
            return redirect()->route('admin.resource.edit', ['hr-employees', $candidate->employee_id]);
        }

        [$first, $last] = array_pad(explode(' ', trim($candidate->name), 2), 2, '');
        $employee = HrEmployee::create([
            'code' => 'EMP-'.str_pad((string) (((int) HrEmployee::max('id')) + 1), 4, '0', STR_PAD_LEFT),
            'first_name' => $first, 'last_name' => $last, 'email' => $candidate->email, 'phone' => $candidate->phone,
            'join_date' => today(), 'position_id' => $candidate->position_id, 'department_id' => $candidate->position?->department_id,
            'basic_salary' => 0, 'is_active' => true,
        ]);
        $candidate->update(['stage' => 'hired', 'employee_id' => $employee->id]);

        return redirect()->route('admin.hr.employees.salary', $employee)->with('status', $employee->full_name.' was added as an employee. Set up the salary below.');
    }
}
