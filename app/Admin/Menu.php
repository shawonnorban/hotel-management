<?php

namespace App\Admin;

use Illuminate\Contracts\Auth\Authenticatable;

/** Builds the back-office sidebar, showing only what the signed-in staff member may use. */
class Menu
{
    /**
     * Hand-written (non-CRUD) screens: [group, label, icon, route name, permission].
     * Resource screens are added automatically from the registry.
     */
    private const SCREENS = [
        ['Overview', 'Dashboard', 'bi-speedometer2', 'admin.dashboard', 'dashboard.view'],
        ['Front desk', 'Reservations', 'bi-calendar-check', 'admin.reservations.index', 'reservations.view'],
        ['Administration', 'Backups', 'bi-database', 'admin.backups.index', 'backup.manage'],
        ['Administration', 'Roles & permissions', 'bi-shield-lock', 'admin.roles.index', 'roles.manage'],
        ['Administration', 'Hotel settings', 'bi-gear', 'admin.settings.edit', 'settings.manage'],
        ['Administration', 'Payment gateways', 'bi-credit-card', 'admin.gateways.index', 'settings.manage'],
        ['Reports', 'All reports', 'bi-file-earmark-bar-graph', 'admin.reports.index', 'reports.view'],
        ['Human resources', 'Attendance', 'bi-clock-history', 'admin.hr.attendance', 'hr-attendance.manage'],
        ['Human resources', 'Leave', 'bi-calendar2-minus', 'admin.hr.leave.index', 'hr-leave.manage'],
        ['Human resources', 'Staff loans', 'bi-cash-coin', 'admin.hr.loans.index', 'hr-loans.manage'],
        ['Human resources', 'Payroll', 'bi-cash-stack', 'admin.hr.payroll.index', 'hr-payroll.view'],
        ['Purchasing', 'Purchases', 'bi-cart-check', 'admin.purchases.index', 'purchases.view'],
        ['Purchasing', 'Stock levels', 'bi-boxes', 'admin.stock.index', 'stock.view'],
        ['Accounting', 'Vouchers', 'bi-journal-text', 'admin.vouchers.index', 'accounts.view'],
        ['Accounting', 'Account ledger', 'bi-book', 'admin.accounting.ledger', 'accounts.view'],
        ['Accounting', 'Cash & bank book', 'bi-wallet2', 'admin.accounting.cash-book', 'accounts.view'],
        ['Accounting', 'Trial balance', 'bi-calculator', 'admin.accounting.trial-balance', 'accounts.view'],
        ['Accounting', 'Income statement', 'bi-graph-up-arrow', 'admin.accounting.income-statement', 'accounts.view'],
        ['Accounting', 'Balance sheet', 'bi-bar-chart-steps', 'admin.accounting.balance-sheet', 'accounts.view'],
    ];

    /** Order in which groups appear in the sidebar. */
    private const GROUP_ORDER = ['Overview', 'Front desk', 'Hotel setup', 'Guests & sales', 'Accounting', 'Purchasing', 'Human resources', 'Reports', 'Website', 'Administration'];

    /** @var list<array{0:string,1:string,2:string,3:string,4:?string}> */
    private static array $extra = [];

    /** Register an additional screen from a module. */
    public static function add(string $group, string $label, string $icon, string $route, ?string $permission = null): void
    {
        self::$extra[] = [$group, $label, $icon, $route, $permission];
    }

    /** @return array<string,list<array{label:string,icon:string,url:string,active:bool}>> */
    public static function for(?Authenticatable $user): array
    {
        $items = [];

        foreach (array_merge(self::SCREENS, self::$extra) as [$group, $label, $icon, $route, $permission]) {
            if ($permission && ! $user?->can($permission)) {
                continue;
            }
            $items[$group][] = ['label' => $label, 'icon' => $icon, 'url' => route($route), 'active' => request()->routeIs(str_ends_with($route, '.index') ? substr($route, 0, -5).'*' : $route.'*')];
        }

        foreach (ResourceRegistry::all() as $slug => $class) {
            if (! $user?->can($slug.'.view')) {
                continue;
            }
            $items[$class::$group][] = [
                'label' => $class::$label,
                'icon' => $class::$icon,
                'url' => route('admin.resource.index', $slug),
                'active' => request()->is('admin/'.$slug) || request()->is('admin/'.$slug.'/*'),
            ];
        }

        $rank = fn ($group) => ($i = array_search($group, self::GROUP_ORDER, true)) === false ? 99 : $i;
        uksort($items, fn ($a, $b) => $rank($a) <=> $rank($b));

        return $items;
    }
}
