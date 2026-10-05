<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class AccCoa extends Model
{
    protected $table = 'acc_coa';

    protected $primaryKey = 'HeadName';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
