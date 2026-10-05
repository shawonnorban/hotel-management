<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'supplier';

    protected $primaryKey = 'supid';

    public $timestamps = false;

    protected $guarded = [];
}
