<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Roomfacilitytype extends Model
{
    protected $table = 'roomfacilitytype';

    protected $primaryKey = 'facilitytypeid';

    public $timestamps = false;

    protected $guarded = [];
}
