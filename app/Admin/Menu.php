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
        ['Overview', 'Dashboard', 'bi-speedometer2', 'admin.dashboard', null],
    ];

    /** Order in which groups appear in the sidebar. */
    private const GROUP_ORDER = ['Overview', 'Front desk', 'Hotel setup', 'Guests & sales', 'Accounting', 'Purchasing', 'Human resources', 'Reports', 'Administration'];

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
            $items[$group][] = ['label' => $label, 'icon' => $icon, 'url' => route($route), 'active' => request()->routeIs($route.'*')];
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

        uksort($items, fn ($a, $b) => (array_search($a, self::GROUP_ORDER) ?: 99) <=> (array_search($b, self::GROUP_ORDER) ?: 99));

        return $items;
    }
}
