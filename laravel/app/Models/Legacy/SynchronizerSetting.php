<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class SynchronizerSetting extends Model
{
    protected $table = 'synchronizer_setting';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
