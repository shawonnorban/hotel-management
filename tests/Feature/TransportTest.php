<?php

namespace Tests\Feature;

use App\Models\TrFlight;
use App\Models\TrVehicle;
use App\Models\TrVehicleBooking;

class TransportTest extends HotelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->staff, 'admin');
    }

    public function test_vehicles_flights_and_bookings_with_pricing_and_clash_check(): void
    {
        $this->post('/admin/tr-vehicles', ['reg_no' => 'DHK-1', 'type' => 'Car', 'seats' => 4, 'base_fare' => 10, 'rate_per_km' => 2, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->post('/admin/tr-vehicles', ['reg_no' => 'DHK-1', 'type' => 'Car', 'seats' => 4])->assertSessionHasErrors('reg_no');
        $car = TrVehicle::firstOrFail();

        $this->post('/admin/tr-flights', ['guest_name' => 'Ada', 'direction' => 'arrival', 'flight_no' => 'BG101', 'flight_at' => '2026-05-01 10:00'])->assertRedirect()->assertSessionHasNoErrors();
        $flight = TrFlight::firstOrFail();

        $trip = ['vehicle_id' => $car->id, 'flight_id' => $flight->id, 'guest_name' => 'Ada', 'pickup_at' => '2026-05-01 10:30', 'pickup_location' => 'Airport', 'drop_location' => 'Hotel', 'distance_km' => 20, 'status' => 'booked'];
        $this->post('/admin/tr-vehicle-bookings', $trip)->assertSessionHasNoErrors();
        $this->assertSame('50.00', TrVehicleBooking::firstOrFail()->amount); // 10 + 2 × 20

        // Same car, overlapping hours.
        $this->post('/admin/tr-vehicle-bookings', array_merge($trip, ['guest_name' => 'Bob', 'pickup_at' => '2026-05-01 12:00']))->assertSessionHasErrors('vehicle_id');
        $this->post('/admin/tr-vehicle-bookings', array_merge($trip, ['guest_name' => 'Bob', 'pickup_at' => '2026-05-01 15:00']))->assertSessionHasNoErrors();
        $this->assertSame(2, TrVehicleBooking::count());

        foreach (['tr-vehicles', 'tr-flights', 'tr-vehicle-bookings'] as $slug) {
            $this->get("/admin/$slug")->assertOk();
        }
        $this->get('/admin/tr-vehicle-bookings')->assertSee('DHK-1');
        $this->delete('/admin/tr-vehicles/'.$car->id)->assertSessionHasErrors();
        $this->delete('/admin/tr-flights/'.$flight->id)->assertSessionHasErrors();
    }
}
