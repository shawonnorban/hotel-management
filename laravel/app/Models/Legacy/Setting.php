<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'setting';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
