<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class DutyType extends Model
{
    protected $table = 'duty_type';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
