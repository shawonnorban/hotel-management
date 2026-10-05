<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class PurchaseDetails extends Model
{
    protected $table = 'purchase_details';

    protected $primaryKey = 'detailsid';

    public $timestamps = false;

    protected $guarded = [];
}
