<?php

namespace Tests\Feature;

use App\Models\BookedInfo;

class AdminTest extends HotelTestCase
{
    private function makeBooking(): BookedInfo
    {
        $this->actingAs($this->guest, 'customer')->post('/book', $this->bookingPayload());
        auth('customer')->logout();

        return BookedInfo::firstOrFail();
    }

    public function test_admin_area_requires_staff_sign_in(): void
    {
        foreach (['/admin', '/admin/reservations', '/admin/rooms', '/admin/customers'] as $url) {
            $this->get($url)->assertRedirect('/admin/login');
        }
    }

    public function test_guests_cannot_use_the_admin_area(): void
    {
        $this->actingAs($this->guest, 'customer')->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_staff_with_legacy_md5_password_can_sign_in(): void
    {
        $this->post('/admin/login', ['email' => 'staff@example.com', 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->post('/admin/login', ['email' => 'staff@example.com', 'password' => 'staffpass'])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($this->staff, 'admin');
        $this->assertNotNull($this->staff->fresh()->last_login);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('staffpass', $this->staff->fresh()->password), 'MD5 hash should be upgraded on login');
    }

    public function test_disabled_or_non_staff_accounts_cannot_sign_in(): void
    {
        $this->staff->update(['status' => 0]);
        $this->post('/admin/login', ['email' => 'staff@example.com', 'password' => 'staffpass'])->assertSessionHasErrors('email');

        $this->staff->update(['status' => 1, 'usertype' => 2]);
        $this->post('/admin/login', ['email' => 'staff@example.com', 'password' => 'staffpass'])->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_dashboard_reservations_and_rooms_render(): void
    {
        $booking = $this->makeBooking();
        $this->actingAs($this->staff, 'admin');

        $this->get('/admin')->assertOk()->assertSee('Pending bookings')->assertSee($booking->booking_number);
        $this->get('/admin/reservations')->assertOk()->assertSee($booking->booking_number);
        $this->get('/admin/reservations?q='.$booking->booking_number)->assertOk()->assertSee($booking->booking_number);
        $this->get('/admin/reservations?status=5')->assertOk()->assertDontSee($booking->booking_number);
        $this->get('/admin/reservations/'.$booking->booking_number)->assertOk()->assertSee('Ada Guest');
        $this->get('/admin/rooms')->assertOk()->assertSee('101')->assertSee('102');
    }

    public function test_search_treats_wildcards_literally(): void
    {
        $this->makeBooking();
        $this->actingAs($this->staff, 'admin');

        $this->get('/admin/reservations?q=%25')->assertOk()->assertSee('No reservations');
    }
}
