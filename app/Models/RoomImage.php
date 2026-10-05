<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomImage extends Model
{
    protected $table = 'room_image';

    protected $primaryKey = 'room_img_id';

    public $timestamps = false;

    protected $guarded = [];
}
