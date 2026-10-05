<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseDetails extends Model
{
    protected $table = 'purchase_details';

    protected $primaryKey = 'detailsid';

    public $timestamps = false;

    protected $guarded = [];
}
