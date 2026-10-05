<?php

namespace Tests\Feature;

use App\Models\Customerinfo;
use App\Support\Settings;
use App\Models\PaymentMethod;
use App\Models\Roomdetails;
use App\Models\Setting;
use App\Models\TblRoomnofloorassign;
use App\Models\TblTaxmgt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class HotelTestCase extends TestCase
{
    use RefreshDatabase;

    protected Roomdetails $room;

    protected Customerinfo $guest;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Settings::flush();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        \App\Models\TblFloor::create(['floorid' => 1, 'floorname' => 'Ground', 'status' => 1]);
        Setting::create(['id' => 2, 'title' => 'Test Hotel', 'servicecharge' => 10, 'splash_logo' => '', 'timezone' => 'UTC', 'checkintime' => '14:00', 'checkouttime' => '12:00', 'dateformat' => 'Y-m-d']);
        TblTaxmgt::create(['taxname' => 'VAT', 'rate' => 5, 'isactive' => 1]);
        foreach ([[1, 'Card Payment', 0], [3, 'Paypal', 1], [4, 'Cash Payment', 1]] as [$id, $name, $active]) {
            PaymentMethod::create(['payment_method_id' => $id, 'payment_method' => $name, 'is_active' => $active]);
        }

        $this->room = Roomdetails::create([
            'roomtype' => 'Deluxe', 'roomsize' => 300, 'roomsizemesurement' => 'sqft', 'roomactive' => 1, 'bedsno' => 1, 'bedstype' => 1,
            'roomdescription' => 'Nice room', 'capacity' => 2, 'rate' => 100, 'bedcharge' => 0, 'personcharge' => 0,
        ]);
        foreach ([101, 102] as $no) {
            TblRoomnofloorassign::create(['roomid' => $this->room->roomid, 'floorid' => 1, 'roomno' => $no, 'status' => 1]);
        }

        // Legacy accounts carry unsalted MD5 passwords.
        $this->guest = Customerinfo::create(['firstname' => 'Ada', 'lastname' => 'Guest', 'email' => 'ada@example.com', 'cust_phone' => '0170000001', 'pass' => md5('secret12'), 'balance' => 0, 'active' => 1]);
        $this->staff = User::create(['firstname' => 'Sam', 'lastname' => 'Staff', 'email' => 'staff@example.com', 'password' => md5('staffpass'), 'status' => 1, 'usertype' => 1, 'is_admin' => 1]);
        $this->staff->assignRole('Super Admin');
    }

    protected function stay(int $inDays = 10, int $nights = 2): array
    {
        return ['checkin' => now()->addDays($inDays)->toDateString(), 'checkout' => now()->addDays($inDays + $nights)->toDateString()];
    }

    protected function bookingPayload(array $overrides = []): array
    {
        return array_merge($this->stay(), ['room' => $this->room->roomid, 'rooms' => 1, 'adults' => 2, 'children' => 0], $overrides);
    }
}
