<?php

namespace App\Services;

use App\Models\BookedInfo;
use App\Models\BookingEvent;

/** Audit trail shown on every reservation. */
class ReservationLog
{
    public function add(BookedInfo $booking, string $event, ?string $detail = null, ?int $userId = null): void
    {
        BookingEvent::create(['bookedid' => $booking->bookedid, 'event' => $event, 'detail' => $detail, 'user_id' => $userId]);
    }
}
