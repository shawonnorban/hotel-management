<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyType extends Model
{
    protected $table = 'duty_type';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
