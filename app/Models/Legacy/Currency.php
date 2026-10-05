<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = 'currency';

    protected $primaryKey = 'currencyid';

    public $timestamps = false;

    protected $guarded = [];
}
