<?php

namespace Tests\Feature;

use App\Models\BookedInfo;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Models\User;
use App\Services\BackupService;
use App\Services\PaymentService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ReportsAndBackupTest extends HotelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->actingAs($this->staff, 'admin');
    }

    private function booking(?array $stay = null): BookedInfo
    {
        $this->post('/admin/reservations', array_merge($stay ?? $this->stay(0, 2), ['room' => $this->room->roomid, 'rooms' => 1, 'adults' => 2, 'guest_id' => $this->guest->customerid, 'source' => 'phone']));

        return BookedInfo::orderByDesc('bookedid')->firstOrFail();
    }

    public function test_reports_render_and_total_correctly(): void
    {
        $b = $this->booking();
        app(PaymentService::class)->receive($b, 100, PaymentMethod::where('payment_method', 'Cash Payment')->first(), $this->staff->id);

        $this->get('/admin/reports')->assertOk()->assertSee('Occupancy');
        $this->get('/admin/reports/bookings?from='.today()->toDateString().'&to='.today()->addDays(5)->toDateString())->assertOk()->assertSee($b->booking_number)->assertSee('230.00');
        $this->get('/admin/reports/bookings?status=1')->assertOk()->assertDontSee($b->booking_number);
        $this->get('/admin/reports/receipts')->assertOk()->assertSee('Cash Payment')->assertSee('100.00');
        $this->get('/admin/reports/occupancy?from='.today()->toDateString().'&to='.today()->addDays(3)->toDateString())->assertOk()->assertSee('Average occupancy');
        $this->get('/admin/reports/purchases')->assertOk();
    }

    public function test_occupancy_counts_nights_not_the_checkout_day(): void
    {
        $this->booking($this->stay(0, 2)); // nights today and tomorrow, leaves on day 3
        $csv = $this->get('/admin/reports/occupancy?from='.today()->toDateString().'&to='.today()->addDays(2)->toDateString().'&export=csv')->assertOk()->streamedContent();
        $lines = array_map('str_getcsv', array_filter(explode("\n", $csv)));

        $this->assertSame('1', $lines[1][1]);
        $this->assertSame('1', $lines[2][1]);
        $this->assertSame('0', $lines[3][1]);
        $this->get('/admin/reports/occupancy?from=2026-01-01&to=2026-12-31')->assertSessionHasErrors('period');
    }

    public function test_csv_exports(): void
    {
        $this->booking();
        Supplier::create(['code' => 'S', 'name' => '=cmd|calc']);

        $csv = $this->get('/admin/reports/bookings?export=csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('Booking,Guest', $csv);
        $this->assertStringContainsString('Ada Guest', $csv);
        $this->get('/admin/reports/receipts?export=csv')->assertOk();
        $this->get('/admin/reports/purchases?export=csv')->assertOk();
        $this->get('/admin/reports/bookings?from=2026-02-01&to=2026-01-01')->assertSessionHasErrors('to');
    }

    public function test_reports_need_permission(): void
    {
        $desk = User::create(['firstname' => 'F', 'lastname' => 'D', 'email' => 'fdr@example.com', 'password' => Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $desk->assignRole('Front Desk');
        $this->actingAs($desk, 'admin')->get('/admin/reports/bookings')->assertForbidden();

        $acc = User::create(['firstname' => 'A', 'lastname' => 'C', 'email' => 'acr@example.com', 'password' => Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $acc->assignRole('Accountant');
        $this->actingAs($acc, 'admin')->get('/admin/reports/receipts')->assertOk();
    }

    public function test_backup_contains_schema_and_data_and_can_be_downloaded_and_deleted(): void
    {
        Storage::fake('local');
        $name = app(BackupService::class)->create();

        $sql = gzdecode(Storage::disk('local')->get('backups/'.$name));
        $this->assertStringContainsString('CREATE TABLE `customerinfo`', $sql);
        $this->assertStringContainsString("'ada@example.com'", $sql);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=0', $sql);

        $this->get('/admin/backups')->assertOk()->assertSee($name);
        $this->get('/admin/backups/'.$name)->assertOk();
        $this->get('/admin/backups/backup-19990101-000000.sql.gz')->assertNotFound();
        $this->get('/admin/backups/..%2F..%2F.env')->assertNotFound();
        $this->delete('/admin/backups/'.$name)->assertSessionHasNoErrors();
        $this->assertSame([], app(BackupService::class)->list());
    }

    public function test_backup_command_and_pruning(): void
    {
        Storage::fake('local');
        $this->artisan('hotel:backup', ['--keep' => 1])->assertSuccessful();
        Storage::disk('local')->put('backups/backup-20200101-000000.sql.gz', 'x');
        touch(Storage::disk('local')->path('backups/backup-20200101-000000.sql.gz'), time() - 86400);
        $this->artisan('hotel:backup', ['--keep' => 1])->assertSuccessful();

        $this->assertCount(1, app(BackupService::class)->list());
    }

    public function test_backup_screen_is_super_admin_or_permitted_only(): void
    {
        $desk = User::create(['firstname' => 'F', 'lastname' => 'D', 'email' => 'fdb@example.com', 'password' => Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $desk->assignRole('Front Desk');

        $this->actingAs($desk, 'admin')->get('/admin/backups')->assertForbidden();
        $this->post('/admin/backups')->assertForbidden();
    }

    public function test_adopt_command_marks_the_baseline_only_for_existing_databases(): void
    {
        // On this fresh database the baseline is already recorded by `migrate`, so there is nothing to do.
        $this->artisan('hotel:adopt-existing-database', ['--force' => true])->assertSuccessful();

        DB::table('migrations')->where('migration', 'like', '%_create_user_table')->delete();
        $this->artisan('hotel:adopt-existing-database', ['--force' => true])->assertSuccessful();
        $this->assertSame(1, DB::table('migrations')->where('migration', 'like', '%_create_user_table')->count());

        Schema::drop('journal_lines');
        Schema::drop('journal_entries');
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('roomdetails');
        $this->artisan('hotel:adopt-existing-database', ['--force' => true])->assertFailed();
    }
}
