<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'Create a full compressed SQL dump of the MySQL database (storage/app/backups)';

    public function handle(DatabaseBackupService $service): int
    {
        try {
            $path = $service->createBackup();
            $this->info('Backup created: ' . $path);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
