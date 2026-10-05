<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $table = 'products';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
