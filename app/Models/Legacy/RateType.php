<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class RateType extends Model
{
    protected $table = 'rate_type';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
