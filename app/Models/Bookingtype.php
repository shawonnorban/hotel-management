<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookingtype extends Model
{
    protected $table = 'bookingtype';

    protected $primaryKey = 'booktypeid';

    public $timestamps = false;

    protected $guarded = [];
}
