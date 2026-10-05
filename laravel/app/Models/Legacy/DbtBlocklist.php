<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class DbtBlocklist extends Model
{
    protected $table = 'dbt_blocklist';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
