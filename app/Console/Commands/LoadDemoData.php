<?php

namespace App\Console\Commands;

use App\Models\BookedInfo;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

class LoadDemoData extends Command
{
    protected $signature = 'hotel:demo-data {--force : Add the sample data even though reservations already exist}';

    protected $description = 'Fill every module with sample data (rooms, guests, reservations, purchases, staff, housekeeping, transport, halls)';

    public function handle(): int
    {
        if (BookedInfo::exists() && ! $this->option('force')) {
            $this->components->error('This database already has reservations. Demo data is meant for a fresh installation; use --force to add it anyway.');

            return self::FAILURE;
        }

        $this->components->info('Loading demo data…');
        $this->call('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true]);
        $this->components->info('Done. Sign in as the administrator and look around.');

        return self::SUCCESS;
    }
}
