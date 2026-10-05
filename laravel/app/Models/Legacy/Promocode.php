<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    protected $table = 'promocode';

    protected $primaryKey = 'promocodeid';

    public $timestamps = false;

    protected $guarded = [];
}
