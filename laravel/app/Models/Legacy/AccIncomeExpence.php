<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class AccIncomeExpence extends Model
{
    protected $table = 'acc_income_expence';

    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];
}
