<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccTransaction extends Model
{
    protected $table = 'acc_transaction';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
