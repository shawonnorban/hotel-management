<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblOtherguest extends Model
{
    protected $table = 'tbl_otherguest';

    protected $primaryKey = 'otherguest_id';

    public $timestamps = false;

    protected $guarded = [];

    public function booking()
    {
        return $this->belongsTo(BookedInfo::class, 'booking_id', 'bookedid');
    }
}
