<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'department';

    protected $primaryKey = 'dept_id';

    public $timestamps = false;

    protected $guarded = [];
}
