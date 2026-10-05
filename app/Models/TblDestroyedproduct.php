<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblDestroyedproduct extends Model
{
    protected $table = 'tbl_destroyedproduct';

    protected $primaryKey = 'destroy_id';

    public $timestamps = false;

    protected $guarded = [];
}
