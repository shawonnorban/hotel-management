<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchaseitem extends Model
{
    protected $table = 'purchaseitem';

    protected $primaryKey = 'purID';

    public $timestamps = false;

    protected $guarded = [];
}
