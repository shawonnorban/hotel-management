<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrLoanInstallment extends Model
{
    protected $table = 'hr_loan_installments';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'deducted' => 'boolean'];
    }

}
