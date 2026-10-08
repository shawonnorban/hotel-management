<?php

namespace App\Console\Commands;

use App\Services\AdvanceBookingService;
use Illuminate\Console\Command;

class ReleaseUnpaidBookings extends Command
{
    protected $signature = 'hotel:release-unpaid-bookings';

    protected $description = 'Cancel pending bookings that received no advance within the configured hold period';

    public function handle(AdvanceBookingService $advance): int
    {
        $n = $advance->releaseUnpaid();
        $this->info($n.' booking(s) released.');

        return self::SUCCESS;
    }
}
