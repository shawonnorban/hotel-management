<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\BookedInfo;
use App\Models\ContactMessage;
use App\Models\Customerinfo;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Subscriber;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Support\AppSettings;
use App\Support\Settings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class SiteTest extends HotelTestCase
{
    public function test_cms_pages_render_markdown_but_strip_raw_html(): void
    {
        Page::create(['slug' => 'about', 'title' => 'About us', 'body' => "## Hello\n\n<script>alert(1)</script>\n\n**bold** [x](javascript:alert(1))", 'show_in_menu' => true]);
        Page::create(['slug' => 'draft', 'title' => 'Draft', 'body' => 'secret', 'published' => false]);

        $html = $this->get('/page/about')->assertOk()->assertSee('About us')->getContent();

        $this->assertStringContainsString('<h2>Hello</h2>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->get('/page/draft')->assertNotFound();
        $this->get('/page/missing')->assertNotFound();
        $this->get('/')->assertSee('About us'); // menu link
    }

    public function test_contact_form_stores_the_message_and_emails_the_hotel(): void
    {
        Mail::fake();
        Setting::query()->update(['email' => 'front@hotel.test']);
        Settings::flush();

        $this->post('/contact', ['name' => 'Sam', 'email' => 'sam@example.com', 'subject' => 'Hi', 'message' => 'Do you have parking?', 'website' => ''])->assertRedirect('/contact');

        $this->assertSame('Do you have parking?', ContactMessage::firstOrFail()->message);
        Mail::assertSent(ContactMessageReceived::class, fn ($m) => $m->hasTo('front@hotel.test') && $m->hasReplyTo('sam@example.com'));
    }

    public function test_contact_form_ignores_bots_and_validates(): void
    {
        $this->post('/contact', ['name' => 'Bot', 'email' => 'b@example.com', 'message' => 'spam', 'website' => 'http://spam'])->assertSessionHasErrors('website');
        $this->post('/contact', ['name' => '', 'email' => 'not-an-email', 'message' => ''])->assertSessionHasErrors(['name', 'email', 'message']);
        $this->assertSame(0, ContactMessage::count());
        $this->get('/contact')->assertOk()->assertSee('Send message');
    }

    public function test_newsletter_signup_is_idempotent(): void
    {
        $this->post('/subscribe', ['email' => 'Fan@Example.com'])->assertRedirect();
        $this->post('/subscribe', ['email' => 'fan@example.com'])->assertRedirect();

        $this->assertSame(1, Subscriber::count());
        $this->post('/subscribe', ['email' => 'nope'])->assertSessionHasErrors('email');
    }

    public function test_gallery_and_public_pages_load(): void
    {
        $this->get('/gallery')->assertOk()->assertSee('Photos will appear');
        $this->get('/contact')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_guest_password_reset_flow(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'ada@example.com'])->assertSessionHas('status');
        Notification::assertSentTo($this->guest, ResetPasswordNotification::class, function ($n) {
            $this->token = $n->token;

            return true;
        });
        // Unknown e-mail gives the same answer and sends nothing.
        $this->post('/forgot-password', ['email' => 'ghost@example.com'])->assertSessionHas('status');
        Notification::assertSentTimes(ResetPasswordNotification::class, 1);

        $this->get('/reset-password/'.$this->token.'?email=ada@example.com')->assertOk()->assertSee('Reset your password');
        $this->post('/reset-password', ['token' => 'bad', 'email' => 'ada@example.com', 'password' => 'NewPassw0rd', 'password_confirmation' => 'NewPassw0rd'])->assertSessionHasErrors('email');
        $this->post('/reset-password', ['token' => $this->token, 'email' => 'ada@example.com', 'password' => 'weak', 'password_confirmation' => 'weak'])->assertSessionHasErrors('password');
        $this->post('/reset-password', ['token' => $this->token, 'email' => 'ada@example.com', 'password' => 'NewPassw0rd', 'password_confirmation' => 'NewPassw0rd'])->assertRedirect('/login');

        $this->assertTrue(Hash::check('NewPassw0rd', $this->guest->fresh()->pass));
        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'NewPassw0rd'])->assertRedirect('/');
    }

    public function test_staff_password_reset_uses_the_staff_screens(): void
    {
        Notification::fake();

        $this->post('/admin/forgot-password', ['email' => 'staff@example.com'])->assertSessionHas('status');

        Notification::assertSentTo($this->staff, ResetPasswordNotification::class, function ($n) {
            $url = (new \ReflectionMethod($n, 'resetUrl'))->invoke($n, $this->staff);
            $this->assertStringContainsString('/admin/reset-password/', $url);
            $this->token = $n->token;

            return true;
        });
        $this->post('/admin/reset-password', ['token' => $this->token, 'email' => 'staff@example.com', 'password' => 'StaffPassw0rd', 'password_confirmation' => 'StaffPassw0rd'])->assertRedirect('/admin/login');
        $this->assertTrue(Hash::check('StaffPassw0rd', $this->staff->fresh()->password));
    }

    public function test_guest_can_edit_profile_and_change_password(): void
    {
        $this->actingAs($this->guest, 'customer');
        $payload = ['firstname' => 'Ada', 'lastname' => 'Lovelace', 'email' => 'ada@example.com', 'cust_phone' => '0170000001', 'city' => 'London'];

        $this->get('/account')->assertOk()->assertSee('My profile');
        $this->put('/account', $payload)->assertSessionHasNoErrors();
        $this->assertSame('Lovelace', $this->guest->fresh()->lastname);

        // The current password is required (the imported MD5 one is still accepted here).
        $this->put('/account', $payload + ['password' => 'Better1234', 'password_confirmation' => 'Better1234'])->assertSessionHasErrors('current_password');
        $this->put('/account', $payload + ['current_password' => 'wrong', 'password' => 'Better1234', 'password_confirmation' => 'Better1234'])->assertSessionHasErrors('current_password');
        $this->put('/account', $payload + ['current_password' => 'secret12', 'password' => 'Better1234', 'password_confirmation' => 'Better1234'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Better1234', $this->guest->fresh()->pass));
    }

    public function test_profile_cannot_take_another_guests_email(): void
    {
        Customerinfo::create(['firstname' => 'Eve', 'lastname' => 'X', 'email' => 'eve@example.com', 'cust_phone' => '0170000077', 'pass' => 'x', 'balance' => 0, 'active' => 1]);
        $this->actingAs($this->guest, 'customer')->put('/account', ['firstname' => 'Ada', 'lastname' => 'G', 'email' => 'eve@example.com', 'cust_phone' => '0170000001'])->assertSessionHasErrors('email');
    }

    public function test_guest_can_cancel_an_unpaid_pending_booking_only(): void
    {
        $this->actingAs($this->guest, 'customer')->post('/book', $this->bookingPayload());
        $booking = BookedInfo::firstOrFail();

        $this->get('/my-bookings/'.$booking->booking_number)->assertOk()->assertSee('Cancel booking');
        $this->post('/my-bookings/'.$booking->booking_number.'/cancel')->assertSessionHasNoErrors();
        $this->assertSame('1', (string) $booking->fresh()->bookingstatus);

        $this->post('/book', $this->bookingPayload());
        $second = BookedInfo::orderByDesc('bookedid')->first();
        $second->update(['paid_amount' => 10]);
        $this->post('/my-bookings/'.$second->booking_number.'/cancel')->assertSessionHasErrors('booking');
        $this->assertSame('0', (string) $second->fresh()->bookingstatus);
    }

    public function test_staff_accounts_with_roles(): void
    {
        $this->actingAs($this->staff, 'admin');

        $this->post('/admin/staff', ['firstname' => 'New', 'lastname' => 'Clerk', 'email' => 'clerk@example.com', 'password' => 'ClerkPass123', 'role_names' => ['Front Desk'], 'status' => 1])->assertRedirect('/admin/staff');
        $clerk = User::where('email', 'clerk@example.com')->firstOrFail();
        $this->assertTrue($clerk->hasRole('Front Desk'));
        $this->assertTrue(Hash::check('ClerkPass123', $clerk->password));
        $this->assertSame(1, (int) $clerk->usertype);
        $this->assertSame(0, (int) $clerk->is_admin);

        // Edit: change role, keep the password when left blank.
        $this->put("/admin/staff/{$clerk->id}", ['firstname' => 'New', 'lastname' => 'Clerk', 'email' => 'clerk@example.com', 'password' => '', 'role_names' => ['Accountant'], 'status' => 1])->assertRedirect('/admin/staff');
        $this->assertTrue($clerk->fresh()->hasRole('Accountant'));
        $this->assertFalse($clerk->fresh()->hasRole('Front Desk'));
        $this->assertTrue(Hash::check('ClerkPass123', $clerk->fresh()->password));

        // The staff list shows staff only, not guests.
        $this->get('/admin/staff')->assertOk()->assertSee('clerk@example.com')->assertDontSee('ada@example.com');
    }

    public function test_the_last_super_admin_cannot_be_removed_disabled_or_demoted(): void
    {
        $this->actingAs($this->staff, 'admin');
        $payload = ['firstname' => 'Sam', 'lastname' => 'Staff', 'email' => 'staff@example.com', 'password' => ''];

        $this->put("/admin/staff/{$this->staff->id}", $payload + ['role_names' => ['Manager'], 'status' => 1])->assertSessionHasErrors('role_names');
        $this->put("/admin/staff/{$this->staff->id}", $payload + ['role_names' => ['Super Admin'], 'status' => 0])->assertSessionHasErrors('role_names');
        $this->delete("/admin/staff/{$this->staff->id}")->assertSessionHasErrors('delete');
        $this->assertTrue($this->staff->fresh()->hasRole('Super Admin'));
    }

    public function test_roles_screen_creates_edits_and_protects_roles(): void
    {
        $this->actingAs($this->staff, 'admin');

        $this->get('/admin/roles')->assertOk()->assertSee('Front Desk');
        $this->post('/admin/roles', ['name' => 'Night Auditor', 'permissions' => ['dashboard.view', 'reservations.view']])->assertRedirect('/admin/roles');
        $role = Role::findByName('Night Auditor', 'admin');
        $this->assertEqualsCanonicalizing(['dashboard.view', 'reservations.view'], $role->permissions->pluck('name')->all());

        $this->get("/admin/roles/{$role->id}/edit")->assertOk();
        $this->put("/admin/roles/{$role->id}", ['name' => 'Night Auditor', 'permissions' => ['dashboard.view']])->assertRedirect('/admin/roles');
        $this->assertSame(['dashboard.view'], $role->fresh()->permissions->pluck('name')->all());

        $this->post('/admin/roles', ['name' => 'Night Auditor'])->assertSessionHasErrors('name');
        $this->post('/admin/roles', ['name' => 'Hacker', 'permissions' => ['not.a.permission']])->assertSessionHasErrors('permissions.0');

        $super = Role::findByName('Super Admin', 'admin');
        $this->delete("/admin/roles/{$super->id}")->assertSessionHasErrors('role');
        $this->put("/admin/roles/{$super->id}", ['name' => 'Renamed', 'permissions' => []]);
        $this->assertSame('Super Admin', $super->fresh()->name);

        $desk = Role::findByName('Front Desk', 'admin');
        $this->delete("/admin/roles/{$desk->id}")->assertRedirect('/admin/roles');
        $this->delete("/admin/roles/{$role->id}")->assertRedirect('/admin/roles');
    }

    public function test_roles_with_staff_cannot_be_deleted_and_screen_is_protected(): void
    {
        $user = User::create(['firstname' => 'A', 'lastname' => 'B', 'email' => 'ab@example.com', 'password' => Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $user->assignRole('Accountant');

        $this->actingAs($this->staff, 'admin')->delete('/admin/roles/'.Role::findByName('Accountant', 'admin')->id)->assertSessionHasErrors('role');
        $this->actingAs($user, 'admin')->get('/admin/roles')->assertForbidden();
    }

    public function test_hotel_settings_and_mail_settings(): void
    {
        $this->actingAs($this->staff, 'admin');
        $currency = \App\Models\Currency::create(['currencyname' => 'EUR', 'curr_icon' => '€', 'position' => 2, 'curr_rate' => 1]);

        $this->get('/admin/settings')->assertOk()->assertSee('Hotel profile');
        $this->put('/admin/settings', ['title' => 'Seaside Inn', 'address' => '1 Beach Rd', 'phone' => '123', 'email' => 'hi@seaside.test', 'currency' => $currency->currencyid, 'servicecharge' => 7.5, 'checkintime' => '15:00', 'checkouttime' => '11:00'])->assertSessionHasNoErrors();

        Settings::flush();
        $this->assertSame('Seaside Inn', Settings::hotelName());
        $this->assertSame('7.50', (string) Settings::get('servicecharge'));
        $this->assertSame('€', \App\Support\Money::currency()->curr_icon);
        $this->assertSame('100,00€', str_replace('.', ',', \App\Support\Money::format(100)));
        $this->get('/')->assertSee('Seaside Inn');

        $this->put('/admin/settings/mail', ['host' => 'smtp.example.com', 'port' => 465, 'username' => 'u', 'password' => 'smtp-secret', 'encryption' => 'ssl', 'from_address' => 'no-reply@seaside.test', 'from_name' => 'Seaside'])->assertSessionHasNoErrors();
        $this->put('/admin/settings/mail', ['host' => 'smtp.example.com', 'port' => 465, 'username' => 'u', 'password' => '', 'encryption' => 'ssl'])->assertSessionHasNoErrors();

        AppSettings::flush();
        $this->assertSame('smtp-secret', AppSettings::get('mail.password'));
        $this->assertStringNotContainsString('smtp-secret', (string) \Illuminate\Support\Facades\DB::table('app_settings')->where('key', 'mail.password')->value('value'));
        $this->get('/admin/settings')->assertDontSee('smtp-secret');

        AppSettings::applyMailConfig();
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
    }

    public function test_settings_validation_and_test_mail(): void
    {
        $this->actingAs($this->staff, 'admin');
        Mail::fake();

        $this->put('/admin/settings', ['title' => ''])->assertSessionHasErrors('title');
        $this->put('/admin/settings', ['title' => 'X', 'servicecharge' => 150])->assertSessionHasErrors('servicecharge');
        $this->post('/admin/settings/mail-test', ['to' => 'me@example.com'])->assertSessionHas('status');
        Mail::assertSent(\App\Mail\TestMail::class, fn ($m) => $m->hasTo('me@example.com'));
    }
}
