<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscribeEmaillist extends Model
{
    protected $table = 'subscribe_emaillist';

    protected $primaryKey = 'emailid';

    public $timestamps = false;

    protected $guarded = [];
}
