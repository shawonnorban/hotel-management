<?php

namespace Tests\Feature;

use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Services\LedgerService;
use InvalidArgumentException;
use RuntimeException;

class LedgerTest extends HotelTestCase
{
    private LedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledger = app(LedgerService::class);
    }

    private function receive(float $amount, string $date = null): JournalEntry
    {
        return $this->ledger->post('receipt', $date ?? today(), [
            ['account' => 'cash', 'debit' => $amount],
            ['account' => 'other_income', 'credit' => $amount],
        ], 'Test receipt');
    }

    public function test_balanced_entries_update_balances(): void
    {
        $entry = $this->receive(150.50);

        $this->assertMatchesRegularExpression('/^JE-\d{6}$/', $entry->number);
        $this->assertSame(150.50, $this->ledger->balance('cash'));
        $this->assertSame(150.50, $this->ledger->balance('other_income'));
    }

    public function test_unbalanced_or_malformed_entries_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->ledger->post('journal', today(), [['account' => 'cash', 'debit' => 10], ['account' => 'other_income', 'credit' => 9.99]]);
    }

    public function test_entry_needs_two_lines_and_no_mixed_sides(): void
    {
        try {
            $this->ledger->post('journal', today(), [['account' => 'cash', 'debit' => 10]]);
            $this->fail('single line accepted');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->ledger->post('journal', today(), [['account' => 'cash', 'debit' => 10, 'credit' => 10], ['account' => 'other_income', 'credit' => 0]]);
    }

    public function test_group_and_inactive_accounts_cannot_be_posted_to(): void
    {
        $group = LedgerAccount::where('code', '1000')->first();
        try {
            $this->ledger->post('journal', today(), [['account' => $group, 'debit' => 5], ['account' => 'cash', 'credit' => 5]]);
            $this->fail('group account accepted');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        LedgerAccount::where('system_key', 'other_income')->update(['is_active' => false]);
        $this->expectException(InvalidArgumentException::class);
        $this->ledger->post('journal', today(), [['account' => 'cash', 'debit' => 5], ['account' => LedgerAccount::where('system_key', 'other_income')->first(), 'credit' => 5]]);
    }

    public function test_voided_entries_do_not_count(): void
    {
        $entry = $this->receive(100);
        $this->receive(40);
        $this->ledger->void($entry);

        $this->assertSame(40.0, $this->ledger->balance('cash'));
        $this->assertSame('void', $entry->fresh()->status);
    }

    public function test_closed_financial_year_blocks_posting_and_voiding(): void
    {
        $entry = $this->receive(10, '2025-03-01');
        FinancialYear::create(['title' => 'FY2025', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'is_closed' => true]);

        $this->expectException(RuntimeException::class);
        try {
            $this->ledger->void($entry);
        } finally {
            $this->assertSame('posted', $entry->fresh()->status);
        }
    }

    public function test_posting_into_a_closed_year_is_blocked(): void
    {
        FinancialYear::create(['title' => 'FY2024', 'start_date' => '2024-01-01', 'end_date' => '2024-12-31', 'is_closed' => true]);

        $this->expectException(RuntimeException::class);
        $this->receive(10, '2024-06-01');
    }

    public function test_trial_balance_always_balances_and_includes_opening_balances(): void
    {
        LedgerAccount::where('system_key', 'cash')->update(['opening_balance' => 1000]);
        LedgerAccount::where('system_key', 'capital')->update(['opening_balance' => 1000]);
        $this->receive(200);
        $this->ledger->post('payment', today(), [['account' => 'consumables', 'debit' => 60], ['account' => 'cash', 'credit' => 60]]);

        $tb = $this->ledger->trialBalance(today());

        $this->assertSame($tb['debit'], $tb['credit']);
        $this->assertSame(1140.0, $this->ledger->balance('cash'));
    }

    public function test_financial_statements(): void
    {
        LedgerAccount::where('system_key', 'cash')->update(['opening_balance' => 500]);
        LedgerAccount::where('system_key', 'capital')->update(['opening_balance' => 500]);
        $this->ledger->post('journal', today(), [['account' => 'cash', 'debit' => 300], ['account' => 'room_revenue', 'credit' => 300]]);
        $this->ledger->post('payment', today(), [['account' => 'salary_expense', 'debit' => 120], ['account' => 'cash', 'credit' => 120]]);

        $is = $this->ledger->incomeStatement(today()->startOfMonth(), today());
        $this->assertSame(300.0, $is['total_income']);
        $this->assertSame(120.0, $is['total_expenses']);
        $this->assertSame(180.0, $is['net']);

        $bs = $this->ledger->balanceSheet(today());
        $this->assertSame(680.0, $bs['total_assets']);
        $this->assertSame($bs['total_assets'], round($bs['total_liabilities'] + $bs['total_equity'], 2));
    }

    public function test_ledger_running_balance(): void
    {
        $this->receive(100, today()->subDays(40)->toDateString());
        $this->receive(50, today()->subDay()->toDateString());
        $this->ledger->post('payment', today(), [['account' => 'consumables', 'debit' => 30], ['account' => 'cash', 'credit' => 30]]);

        $data = $this->ledger->ledger($this->ledger->account('cash'), today()->subDays(7), today());

        $this->assertSame(100.0, $data['opening']);
        $this->assertCount(2, $data['rows']);
        $this->assertSame(150.0, $data['rows'][0]['balance']);
        $this->assertSame(120.0, $data['closing']);
    }

    public function test_receipt_and_payment_vouchers_via_the_screens(): void
    {
        $this->actingAs($this->staff, 'admin');
        $cash = LedgerAccount::where('system_key', 'cash')->first();
        $income = LedgerAccount::where('system_key', 'other_income')->first();
        $expense = LedgerAccount::where('system_key', 'consumables')->first();

        $this->get('/admin/accounting/vouchers/create?type=receipt')->assertOk()->assertSee('Receipt voucher');
        $this->post('/admin/accounting/vouchers', ['type' => 'receipt', 'entry_date' => today()->toDateString(), 'cash_account' => $cash->id, 'other_account' => $income->id, 'amount' => 75, 'narration' => 'Donation'])
            ->assertRedirect();
        $this->post('/admin/accounting/vouchers', ['type' => 'payment', 'entry_date' => today()->toDateString(), 'cash_account' => $cash->id, 'other_account' => $expense->id, 'amount' => 25])
            ->assertRedirect();

        $this->assertSame(50.0, $this->ledger->balance('cash'));
        $this->get('/admin/accounting/vouchers')->assertOk()->assertSee('Donation');
    }

    public function test_journal_voucher_must_balance(): void
    {
        $this->actingAs($this->staff, 'admin');
        $cash = LedgerAccount::where('system_key', 'cash')->first()->id;
        $income = LedgerAccount::where('system_key', 'other_income')->first()->id;
        $payload = fn ($credit) => ['type' => 'journal', 'entry_date' => today()->toDateString(), 'lines' => [
            ['account' => $cash, 'debit' => 10, 'credit' => ''], ['account' => $income, 'debit' => '', 'credit' => $credit], ['account' => '', 'debit' => '', 'credit' => ''],
        ]];

        $this->post('/admin/accounting/vouchers', $payload(9))->assertSessionHasErrors('voucher');
        $this->assertSame(0, JournalEntry::count());
        $this->post('/admin/accounting/vouchers', $payload(10))->assertSessionHasNoErrors();
        $this->assertSame(1, JournalEntry::count());
    }

    public function test_voucher_can_be_voided_from_the_screen(): void
    {
        $this->actingAs($this->staff, 'admin');
        $entry = $this->receive(10);

        $this->get('/admin/accounting/vouchers/'.$entry->id)->assertOk()->assertSee($entry->number);
        $this->post("/admin/accounting/vouchers/{$entry->id}/void")->assertRedirect();
        $this->assertSame('void', $entry->fresh()->status);
    }

    public function test_reports_render_and_export(): void
    {
        $this->actingAs($this->staff, 'admin');
        $cash = LedgerAccount::where('system_key', 'cash')->first();
        $this->receive(10);

        $this->get('/admin/accounting/ledger?account='.$cash->id)->assertOk()->assertSee('Test receipt');
        $this->get('/admin/accounting/cash-book')->assertOk()->assertSee('Cash in hand');
        $this->get('/admin/accounting/trial-balance')->assertOk();
        $this->get('/admin/accounting/income-statement')->assertOk()->assertSee('Net profit');
        $this->get('/admin/accounting/balance-sheet')->assertOk();
        $this->get('/admin/accounting/trial-balance?export=csv')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_chart_of_accounts_protects_system_accounts(): void
    {
        $this->actingAs($this->staff, 'admin');
        $cash = LedgerAccount::where('system_key', 'cash')->first();
        $custom = LedgerAccount::create(['code' => '5950', 'name' => 'Spare', 'type' => 'expense', 'is_group' => false]);

        $this->delete("/admin/accounts/{$cash->id}")->assertSessionHasErrors('delete');
        $this->delete("/admin/accounts/{$custom->id}")->assertRedirect('/admin/accounts');
        $this->assertNull(LedgerAccount::find($custom->id));
    }

    public function test_accountant_can_post_but_front_desk_cannot(): void
    {
        $accountant = \App\Models\User::create(['firstname' => 'A', 'lastname' => 'C', 'email' => 'acc@example.com', 'password' => \Illuminate\Support\Facades\Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $accountant->assignRole('Accountant');
        $this->actingAs($accountant, 'admin')->get('/admin/accounting/vouchers/create')->assertOk();

        $desk = \App\Models\User::create(['firstname' => 'F', 'lastname' => 'D', 'email' => 'desk@example.com', 'password' => \Illuminate\Support\Facades\Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $desk->assignRole('Front Desk');
        $this->actingAs($desk, 'admin')->get('/admin/accounting/vouchers/create')->assertForbidden();
        $this->get('/admin/accounting/trial-balance')->assertForbidden();
    }
}
