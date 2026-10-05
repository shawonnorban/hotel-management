<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class EmailConfig extends Model
{
    protected $table = 'email_config';

    protected $primaryKey = 'email_config_id';

    public $timestamps = false;

    protected $guarded = [];
}
