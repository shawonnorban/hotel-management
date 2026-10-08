<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HkLaundryPayment extends Model
{
    protected $table = 'hk_laundry_payments';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['paid_on' => 'date', 'amount' => 'decimal:2'];
    }

    public function order()
    {
        return $this->belongsTo(HkLaundryOrder::class, 'order_id');
    }

    public function account()
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }
}
