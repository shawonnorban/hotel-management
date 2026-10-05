<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommonSetting extends Model
{
    protected $table = 'common_setting';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
