<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateShortlist extends Model
{
    protected $table = 'candidate_shortlist';

    protected $primaryKey = 'can_short_id';

    public $timestamps = false;

    protected $guarded = [];
}
