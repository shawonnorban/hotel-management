<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblCountry extends Model
{
    protected $table = 'tbl_country';

    protected $primaryKey = 'countryid';

    public $timestamps = false;

    protected $guarded = [];
}
