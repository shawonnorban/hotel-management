<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblModulePurchasekey extends Model
{
    protected $table = 'tbl_module_purchasekey';

    protected $primaryKey = 'mpid';

    public $timestamps = false;

    protected $guarded = [];
}
