<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HkTask extends Model
{
    protected $table = 'hk_tasks';

    protected $guarded = [];

    public const STATUSES = ['pending' => 'Pending', 'in_progress' => 'In progress', 'done' => 'Done', 'inspected' => 'Inspected', 'cancelled' => 'Cancelled'];

    protected function casts(): array
    {
        return ['task_date' => 'date', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function room()
    {
        return $this->belongsTo(TblRoomnofloorassign::class, 'room_assign_id', 'roomassignid');
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'assigned_to');
    }

    public function items()
    {
        return $this->hasMany(HkTaskItem::class, 'task_id');
    }
}
