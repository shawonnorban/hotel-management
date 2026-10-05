<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblTaxmgt extends Model
{
    protected $table = 'tbl_taxmgt';

    protected $primaryKey = 'tax_id';

    public $timestamps = false;

    protected $guarded = [];
}
