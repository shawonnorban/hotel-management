<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class SecRolePermission extends Model
{
    protected $table = 'sec_role_permission';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
