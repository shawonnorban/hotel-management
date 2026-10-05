<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblGuestpayments extends Model
{
    protected $table = 'tbl_guestpayments';

    protected $primaryKey = 'payid';

    public $timestamps = false;

    protected $guarded = [];
}
