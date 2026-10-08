<?php

namespace App\Support;

use App\Admin\ResourceRegistry;

/** Every permission in the system and the default roles built from them. */
class Permissions
{
    /** Non-CRUD permissions: name => description. */
    public const EXTRA = [
        'dashboard.view' => 'See the dashboard',
        'reservations.view' => 'View reservations',
        'reservations.create' => 'Create reservations',
        'reservations.edit' => 'Edit reservations',
        'reservations.status' => 'Confirm, check in, check out and cancel',
        'reservations.payments' => 'Take payments',
        'purchases.view' => 'View purchases and supplier balances',
        'purchases.create' => 'Record purchases and returns',
        'purchases.pay' => 'Pay suppliers',
        'stock.view' => 'View stock levels and movements',
        'stock.adjust' => 'Issue stock, write off and count stock',
        'hr-attendance.manage' => 'Record attendance',
        'hr-roster.view' => 'View the duty roster and attendance dashboard',
        'hr-roster.manage' => 'Assign duty rosters',
        'hk-tasks.view' => 'View cleaning tasks, room QR codes and housekeeping reports',
        'hk-tasks.manage' => 'Assign and update cleaning tasks',
        'hk-laundry.view' => 'View laundry orders and payments',
        'hk-laundry.manage' => 'Create laundry orders and take payments',
        'hr-leave.manage' => 'Manage leave requests',
        'hr-loans.manage' => 'Issue staff loans',
        'hr-payroll.view' => 'View payroll and salaries',
        'hr-payroll.run' => 'Run payroll and edit salaries',
        'reports.view' => 'View reports',
        'accounts.view' => 'View accounts',
        'accounts.manage' => 'Post vouchers and manage the chart of accounts',
        'settings.manage' => 'Change hotel settings',
        'users.manage' => 'Manage staff accounts',
        'roles.manage' => 'Manage roles and permissions',
        'backup.manage' => 'Create and download backups',
    ];

    /** @return list<string> */
    public static function all(): array
    {
        return array_values(array_unique(array_merge(array_keys(self::EXTRA), ResourceRegistry::permissions(), self::moduleCrud())));
    }

    /**
     * Permission names granted to each default role. "*" patterns match by prefix.
     *
     * @return array<string,list<string>>
     */
    public static function roles(): array
    {
        return [
            'Super Admin' => [],
            'Manager' => ['dashboard.', 'hk-', 'tr-', 'reservations.', 'reports.', 'purchases.', 'stock.', 'inv-', 'room-types.', 'rooms.', 'floors.', 'bed-types.', 'size-units.', 'facility-types.', 'facilities.', 'room-facilities.', 'room-images.', 'offers.', 'services.', 'taxes.', 'currencies.', 'promo-codes.', 'wake-up-calls.', 'customers.', 'star-classes.', 'booking-types.', 'accounts.view'],
            'Front Desk' => ['dashboard.view', 'tr-', 'reservations.view', 'reservations.create', 'reservations.edit', 'reservations.status', 'reservations.payments', 'customers.view', 'customers.create', 'customers.edit', 'wake-up-calls.', 'rooms.view', 'room-types.view', 'offers.view', 'promo-codes.view'],
            'Accountant' => ['dashboard.view', 'reservations.view', 'reservations.payments', 'reports.view', 'accounts.', 'taxes.', 'currencies.', 'payment-methods.view', 'purchases.view', 'purchases.pay', 'inv-suppliers.view', 'hr-payroll.view'],
            'HR Manager' => ['dashboard.view', 'hr-'],
            'Housekeeping' => ['dashboard.view', 'hk-tasks.', 'hk-laundry.'],
            'Store Keeper' => ['dashboard.view', 'inv-', 'purchases.view', 'purchases.create', 'stock.', 'reports.view'],
        ];
    }

    /** Permission names created by module resources registered later (slugs prefixed hr-/inv-/...). */
    private static function moduleCrud(): array
    {
        return [];
    }

    /** @return list<string> permission names a role pattern list expands to */
    public static function expand(array $patterns): array
    {
        $all = self::all();

        return array_values(array_filter($all, function ($name) use ($patterns) {
            foreach ($patterns as $pattern) {
                if ($name === $pattern || (str_ends_with($pattern, '.') || str_ends_with($pattern, '-')) && str_starts_with($name, $pattern)) {
                    return true;
                }
            }

            return false;
        }));
    }
}
