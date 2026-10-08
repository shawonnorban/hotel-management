<?php

namespace App\Http\Controllers\Admin\Housekeeping;

use App\Http\Controllers\Controller;
use App\Models\HkTask;
use App\Models\HrEmployee;
use App\Models\Roomdetails;
use App\Models\TblRoomnofloorassign;
use App\Services\HousekeepingService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CleaningController extends Controller
{
    /** Room statuses as stored by the legacy room table. */
    public const ROOM_STATUS = [1 => 'Ready', 2 => 'Booked', 3 => 'Assigned to clean', 4 => 'Booked · assigned to clean', 5 => 'Under maintenance', 6 => 'Dirty', 7 => 'Blocked', 8 => 'Do not reserve'];

    public function __construct(private HousekeepingService $housekeeping) {}

    public function assignForm()
    {
        return view('admin.housekeeping.assign', [
            'rooms' => $this->rooms(),
            'types' => Roomdetails::pluck('roomtype', 'roomid'),
            'employees' => HrEmployee::where('is_active', true)->orderBy('first_name')->get(),
            'statuses' => self::ROOM_STATUS,
        ]);
    }

    public function assign(Request $request)
    {
        $d = $request->validate([
            'rooms' => ['required', 'array', 'min:1'], 'rooms.*' => ['integer', 'exists:tbl_roomnofloorassign,roomassignid'],
            'employee' => ['nullable', 'integer', 'exists:hr_employees,id'], 'task_date' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $made = $this->housekeeping->assign($d['rooms'], $d['employee'] ?? null, $d['task_date'], $d['notes'] ?? null, 'staff', auth('admin')->id());

        return redirect()->route('admin.housekeeping.tasks', ['date' => $d['task_date']])->with('status', $made.' cleaning task'.($made === 1 ? '' : 's').' created.');
    }

    public function tasks(Request $request)
    {
        $f = $request->validate(['date' => ['nullable', 'date'], 'status' => ['nullable', 'in:'.implode(',', array_keys(HkTask::STATUSES))], 'employee' => ['nullable', 'integer']]);
        $date = Carbon::parse($f['date'] ?? today())->startOfDay();

        $tasks = HkTask::with('room', 'employee', 'items')->whereDate('task_date', $date)
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['employee'] ?? null, fn ($q, $v) => $q->where('assigned_to', $v))
            ->orderByRaw("field(status,'in_progress','pending','done','inspected','cancelled')")->orderBy('id')->get();

        return view('admin.housekeeping.tasks', [
            'tasks' => $tasks, 'date' => $date, 'f' => $f, 'employees' => HrEmployee::where('is_active', true)->orderBy('first_name')->get(),
            'types' => Roomdetails::pluck('roomtype', 'roomid'),
        ]);
    }

    public function transition(HkTask $task, string $action)
    {
        try {
            $this->housekeeping->transition($task, $action);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['task' => $e->getMessage()]);
        }

        return back()->with('status', 'Task updated.');
    }

    public function toggleItem(HkTask $task, int $item)
    {
        $row = $task->items()->findOrFail($item);
        if (in_array($task->status, ['done', 'inspected', 'cancelled'], true)) {
            return back()->withErrors(['task' => 'This task is closed.']);
        }
        $row->update(['is_done' => ! $row->is_done]);

        return back();
    }

    public function qrList()
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(160, 1), new SvgImageBackEnd));
        $rooms = $this->rooms()->map(function ($room) use ($writer) {
            $room->qr = $writer->writeString(route('room.show', $room->roomassignid));

            return $room;
        });

        return view('admin.housekeeping.qr', ['rooms' => $rooms, 'types' => Roomdetails::pluck('roomtype', 'roomid')]);
    }

    public function report(Request $request)
    {
        $d = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $to = Carbon::parse($d['to'] ?? today()->endOfMonth())->startOfDay();
        $from = Carbon::parse($d['from'] ?? $to->copy()->startOfMonth())->startOfDay();

        $tasks = HkTask::with('employee', 'room')->whereDate('task_date', '>=', $from)->whereDate('task_date', '<=', $to)->get();
        $byStatus = $tasks->groupBy('status')->map->count();
        $byEmployee = $tasks->groupBy(fn ($t) => $t->employee?->full_name ?? 'Unassigned')->map(function ($g) {
            $done = $g->whereIn('status', ['done', 'inspected']);
            $minutes = $done->filter(fn ($t) => $t->started_at && $t->completed_at)->map(fn ($t) => $t->started_at->diffInMinutes($t->completed_at));

            return ['total' => $g->count(), 'done' => $done->count(), 'avg_minutes' => $minutes->isEmpty() ? null : (int) round($minutes->avg())];
        })->sortKeys();

        $laundry = \App\Models\HkLaundryOrder::whereBetween('order_date', [$from->toDateString(), $to->toDateString()])->where('status', '!=', 'cancelled')->get();
        $received = \App\Models\HkLaundryPayment::whereBetween('paid_on', [$from->toDateString(), $to->toDateString()])->sum('amount');

        return view('admin.housekeeping.report', [
            'from' => $from, 'to' => $to, 'tasks' => $tasks, 'byStatus' => $byStatus, 'byEmployee' => $byEmployee,
            'laundryCount' => $laundry->count(), 'laundryTotal' => round($laundry->sum('total'), 2), 'laundryReceived' => round((float) $received, 2),
        ]);
    }

    private function rooms()
    {
        return TblRoomnofloorassign::orderBy('roomno')->get();
    }
}
