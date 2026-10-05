<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class PayFrequency extends Model
{
    protected $table = 'pay_frequency';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
