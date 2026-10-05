<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Plain-PHP MySQL/MariaDB dump (no mysqldump needed), written compressed to storage/app/backups. */
class BackupService
{
    private const DIR = 'backups';

    public function disk()
    {
        return Storage::disk('local');
    }

    /** @return list<array{name:string,size:int,modified:int}> newest first */
    public function list(): array
    {
        return collect($this->disk()->files(self::DIR))
            ->filter(fn ($f) => preg_match('/backup-\d{8}-\d{6}\.sql\.gz$/', $f))
            ->map(fn ($f) => ['name' => basename($f), 'size' => $this->disk()->size($f), 'modified' => $this->disk()->lastModified($f)])
            ->sortByDesc('modified')->values()->all();
    }

    public function path(string $name): string
    {
        if (! preg_match('/^backup-\d{8}-\d{6}\.sql\.gz$/', $name) || ! $this->disk()->exists(self::DIR.'/'.$name)) {
            throw new RuntimeException('Backup not found.');
        }

        return $this->disk()->path(self::DIR.'/'.$name);
    }

    public function delete(string $name): void
    {
        $this->path($name);
        $this->disk()->delete(self::DIR.'/'.$name);
    }

    public function create(): string
    {
        $this->disk()->makeDirectory(self::DIR);
        $name = 'backup-'.now()->format('Ymd-His').'.sql.gz';
        $target = $this->disk()->path(self::DIR.'/'.$name);

        $gz = gzopen($target, 'wb6');
        if (! $gz) {
            throw new RuntimeException('Cannot write the backup file.');
        }

        try {
            $pdo = DB::connection()->getPdo();
            gzwrite($gz, '-- Hotel backup '.now()->toDateTimeString()."\n-- Restore with: gunzip < {$name} | mysql -u USER -p DATABASE\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET UNIQUE_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            foreach (DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"') as $row) {
                $table = array_values((array) $row)[0];
                $quoted = '`'.str_replace('`', '``', $table).'`';
                $create = array_values((array) DB::selectOne("SHOW CREATE TABLE {$quoted}"))[1];
                gzwrite($gz, "DROP TABLE IF EXISTS {$quoted};\n{$create};\n\n");

                $batch = [];
                foreach (DB::table($table)->cursor() as $record) {
                    $values = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), (array) $record);
                    $batch[] = '('.implode(',', $values).')';
                    if (count($batch) >= 200) {
                        gzwrite($gz, "INSERT INTO {$quoted} VALUES\n".implode(",\n", $batch).";\n");
                        $batch = [];
                    }
                }
                if ($batch) {
                    gzwrite($gz, "INSERT INTO {$quoted} VALUES\n".implode(",\n", $batch).";\n");
                }
                gzwrite($gz, "\n");
            }

            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\nSET UNIQUE_CHECKS=1;\n");
        } finally {
            gzclose($gz);
        }

        return $name;
    }

    /** Keep only the newest $keep backups. */
    public function prune(int $keep): int
    {
        $old = array_slice($this->list(), $keep);
        foreach ($old as $b) {
            $this->disk()->delete(self::DIR.'/'.$b['name']);
        }

        return count($old);
    }
}
