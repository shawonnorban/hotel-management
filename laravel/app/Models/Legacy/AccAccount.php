<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class AccAccount extends Model
{
    protected $table = 'acc_account';

    protected $primaryKey = 'account_id';

    public $timestamps = false;

    protected $guarded = [];
}
