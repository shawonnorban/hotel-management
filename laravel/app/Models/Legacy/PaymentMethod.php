<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_method';

    protected $primaryKey = 'payment_method_id';

    public $timestamps = false;

    protected $guarded = [];
}
