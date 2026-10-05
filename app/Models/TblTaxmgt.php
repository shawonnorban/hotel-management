<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblTaxmgt extends Model
{
    protected $table = 'tbl_taxmgt';

    protected $primaryKey = 'tax_id';

    public $timestamps = false;

    protected $guarded = [];
}
