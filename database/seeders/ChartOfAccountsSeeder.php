<?php

namespace Database\Seeders;

use App\Models\LedgerAccount;
use Illuminate\Database\Seeder;

/** A ready-to-use chart of accounts for a hotel. Idempotent; never touches existing accounts. */
class ChartOfAccountsSeeder extends Seeder
{
    /** code => [name, type, parent code, group?, system key, cash?] */
    private const ACCOUNTS = [
        '1000' => ['Assets', 'asset', null, true],
        '1100' => ['Current assets', 'asset', '1000', true],
        '1110' => ['Cash in hand', 'asset', '1100', false, 'cash', true],
        '1120' => ['Bank accounts', 'asset', '1100', true],
        '1121' => ['Main bank account', 'asset', '1120', false, 'bank', true],
        '1130' => ['Online payment gateways', 'asset', '1100', false, 'online', true],
        '1140' => ['Accounts receivable', 'asset', '1100', false, 'receivable'],
        '1150' => ['Inventory', 'asset', '1100', false, 'inventory'],
        '1160' => ['Staff loans & advances', 'asset', '1100', false, 'staff_loans'],
        '1200' => ['Fixed assets', 'asset', '1000', true],
        '1210' => ['Furniture & fixtures', 'asset', '1200', false],
        '1220' => ['Equipment', 'asset', '1200', false],
        '2000' => ['Liabilities', 'liability', null, true],
        '2110' => ['Guest deposits', 'liability', '2000', false, 'guest_deposits'],
        '2120' => ['Accounts payable', 'liability', '2000', false, 'payable'],
        '2130' => ['Taxes payable', 'liability', '2000', false, 'tax_payable'],
        '2140' => ['Salaries payable', 'liability', '2000', false, 'salary_payable'],
        '2150' => ['Payroll tax payable', 'liability', '2000', false, 'payroll_tax_payable'],
        '3000' => ['Equity', 'equity', null, true],
        '3100' => ["Owner's capital", 'equity', '3000', false, 'capital'],
        '3200' => ['Retained earnings', 'equity', '3000', false, 'retained_earnings'],
        '4000' => ['Income', 'income', null, true],
        '4100' => ['Room revenue', 'income', '4000', false, 'room_revenue'],
        '4200' => ['Service charge income', 'income', '4000', false, 'service_income'],
        '4300' => ['Other income', 'income', '4000', false, 'other_income'],
        '5000' => ['Expenses', 'expense', null, true],
        '5100' => ['Consumables & supplies', 'expense', '5000', false, 'consumables'],
        '5200' => ['Salaries & wages', 'expense', '5000', false, 'salary_expense'],
        '5300' => ['Utilities', 'expense', '5000', false],
        '5400' => ['Repairs & maintenance', 'expense', '5000', false],
        '5500' => ['Marketing', 'expense', '5000', false],
        '5600' => ['Administrative expenses', 'expense', '5000', false],
        '5700' => ['Bank & gateway charges', 'expense', '5000', false],
        '5800' => ['Stock write-off', 'expense', '5000', false, 'stock_loss'],
        '5900' => ['Other expenses', 'expense', '5000', false],
    ];

    public function run(): void
    {
        $ids = [];
        foreach (self::ACCOUNTS as $code => $row) {
            [$name, $type, $parent, $group] = $row;
            $account = LedgerAccount::firstOrCreate(['code' => $code], [
                'name' => $name,
                'type' => $type,
                'parent_id' => $parent ? $ids[$parent] : null,
                'is_group' => $group,
                'system_key' => $row[4] ?? null,
                'is_cash' => $row[5] ?? false,
            ]);
            $ids[$code] = $account->id;
        }
    }
}
