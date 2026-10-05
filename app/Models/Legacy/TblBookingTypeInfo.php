<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class TblBookingTypeInfo extends Model
{
    protected $table = 'tbl_booking_type_info';

    protected $primaryKey = 'btypeinfoid';

    public $timestamps = false;

    protected $guarded = [];
}
