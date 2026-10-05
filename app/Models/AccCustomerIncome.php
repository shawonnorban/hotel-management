<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccCustomerIncome extends Model
{
    protected $table = 'acc_customer_income';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
