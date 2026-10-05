<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Roomsizemesurement extends Model
{
    protected $table = 'roomsizemesurement';

    protected $primaryKey = 'mesurementid';

    public $timestamps = false;

    protected $guarded = [];
}
