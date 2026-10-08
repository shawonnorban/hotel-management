<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Support\Facades\Hash;

class InventoryTest extends HotelTestCase
{
    private LedgerService $ledger;

    private Supplier $supplier;

    private InventoryItem $soap;

    private InventoryItem $rice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledger = app(LedgerService::class);
        $kg = Unit::create(['name' => 'Kilogram', 'short_code' => 'kg']);
        $pc = Unit::create(['name' => 'Piece', 'short_code' => 'pc']);
        $cat = ItemCategory::create(['name' => 'Housekeeping']);
        $this->soap = InventoryItem::create(['sku' => 'SOAP', 'name' => 'Soap bar', 'unit_id' => $pc->id, 'category_id' => $cat->id, 'reorder_level' => 20]);
        $this->rice = InventoryItem::create(['sku' => 'RICE', 'name' => 'Rice', 'unit_id' => $kg->id]);
        $this->supplier = Supplier::create(['code' => 'S1', 'name' => 'Acme Supplies']);
        $this->actingAs($this->staff, 'admin');
    }

    private function buy(?array $lines = null, array $extra = []): Purchase
    {
        $lines ??= [['item' => $this->soap->id, 'quantity' => 100, 'unit_cost' => 2]];
        $this->post('/admin/purchasing/purchases', array_merge(['supplier_id' => $this->supplier->id, 'purchase_date' => today()->toDateString(), 'lines' => $lines], $extra))->assertSessionHasNoErrors();

        return Purchase::orderByDesc('id')->firstOrFail();
    }

    private function assertLedgerBalanced(): void
    {
        $tb = $this->ledger->trialBalance(today());
        $this->assertSame($tb['debit'], $tb['credit'], 'trial balance is out of balance');
    }

    public function test_purchase_increases_stock_and_posts_to_the_ledger(): void
    {
        $purchase = $this->buy();

        $this->assertMatchesRegularExpression('/^PO-\d{6}$/', $purchase->number);
        $this->assertSame('200.00', $purchase->total);
        $this->assertSame('100.000', $this->soap->fresh()->stock);
        $this->assertSame('2.0000', $this->soap->fresh()->avg_cost);
        $this->assertSame(200.0, $this->ledger->balance('inventory'));
        $this->assertSame(200.0, $this->ledger->balance('payable'));
        $this->assertSame('purchase', StockMovement::firstOrFail()->type);
        $this->assertLedgerBalanced();
    }

    public function test_average_cost_is_weighted_and_discount_is_spread_over_the_goods(): void
    {
        $this->buy([['item' => $this->soap->id, 'quantity' => 100, 'unit_cost' => 2]]);
        $this->buy([['item' => $this->soap->id, 'quantity' => 100, 'unit_cost' => 4]]);
        $this->assertSame('3.0000', $this->soap->fresh()->avg_cost);

        // 10 % discount on 100 × 5 = 500 → stock is valued at 4.50 each.
        $this->buy([['item' => $this->rice->id, 'quantity' => 100, 'unit_cost' => 5]], ['discount' => 50]);
        $this->assertSame('4.5000', $this->rice->fresh()->avg_cost);
        $this->assertSame(1050.0, $this->ledger->balance('inventory'));
        $this->assertSame(1050.0, $this->ledger->balance('payable'));
        $this->assertLedgerBalanced();
    }

    public function test_paying_now_and_later(): void
    {
        $cash = LedgerAccount::where('system_key', 'cash')->first();
        $purchase = $this->buy(null, ['pay_amount' => 50, 'pay_account' => $cash->id]);

        $this->assertSame('50.00', $purchase->paid);
        $this->assertSame(150.0, $purchase->due);
        $this->assertSame(-50.0, $this->ledger->balance('cash'));
        $this->assertSame(150.0, $this->ledger->balance('payable'));

        $this->post("/admin/purchasing/purchases/{$purchase->id}/pay", ['amount' => 500, 'account' => $cash->id, 'paid_on' => today()->toDateString()])->assertSessionHasErrors('action');
        $this->post("/admin/purchasing/purchases/{$purchase->id}/pay", ['amount' => 150, 'account' => $cash->id, 'paid_on' => today()->toDateString(), 'reference' => 'CHQ-1'])->assertSessionHasNoErrors();

        $this->assertSame(0.0, $purchase->fresh()->load('returns')->due);
        $this->assertSame(0.0, $this->ledger->balance('payable'));
        $this->assertLedgerBalanced();
    }

    public function test_paying_from_a_non_cash_account_is_rejected(): void
    {
        $purchase = $this->buy();
        $expense = LedgerAccount::where('system_key', 'consumables')->first();

        $this->post("/admin/purchasing/purchases/{$purchase->id}/pay", ['amount' => 10, 'account' => $expense->id, 'paid_on' => today()->toDateString()])->assertSessionHasErrors('action');
        $this->assertSame('0.00', $purchase->fresh()->paid);
    }

    public function test_purchase_validation(): void
    {
        $base = ['supplier_id' => $this->supplier->id, 'purchase_date' => today()->toDateString()];

        $this->post('/admin/purchasing/purchases', $base)->assertSessionHasErrors('lines');
        $this->post('/admin/purchasing/purchases', $base + ['lines' => [['item' => $this->soap->id, 'quantity' => 0, 'unit_cost' => 1]]])->assertSessionHasErrors('lines.0.quantity');
        $this->post('/admin/purchasing/purchases', $base + ['lines' => [['item' => $this->soap->id, 'quantity' => 1, 'unit_cost' => 10]], 'discount' => 50])->assertSessionHasErrors('purchase');
        $this->assertSame(0, Purchase::count());
        $this->assertSame('0.000', $this->soap->fresh()->stock);
    }

    public function test_return_goods_reduces_stock_and_payable(): void
    {
        $purchase = $this->buy([['item' => $this->soap->id, 'quantity' => 100, 'unit_cost' => 2]]);
        $line = PurchaseItem::firstOrFail();

        $this->post("/admin/purchasing/purchases/{$purchase->id}/return", ['return_date' => today()->toDateString(), 'reason' => 'Damaged', 'qty' => [$line->id => 10]])->assertSessionHasNoErrors();

        $this->assertSame('90.000', $this->soap->fresh()->stock);
        $this->assertSame('10.000', $line->fresh()->returned);
        $this->assertSame(180.0, $this->ledger->balance('inventory'));
        $this->assertSame(180.0, $this->ledger->balance('payable'));
        $this->assertSame(180.0, $purchase->fresh()->load('returns')->due);

        // 90 left to return, no more.
        $this->post("/admin/purchasing/purchases/{$purchase->id}/return", ['return_date' => today()->toDateString(), 'qty' => [$line->id => 91]])->assertSessionHasErrors('action');
        $this->assertSame('90.000', $this->soap->fresh()->stock);
        $this->assertLedgerBalanced();
    }

    public function test_cannot_return_goods_that_were_already_used(): void
    {
        $purchase = $this->buy([['item' => $this->soap->id, 'quantity' => 10, 'unit_cost' => 2]]);
        $line = PurchaseItem::firstOrFail();
        $this->post('/admin/purchasing/stock/issue', ['item' => $this->soap->id, 'quantity' => 8, 'reason' => 'Rooms'])->assertSessionHasNoErrors();

        $this->post("/admin/purchasing/purchases/{$purchase->id}/return", ['return_date' => today()->toDateString(), 'qty' => [$line->id => 5]])->assertSessionHasErrors('action');

        $this->assertSame('2.000', $this->soap->fresh()->stock);
        $this->assertSame('0.000', $line->fresh()->returned);
        $this->assertSame(0, PurchaseReturn::count());
    }

    public function test_issuing_stock_expenses_it_at_average_cost(): void
    {
        $this->buy([['item' => $this->soap->id, 'quantity' => 100, 'unit_cost' => 2]]);

        $this->post('/admin/purchasing/stock/issue', ['item' => $this->soap->id, 'quantity' => 30, 'reason' => 'Housekeeping'])->assertSessionHasNoErrors();

        $this->assertSame('70.000', $this->soap->fresh()->stock);
        $this->assertSame(60.0, $this->ledger->balance('consumables'));
        $this->assertSame(140.0, $this->ledger->balance('inventory'));
        $this->post('/admin/purchasing/stock/issue', ['item' => $this->soap->id, 'quantity' => 71, 'reason' => 'x'])->assertSessionHasErrors('action');
        $this->assertSame('70.000', $this->soap->fresh()->stock);
        $this->assertLedgerBalanced();
    }

    public function test_returns_list_invoice_destroyed_list_and_stock_report(): void
    {
        $purchase = $this->buy([['item' => $this->soap->id, 'quantity' => 100, 'unit_cost' => 2]]);
        $line = PurchaseItem::firstOrFail();
        $this->post("/admin/purchasing/purchases/{$purchase->id}/return", ['return_date' => today()->toDateString(), 'reason' => 'Damaged', 'qty' => [$line->id => 10]])->assertSessionHasNoErrors();
        $this->post('/admin/purchasing/stock/waste', ['item' => $this->soap->id, 'quantity' => 5, 'reason' => 'Expired'])->assertSessionHasNoErrors();

        $return = \App\Models\PurchaseReturn::firstOrFail();
        $this->get('/admin/purchasing/returns')->assertOk()->assertSee($return->number)->assertSee('Acme Supplies');
        $this->get("/admin/purchasing/returns/{$return->id}/invoice")->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->get('/admin/purchasing/stock/destroyed')->assertOk()->assertSee('Soap bar')->assertSee('Expired');
        $this->get('/admin/reports/stock')->assertOk()->assertSee('Soap bar');
        $this->get('/admin/reports/stock?export=csv')->assertOk();
    }

    public function test_write_off_and_stock_count(): void
    {
        $this->buy([['item' => $this->soap->id, 'quantity' => 100, 'unit_cost' => 2]]);

        $this->post('/admin/purchasing/stock/waste', ['item' => $this->soap->id, 'quantity' => 5, 'reason' => 'Expired'])->assertSessionHasNoErrors();
        $this->assertSame(10.0, $this->ledger->balance('stock_loss'));

        // Count finds 90 although the books say 95: a loss of 5 × 2.
        $this->post('/admin/purchasing/stock/adjust', ['item' => $this->soap->id, 'counted' => 90, 'reason' => 'Annual count'])->assertSessionHasNoErrors();
        $this->assertSame('90.000', $this->soap->fresh()->stock);
        $this->assertSame(20.0, $this->ledger->balance('stock_loss'));
        $this->assertSame(180.0, $this->ledger->balance('inventory'));

        // Counting more than the books is a gain.
        $this->post('/admin/purchasing/stock/adjust', ['item' => $this->soap->id, 'counted' => 92, 'reason' => 'Found a box'])->assertSessionHasNoErrors();
        $this->assertSame(16.0, $this->ledger->balance('stock_loss'));
        // An unchanged count records nothing.
        $this->post('/admin/purchasing/stock/adjust', ['item' => $this->soap->id, 'counted' => 92, 'reason' => 'Again'])->assertSessionHasNoErrors();
        $this->assertSame(4, StockMovement::count());
        $this->assertLedgerBalanced();
    }

    public function test_items_without_a_cost_move_without_ledger_entries(): void
    {
        $this->post('/admin/purchasing/stock/adjust', ['item' => $this->rice->id, 'counted' => 5, 'reason' => 'Opening count'])->assertSessionHasNoErrors();

        $this->assertSame('5.000', $this->rice->fresh()->stock);
        $this->assertSame(0, JournalEntry::count());
    }

    public function test_master_data_screens_generate_codes_and_protect_used_records(): void
    {
        $this->post('/admin/inv-suppliers', ['name' => 'Fresh Foods', 'is_active' => 1])->assertRedirect('/admin/inv-suppliers');
        $this->assertMatchesRegularExpression('/^SUP-\d{4}$/', Supplier::where('name', 'Fresh Foods')->value('code'));

        $this->post('/admin/inv-items', ['name' => 'Towel', 'unit_id' => $this->soap->unit_id, 'reorder_level' => 5, 'is_active' => 1])->assertRedirect('/admin/inv-items');
        $this->assertMatchesRegularExpression('/^ITM-\d{4}$/', InventoryItem::where('name', 'Towel')->value('sku'));
        $this->post('/admin/inv-items', ['name' => 'Dup', 'sku' => 'SOAP', 'unit_id' => $this->soap->unit_id])->assertSessionHasErrors('sku');

        $this->buy();
        $this->delete('/admin/inv-items/'.$this->soap->id)->assertSessionHasErrors('delete');
        $this->delete('/admin/inv-suppliers/'.$this->supplier->id)->assertSessionHasErrors('delete');
        $this->delete('/admin/inv-units/'.$this->soap->unit_id)->assertSessionHasErrors('delete');
        $this->delete('/admin/inv-categories/'.$this->soap->category_id)->assertSessionHasErrors('delete');
        $this->get('/admin/inv-items')->assertOk()->assertSee('Soap bar');
    }

    public function test_screens_render_and_flag_low_stock(): void
    {
        $this->buy([['item' => $this->soap->id, 'quantity' => 10, 'unit_cost' => 2]]); // below the reorder level of 20
        $purchase = Purchase::firstOrFail();

        $this->get('/admin/purchasing/purchases')->assertOk()->assertSee($purchase->number);
        $this->get('/admin/purchasing/purchases?unpaid=1&q='.$purchase->number)->assertOk()->assertSee($purchase->number);
        $this->get('/admin/purchasing/purchases/create')->assertOk()->assertSee('New purchase');
        $this->get("/admin/purchasing/purchases/{$purchase->id}")->assertOk()->assertSee('Pay supplier')->assertSee('Soap bar');
        $this->get('/admin/purchasing/stock')->assertOk()->assertSee('Soap bar')->assertSee('1 low');
        $this->get('/admin/purchasing/stock?low=1')->assertOk()->assertSee('SOAP')->assertDontSee('RICE');
        $this->get('/admin/purchasing/stock/movements')->assertOk()->assertSee('Purchase PO-');
        $this->get('/admin/purchasing/stock/movements?type=waste')->assertOk()->assertDontSee('Purchase PO-');
    }

    public function test_store_keeper_can_buy_and_count_but_not_pay_suppliers_or_see_the_ledger(): void
    {
        $keeper = User::create(['firstname' => 'S', 'lastname' => 'K', 'email' => 'sk@example.com', 'password' => Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $keeper->assignRole('Store Keeper');
        $purchase = $this->buy();
        $cash = LedgerAccount::where('system_key', 'cash')->first();

        $this->actingAs($keeper, 'admin');
        $this->get('/admin/purchasing/purchases')->assertOk();
        $this->get('/admin/purchasing/stock')->assertOk();
        $this->post('/admin/purchasing/stock/issue', ['item' => $this->soap->id, 'quantity' => 1, 'reason' => 'Rooms'])->assertSessionHasNoErrors();
        $this->post("/admin/purchasing/purchases/{$purchase->id}/pay", ['amount' => 10, 'account' => $cash->id, 'paid_on' => today()->toDateString()])->assertForbidden();
        $this->get('/admin/accounting/trial-balance')->assertForbidden();
        $this->get('/admin/inv-items')->assertOk();
        $this->get('/admin/floors')->assertForbidden();
    }

    public function test_accountant_can_pay_suppliers(): void
    {
        $acc = User::create(['firstname' => 'A', 'lastname' => 'C', 'email' => 'acct@example.com', 'password' => Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $acc->assignRole('Accountant');
        $purchase = $this->buy();
        $cash = LedgerAccount::where('system_key', 'cash')->first();

        $this->actingAs($acc, 'admin')->post("/admin/purchasing/purchases/{$purchase->id}/pay", ['amount' => 10, 'account' => $cash->id, 'paid_on' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->post('/admin/purchasing/purchases', ['supplier_id' => $this->supplier->id, 'purchase_date' => today()->toDateString(), 'lines' => []])->assertForbidden();
    }
}
