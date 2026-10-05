<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class AccAccountName extends Model
{
    protected $table = 'acc_account_name';

    protected $primaryKey = 'account_id';

    public $timestamps = false;

    protected $guarded = [];
}
