<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    protected $table = 'award';

    protected $primaryKey = 'award_id';

    public $timestamps = false;

    protected $guarded = [];
}
