<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roomfacilitydetails extends Model
{
    protected $table = 'roomfacilitydetails';

    protected $primaryKey = 'facilityid';

    public $timestamps = false;

    protected $guarded = [];
}
