<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrShift extends Model
{
    protected $table = 'hr_shifts';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getHoursLabelAttribute(): string
    {
        return substr($this->starts_at, 0, 5).'–'.substr($this->ends_at, 0, 5);
    }
}
