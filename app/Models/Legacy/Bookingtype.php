<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Bookingtype extends Model
{
    protected $table = 'bookingtype';

    protected $primaryKey = 'booktypeid';

    public $timestamps = false;

    protected $guarded = [];
}
