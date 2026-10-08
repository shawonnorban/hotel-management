<?php

namespace Tests\Feature;

use App\Models\Hall;
use App\Models\HallBooking;
use App\Models\HallFacility;
use App\Models\HallSeatPlan;
use App\Models\HallType;
use App\Models\LedgerAccount;
use App\Services\LedgerService;

class HallTest extends HotelTestCase
{
    private Hall $hall;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->staff, 'admin');
        $type = HallType::create(['name' => 'Banquet']);
        $this->hall = Hall::create(['name' => 'Grand', 'type_id' => $type->id, 'capacity' => 200, 'rate_per_hour' => 100, 'rate_per_day' => 700]);
    }

    private function book(array $o = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/admin/hall/bookings', array_merge([
            'hall_id' => $this->hall->id, 'customer_name' => 'Acme', 'event_name' => 'Gala', 'event_date' => today()->addDays(5)->toDateString(),
            'starts_at' => '10:00', 'ends_at' => '14:00', 'guests' => 100, 'status' => 'confirmed',
        ], $o));
    }

    public function test_master_data_assigns_facilities_and_validates_seat_plans(): void
    {
        $f = HallFacility::create(['name' => 'Projector']);
        $this->post('/admin/hall-rooms', ['name' => 'Mini', 'type_id' => $this->hall->type_id, 'capacity' => 20, 'facility_ids' => [$f->id]])->assertSessionHasNoErrors();
        $mini = Hall::where('name', 'Mini')->firstOrFail();
        $this->assertSame([$f->id], $mini->facilities()->pluck('hall_facilities.id')->all());

        $this->post('/admin/hall-seat-plans', ['hall_id' => $mini->id, 'name' => 'Theatre', 'layout' => 'theatre', 'seats' => 30])->assertSessionHasErrors('seats');
        $this->post('/admin/hall-seat-plans', ['hall_id' => $mini->id, 'name' => 'Theatre', 'layout' => 'theatre', 'seats' => 20])->assertSessionHasNoErrors();
        $this->assertSame(1, HallSeatPlan::count());
        $this->delete('/admin/hall-rooms/'.$this->hall->id)->assertRedirect(); // no bookings: allowed
    }

    public function test_pricing_caps_at_the_day_rate_and_conflicts_are_blocked(): void
    {
        $this->book()->assertSessionHasNoErrors();
        $this->assertSame('400.00', HallBooking::firstOrFail()->total); // 4 h × 100

        $this->book(['starts_at' => '08:00', 'ends_at' => '23:00', 'event_name' => 'Long', 'event_date' => today()->addDays(9)->toDateString(), 'discount' => 50])->assertSessionHasNoErrors();
        $this->assertSame('650.00', HallBooking::where('event_name', 'Long')->first()->total); // capped at 700, less 50

        $this->book(['starts_at' => '13:00', 'ends_at' => '16:00', 'event_name' => 'Clash'])->assertSessionHasErrors('hall_id');
        $this->book(['starts_at' => '14:00', 'ends_at' => '16:00', 'event_name' => 'After'])->assertSessionHasNoErrors(); // back-to-back is fine
        $this->book(['guests' => 500, 'event_name' => 'Too many', 'event_date' => today()->addDays(20)->toDateString()])->assertSessionHasErrors('hall_id');
        $this->book(['event_date' => today()->subDay()->toDateString(), 'event_name' => 'Past'])->assertSessionHasErrors('event_date');
        $this->assertSame(3, HallBooking::count());

        // A late event crossing midnight blocks the early hours of the next day.
        $this->book(['starts_at' => '22:00', 'ends_at' => '02:00', 'event_name' => 'Night', 'event_date' => today()->addDays(30)->toDateString()])->assertSessionHasNoErrors();
        $this->book(['starts_at' => '01:00', 'ends_at' => '03:00', 'event_name' => 'Early', 'event_date' => today()->addDays(31)->toDateString()])->assertSessionHasErrors('hall_id');
    }

    public function test_payments_post_to_the_ledger_and_cancelling_is_guarded(): void
    {
        $this->book()->assertSessionHasNoErrors();
        $b = HallBooking::firstOrFail();
        $cash = LedgerAccount::where('system_key', 'cash')->firstOrFail();

        $this->post("/admin/hall/bookings/{$b->id}/pay", ['amount' => 500, 'account' => $cash->id, 'paid_on' => today()->toDateString()])->assertSessionHasErrors('booking');
        $this->post("/admin/hall/bookings/{$b->id}/pay", ['amount' => 150, 'account' => $cash->id, 'paid_on' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame(150.0, app(LedgerService::class)->balance('cash'));
        $this->assertSame(250.0, $b->fresh()->due);

        $this->post("/admin/hall/bookings/{$b->id}/status", ['status' => 'cancelled'])->assertSessionHasErrors('booking');
        $this->post("/admin/hall/bookings/{$b->id}/status", ['status' => 'completed'])->assertSessionHasNoErrors();

        $this->get('/admin/hall/bookings')->assertOk()->assertSee($b->number);
        $this->get("/admin/hall/bookings/{$b->id}")->assertOk()->assertSee('Gala');
        $this->get('/admin/hall/bookings/create')->assertOk();
        $this->get('/admin/hall/status?date='.$b->event_date->toDateString())->assertOk()->assertSee('Booked');
        $this->get('/admin/hall/report')->assertOk();
        $this->delete('/admin/hall-rooms/'.$this->hall->id)->assertSessionHasErrors();
    }
}
