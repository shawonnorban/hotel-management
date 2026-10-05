<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class DbtSecurity extends Model
{
    protected $table = 'dbt_security';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
