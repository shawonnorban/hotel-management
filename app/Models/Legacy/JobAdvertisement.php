<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class JobAdvertisement extends Model
{
    protected $table = 'job_advertisement';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
