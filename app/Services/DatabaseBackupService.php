<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    /**
     * Full logical backup of default MySQL database → gzip in storage/app/backups.
     *
     * @return string Absolute path to .sql.gz file
     */
    public function createBackup(): string
    {
        $conn = Config::get('database.connections.mysql');
        if ($conn === null || ($conn['driver'] ?? '') !== 'mysql') {
            throw new \RuntimeException('Only mysql driver is supported for backup.');
        }

        $database = (string) $conn['database'];
        $host = (string) $conn['host'];
        $port = (string) ($conn['port'] ?? '3306');
        $user = (string) $conn['username'];
        $password = (string) ($conn['password'] ?? '');
        $socket = (string) ($conn['unix_socket'] ?? '');

        $binary = (string) Config::get('backup.mysqldump_binary', 'mysqldump');
        $dirName = (string) Config::get('backup.directory', 'backups');
        $backupDir = storage_path('app/' . trim($dirName, '/'));

        if (! is_dir($backupDir) && ! mkdir($backupDir, 0755, true) && ! is_dir($backupDir)) {
            throw new \RuntimeException('Cannot create backup directory: ' . $backupDir);
        }

        $fileBase = 'db_' . date('Y-m-d_His') . '.sql.gz';
        $pathGz = $backupDir . DIRECTORY_SEPARATOR . $fileBase;

        $args = [$binary];
        if ($socket !== '') {
            $args[] = '--socket=' . $socket;
        } else {
            $args[] = '--host=' . $host;
            $args[] = '--port=' . $port;
        }
        $args[] = '--user=' . $user;
        $args[] = '--password=' . $password;
        $args[] = '--single-transaction';
        $args[] = '--routines';
        $args[] = '--triggers';
        $args[] = '--default-character-set=utf8mb4';
        $args[] = $database;

        $process = new Process($args);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(
                trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'mysqldump failed'
            );
        }

        $sql = $process->getOutput();
        if ($sql === '') {
            throw new \RuntimeException('mysqldump produced empty output.');
        }

        $gz = gzencode($sql, 9);
        if ($gz === false) {
            throw new \RuntimeException('gzip encode failed.');
        }

        if (file_put_contents($pathGz, $gz) === false) {
            throw new \RuntimeException('Cannot write backup file: ' . $pathGz);
        }

        $this->pruneOldBackups($backupDir);

        return $pathGz;
    }

    private function pruneOldBackups(string $backupDir): void
    {
        $keepDays = (int) Config::get('backup.keep_days', 14);
        if ($keepDays <= 0) {
            return;
        }

        $cut = time() - ($keepDays * 86400);
        foreach (glob($backupDir . DIRECTORY_SEPARATOR . 'db_*.sql.gz') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cut) {
                @unlink($file);
            }
        }
    }
}
