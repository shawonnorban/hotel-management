<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class SalaryType extends Model
{
    protected $table = 'salary_type';

    protected $primaryKey = 'salary_type_id';

    public $timestamps = false;

    protected $guarded = [];
}
