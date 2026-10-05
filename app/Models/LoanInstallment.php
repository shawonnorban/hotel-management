<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanInstallment extends Model
{
    protected $table = 'loan_installment';

    protected $primaryKey = 'loan_inst_id';

    public $timestamps = false;

    protected $guarded = [];
}
