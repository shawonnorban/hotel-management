<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrAttendance;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrRoster;
use App\Models\HrShift;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RosterController extends Controller
{
    private const MAX_DAYS = 62;

    public function assignForm()
    {
        return view('admin.hr.roster.assign', [
            'employees' => HrEmployee::with('department')->where('is_active', true)->orderBy('first_name')->get(),
            'shifts' => HrShift::where('is_active', true)->orderBy('starts_at')->get(),
            'departments' => HrDepartment::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function assign(Request $request)
    {
        $d = $request->validate([
            'employees' => ['required', 'array', 'min:1'], 'employees.*' => ['integer', 'exists:hr_employees,id'],
            'from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from'],
            'shift' => ['required', 'string'], 'weekdays' => ['nullable', 'array'], 'weekdays.*' => ['integer', 'between:0,6'],
            'note' => ['nullable', 'string', 'max:150'],
        ]);
        $from = Carbon::parse($d['from'])->startOfDay();
        $to = Carbon::parse($d['to'])->startOfDay();
        if ($from->diffInDays($to) >= self::MAX_DAYS) {
            return back()->withInput()->withErrors(['to' => 'Assign at most '.self::MAX_DAYS.' days at a time.']);
        }

        $clear = $d['shift'] === 'clear';
        $off = $d['shift'] === 'off';
        $shiftId = null;
        if (! $clear && ! $off) {
            $shiftId = HrShift::where('is_active', true)->findOrFail($d['shift'])->id;
        }
        $weekdays = isset($d['weekdays']) ? array_map('intval', $d['weekdays']) : null;

        $count = 0;
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            if ($weekdays !== null && $weekdays !== [] && ! in_array($day->dayOfWeek, $weekdays, true)) {
                continue;
            }
            foreach ($d['employees'] as $employeeId) {
                if ($clear) {
                    HrRoster::where('employee_id', $employeeId)->whereDate('work_date', $day)->delete();
                } else {
                    HrRoster::updateOrCreate(['employee_id' => $employeeId, 'work_date' => $day->toDateString()], ['shift_id' => $shiftId, 'note' => $d['note'] ?? null, 'created_by' => auth('admin')->id()]);
                }
                $count++;
            }
        }

        return redirect()->route('admin.hr.roster.index', ['from' => $from->toDateString(), 'to' => $to->toDateString()])
            ->with('status', $clear ? "Cleared $count roster entr".($count === 1 ? 'y' : 'ies').'.' : "Assigned $count roster entr".($count === 1 ? 'y' : 'ies').'.');
    }

    public function index(Request $request)
    {
        $v = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'department' => ['nullable', 'integer']]);
        $from = Carbon::parse($v['from'] ?? today()->startOfWeek())->startOfDay();
        $to = Carbon::parse($v['to'] ?? $from->copy()->addDays(6))->startOfDay();
        if ($from->diffInDays($to) >= self::MAX_DAYS) {
            $to = $from->copy()->addDays(self::MAX_DAYS - 1);
        }

        $employees = HrEmployee::with('department')->where('is_active', true)
            ->when($v['department'] ?? null, fn ($q, $dep) => $q->where('department_id', $dep))->orderBy('first_name')->get();
        $cells = HrRoster::with('shift')->whereDate('work_date', '>=', $from)->whereDate('work_date', '<=', $to)->whereIn('employee_id', $employees->pluck('id'))->get()
            ->groupBy('employee_id')->map(fn ($g) => $g->keyBy(fn ($r) => $r->work_date->toDateString()));
        $days = collect(range(0, $from->diffInDays($to)))->map(fn ($i) => $from->copy()->addDays($i));

        return view('admin.hr.roster.index', [
            'employees' => $employees, 'cells' => $cells, 'days' => $days, 'from' => $from, 'to' => $to,
            'departments' => HrDepartment::orderBy('name')->pluck('name', 'id'), 'department' => $v['department'] ?? null,
            'shifts' => HrShift::where('is_active', true)->orderBy('starts_at')->get(),
        ]);
    }

    public function dashboard(Request $request)
    {
        $date = Carbon::parse($request->validate(['date' => ['nullable', 'date']])['date'] ?? today())->startOfDay();
        $employees = HrEmployee::with('department')->where('is_active', true)->whereDate('join_date', '<=', $date)->orderBy('first_name')->get();
        $records = HrAttendance::whereDate('work_date', $date)->get()->keyBy('employee_id');
        $rostered = HrRoster::with('shift')->whereDate('work_date', $date)->get()->keyBy('employee_id');

        $counts = ['present' => 0, 'late' => 0, 'half_day' => 0, 'leave' => 0, 'absent' => 0, 'unmarked' => 0];
        foreach ($employees as $e) {
            $status = $records->get($e->id)?->status;
            $counts[$status ?? 'unmarked'] = ($counts[$status ?? 'unmarked'] ?? 0) + 1;
        }
        $byShift = $rostered->groupBy(fn ($r) => $r->shift?->name ?? 'Day off')->map->count()->sortKeys();
        // Rostered to work but not marked in (or absent) today.
        $missing = $employees->filter(fn ($e) => $rostered->get($e->id)?->shift_id && in_array($records->get($e->id)?->status, [null, 'absent'], true));

        return view('admin.hr.roster.dashboard', compact('date', 'employees', 'records', 'rostered', 'counts', 'byShift', 'missing'));
    }
}
