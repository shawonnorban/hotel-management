<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblFinancialyear extends Model
{
    protected $table = 'tbl_financialyear';

    protected $primaryKey = 'fiyear_id';

    public $timestamps = false;

    protected $guarded = [];
}
