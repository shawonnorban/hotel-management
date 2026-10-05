<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class MaritalInfo extends Model
{
    protected $table = 'marital_info';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
