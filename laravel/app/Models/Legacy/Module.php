<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'module';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
