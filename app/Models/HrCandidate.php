<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrCandidate extends Model
{
    protected $table = 'hr_candidates';

    protected $guarded = [];

    public const STAGES = ['applied' => 'Applied', 'shortlisted' => 'Shortlisted', 'interview' => 'Interview', 'selected' => 'Selected', 'rejected' => 'Rejected', 'hired' => 'Hired'];

    protected function casts(): array
    {
        return ['interview_on' => 'date'];
    }

    public function position()
    {
        return $this->belongsTo(HrPosition::class, 'position_id');
    }

}
