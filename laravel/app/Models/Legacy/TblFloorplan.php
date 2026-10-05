<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblFloorplan extends Model
{
    protected $table = 'tbl_floorplan';

    protected $primaryKey = 'floorplanid';

    public $timestamps = false;

    protected $guarded = [];
}
