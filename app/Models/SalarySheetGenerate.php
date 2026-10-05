<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySheetGenerate extends Model
{
    protected $table = 'salary_sheet_generate';

    protected $primaryKey = 'ssg_id';

    public $timestamps = false;

    protected $guarded = [];
}
