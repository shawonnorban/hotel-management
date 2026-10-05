<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class CandidateBasicInfo extends Model
{
    protected $table = 'candidate_basic_info';

    protected $primaryKey = 'can_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
