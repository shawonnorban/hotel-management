<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblDestroyedproduct extends Model
{
    protected $table = 'tbl_destroyedproduct';

    protected $primaryKey = 'destroy_id';

    public $timestamps = false;

    protected $guarded = [];
}
