<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrAward extends Model
{
    protected $table = 'hr_awards';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['awarded_on' => 'date', 'cash_amount' => 'decimal:2'];
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

}
