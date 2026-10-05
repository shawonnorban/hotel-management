<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrAttendance;
use App\Models\HrEmployee;
use App\Services\Hr\WorkCalendar;
use App\Support\AppSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public const STATUSES = ['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'leave' => 'On leave', 'half_day' => 'Half day'];

    public function __construct(private WorkCalendar $calendar)
    {
    }

    public function sheet(Request $request)
    {
        $date = Carbon::parse($request->validate(['date' => ['nullable', 'date']])['date'] ?? today())->startOfDay();
        $employees = HrEmployee::with('department')->where('is_active', true)->whereDate('join_date', '<=', $date)->orderBy('first_name')->get();
        $records = HrAttendance::whereDate('work_date', $date)->get()->keyBy('employee_id');

        return view('admin.hr.attendance.sheet', [
            'date' => $date, 'employees' => $employees, 'records' => $records, 'statuses' => self::STATUSES,
            'offDay' => ! $this->calendar->isWorkingDay($date),
            'weeklyOff' => $this->calendar->weeklyOff(), 'days' => WorkCalendar::DAYS,
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'rows' => ['required', 'array'],
            'rows.*.employee' => ['required', 'integer', 'exists:hr_employees,id'],
            'rows.*.status' => ['nullable', 'in:'.implode(',', array_keys(self::STATUSES))],
            'rows.*.check_in' => ['nullable', 'date_format:H:i'],
            'rows.*.check_out' => ['nullable', 'date_format:H:i', 'after_or_equal:rows.*.check_in'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['rows'] as $row) {
                if (empty($row['status'])) {
                    HrAttendance::where('employee_id', $row['employee'])->whereDate('work_date', $data['date'])->delete();

                    continue;
                }
                HrAttendance::updateOrCreate(
                    ['employee_id' => $row['employee'], 'work_date' => $data['date']],
                    ['status' => $row['status'], 'check_in' => $row['check_in'] ?: null, 'check_out' => $row['check_out'] ?: null],
                );
            }
        });

        return redirect()->route('admin.hr.attendance', ['date' => $data['date']])->with('status', 'Attendance saved.');
    }

    public function report(Request $request)
    {
        $month = $request->validate(['month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']])['month'] ?? today()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $days = collect(range(1, $start->daysInMonth))->map(fn ($d) => $start->copy()->day($d));

        $records = HrAttendance::whereBetween('work_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])->get()->groupBy('employee_id');
        $employees = HrEmployee::where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $records->keys()))->orderBy('first_name')->get();

        $rows = $employees->map(function ($e) use ($records, $days) {
            $byDay = ($records[$e->id] ?? collect())->keyBy(fn ($r) => $r->work_date->day);
            $count = fn ($s) => $byDay->where('status', $s)->count();

            return ['employee' => $e, 'byDay' => $byDay, 'present' => $count('present') + $count('late'), 'late' => $count('late'), 'absent' => $count('absent'), 'leave' => $count('leave'), 'half' => $count('half_day')];
        });

        return view('admin.hr.attendance.report', ['month' => $month, 'start' => $start, 'days' => $days, 'rows' => $rows, 'calendar' => $this->calendar]);
    }

    public function weeklyOff(Request $request)
    {
        $d = $request->validate(['off' => ['nullable', 'array'], 'off.*' => ['in:'.implode(',', array_keys(WorkCalendar::DAYS))]]);
        AppSettings::set('hr.weekly_off', implode(',', $d['off'] ?? []));

        return back()->with('status', 'Weekly days off saved.');
    }
}
