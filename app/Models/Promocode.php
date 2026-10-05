<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    protected $table = 'promocode';

    protected $primaryKey = 'promocodeid';

    public $timestamps = false;

    protected $guarded = [];
}
