<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class CandidateSelection extends Model
{
    protected $table = 'candidate_selection';

    protected $primaryKey = 'can_sel_id';

    public $timestamps = false;

    protected $guarded = [];
}
