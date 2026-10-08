<?php

namespace Tests\Feature;

use App\Models\WhatsappMessage;
use App\Services\WhatsAppService;
use App\Support\AppSettings;
use Illuminate\Support\Facades\Http;

class WhatsAppTest extends HotelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AppSettings::flush();
        $this->actingAs($this->staff, 'admin');
    }

    public function test_numbers_are_normalised_and_links_built(): void
    {
        AppSettings::set('whatsapp.country_code', '880');
        $svc = app(WhatsAppService::class);

        $this->assertSame('8801712345678', $svc->normalize('01712-345678'));
        $this->assertSame('447700900123', $svc->normalize('+44 7700 900123'));
        $this->assertSame('447700900123', $svc->normalize('00447700900123'));
        $this->assertSame('https://wa.me/8801712345678?text=Hi%20there', $svc->link('01712345678', 'Hi there'));
        $this->expectException(\InvalidArgumentException::class);
        $svc->normalize('123');
    }

    public function test_token_is_stored_encrypted_and_never_shown_again(): void
    {
        $this->put('/admin/whatsapp/settings', ['enabled' => 1, 'phone_number_id' => '123456789', 'token' => 'SECRET-TOKEN', 'country_code' => '880'])->assertSessionHasNoErrors();

        $raw = \DB::table('app_settings')->where('key', 'whatsapp.token')->value('value');
        $this->assertNotSame('SECRET-TOKEN', $raw);
        $this->assertSame('SECRET-TOKEN', AppSettings::get('whatsapp.token'));
        $this->get('/admin/whatsapp/settings')->assertOk()->assertDontSee('SECRET-TOKEN');

        // Saving again with a blank token keeps the old one.
        $this->put('/admin/whatsapp/settings', ['enabled' => 1, 'phone_number_id' => '123456789', 'token' => ''])->assertSessionHasNoErrors();
        AppSettings::flush();
        $this->assertSame('SECRET-TOKEN', AppSettings::get('whatsapp.token'));

        // Can't switch sending on without credentials.
        \DB::table('app_settings')->whereIn('key', ['whatsapp.token', 'whatsapp.phone_number_id'])->delete();
        AppSettings::flush();
        $this->put('/admin/whatsapp/settings', ['enabled' => 1])->assertSessionHasErrors('enabled');
    }

    public function test_sending_through_the_cloud_api_is_logged(): void
    {
        $this->put('/admin/whatsapp/settings', ['enabled' => 1, 'phone_number_id' => '55555', 'token' => 'tok', 'country_code' => '880']);
        Http::fake(['graph.facebook.com/*' => Http::sequence()->push(['messages' => [['id' => 'wamid.1']]], 200)->push(['error' => ['message' => 'Bad number']], 400)]);

        $this->post('/admin/whatsapp/messages', ['to' => '01712345678', 'body' => 'Welcome'])->assertSessionHasNoErrors();
        $m = WhatsappMessage::firstOrFail();
        $this->assertSame(['8801712345678', 'sent', 'wamid.1'], [$m->to, $m->status, $m->provider_id]);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/55555/messages') && $r->hasHeader('Authorization', 'Bearer tok') && $r['text']['body'] === 'Welcome');

        $this->post('/admin/whatsapp/messages', ['to' => '01712345679', 'body' => 'Again'])->assertSessionHasErrors('to');
        $this->assertSame('failed', WhatsappMessage::orderByDesc('id')->first()->status);

        $this->get('/admin/whatsapp/messages')->assertOk()->assertSee('Welcome')->assertSee('Bad number');
    }

    public function test_sending_is_refused_while_switched_off_and_booking_page_offers_chat_link(): void
    {
        $this->post('/admin/whatsapp/messages', ['to' => '01712345678', 'body' => 'x'])->assertSessionHasErrors('to');
        $this->assertSame(0, WhatsappMessage::count());

        $this->post('/admin/reservations', array_merge($this->stay(0, 1), ['room' => $this->room->roomid, 'rooms' => 1, 'adults' => 1, 'guest_id' => $this->guest->customerid, 'source' => 'phone']))->assertSessionHasNoErrors();
        $b = \App\Models\BookedInfo::firstOrFail();
        AppSettings::set('whatsapp.country_code', '880');
        $this->get('/admin/reservations/'.$b->booking_number)->assertOk()->assertSee('https://wa.me/', false);
    }
}
