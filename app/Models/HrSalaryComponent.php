<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrSalaryComponent extends Model
{
    protected $table = 'hr_salary_components';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'is_percent' => 'boolean'];
    }

}
