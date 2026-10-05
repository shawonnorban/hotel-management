<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateWorkexperience extends Model
{
    protected $table = 'candidate_workexperience';

    protected $primaryKey = 'can_workexp_id';

    public $timestamps = false;

    protected $guarded = [];
}
