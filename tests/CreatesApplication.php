<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if ($app->environment('testing')) {
            $connection = $app['config']->get('database.default');
            $database = (string) $app['config']->get("database.connections.{$connection}.database", '');
            $normalizedDatabase = strtolower(trim($database));
            $defaultDatabase = $this->readEnvValue(__DIR__ . '/../.env', 'DB_DATABASE');
            $normalizedDefaultDatabase = strtolower(trim((string) $defaultDatabase));

            if ($normalizedDatabase === '' || ! str_contains($normalizedDatabase, 'testing')) {
                throw new RuntimeException(sprintf(
                    'Refusing to run tests against non-testing database [%s] on connection [%s].',
                    $database !== '' ? $database : 'undefined',
                    $connection
                ));
            }

            if ($normalizedDefaultDatabase !== '' && $normalizedDatabase === $normalizedDefaultDatabase) {
                throw new RuntimeException(sprintf(
                    'Refusing to run tests because testing database [%s] matches .env DB_DATABASE.',
                    $database
                ));
            }
        }

        return $app;
    }

    private function readEnvValue(string $path, string $key): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$envKey, $value] = explode('=', $line, 2);

            if (trim($envKey) === $key) {
                return trim($value, " \t\n\r\0\x0B\"'");
            }
        }

        return null;
    }
}
