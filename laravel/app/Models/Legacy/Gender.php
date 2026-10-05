<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    protected $table = 'gender';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
