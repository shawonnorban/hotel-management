<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class SmsConfiguration extends Model
{
    protected $table = 'sms_configuration';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
