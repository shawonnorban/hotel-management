<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmationMail;
use App\Mail\NewBookingAlertMail;
use App\Models\BookedInfo;
use App\Models\OnlinePayment;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\TblGuestpayments;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class OnlinePaymentTest extends HotelTestCase
{
    private BookedInfo $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);

        PaymentMethod::firstOrCreate(['payment_method_id' => 5], ['payment_method' => 'SSLCommerz', 'is_active' => 1]);
        PaymentMethod::firstOrCreate(['payment_method_id' => 7], ['payment_method' => 'Stripe', 'is_active' => 1]);
        PaymentMethod::whereKey([3, 5, 7])->update(['is_active' => 1, 'ledger_account_id' => \App\Models\LedgerAccount::where('system_key', 'online')->value('id')]);

        PaymentGateway::create(['driver' => 'stripe', 'payment_method_id' => 7, 'currency' => 'USD', 'credentials' => ['secret_key' => 'sk_test_x', 'webhook_secret' => 'whsec_x']]);
        PaymentGateway::create(['driver' => 'paypal', 'payment_method_id' => 3, 'currency' => 'USD', 'credentials' => ['client_id' => 'cid', 'client_secret' => 'csec']]);
        PaymentGateway::create(['driver' => 'sslcommerz', 'payment_method_id' => 5, 'currency' => 'BDT', 'credentials' => ['store_id' => 'store1', 'store_password' => 'pw']]);

        $this->actingAs($this->guest, 'customer')->post('/book', $this->bookingPayload());
        $this->booking = BookedInfo::firstOrFail();
        Mail::fake();
    }

    private function payUrl(): string
    {
        return '/checkout/'.$this->booking->booking_number;
    }

    private function fakeStripeStart(): void
    {
        Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_1', 'url' => 'https://checkout.stripe.test/pay/cs_test_1'])]);
    }

    private function stripeSession(array $overrides = []): array
    {
        $payment = OnlinePayment::firstOrFail();

        return array_replace_recursive(['id' => 'cs_test_1', 'payment_status' => 'paid', 'amount_total' => 23000, 'currency' => 'usd', 'metadata' => ['payment' => (string) $payment->id, 'booking' => $this->booking->booking_number]], $overrides);
    }

    public function test_checkout_marks_configured_gateways_as_online(): void
    {
        $this->get($this->payUrl())->assertOk()->assertSee('Pay online now')->assertSee('Stripe');
    }

    public function test_stripe_payment_flow(): void
    {
        $this->fakeStripeStart();

        $this->post($this->payUrl(), ['method' => 7])->assertRedirect('https://checkout.stripe.test/pay/cs_test_1');

        $payment = OnlinePayment::firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('230.00', $payment->amount);
        $this->assertSame('cs_test_1', $payment->reference);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'checkout/sessions') && $r['line_items[0][price_data][unit_amount]'] == 23000 && $r['metadata[payment]'] == $payment->id);

        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_1' => Http::response($this->stripeSession())]);
        $this->get('/payments/stripe/return?session_id=cs_test_1&payment='.$payment->id)->assertOk()->assertSee('Payment successful');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('230.00', $this->booking->fresh()->paid_amount);
        $this->assertSame('2', (string) $this->booking->fresh()->bookingstatus);
        $this->assertSame(230.0, app(LedgerService::class)->balance('online'));
        $this->assertSame(230.0, app(LedgerService::class)->balance('guest_deposits'));
        Mail::assertSent(BookingConfirmationMail::class, fn ($m) => $m->hasTo('ada@example.com') && $m->kind === 'paid');
    }

    public function test_returning_twice_never_records_the_money_twice(): void
    {
        $this->fakeStripeStart();
        $this->post($this->payUrl(), ['method' => 7]);
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_1' => Http::response($this->stripeSession())]);

        $this->get('/payments/stripe/return?session_id=cs_test_1')->assertOk();
        $this->get('/payments/stripe/return?session_id=cs_test_1')->assertOk();

        $this->assertSame(1, TblGuestpayments::count());
        $this->assertSame('230.00', $this->booking->fresh()->paid_amount);
    }

    public function test_unpaid_wrong_amount_or_foreign_sessions_are_rejected(): void
    {
        $this->fakeStripeStart();
        $this->post($this->payUrl(), ['method' => 7]);

        foreach ([['payment_status' => 'unpaid'], ['amount_total' => 100], ['currency' => 'eur'], ['metadata' => ['payment' => '999']]] as $case) {
            OnlinePayment::query()->update(['status' => 'pending']);
            Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_1' => Http::response($this->stripeSession($case))]);

            $this->get('/payments/stripe/return?session_id=cs_test_1')->assertOk()->assertSee('Payment not completed');
            $this->assertSame('failed', OnlinePayment::firstOrFail()->status, json_encode($case));
            $this->assertSame('0.00', $this->booking->fresh()->paid_amount);
        }
        $this->assertSame(0, TblGuestpayments::count());
    }

    public function test_unknown_references_are_not_found(): void
    {
        $this->get('/payments/stripe/return?session_id=nope')->assertNotFound();
        $this->get('/payments/stripe/return')->assertNotFound();
    }

    public function test_cancelled_payment_is_marked_and_nothing_is_charged(): void
    {
        $this->fakeStripeStart();
        $this->post($this->payUrl(), ['method' => 7]);
        $payment = OnlinePayment::firstOrFail();

        $this->get('/payments/stripe/cancel?payment='.$payment->id)->assertOk()->assertSee('cancelled');

        $this->assertSame('cancelled', $payment->fresh()->status);
        $this->assertSame('0.00', $this->booking->fresh()->paid_amount);
    }

    public function test_provider_outage_shows_a_friendly_error(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);

        $this->post($this->payUrl(), ['method' => 7])->assertSessionHasErrors('method');
        $this->assertSame('failed', OnlinePayment::firstOrFail()->status);
    }

    public function test_stripe_webhook_requires_a_valid_signature(): void
    {
        $this->fakeStripeStart();
        $this->post($this->payUrl(), ['method' => 7]);
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_1' => Http::response($this->stripeSession())]);

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['id' => 'cs_test_1']]]);
        $sign = fn (int $t, string $secret = 'whsec_x') => "t=$t,v1=".hash_hmac('sha256', $t.'.'.$payload, $secret);
        $post = fn ($sig) => $this->call('POST', '/payments/stripe/notify', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $payload);

        $post($sign(time(), 'wrong'))->assertStatus(400);
        $post($sign(time() - 3600))->assertStatus(400);
        $post('garbage')->assertStatus(400);
        $this->assertSame('0.00', $this->booking->fresh()->paid_amount);

        $post($sign(time()))->assertOk();
        $this->assertSame('230.00', $this->booking->fresh()->paid_amount);
    }

    public function test_paypal_payment_flow(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response(['id' => 'ORDER1', 'links' => [['rel' => 'approve', 'href' => 'https://paypal.test/approve/ORDER1']]]),
        ]);

        $this->post($this->payUrl(), ['method' => 3])->assertRedirect('https://paypal.test/approve/ORDER1');
        $payment = OnlinePayment::firstOrFail();
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v2/checkout/orders') && $r['purchase_units'][0]['amount']['value'] === '230.00' && $r['purchase_units'][0]['reference_id'] == (string) $payment->id);

        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER1/capture' => Http::response(['status' => 'COMPLETED', 'purchase_units' => [['reference_id' => (string) $payment->id, 'payments' => ['captures' => [['id' => 'CAP1', 'status' => 'COMPLETED', 'amount' => ['value' => '230.00', 'currency_code' => 'USD']]]]]]]),
        ]);
        $this->get('/payments/paypal/return?token=ORDER1&PayerID=X&payment='.$payment->id)->assertOk()->assertSee('Payment successful');

        $this->assertSame('230.00', $this->booking->fresh()->paid_amount);
        $this->assertStringContainsString('CAP1', TblGuestpayments::firstOrFail()->details);
    }

    public function test_paypal_rejects_incomplete_or_mismatched_captures(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response(['id' => 'ORDER1', 'links' => [['rel' => 'approve', 'href' => 'https://paypal.test/a']]]),
        ]);
        $this->post($this->payUrl(), ['method' => 3]);
        $payment = OnlinePayment::firstOrFail();

        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER1/capture' => Http::response(['status' => 'COMPLETED', 'purchase_units' => [['reference_id' => (string) $payment->id, 'payments' => ['captures' => [['id' => 'C', 'status' => 'COMPLETED', 'amount' => ['value' => '1.00', 'currency_code' => 'USD']]]]]]]),
        ]);
        $this->get('/payments/paypal/return?token=ORDER1')->assertSee('Payment not completed');
        $this->assertSame('0.00', $this->booking->fresh()->paid_amount);
    }

    public function test_sslcommerz_payment_flow(): void
    {
        Http::fake(['sandbox.sslcommerz.com/gwprocess/v4/api.php' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://sandbox.sslcommerz.test/pay/1'])]);

        $this->post($this->payUrl(), ['method' => 5])->assertRedirect('https://sandbox.sslcommerz.test/pay/1');
        $payment = OnlinePayment::firstOrFail();
        $this->assertSame('BK'.$payment->id, $payment->reference);
        $this->assertSame('BDT', $payment->currency);

        Http::fake(['sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response(['status' => 'VALID', 'tran_id' => 'BK'.$payment->id, 'amount' => '230.00', 'currency_amount' => '230.00', 'currency_type' => 'BDT', 'bank_tran_id' => 'BANK9'])]);
        $this->post('/payments/sslcommerz/return?payment='.$payment->id, ['tran_id' => 'BK'.$payment->id, 'val_id' => 'V1', 'status' => 'VALID'])->assertOk()->assertSee('Payment successful');

        $this->assertSame('230.00', $this->booking->fresh()->paid_amount);
    }

    public function test_sslcommerz_forged_callbacks_are_rejected(): void
    {
        Http::fake(['sandbox.sslcommerz.com/gwprocess/v4/api.php' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://x.test'])]);
        $this->post($this->payUrl(), ['method' => 5]);
        $payment = OnlinePayment::firstOrFail();

        // Browser says VALID, the validator says it is not.
        Http::fake(['sandbox.sslcommerz.com/validator/*' => Http::response(['status' => 'INVALID_TRANSACTION', 'tran_id' => 'BK'.$payment->id])]);
        $this->post('/payments/sslcommerz/return', ['tran_id' => 'BK'.$payment->id, 'val_id' => 'V1', 'status' => 'VALID'])->assertSee('Payment not completed');
        // A valid validation of a different transaction.
        OnlinePayment::query()->update(['status' => 'pending']);
        Http::fake(['sandbox.sslcommerz.com/validator/*' => Http::response(['status' => 'VALID', 'tran_id' => 'BK999', 'currency_amount' => '230.00', 'currency_type' => 'BDT'])]);
        $this->post('/payments/sslcommerz/return', ['tran_id' => 'BK'.$payment->id, 'val_id' => 'V2'])->assertSee('Payment not completed');
        // No val_id at all.
        $this->post('/payments/sslcommerz/return', ['tran_id' => 'BK'.$payment->id])->assertSee('Payment not completed');

        $this->assertSame('0.00', $this->booking->fresh()->paid_amount);
    }

    public function test_money_arriving_after_the_booking_was_cancelled_is_flagged_not_lost(): void
    {
        $this->fakeStripeStart();
        $this->post($this->payUrl(), ['method' => 7]);
        BookedInfo::query()->update(['bookingstatus' => '1']);
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_1' => Http::response($this->stripeSession())]);

        $this->get('/payments/stripe/return?session_id=cs_test_1')->assertOk()->assertSee('needs attention');

        $payment = OnlinePayment::firstOrFail();
        $this->assertSame('paid', $payment->status);
        $this->assertStringContainsString('refund required', $payment->failure);
        $this->assertSame('0.00', $this->booking->fresh()->paid_amount);
    }

    public function test_credentials_are_encrypted_at_rest(): void
    {
        $raw = DB::table('payment_gateways')->where('driver', 'stripe')->value('credentials');

        $this->assertStringNotContainsString('sk_test_x', $raw);
        $this->assertSame('sk_test_x', PaymentGateway::where('driver', 'stripe')->first()->credential('secret_key'));
    }

    public function test_offline_checkout_sends_the_guest_a_confirmation_with_the_invoice_and_alerts_the_hotel(): void
    {
        \App\Models\Setting::query()->update(['email' => 'front@hotel.test']);
        \App\Support\Settings::flush();

        $this->post($this->payUrl(), ['method' => 4])->assertRedirect();

        Mail::assertSent(BookingConfirmationMail::class, fn ($m) => $m->hasTo('ada@example.com') && $m->kind === 'received');
        Mail::assertSent(NewBookingAlertMail::class, fn ($m) => $m->hasTo('front@hotel.test'));
    }

    public function test_gateway_settings_screen_keeps_blank_secrets_and_is_permission_protected(): void
    {
        $this->actingAs($this->staff, 'admin');
        $this->get('/admin/settings/payment-gateways')->assertOk()->assertSee('Stripe')->assertDontSee('sk_test_x');

        $this->put('/admin/settings/payment-gateways/stripe', ['payment_method_id' => 7, 'currency' => 'eur', 'live' => 1, 'credentials' => ['secret_key' => '', 'webhook_secret' => 'whsec_new']])->assertSessionHasNoErrors();

        $g = PaymentGateway::where('driver', 'stripe')->first();
        $this->assertSame('sk_test_x', $g->credential('secret_key'));
        $this->assertSame('whsec_new', $g->credential('webhook_secret'));
        $this->assertSame('EUR', $g->currency);
        $this->assertTrue($g->live);

        $desk = \App\Models\User::create(['firstname' => 'F', 'lastname' => 'D', 'email' => 'd3@example.com', 'password' => \Illuminate\Support\Facades\Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $desk->assignRole('Front Desk');
        $this->actingAs($desk, 'admin')->get('/admin/settings/payment-gateways')->assertForbidden();
    }
}
