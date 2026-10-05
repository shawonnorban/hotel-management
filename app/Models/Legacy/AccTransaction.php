<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class AccTransaction extends Model
{
    protected $table = 'acc_transaction';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
