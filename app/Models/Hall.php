<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    protected $table = 'halls';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function type()
    {
        return $this->belongsTo(HallType::class, 'type_id');
    }

    public function facilities()
    {
        return $this->belongsToMany(HallFacility::class, 'hall_facility_hall', 'hall_id', 'facility_id');
    }

    public function seatPlans()
    {
        return $this->hasMany(HallSeatPlan::class);
    }
}
