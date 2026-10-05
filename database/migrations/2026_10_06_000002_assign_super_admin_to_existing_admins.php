<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** Staff flagged as administrators in the old system become Super Admins. */
    public function up(): void
    {
        if (! Schema::hasTable('user') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role = Role::findOrCreate('Super Admin', 'admin');

        User::query()->where('is_admin', 1)->where('usertype', 1)->each(function (User $user) use ($role) {
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        });
    }

    public function down(): void {}
};
