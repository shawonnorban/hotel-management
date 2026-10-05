<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paymentsetup extends Model
{
    protected $table = 'paymentsetup';

    protected $primaryKey = 'setupid';

    public $timestamps = false;

    protected $guarded = [];
}
