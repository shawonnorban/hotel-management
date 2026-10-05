<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roomdetails extends Model
{
    protected $table = 'roomdetails';

    protected $primaryKey = 'roomid';

    public $timestamps = false;

    protected $guarded = [];

    public function roomNumbers()
    {
        return $this->hasMany(TblRoomnofloorassign::class, 'roomid', 'roomid');
    }

    public function images()
    {
        return $this->hasMany(RoomImage::class, 'room_id', 'roomid');
    }

    public function bedType()
    {
        return $this->belongsTo(Bedstype::class, 'bedstype', 'Bedstypeid');
    }

    public function sizeUnit()
    {
        return $this->belongsTo(Roomsizemesurement::class, 'roomsizemesurement', 'mesurementid');
    }

    /** Room-size text such as "300 sqft". */
    public function getSizeLabelAttribute(): string
    {
        return trim($this->roomsize.' '.($this->sizeUnit?->roommesurementitle ?? ''));
    }
}
