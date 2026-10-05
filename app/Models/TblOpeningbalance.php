<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblOpeningbalance extends Model
{
    protected $table = 'tbl_openingbalance';

    protected $primaryKey = 'opbalance_id';

    public $timestamps = false;

    protected $guarded = [];
}
