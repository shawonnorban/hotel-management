<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblWakeupCall extends Model
{
    protected $table = 'tbl_wakeup_call';

    protected $primaryKey = 'wapupid';

    public $timestamps = false;

    protected $guarded = [];
}
