<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class SecUserAccessTbl extends Model
{
    protected $table = 'sec_user_access_tbl';

    protected $primaryKey = 'role_acc_id';

    public $timestamps = false;

    protected $guarded = [];
}
