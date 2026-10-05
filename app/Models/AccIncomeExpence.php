<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccIncomeExpence extends Model
{
    protected $table = 'acc_income_expence';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
