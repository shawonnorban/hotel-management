<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnDetails extends Model
{
    protected $table = 'purchase_return_details';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
