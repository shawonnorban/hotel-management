<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Roomdetails extends Model
{
    protected $table = 'roomdetails';

    protected $primaryKey = 'roomid';

    public $timestamps = false;

    protected $guarded = [];
}
