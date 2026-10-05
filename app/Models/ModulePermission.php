<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModulePermission extends Model
{
    protected $table = 'module_permission';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
