<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblSlider extends Model
{
    protected $table = 'tbl_slider';

    protected $primaryKey = 'slid';

    public $timestamps = false;

    protected $guarded = [];
}
