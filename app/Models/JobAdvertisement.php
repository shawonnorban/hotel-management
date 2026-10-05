<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobAdvertisement extends Model
{
    protected $table = 'job_advertisement';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
