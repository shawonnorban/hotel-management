<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class AccBank extends Model
{
    protected $table = 'acc_bank';

    protected $primaryKey = 'bank_id';

    public $timestamps = false;

    protected $guarded = [];
}
