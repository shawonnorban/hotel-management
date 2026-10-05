<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblWidget extends Model
{
    protected $table = 'tbl_widget';

    protected $primaryKey = 'widgetid';

    public $timestamps = false;

    protected $guarded = [];
}
