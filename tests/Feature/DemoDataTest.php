<?php

namespace Tests\Feature;

use App\Models\BookedInfo;
use App\Models\HallBooking;
use App\Models\HrEmployee;
use App\Models\HrPayrollRun;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_loads_every_module_and_the_books_balance(): void
    {
        $this->artisan('hotel:demo-data')->assertSuccessful();

        $this->assertGreaterThanOrEqual(10, BookedInfo::count());
        $this->assertSame(4, \App\Models\Roomdetails::count());
        $this->assertGreaterThanOrEqual(10, HrEmployee::count());
        $this->assertSame('paid', HrPayrollRun::firstOrFail()->status);
        $this->assertGreaterThanOrEqual(3, Purchase::count());
        $this->assertGreaterThan(0, InventoryItem::sum('stock'));
        $this->assertSame(3, HallBooking::count());
        $this->assertGreaterThan(0, \App\Models\HkLaundryOrder::count());
        $this->assertGreaterThan(0, \App\Models\TrVehicleBooking::count());

        $tb = app(LedgerService::class)->trialBalance(today());
        $this->assertEqualsWithDelta($tb['debit'] ?? $tb['total_debit'] ?? 0, $tb['credit'] ?? $tb['total_credit'] ?? 0, 0.01);

        // It refuses to pile onto a database that already has reservations.
        $this->artisan('hotel:demo-data')->assertFailed();
    }
}
