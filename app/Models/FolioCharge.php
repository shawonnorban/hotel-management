<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolioCharge extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'charged_on' => 'date'];
    }

    public function booking()
    {
        return $this->belongsTo(BookedInfo::class, 'bookedid', 'bookedid');
    }
}
