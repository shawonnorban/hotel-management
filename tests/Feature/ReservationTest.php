<?php

namespace Tests\Feature;

use App\Models\BookedInfo;
use App\Models\Customerinfo;
use App\Models\FolioCharge;
use App\Models\PaymentMethod;
use App\Models\Promocode;
use App\Models\TblGuestpayments;
use App\Services\LedgerService;

class ReservationTest extends HotelTestCase
{
    private LedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledger = app(LedgerService::class);
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class); // links payment methods to accounts
        $this->actingAs($this->staff, 'admin');
    }

    private function create(array $overrides = []): BookedInfo
    {
        $this->post('/admin/reservations', array_merge($this->stay(0, 2), [
            'room' => $this->room->roomid, 'rooms' => 1, 'adults' => 2, 'children' => 0,
            'guest_id' => $this->guest->customerid, 'source' => 'phone',
        ], $overrides))->assertSessionHasNoErrors();

        return BookedInfo::orderByDesc('bookedid')->firstOrFail();
    }

    private function cash(): PaymentMethod
    {
        return PaymentMethod::where('payment_method', 'Cash Payment')->firstOrFail();
    }

    public function test_staff_can_create_a_confirmed_booking_with_a_new_guest_and_deposit(): void
    {
        $this->post('/admin/reservations', array_merge($this->stay(0, 2), [
            'room' => $this->room->roomid, 'rooms' => 1, 'adults' => 2, 'source' => 'walk-in',
            'new_firstname' => 'Walk', 'new_lastname' => 'In', 'new_phone' => '0188888888', 'deposit' => 100, 'deposit_method' => $this->cash()->payment_method_id,
        ]))->assertRedirect();

        $booking = BookedInfo::firstOrFail();
        $this->assertSame('2', (string) $booking->bookingstatus);
        $this->assertSame('walk-in', $booking->source);
        $this->assertSame('230.00', $booking->total_price);
        $this->assertSame('100.00', $booking->paid_amount);
        $this->assertSame(130.0, $booking->balance);
        $this->assertSame('Walk', $booking->customer->firstname);

        // The deposit is cash in hand and a liability to the guest.
        $this->assertSame(100.0, $this->ledger->balance('cash'));
        $this->assertSame(100.0, $this->ledger->balance('guest_deposits'));
        $this->assertEqualsCanonicalizing(['created', 'payment'], $booking->events->pluck('event')->all());
    }

    public function test_new_guest_needs_contact_details_and_unique_phone(): void
    {
        $this->post('/admin/reservations', array_merge($this->stay(0, 2), ['room' => $this->room->roomid, 'rooms' => 1, 'adults' => 2, 'source' => 'phone']))->assertSessionHasErrors(['new_firstname', 'new_phone']);
        $this->post('/admin/reservations', array_merge($this->stay(0, 2), ['room' => $this->room->roomid, 'rooms' => 1, 'adults' => 2, 'source' => 'phone', 'new_firstname' => 'X', 'new_phone' => $this->guest->cust_phone]))->assertSessionHasErrors('new_phone');
    }

    public function test_staff_cannot_double_book_or_overfill(): void
    {
        $this->create(['rooms' => 2, 'adults' => 4]);

        $this->post('/admin/reservations', array_merge($this->stay(0, 2), ['room' => $this->room->roomid, 'rooms' => 1, 'adults' => 2, 'guest_id' => $this->guest->customerid, 'source' => 'phone']))->assertSessionHasErrors('booking');
        $this->post('/admin/reservations', array_merge($this->stay(20, 2), ['room' => $this->room->roomid, 'rooms' => 1, 'adults' => 5, 'guest_id' => $this->guest->customerid, 'source' => 'phone']))->assertSessionHasErrors('booking');
    }

    public function test_quote_endpoint_returns_price_and_availability(): void
    {
        $r = $this->getJson('/admin/reservations/quote?'.http_build_query(array_merge($this->stay(5, 3), ['room' => $this->room->roomid, 'rooms' => 2])))->assertOk();

        $r->assertJsonPath('subtotal', 600)->assertJsonPath('available', 2)->assertJsonPath('nights', 3)->assertJsonPath('total', 690);
    }

    public function test_full_lifecycle_posts_revenue_split_to_the_ledger(): void
    {
        $booking = $this->create(['deposit' => 230, 'deposit_method' => $this->cash()->payment_method_id]);
        $url = "/admin/reservations/{$booking->booking_number}";

        $this->post("$url/check-in")->assertSessionHasNoErrors();
        $this->assertSame('4', (string) $booking->fresh()->bookingstatus);
        $this->post("$url/check-out")->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('5', (string) $booking->bookingstatus);
        $this->assertNotNull($booking->revenue_entry_id);
        $this->assertSame(200.0, $this->ledger->balance('room_revenue'));
        $this->assertSame(10.0, $this->ledger->balance('tax_payable'));
        $this->assertSame(20.0, $this->ledger->balance('service_income'));
        $this->assertSame(0.0, $this->ledger->balance('guest_deposits'));
        $this->assertSame(0.0, $this->ledger->balance('receivable'));
        $this->assertSame(230.0, $this->ledger->balance('cash'));

        $tb = $this->ledger->trialBalance(today());
        $this->assertSame($tb['debit'], $tb['credit']);
    }

    public function test_cannot_check_in_before_arrival(): void
    {
        $booking = $this->create($this->stay(3, 2));

        $this->post("/admin/reservations/{$booking->booking_number}/check-in")->assertSessionHasErrors('action');
        $this->assertSame('2', (string) $booking->fresh()->bookingstatus);
    }

    public function test_checkout_requires_settlement_unless_left_on_account(): void
    {
        $booking = $this->create();
        $url = "/admin/reservations/{$booking->booking_number}";
        $this->post("$url/check-in");

        $this->post("$url/check-out")->assertSessionHasErrors('action');
        $this->assertSame('4', (string) $booking->fresh()->bookingstatus);

        $this->post("$url/check-out", ['on_account' => 1])->assertSessionHasNoErrors();
        $this->assertSame(230.0, $this->ledger->balance('receivable'));

        // Paying the account after check-out clears the receivable (not the deposits).
        $this->post("$url/payments", ['amount' => 230, 'method' => $this->cash()->payment_method_id])->assertSessionHasNoErrors();
        $this->assertSame(0.0, $this->ledger->balance('receivable'));
        $this->assertSame(0.0, $this->ledger->balance('guest_deposits'));
        $this->assertSame(230.0, $this->ledger->balance('cash'));
        $this->assertSame(0.0, $booking->fresh()->balance);
    }

    public function test_payments_cannot_exceed_the_balance(): void
    {
        $booking = $this->create();
        $url = "/admin/reservations/{$booking->booking_number}/payments";

        $this->post($url, ['amount' => 500, 'method' => $this->cash()->payment_method_id])->assertSessionHasErrors('action');
        $this->post($url, ['amount' => 100, 'method' => $this->cash()->payment_method_id])->assertSessionHasNoErrors();
        $this->assertSame('100.00', $booking->fresh()->paid_amount);
        $this->assertSame(1, TblGuestpayments::count());
    }

    public function test_payment_method_decides_the_receiving_account(): void
    {
        $booking = $this->create();
        $bank = PaymentMethod::create(['payment_method_id' => 6, 'payment_method' => 'Bank Payment', 'is_active' => 1, 'ledger_account_id' => \App\Models\LedgerAccount::where('system_key', 'bank')->value('id')]);

        $this->post("/admin/reservations/{$booking->booking_number}/payments", ['amount' => 50, 'method' => $bank->payment_method_id, 'reference' => 'TRX-1'])->assertSessionHasNoErrors();

        $this->assertSame(50.0, $this->ledger->balance('bank'));
        $this->assertSame(0.0, $this->ledger->balance('cash'));
        $this->assertStringContainsString('TRX-1', TblGuestpayments::firstOrFail()->details);
    }

    public function test_refund_returns_money_and_is_limited_to_what_was_paid(): void
    {
        $booking = $this->create(['deposit' => 100, 'deposit_method' => $this->cash()->payment_method_id]);
        $url = "/admin/reservations/{$booking->booking_number}/refunds";

        $this->post($url, ['amount' => 150, 'method' => $this->cash()->payment_method_id])->assertSessionHasErrors('action');
        $this->post($url, ['amount' => 40, 'method' => $this->cash()->payment_method_id, 'reason' => 'Goodwill'])->assertSessionHasNoErrors();

        $this->assertSame('60.00', $booking->fresh()->paid_amount);
        $this->assertSame(60.0, $this->ledger->balance('cash'));
        $this->assertSame(60.0, $this->ledger->balance('guest_deposits'));
        $this->assertSame('-40.00', TblGuestpayments::orderByDesc('payid')->first()->paymentamount);
    }

    public function test_cancelling_with_partial_refund_keeps_the_rest_as_income(): void
    {
        $booking = $this->create(['deposit' => 100, 'deposit_method' => $this->cash()->payment_method_id]);

        $this->post("/admin/reservations/{$booking->booking_number}/cancel", ['refund' => 70, 'method' => $this->cash()->payment_method_id, 'reason' => 'Changed plans'])->assertSessionHasNoErrors();

        $this->assertSame('1', (string) $booking->fresh()->bookingstatus);
        $this->assertSame(30.0, $this->ledger->balance('cash'));
        $this->assertSame(30.0, $this->ledger->balance('other_income'));
        $this->assertSame(0.0, $this->ledger->balance('guest_deposits'));
    }

    public function test_cancelled_booking_releases_the_rooms_and_cannot_be_cancelled_twice(): void
    {
        $booking = $this->create(['rooms' => 2, 'adults' => 4]);
        $this->post("/admin/reservations/{$booking->booking_number}/cancel")->assertSessionHasNoErrors();

        $this->post("/admin/reservations/{$booking->booking_number}/cancel")->assertSessionHasErrors('action');
        $this->post('/admin/reservations', array_merge($this->stay(0, 2), ['room' => $this->room->roomid, 'rooms' => 2, 'adults' => 4, 'guest_id' => $this->guest->customerid, 'source' => 'phone']))->assertSessionHasNoErrors();
    }

    public function test_charges_change_the_total_and_are_recognised_as_extras_revenue(): void
    {
        $booking = $this->create();
        $url = "/admin/reservations/{$booking->booking_number}";

        $this->post("$url/charges", ['description' => 'Laundry', 'amount' => 20])->assertSessionHasNoErrors();
        $this->assertSame('250.00', $booking->fresh()->total_price);

        $charge = FolioCharge::firstOrFail();
        $this->post("$url/charges", ['description' => 'Minibar', 'amount' => 5]);
        $this->delete("$url/charges/{$charge->id}")->assertSessionHasNoErrors();
        $this->assertSame('235.00', $booking->fresh()->total_price);

        $this->post("$url/check-in");
        $this->post("$url/check-out", ['on_account' => 1]);
        $this->assertSame(5.0, $this->ledger->balance('extras_revenue'));
        $this->assertSame(200.0, $this->ledger->balance('room_revenue'));
        $this->assertSame(235.0, $this->ledger->balance('receivable'));
    }

    public function test_charges_cannot_be_added_to_closed_bookings(): void
    {
        $booking = $this->create();
        $this->post("/admin/reservations/{$booking->booking_number}/cancel");

        $this->post("/admin/reservations/{$booking->booking_number}/charges", ['description' => 'Late', 'amount' => 5])->assertSessionHasErrors('action');
    }

    public function test_changing_dates_and_rooms_reprices_the_booking(): void
    {
        $booking = $this->create();
        $change = array_merge($this->stay(0, 3), ['room' => $this->room->roomid, 'rooms' => 2, 'adults' => 3, 'children' => 0, 'guest_name' => 'Ada L', 'special' => 'Quiet']);

        $this->put("/admin/reservations/{$booking->booking_number}", $change)->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('690.00', $booking->total_price);
        $this->assertSame(2, (int) $booking->total_room);
        $this->assertEqualsCanonicalizing(['101', '102'], explode(',', $booking->room_no));
        $this->assertSame('Quiet', $booking->special_request);
        $this->assertSame(3, $booking->nights);
    }

    public function test_changing_a_booking_cannot_steal_another_guests_room(): void
    {
        $this->create(['rooms' => 1]);
        $second = $this->create(array_merge($this->stay(0, 2), ['rooms' => 1]));

        $this->put("/admin/reservations/{$second->booking_number}", array_merge($this->stay(0, 2), ['room' => $this->room->roomid, 'rooms' => 3, 'adults' => 2]))->assertSessionHasErrors('booking');
    }

    public function test_checked_in_bookings_cannot_be_edited(): void
    {
        $booking = $this->create();
        $this->post("/admin/reservations/{$booking->booking_number}/check-in");

        $this->get("/admin/reservations/{$booking->booking_number}/edit")->assertForbidden();
    }

    public function test_promo_codes_apply_and_are_single_use(): void
    {
        Promocode::create(['roomid' => 0, 'startdate' => today()->subDay(), 'enddate' => today()->addMonth(), 'promocode' => 'SAVE10', 'discount' => 10, 'status' => 1]);

        $first = $this->create(['promo' => 'save10']);
        // 200 − 10% = 180, +5% tax 9, +10% service 18
        $this->assertSame('207.00', $first->total_price);
        $this->assertSame('SAVE10', $first->promocode);

        $this->post('/admin/reservations', array_merge($this->stay(0, 2), ['room' => $this->room->roomid, 'rooms' => 1, 'adults' => 2, 'guest_id' => $this->guest->customerid, 'source' => 'phone', 'promo' => 'SAVE10']))->assertSessionHasErrors('booking');
    }

    public function test_room_offers_discount_the_price_automatically(): void
    {
        \App\Models\TblRoomOffer::create(['roomid' => $this->room->roomid, 'offer' => 20, 'offertitle' => 'Spring', 'offer_date' => today()->addMonth()]);

        $booking = $this->create();

        // 200 − 20% = 160, +8 tax, +16 service
        $this->assertSame('184.00', $booking->total_price);
        $this->assertSame('40.00', $booking->discount_amount);
    }

    public function test_listing_filters_and_views(): void
    {
        $arriving = $this->create();
        $later = $this->create(array_merge($this->stay(30, 2), ['rooms' => 1]));
        $this->get('/admin'); // consume the "created" flash message

        $this->get('/admin/reservations?view=arrivals')->assertOk()->assertSee($arriving->booking_number)->assertDontSee($later->booking_number);
        $this->get('/admin/reservations?view=unpaid')->assertOk()->assertSee($later->booking_number);
        $this->get('/admin/reservations?q=Ada')->assertOk()->assertSee($arriving->booking_number);
        $this->get('/admin/reservations?status=5')->assertOk()->assertDontSee($arriving->booking_number);
        $this->get("/admin/reservations/{$arriving->booking_number}")->assertOk()->assertSee('Take payment')->assertSee('Check in');
        $this->get('/admin/reservations/create')->assertOk()->assertSee('New reservation');
        $this->get("/admin/reservations/{$arriving->booking_number}/edit")->assertOk();
    }

    public function test_invoice_pdf_is_generated(): void
    {
        $booking = $this->create(['deposit' => 50, 'deposit_method' => $this->cash()->payment_method_id]);

        $response = $this->get("/admin/reservations/{$booking->booking_number}/invoice")->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_front_desk_can_work_reservations_but_not_ledger(): void
    {
        $desk = \App\Models\User::create(['firstname' => 'F', 'lastname' => 'D', 'email' => 'desk2@example.com', 'password' => \Illuminate\Support\Facades\Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $desk->assignRole('Front Desk');
        $booking = $this->create();

        $this->actingAs($desk, 'admin');
        $this->post("/admin/reservations/{$booking->booking_number}/payments", ['amount' => 10, 'method' => $this->cash()->payment_method_id])->assertSessionHasNoErrors();
        $this->get('/admin/accounting/trial-balance')->assertForbidden();

        $viewer = \App\Models\User::create(['firstname' => 'V', 'lastname' => 'W', 'email' => 'view@example.com', 'password' => \Illuminate\Support\Facades\Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $viewer->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('reservations.view', 'admin'));
        $this->actingAs($viewer, 'admin');
        $this->get("/admin/reservations/{$booking->booking_number}")->assertOk();
        $this->post("/admin/reservations/{$booking->booking_number}/payments", ['amount' => 10, 'method' => $this->cash()->payment_method_id])->assertForbidden();
        $this->post("/admin/reservations/{$booking->booking_number}/check-in")->assertForbidden();
    }
}
