<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblRoomOffer extends Model
{
    protected $table = 'tbl_room_offer';

    protected $primaryKey = 'offerid';

    public $timestamps = false;

    protected $guarded = [];
}
