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

$inputStream = null;
$tempSqlPath = null;
$lower = strtolower($path);
if (str_ends_with($lower, '.gz')) {
    $gzip = @gzopen($path, 'rb');
    if ($gzip === false) {
        fwrite(STDERR, "Cannot read gzip: {$path}\n");
        exit(1);
    }

    $tempSqlPath = tempnam(sys_get_temp_dir(), 'db-restore-');
    if ($tempSqlPath === false) {
        gzclose($gzip);
        fwrite(STDERR, "Cannot allocate temp file for restore.\n");
        exit(1);
    }

    $tempHandle = fopen($tempSqlPath, 'wb');
    if ($tempHandle === false) {
        gzclose($gzip);
        @unlink($tempSqlPath);
        fwrite(STDERR, "Cannot open temp file for restore.\n");
        exit(1);
    }

    while (! gzeof($gzip)) {
        $chunk = gzread($gzip, 1024 * 1024);
        if ($chunk === false) {
            fclose($tempHandle);
            gzclose($gzip);
            @unlink($tempSqlPath);
            fwrite(STDERR, "Invalid gzip: {$path}\n");
            exit(1);
        }

        if ($chunk !== '' && fwrite($tempHandle, $chunk) === false) {
            fclose($tempHandle);
            gzclose($gzip);
            @unlink($tempSqlPath);
            fwrite(STDERR, "Cannot write temp SQL file.\n");
            exit(1);
        }
    }

    fclose($tempHandle);
    gzclose($gzip);
    $inputStream = fopen($tempSqlPath, 'rb');
} else {
    $inputStream = fopen($path, 'rb');
}

if (! is_resource($inputStream)) {
    if ($tempSqlPath !== null) {
        @unlink($tempSqlPath);
    }

    fwrite(STDERR, "Cannot open SQL input stream: {$path}\n");
    exit(1);
}

$stats = fstat($inputStream);
if (($stats['size'] ?? 0) === 0) {
    fclose($inputStream);
    if ($tempSqlPath !== null) {
        @unlink($tempSqlPath);
    }

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
$process->setInput($inputStream);
$process->run();

fclose($inputStream);
if ($tempSqlPath !== null) {
    @unlink($tempSqlPath);
}

if (! $process->isSuccessful()) {
    fwrite(STDERR, trim($process->getErrorOutput() ?: $process->getOutput()) . "\n");
    exit(1);
}

echo "Restore completed.\n";
exit(0);
