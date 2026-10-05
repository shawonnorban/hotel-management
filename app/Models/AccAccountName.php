<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccAccountName extends Model
{
    protected $table = 'acc_account_name';

    protected $primaryKey = 'account_id';

    public $timestamps = false;

    protected $guarded = [];
}
