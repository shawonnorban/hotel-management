<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HallPayment extends Model
{
    protected $table = 'hall_payments';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['paid_on' => 'date', 'amount' => 'decimal:2'];
    }

    public function booking()
    {
        return $this->belongsTo(HallBooking::class, 'booking_id');
    }

    public function account()
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }
}
