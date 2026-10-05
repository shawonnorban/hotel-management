<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerAccount extends Model
{
    public const TYPES = ['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expense'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_group' => 'boolean', 'is_active' => 'boolean', 'is_cash' => 'boolean', 'opening_balance' => 'decimal:2'];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    /** Assets and expenses increase with debits; everything else with credits. */
    public function isDebitNormal(): bool
    {
        return in_array($this->type, ['asset', 'expense'], true);
    }

    public function getLabelAttribute(): string
    {
        return $this->code.' · '.$this->name;
    }
}
