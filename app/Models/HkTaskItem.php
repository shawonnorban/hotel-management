<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HkTaskItem extends Model
{
    protected $table = 'hk_task_items';

    protected $guarded = [];
    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_done' => 'boolean'];
    }
}
