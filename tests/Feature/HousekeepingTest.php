<?php

namespace Tests\Feature;

use App\Models\HkChecklistItem;
use App\Models\HkLaundryCost;
use App\Models\HkLaundryOrder;
use App\Models\HkLaundryProduct;
use App\Models\HkTask;
use App\Models\HrEmployee;
use App\Models\LedgerAccount;
use App\Models\TblRoomnofloorassign;
use App\Services\LedgerService;

class HousekeepingTest extends HotelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->staff, 'admin');
    }

    public function test_assigning_cleaning_copies_the_checklist_and_drives_room_status(): void
    {
        HkChecklistItem::create(['name' => 'Change linen', 'sort' => 1]);
        HkChecklistItem::create(['name' => 'Vacuum', 'sort' => 2]);
        $maid = HrEmployee::create(['code' => 'H1', 'first_name' => 'Maya', 'join_date' => '2025-01-01', 'basic_salary' => 0]);
        $room = TblRoomnofloorassign::where('roomno', 101)->first();
        $room->update(['status' => 6]); // dirty

        $this->post('/admin/housekeeping/assign', ['rooms' => [$room->roomassignid], 'employee' => $maid->id, 'task_date' => today()->toDateString()])->assertSessionHasNoErrors();
        $task = HkTask::firstOrFail();
        $this->assertSame(2, $task->items()->count());
        $this->assertSame(3, (int) $room->fresh()->status);

        // Assigning the same room again doesn't duplicate the task.
        $this->post('/admin/housekeeping/assign', ['rooms' => [$room->roomassignid], 'task_date' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame(1, HkTask::count());

        $this->get('/admin/housekeeping/tasks')->assertOk()->assertSee('Maya')->assertSee('Change linen');
        $this->post("/admin/housekeeping/tasks/{$task->id}/inspect")->assertSessionHasErrors('task'); // not done yet
        $this->post("/admin/housekeeping/tasks/{$task->id}/start")->assertSessionHasNoErrors();
        $this->post("/admin/housekeeping/tasks/{$task->id}/complete")->assertSessionHasNoErrors();
        $this->assertSame(1, (int) $room->fresh()->status);
        $this->assertSame(0, $task->items()->where('is_done', false)->count());
        $this->post("/admin/housekeeping/tasks/{$task->id}/inspect")->assertSessionHasNoErrors();
        $this->assertSame('inspected', $task->fresh()->status);
        $this->get('/admin/housekeeping/report')->assertOk()->assertSee('Maya');
    }

    public function test_room_qr_page_lets_guests_request_cleaning(): void
    {
        $room = TblRoomnofloorassign::where('roomno', 102)->first();

        $this->get('/admin/housekeeping/qr')->assertOk()->assertSee('<svg', false);
        auth('admin')->logout();
        $this->get("/room/{$room->roomassignid}")->assertOk()->assertSee('Room 102');
        $this->post("/room/{$room->roomassignid}/cleaning")->assertSessionHas('status');
        $this->assertSame('qr', HkTask::firstOrFail()->source);
        $this->post("/room/{$room->roomassignid}/cleaning")->assertSessionHas('status'); // idempotent for the day
        $this->assertSame(1, HkTask::count());
    }

    public function test_laundry_order_is_priced_paid_and_posted_to_the_ledger(): void
    {
        $shirt = HkLaundryProduct::create(['name' => 'Shirt']);
        HkLaundryCost::create(['product_id' => $shirt->id, 'service' => 'wash_iron', 'cost' => 3.5]);

        $this->post('/admin/laundry/orders', ['guest_name' => 'Ada', 'room_no' => '101', 'order_date' => today()->toDateString(), 'lines' => [['product' => $shirt->id, 'service' => 'wash_iron', 'quantity' => 4]]])->assertSessionHasNoErrors();
        $order = HkLaundryOrder::firstOrFail();
        $this->assertSame('14.00', $order->total);

        // No price for that service.
        $this->post('/admin/laundry/orders', ['guest_name' => 'Ada', 'order_date' => today()->toDateString(), 'lines' => [['product' => $shirt->id, 'service' => 'dry_clean', 'quantity' => 1]]])->assertSessionHasErrors('lines');
        $this->assertSame(1, HkLaundryOrder::count());

        $cash = LedgerAccount::where('system_key', 'cash')->firstOrFail();
        $this->post("/admin/laundry/orders/{$order->id}/pay", ['amount' => 20, 'account' => $cash->id, 'paid_on' => today()->toDateString()])->assertSessionHasErrors('order');
        $this->post("/admin/laundry/orders/{$order->id}/pay", ['amount' => 10, 'account' => $cash->id, 'paid_on' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame(10.0, app(LedgerService::class)->balance('cash'));
        $this->assertSame(10.0, app(LedgerService::class)->balance('service_income'));
        $this->assertSame(4.0, $order->fresh()->due);

        $this->post("/admin/laundry/orders/{$order->id}/cancel")->assertSessionHasErrors('order'); // has a payment
        $this->post("/admin/laundry/orders/{$order->id}/status", ['status' => 'delivered'])->assertSessionHasNoErrors();

        $this->get('/admin/laundry/orders')->assertOk()->assertSee($order->number);
        $this->get("/admin/laundry/orders/{$order->id}")->assertOk()->assertSee('Shirt');
        $this->get('/admin/laundry/payments')->assertOk()->assertSee($order->number);
        $this->get('/admin/laundry/orders/create')->assertOk();
    }

    public function test_master_data_resources_and_permissions(): void
    {
        $this->post('/admin/hk-checklist', ['name' => 'Wipe mirrors', 'sort' => 1, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->post('/admin/hk-laundry-products', ['name' => 'Towel', 'is_active' => 1])->assertSessionHasNoErrors();
        $p = HkLaundryProduct::firstOrFail();
        $this->post('/admin/hk-laundry-costs', ['product_id' => $p->id, 'service' => 'wash', 'cost' => 2])->assertSessionHasNoErrors();
        $this->post('/admin/hk-laundry-costs', ['product_id' => $p->id, 'service' => 'wash', 'cost' => 3])->assertSessionHasErrors('service');
        $this->assertSame(1, HkLaundryCost::count());

        $order = app(\App\Services\LaundryService::class)->create('Ada', null, null, today()->toDateString(), [['product' => $p->id, 'service' => 'wash', 'quantity' => 1]], null, null);
        $this->delete('/admin/hk-laundry-products/'.$p->id)->assertSessionHasErrors();
        $this->assertNotNull($order);

        // Housekeeping staff can work the board but not see the ledger.
        $maid = \App\Models\User::create(['firstname' => 'H', 'lastname' => 'K', 'email' => 'hk@example.com', 'password' => md5('x'), 'status' => 1, 'usertype' => 1, 'is_admin' => 1]);
        $maid->assignRole('Housekeeping');
        $this->actingAs($maid, 'admin');
        $this->get('/admin/housekeeping/tasks')->assertOk();
        $this->get('/admin/accounting/trial-balance')->assertForbidden();
    }
}
