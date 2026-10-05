<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecRoleTbl extends Model
{
    protected $table = 'sec_role_tbl';

    protected $primaryKey = 'role_id';

    public $timestamps = false;

    protected $guarded = [];
}
