<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class GrandLoan extends Model
{
    protected $table = 'grand_loan';

    protected $primaryKey = 'loan_id';

    public $timestamps = false;

    protected $guarded = [];
}
