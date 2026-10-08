<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HkChecklistItem extends Model
{
    protected $table = 'hk_checklist_items';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
