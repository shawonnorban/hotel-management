<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['debit' => 'decimal:2', 'credit' => 'decimal:2'];
    }

    public function entry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account()
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }
}
