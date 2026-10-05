<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class PayrollTaxSetup extends Model
{
    protected $table = 'payroll_tax_setup';

    protected $primaryKey = 'tax_setup_id';

    public $timestamps = false;

    protected $guarded = [];
}
