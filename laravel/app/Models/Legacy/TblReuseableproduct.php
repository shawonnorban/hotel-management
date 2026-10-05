<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblReuseableproduct extends Model
{
    protected $table = 'tbl_reuseableproduct';

    protected $primaryKey = 'reuse_id';

    public $timestamps = false;

    protected $guarded = [];
}
