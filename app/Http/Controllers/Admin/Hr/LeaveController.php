<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrEmployee;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveType;
use App\Services\Hr\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class LeaveController extends Controller
{
    public function __construct(private LeaveService $leave) {}

    public function index(Request $request)
    {
        $f = $request->validate(['status' => ['nullable', 'in:pending,approved,rejected'], 'employee' => ['nullable', 'integer']]);

        $requests = HrLeaveRequest::with('employee', 'type')
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['employee'] ?? null, fn ($q, $v) => $q->where('employee_id', $v))
            ->orderByRaw("field(status, 'pending', 'approved', 'rejected')")->orderByDesc('from_date')
            ->paginate(20)->withQueryString();

        return view('admin.hr.leave.index', [
            'requests' => $requests, 'f' => $f,
            'employees' => HrEmployee::where('is_active', true)->orderBy('first_name')->get(),
            'types' => HrLeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:hr_employees,id'],
            'leave_type_id' => ['required', 'integer', 'exists:hr_leave_types,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'half_day' => ['nullable', 'boolean'],
        ]);

        try {
            $this->leave->request(HrEmployee::findOrFail($d['employee_id']), HrLeaveType::findOrFail($d['leave_type_id']), Carbon::parse($d['from_date']), Carbon::parse($d['to_date']), $d['reason'] ?? null, $request->boolean('half_day'));
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['leave' => $e->getMessage()]);
        }

        return back()->with('status', 'Leave request recorded.');
    }

    public function decide(Request $request, HrLeaveRequest $leaveRequest)
    {
        $approve = $request->validate(['decision' => ['required', 'in:approve,reject']])['decision'] === 'approve';

        try {
            $this->leave->decide($leaveRequest, $approve, auth('admin')->id());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['leave' => $e->getMessage()]);
        }

        return back()->with('status', 'Request '.($approve ? 'approved' : 'rejected').'.');
    }
}
