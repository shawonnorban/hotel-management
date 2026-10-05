<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roomsizemesurement extends Model
{
    protected $table = 'roomsizemesurement';

    protected $primaryKey = 'mesurementid';

    public $timestamps = false;

    protected $guarded = [];
}
