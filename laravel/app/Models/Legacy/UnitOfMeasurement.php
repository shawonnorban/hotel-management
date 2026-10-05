<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class UnitOfMeasurement extends Model
{
    protected $table = 'unit_of_measurement';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}
