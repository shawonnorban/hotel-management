<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Starclass extends Model
{
    protected $table = 'starclass';

    protected $primaryKey = 'starcalssid';

    public $timestamps = false;

    protected $guarded = [];
}
