<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Support\Facades\Schema;

/**
 * Hotels upgrading from the old CodeIgniter system already have all tables. This marks the baseline
 * schema migrations as applied so that `php artisan migrate` only runs the newer ones.
 */
class AdoptExistingDatabase extends Command
{
    protected $signature = 'hotel:adopt-existing-database {--force : Skip the confirmation}';

    protected $description = 'Mark the baseline schema migrations as run on a database created by the old system';

    public function handle(MigrationRepositoryInterface $repository): int
    {
        if (! Schema::hasTable('user') || ! Schema::hasTable('roomdetails')) {
            $this->components->error('This does not look like an existing hotel database (tables "user" and "roomdetails" are missing). Use `php artisan migrate` instead.');

            return self::FAILURE;
        }

        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }

        $baseline = collect(glob(database_path('migrations/2026_10_05_065551_create_*_table.php')))
            ->map(fn ($file) => basename($file, '.php'));

        $already = collect($repository->getRan());
        $todo = $baseline->diff($already)->values();

        if ($todo->isEmpty()) {
            $this->components->info('Nothing to do: the baseline is already recorded.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Record {$todo->count()} baseline migrations as already applied?", true)) {
            return self::FAILURE;
        }

        $batch = $repository->getNextBatchNumber();
        foreach ($todo as $migration) {
            $repository->log($migration, $batch);
        }

        $this->components->info("Recorded {$todo->count()} migrations. Now run `php artisan migrate` and `php artisan db:seed`.");

        return self::SUCCESS;
    }
}
