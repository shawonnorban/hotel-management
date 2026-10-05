<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $table = 'purchase_return';

    protected $primaryKey = 'preturn_id';

    public $timestamps = false;

    protected $guarded = [];
}
