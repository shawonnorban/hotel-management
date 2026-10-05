<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateInterview extends Model
{
    protected $table = 'candidate_interview';

    protected $primaryKey = 'can_int_id';

    public $timestamps = false;

    protected $guarded = [];
}
