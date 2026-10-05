<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Roomfacilitydetails extends Model
{
    protected $table = 'roomfacilitydetails';

    protected $primaryKey = 'facilityid';

    public $timestamps = false;

    protected $guarded = [];
}
