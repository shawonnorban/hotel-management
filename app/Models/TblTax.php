<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblTax extends Model
{
    protected $table = 'tbl_tax';

    protected $primaryKey = 'taxsettings';

    public $timestamps = false;

    protected $guarded = [];
}
