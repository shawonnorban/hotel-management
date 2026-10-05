<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaritalInfo extends Model
{
    protected $table = 'marital_info';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
