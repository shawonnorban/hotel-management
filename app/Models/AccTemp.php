<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccTemp extends Model
{
    protected $table = 'acc_temp';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
