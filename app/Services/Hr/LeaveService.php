<?php

namespace App\Services\Hr;

use App\Models\HrAttendance;
use App\Models\HrEmployee;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeaveService
{
    public function __construct(private WorkCalendar $calendar) {}

    /** Days of this type already approved in the calendar year. */
    public function used(HrEmployee $employee, HrLeaveType $type, int $year): float
    {
        return (float) HrLeaveRequest::where('employee_id', $employee->id)->where('leave_type_id', $type->id)->where('status', 'approved')
            ->whereYear('from_date', $year)->sum('days');
    }

    public function request(HrEmployee $employee, HrLeaveType $type, Carbon $from, Carbon $to, ?string $reason, bool $halfDay = false): HrLeaveRequest
    {
        if ($to->lt($from)) {
            throw new InvalidArgumentException('The end date cannot be before the start date.');
        }
        $days = $halfDay ? 0.5 : $this->calendar->workingDays($from, $to);
        if ($days <= 0) {
            throw new InvalidArgumentException('That period contains no working days.');
        }
        if ($halfDay && ! $from->isSameDay($to)) {
            throw new InvalidArgumentException('A half day must start and end on the same date.');
        }

        $overlap = HrLeaveRequest::where('employee_id', $employee->id)->whereIn('status', ['pending', 'approved'])
            ->whereDate('from_date', '<=', $to)->whereDate('to_date', '>=', $from)->exists();
        if ($overlap) {
            throw new InvalidArgumentException('The employee already has leave in that period.');
        }

        if ($type->days_per_year > 0) {
            $left = $type->days_per_year - $this->used($employee, $type, $from->year);
            if ($days > $left + 0.0001) {
                throw new InvalidArgumentException("Only {$left} day(s) of {$type->name} are left this year.");
            }
        }

        return HrLeaveRequest::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'from_date' => $from, 'to_date' => $to, 'days' => $days, 'reason' => $reason, 'status' => 'pending']);
    }

    /** Approving marks those working days as leave in the attendance sheet. */
    public function decide(HrLeaveRequest $request, bool $approve, ?int $userId): HrLeaveRequest
    {
        if ($request->status !== 'pending') {
            throw new InvalidArgumentException('This request has already been decided.');
        }

        return DB::transaction(function () use ($request, $approve, $userId) {
            $request->update(['status' => $approve ? 'approved' : 'rejected', 'decided_by' => $userId, 'decided_at' => now()]);

            if ($approve) {
                for ($d = $request->from_date->copy(); $d->lte($request->to_date); $d->addDay()) {
                    if ($this->calendar->isWorkingDay($d)) {
                        HrAttendance::updateOrCreate(['employee_id' => $request->employee_id, 'work_date' => $d->toDateString()], ['status' => 'leave', 'note' => $request->type->name.((float) $request->days === 0.5 ? ' (half day)' : '')]);
                    }
                }
            }

            return $request;
        });
    }
}
