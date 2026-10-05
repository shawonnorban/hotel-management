<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomfailityRefAccomodation extends Model
{
    protected $table = 'roomfaility_ref_accomodation';

    protected $primaryKey = 'accomodationid';

    public $timestamps = false;

    protected $guarded = [];
}
