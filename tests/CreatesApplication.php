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

            if ($normalizedDatabase === '' || ! str_contains($normalizedDatabase, 'testing')) {
                throw new RuntimeException(sprintf(
                    'Refusing to run tests against non-testing database [%s] on connection [%s].',
                    $database !== '' ? $database : 'undefined',
                    $connection
                ));
            }
        }

        return $app;
    }
}
