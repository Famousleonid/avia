<?php

// php artisan down --render=errors.503
// php database/scripts/restore-database.php storage/app/backups/db_2026-04-06_183128.sql.gz
// php artisan up





/**
 * Full MySQL restore from a backup file produced by `php artisan db:backup`
 * (.sql.gz or plain .sql).
 *
 * Usage (from project root):
 *   php database/scripts/restore-database.php storage/app/backups/db_2026-04-06_120000.sql.gz
 *
 * Before restore:
 *   1. php artisan down --render=errors.503
 *   2. Stop queue workers / cron that write to the DB.
 *   3. Optionally take a fresh backup of the current state.
 *
 * Env: same .env as the app. Optional BACKUP_MYSQL_PATH to full path to mysql.exe on Windows.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$autoload = $projectRoot . '/vendor/autoload.php';
if (! is_readable($autoload)) {
    fwrite(STDERR, "Run from project root; vendor/autoload.php not found.\n");
    exit(1);
}

require $autoload;

$app = require $projectRoot . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (($argc ?? 0) < 2) {
    fwrite(STDERR, "Usage: php database/scripts/restore-database.php <path-to-backup.sql.gz|.sql>\n");
    exit(1);
}

$path = $argv[1];
if (! is_readable($path)) {
    fwrite(STDERR, "File not readable: {$path}\n");
    exit(1);
}

$conn = config('database.connections.mysql');
if (($conn['driver'] ?? '') !== 'mysql') {
    fwrite(STDERR, 'Only mysql connection is supported.' . "\n");
    exit(1);
}

$database = (string) $conn['database'];
$host = (string) $conn['host'];
$port = (string) ($conn['port'] ?? '3306');
$user = (string) $conn['username'];
$password = (string) ($conn['password'] ?? '');
$socket = (string) ($conn['unix_socket'] ?? '');

$binary = (string) config('backup.mysql_binary', 'mysql');

$sql = '';
$lower = strtolower($path);
if (str_ends_with($lower, '.gz')) {
    $raw = @file_get_contents($path);
    if ($raw === false) {
        fwrite(STDERR, "Cannot read: {$path}\n");
        exit(1);
    }
    $sql = gzdecode($raw);
    if ($sql === false) {
        fwrite(STDERR, "Invalid gzip: {$path}\n");
        exit(1);
    }
} else {
    $sql = file_get_contents($path);
    if ($sql === false) {
        fwrite(STDERR, "Cannot read: {$path}\n");
        exit(1);
    }
}

if ($sql === '') {
    fwrite(STDERR, "Backup file is empty.\n");
    exit(1);
}

$args = [$binary];
if ($socket !== '') {
    $args[] = '--socket=' . $socket;
} else {
    $args[] = '--host=' . $host;
    $args[] = '--port=' . $port;
}
$args[] = '--user=' . $user;
$args[] = '--password=' . $password;
$args[] = '--default-character-set=utf8mb4';
$args[] = $database;

$process = new Symfony\Component\Process\Process($args);
$process->setTimeout(7200);
$process->setInput($sql);
$process->run();

if (! $process->isSuccessful()) {
    fwrite(STDERR, trim($process->getErrorOutput() ?: $process->getOutput()) . "\n");
    exit(1);
}

echo "Restore completed.\n";
exit(0);
