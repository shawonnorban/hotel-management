<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblTax extends Model
{
    protected $table = 'tbl_tax';

    protected $primaryKey = 'taxsettings';

    public $timestamps = false;

    protected $guarded = [];
}
