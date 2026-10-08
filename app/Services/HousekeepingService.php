<?php

namespace App\Services;

use App\Models\HkChecklistItem;
use App\Models\HkTask;
use App\Models\TblRoomnofloorassign;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Cleaning tasks and the room status they drive (1 ready, 2 booked, 3 assigned to clean, 4 booked + assigned, 6 dirty). */
class HousekeepingService
{
    /** @param list<int|string> $roomIds tbl_roomnofloorassign.roomassignid */
    public function assign(array $roomIds, ?int $employeeId, string $date, ?string $notes, string $source = 'staff', ?int $userId = null): int
    {
        $checklist = HkChecklistItem::where('is_active', true)->orderBy('sort')->orderBy('id')->pluck('name');
        $made = 0;

        DB::transaction(function () use ($roomIds, $employeeId, $date, $notes, $source, $userId, $checklist, &$made) {
            foreach (array_unique($roomIds) as $roomId) {
                $room = TblRoomnofloorassign::findOrFail($roomId);
                // One open task per room and day; assigning again just hands it to someone else.
                $existing = HkTask::where('room_assign_id', $room->roomassignid)->whereDate('task_date', $date)->whereIn('status', ['pending', 'in_progress'])->first();
                if ($existing) {
                    if ($employeeId) {
                        $existing->update(['assigned_to' => $employeeId]);
                    }

                    continue;
                }
                $task = HkTask::create(['room_assign_id' => $room->roomassignid, 'assigned_to' => $employeeId, 'task_date' => $date, 'notes' => $notes, 'source' => $source, 'created_by' => $userId]);
                $task->items()->createMany($checklist->map(fn ($n) => ['name' => $n])->all());
                $this->setRoom($room, [1 => 3, 6 => 3, 2 => 4]);
                $made++;
            }
        });

        return $made;
    }

    public function transition(HkTask $task, string $action): void
    {
        $rules = [
            'start' => [['pending'], 'in_progress'], 'complete' => [['pending', 'in_progress'], 'done'],
            'inspect' => [['done'], 'inspected'], 'cancel' => [['pending', 'in_progress'], 'cancelled'],
        ];
        if (! isset($rules[$action])) {
            throw new InvalidArgumentException('Unknown action.');
        }
        [$allowed, $to] = $rules[$action];
        if (! in_array($task->status, $allowed, true)) {
            throw new InvalidArgumentException('This task is '.str_replace('_', ' ', $task->status).' and cannot be changed that way.');
        }

        DB::transaction(function () use ($task, $to, $action) {
            $changes = ['status' => $to];
            if ($action === 'start' || ($action === 'complete' && ! $task->started_at)) {
                $changes['started_at'] = now();
            }
            if ($action === 'complete') {
                $changes['completed_at'] = now();
                $task->items()->update(['is_done' => true]);
            }
            $task->update($changes);

            if (in_array($action, ['complete', 'cancel'], true) && $task->room) {
                $this->setRoom($task->room, [3 => 1, 4 => 2]);
            }
        });
    }

    /** Move a room between statuses only when it is currently in one of the mapped states. */
    private function setRoom(TblRoomnofloorassign $room, array $map): void
    {
        if (isset($map[(int) $room->status])) {
            $room->update(['status' => $map[(int) $room->status]]);
        }
    }
}
