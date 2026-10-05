<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblState extends Model
{
    protected $table = 'tbl_state';

    protected $primaryKey = 'stateid';

    public $timestamps = false;

    protected $guarded = [];
}
