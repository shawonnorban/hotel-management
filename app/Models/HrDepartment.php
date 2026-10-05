<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrDepartment extends Model
{
    protected $table = 'hr_departments';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function employees()
    {
        return $this->hasMany(HrEmployee::class, 'department_id');
    }
}
