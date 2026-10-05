<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class CustomTable extends Model
{
    protected $table = 'custom_table';

    protected $primaryKey = 'custom_id';

    public $timestamps = false;

    protected $guarded = [];
}
