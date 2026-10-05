<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'hotel:backup {--keep=14 : How many backups to keep}';

    protected $description = 'Create a compressed database backup in storage/app/backups';

    public function handle(BackupService $backups): int
    {
        $name = $backups->create();
        $pruned = $backups->prune(max(1, (int) $this->option('keep')));

        $this->components->info("Backup written: storage/app/backups/{$name}".($pruned ? " ({$pruned} old backup(s) removed)" : ''));

        return self::SUCCESS;
    }
}
