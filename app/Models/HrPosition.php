<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrPosition extends Model
{
    protected $table = 'hr_positions';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function department()
    {
        return $this->belongsTo(HrDepartment::class, 'department_id');
    }
}
