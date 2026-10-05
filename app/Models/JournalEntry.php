<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    public const TYPES = ['journal' => 'Journal', 'receipt' => 'Receipt', 'payment' => 'Payment', 'contra' => 'Contra', 'booking' => 'Booking', 'purchase' => 'Purchase', 'payroll' => 'Payroll', 'adjustment' => 'Adjustment'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['entry_date' => 'date'];
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class)->with('account');
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->lines->sum('debit');
    }
}
