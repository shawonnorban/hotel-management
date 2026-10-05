<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblFloor extends Model
{
    protected $table = 'tbl_floor';

    protected $primaryKey = 'floorid';

    public $timestamps = false;

    protected $guarded = [];
}
