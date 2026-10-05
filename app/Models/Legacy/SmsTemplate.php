<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $table = 'sms_template';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
