<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class PaymentCurrency extends Model
{
    protected $table = 'payment_currency';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
