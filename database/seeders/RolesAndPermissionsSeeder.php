<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Idempotent: safe to run on every deployment to pick up new permissions. */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::all() as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        foreach (Permissions::roles() as $roleName => $patterns) {
            $role = Role::findOrCreate($roleName, 'admin');
            // Default roles are only filled the first time, so edits made in the UI survive re-seeding.
            if ($role->wasRecentlyCreated && $patterns) {
                $role->syncPermissions(Permissions::expand($patterns));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
