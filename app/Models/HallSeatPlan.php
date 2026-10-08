<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HallSeatPlan extends Model
{
    protected $table = 'hall_seat_plans';

    protected $guarded = [];

    public const LAYOUTS = ['theatre' => 'Theatre', 'classroom' => 'Classroom', 'banquet' => 'Banquet (round tables)', 'u_shape' => 'U-shape', 'boardroom' => 'Boardroom', 'cocktail' => 'Cocktail / standing'];

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }
}
