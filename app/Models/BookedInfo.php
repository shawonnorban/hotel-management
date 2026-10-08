<?php

namespace App\Models;

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
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'service_amount' => 'decimal:2',
            'extras_amount' => 'decimal:2',
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

    public const STATUS_PILLS = ['0' => 'pill-pending', '1' => 'pill-cancelled', '2' => 'pill-confirmed', '4' => 'pill-in', '5' => 'pill-out'];

    public function getStatusPillAttribute(): string
    {
        return self::STATUS_PILLS[(string) $this->bookingstatus] ?? 'pill-out';
    }

    public function payments()
    {
        return $this->hasMany(TblGuestpayments::class, 'bookedid', 'bookedid')->orderBy('payid');
    }

    public function guests()
    {
        return $this->hasMany(TblOtherguest::class, 'booking_id', 'bookedid')->orderBy('otherguest_id');
    }

    public function charges()
    {
        return $this->hasMany(FolioCharge::class, 'bookedid', 'bookedid')->orderBy('id');
    }

    public function events()
    {
        return $this->hasMany(BookingEvent::class, 'bookedid', 'bookedid')->orderByDesc('id');
    }

    /** Is the stay over (revenue has been recognised)? */
    public function getIsClosedAttribute(): bool
    {
        return (string) $this->bookingstatus === '5';
    }

    public function getIsOpenAttribute(): bool
    {
        return in_array((string) $this->bookingstatus, ['0', '2', '4'], true);
    }

    public function getBalanceAttribute(): float
    {
        return max(0.0, round((float) $this->total_price - (float) $this->paid_amount, 2));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[(string) $this->bookingstatus] ?? 'Unknown';
    }

    /**
     * The booking's rooms grouped by room type, rebuilt from the per-room lists the table stores.
     *
     * @return list<array{room_id:int,rooms:int,adults:int,children:int,numbers:list<string>,rate:float}>
     */
    public function roomLines(): array
    {
        $ids = array_values(array_filter(explode(',', (string) $this->roomid), fn ($v) => $v !== ''));
        $numbers = array_map('trim', explode(',', (string) $this->room_no));
        $adults = explode(',', (string) $this->nuofpeople);
        $children = explode(',', (string) $this->children);
        $rates = explode(',', (string) $this->roomrate);

        $lines = [];
        foreach ($ids as $i => $id) {
            $id = (int) $id;
            $lines[$id] ??= ['room_id' => $id, 'rooms' => 0, 'adults' => 0, 'children' => 0, 'numbers' => [], 'rate' => (float) ($rates[$i] ?? 0)];
            $lines[$id]['rooms']++;
            $lines[$id]['adults'] += (int) ($adults[$i] ?? 0);
            $lines[$id]['children'] += (int) ($children[$i] ?? 0);
            if (($numbers[$i] ?? '') !== '') {
                $lines[$id]['numbers'][] = $numbers[$i];
            }
        }

        return array_values($lines);
    }

    public function getNightsAttribute(): int
    {
        return max(1, (int) $this->checkindate->copy()->startOfDay()->diffInDays($this->checkoutdate->copy()->startOfDay()));
    }
}
