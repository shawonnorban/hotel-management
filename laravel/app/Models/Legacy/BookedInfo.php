<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class BookedInfo extends Model
{
    public const STATUS_LABELS = [
        '0' => 'Pending',
        '1' => 'Cancelled',
        '2' => 'Confirmed',
        '4' => 'Checked in',
        '5' => 'Checked out',
    ];

    /** Allowed status changes: from => [to, ...] */
    public const TRANSITIONS = [
        '0' => ['2', '1'],
        '2' => ['4', '1'],
        '4' => ['5'],
    ];

    protected $table = 'booked_info';

    protected $primaryKey = 'bookedid';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_time' => 'datetime',
            'checkindate' => 'datetime',
            'checkoutdate' => 'datetime',
            'total_price' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'booking_number';
    }

    public function customer()
    {
        return $this->belongsTo(Customerinfo::class, 'cutomerid', 'customerid');
    }

    public function details()
    {
        return $this->hasMany(BookedDetails::class, 'bookedid', 'bookedid');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[(string) $this->bookingstatus] ?? 'Unknown';
    }

    public function getNightsAttribute(): int
    {
        return max(1, (int) $this->checkindate->copy()->startOfDay()->diffInDays($this->checkoutdate->copy()->startOfDay()));
    }
}
