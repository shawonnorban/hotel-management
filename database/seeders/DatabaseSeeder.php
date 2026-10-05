<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([BaseDataSeeder::class, ChartOfAccountsSeeder::class, RolesAndPermissionsSeeder::class, PagesSeeder::class]);

        // First administrator for a brand-new installation. Change the password after signing in.
        if (! User::where('usertype', 1)->exists()) {
            $admin = User::create([
                'firstname' => 'System',
                'lastname' => 'Administrator',
                'email' => env('ADMIN_EMAIL', 'admin@example.com'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe!2026')),
                'status' => 1,
                'usertype' => 1,
                'is_admin' => 1,
            ]);
            $admin->assignRole('Super Admin');
        }
    }
}
