<?php

namespace Tests\Feature;

use App\Models\Legacy\BookedInfo;
use App\Models\Legacy\Customerinfo;

class GuestBookingTest extends HotelTestCase
{
    public function test_public_pages_load(): void
    {
        $this->get('/')->assertOk()->assertSee('Test Hotel')->assertSee('Deluxe');
        $this->get('/rooms')->assertOk();
        $this->get('/rooms/'.$this->room->roomid)->assertOk()->assertSee('Deluxe');
    }

    public function test_guest_can_register_and_sign_in_with_legacy_compatible_password(): void
    {
        $this->post('/register', [
            'firstname' => 'New', 'lastname' => 'Guest', 'email' => 'New@Example.com', 'phone' => '0180000002',
            'password' => 'password1', 'password_confirmation' => 'password1', 'terms' => '1',
        ])->assertRedirect('/');

        $guest = Customerinfo::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame(md5('password1'), $guest->pass);
        $this->assertAuthenticatedAs($guest, 'customer');
    }

    public function test_registration_rejects_duplicate_email_and_phone(): void
    {
        $this->post('/register', [
            'firstname' => 'Dup', 'lastname' => 'Guest', 'email' => 'ada@example.com', 'phone' => '0170000001',
            'password' => 'password1', 'password_confirmation' => 'password1', 'terms' => '1',
        ])->assertSessionHasErrors(['email', 'phone']);
    }

    public function test_legacy_md5_guest_can_sign_in_and_wrong_password_fails(): void
    {
        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest('customer');

        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'secret12'])->assertRedirect('/');
        $this->assertAuthenticatedAs($this->guest, 'customer');
    }

    public function test_deactivated_guest_cannot_sign_in(): void
    {
        $this->guest->update(['active' => 0]);

        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'secret12'])->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    public function test_booking_requires_sign_in(): void
    {
        $this->post('/book', $this->bookingPayload())->assertRedirect('/login');
        $this->get('/my-bookings')->assertRedirect('/login');
    }

    public function test_availability_is_shown_for_dates(): void
    {
        $this->get('/rooms?'.http_build_query($this->stay()))->assertOk()->assertSee('2 room(s) available');
    }

    public function test_full_booking_flow_with_cash_payment(): void
    {
        $this->actingAs($this->guest, 'customer');

        $response = $this->post('/book', $this->bookingPayload(['guest' => 'Ada L', 'special' => 'Late arrival']));
        $booking = BookedInfo::firstOrFail();
        $response->assertRedirect('/checkout/'.$booking->booking_number);

        // 100 × 2 nights = 200, + 5% tax (10) + 10% service charge (20)
        $this->assertSame('230.00', $booking->total_price);
        $this->assertSame('0', (string) $booking->bookingstatus);
        $this->assertSame('101', $booking->room_no);
        $this->assertSame($this->guest->customerid, (int) $booking->cutomerid);
        $this->assertSame('Ada L', $booking->full_guest_name);
        $this->assertCount(1, $booking->details);

        $this->get('/checkout/'.$booking->booking_number)->assertOk()->assertSee('Cash Payment');
        $this->post('/checkout/'.$booking->booking_number, ['method' => 4])->assertRedirect('/my-bookings/'.$booking->booking_number);

        $this->assertSame('Cash Payment', $booking->details()->first()->payment_method);
        $this->get('/my-bookings')->assertOk()->assertSee($booking->booking_number);
        $this->get('/my-bookings/'.$booking->booking_number)->assertOk()->assertSee('Late arrival');
    }

    public function test_price_is_computed_on_the_server(): void
    {
        $this->actingAs($this->guest, 'customer');

        $this->post('/book', $this->bookingPayload(['amount' => 1, 'total_price' => 1, 'rate' => 1]));

        $this->assertSame('230.00', BookedInfo::firstOrFail()->total_price);
    }

    public function test_rooms_cannot_be_double_booked(): void
    {
        $this->actingAs($this->guest, 'customer');

        $this->post('/book', $this->bookingPayload())->assertRedirect();
        $this->post('/book', $this->bookingPayload())->assertRedirect();
        $this->post('/book', $this->bookingPayload())->assertSessionHasErrors('booking');

        $this->assertSame(2, BookedInfo::count());
        $this->assertEqualsCanonicalizing(['101', '102'], BookedInfo::pluck('room_no')->all());
    }

    public function test_cancelled_booking_frees_the_room(): void
    {
        $this->actingAs($this->guest, 'customer');
        $this->post('/book', $this->bookingPayload(['rooms' => 2]))->assertRedirect();
        $this->post('/book', $this->bookingPayload())->assertSessionHasErrors('booking');

        BookedInfo::query()->update(['bookingstatus' => '1']);

        $this->post('/book', $this->bookingPayload())->assertRedirect();
    }

    public function test_party_larger_than_capacity_is_rejected(): void
    {
        $this->actingAs($this->guest, 'customer');

        $this->post('/book', $this->bookingPayload(['adults' => 3]))->assertSessionHasErrors('booking');
        $this->assertSame(0, BookedInfo::count());
    }

    public function test_past_dates_and_bad_ranges_are_rejected(): void
    {
        $this->actingAs($this->guest, 'customer');

        $this->post('/book', $this->bookingPayload(['checkin' => now()->subDay()->toDateString()]))->assertSessionHasErrors('checkin');
        $this->post('/book', $this->bookingPayload(['checkout' => now()->addDays(5)->toDateString()]))->assertSessionHasErrors('checkout');
    }

    public function test_online_gateways_are_not_offered_yet(): void
    {
        $this->actingAs($this->guest, 'customer');
        $this->post('/book', $this->bookingPayload());
        $booking = BookedInfo::firstOrFail();

        $this->post('/checkout/'.$booking->booking_number, ['method' => 3])->assertSessionHasErrors('method');
        $this->post('/checkout/'.$booking->booking_number, ['method' => 1])->assertSessionHasErrors('method'); // inactive
    }

    public function test_a_guest_cannot_see_someone_elses_booking(): void
    {
        $this->actingAs($this->guest, 'customer');
        $this->post('/book', $this->bookingPayload());
        $booking = BookedInfo::firstOrFail();

        $other = Customerinfo::create(['firstname' => 'Eve', 'lastname' => 'Other', 'email' => 'eve@example.com', 'cust_phone' => '0170000009', 'pass' => md5('x'), 'balance' => 0, 'active' => 1]);
        $this->actingAs($other, 'customer');

        $this->get('/my-bookings/'.$booking->booking_number)->assertNotFound();
        $this->get('/checkout/'.$booking->booking_number)->assertNotFound();
        $this->post('/checkout/'.$booking->booking_number, ['method' => 4])->assertNotFound();
    }
}
