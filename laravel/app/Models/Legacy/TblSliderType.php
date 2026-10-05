<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblSliderType extends Model
{
    protected $table = 'tbl_slider_type';

    protected $primaryKey = 'stype_id';

    public $timestamps = false;

    protected $guarded = [];
}
