<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateEducationInfo extends Model
{
    protected $table = 'candidate_education_info';

    protected $primaryKey = 'can_edu_id';

    public $timestamps = false;

    protected $guarded = [];
}
