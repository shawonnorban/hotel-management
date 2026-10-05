<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblEmailPermission extends Model
{
    protected $table = 'tbl_email_permission';

    protected $primaryKey = 'permission_id';

    public $timestamps = false;

    protected $guarded = [];
}
