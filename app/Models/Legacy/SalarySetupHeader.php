<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class SalarySetupHeader extends Model
{
    protected $table = 'salary_setup_header';

    protected $primaryKey = 's_s_h_id';

    public $timestamps = false;

    protected $guarded = [];
}
