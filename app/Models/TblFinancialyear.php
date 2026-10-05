<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblFinancialyear extends Model
{
    protected $table = 'tbl_financialyear';

    protected $primaryKey = 'fiyear_id';

    public $timestamps = false;

    protected $guarded = [];
}
