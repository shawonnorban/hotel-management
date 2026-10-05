<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblOtherguest extends Model
{
    protected $table = 'tbl_otherguest';

    protected $primaryKey = 'otherguest_id';

    public $timestamps = false;

    protected $guarded = [];
}
