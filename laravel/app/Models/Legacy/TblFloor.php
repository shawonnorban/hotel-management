<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblFloor extends Model
{
    protected $table = 'tbl_floor';

    protected $primaryKey = 'floorid';

    public $timestamps = false;

    protected $guarded = [];
}
