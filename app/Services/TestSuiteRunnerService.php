<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class TestSuiteRunnerService
{
    public function suites(): array
    {
        $phpBinary = $this->resolvePhpBinary();

        return [
            'smoke' => [
                'label' => 'Smoke Suite',
                'description' => 'Быстрая проверка ключевых экранов и критичных сценариев.',
                'command' => [$phpBinary, base_path('vendor/phpunit/phpunit/phpunit'), 'tests/Feature', '--group', 'smoke'],
            ],
            'feature' => [
                'label' => 'Feature Suite',
                'description' => 'Полный feature-набор проекта.',
                'command' => [$phpBinary, base_path('vendor/phpunit/phpunit/phpunit'), 'tests/Feature'],
            ],
        ];
    }

    public function allResults(): array
    {
        $results = [];

        foreach ($this->suites() as $suite => $meta) {
            $results[$suite] = array_merge($meta, $this->readResult($suite));
        }

        return $results;
    }

    public function run(string $suite): array
    {
        $suites = $this->suites();
        abort_unless(isset($suites[$suite]), 404);

        $runtimePath = $this->runtimePath();
        File::ensureDirectoryExists($runtimePath);
        File::ensureDirectoryExists($runtimePath . DIRECTORY_SEPARATOR . 'views');
        File::ensureDirectoryExists($runtimePath . DIRECTORY_SEPARATOR . 'disks' . DIRECTORY_SEPARATOR . 'public');
        File::ensureDirectoryExists($runtimePath . DIRECTORY_SEPARATOR . 'temp-media');
        File::ensureDirectoryExists($runtimePath . DIRECTORY_SEPARATOR . 'tmp');

        $process = new Process(
            $suites[$suite]['command'],
            base_path(),
            [
                'APP_ENV' => 'testing',
                'LOG_CHANNEL' => 'stderr',
                'SESSION_DRIVER' => 'array',
                'VIEW_COMPILED_PATH' => str_replace('\\', '/', $runtimePath . DIRECTORY_SEPARATOR . 'views'),
                'TMP' => $runtimePath . DIRECTORY_SEPARATOR . 'tmp',
                'TEMP' => $runtimePath . DIRECTORY_SEPARATOR . 'tmp',
                'DB_CONNECTION' => false,
                'DB_HOST' => false,
                'DB_PORT' => false,
                'DB_DATABASE' => false,
                'DB_USERNAME' => false,
                'DB_PASSWORD' => false,
                'CACHE_DRIVER' => false,
                'QUEUE_CONNECTION' => false,
                'MAIL_MAILER' => false,
            ]
        );

        $process->setTimeout(900);

        $startedAt = microtime(true);
        $process->run();
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $output = trim($process->getOutput() . PHP_EOL . $process->getErrorOutput());

        $result = [
            'status' => $process->isSuccessful() ? 'passed' : 'failed',
            'exit_code' => $process->getExitCode(),
            'duration_ms' => $durationMs,
            'finished_at' => now()->toDateTimeString(),
            'summary' => $this->extractSummary($output, $process->isSuccessful()),
            'output' => $output,
        ];

        $this->writeResult($suite, $result);

        return array_merge($suites[$suite], $result);
    }

    protected function extractSummary(string $output, bool $successful): string
    {
        if (preg_match('/OK \(([^)]+)\)/', $output, $matches)) {
            return 'OK (' . $matches[1] . ')';
        }

        if (preg_match('/(FAILURES!|ERRORS!)/', $output, $matches)) {
            return $matches[1];
        }

        return $successful ? 'Completed successfully' : 'Finished with failures';
    }

    protected function readResult(string $suite): array
    {
        $path = $this->resultPath($suite);

        if (!File::exists($path)) {
            return [
                'status' => 'unknown',
                'exit_code' => null,
                'duration_ms' => null,
                'finished_at' => null,
                'summary' => 'Ещё не запускался',
                'output' => '',
            ];
        }

        $decoded = json_decode(File::get($path), true);

        return is_array($decoded) ? $decoded : [
            'status' => 'unknown',
            'exit_code' => null,
            'duration_ms' => null,
            'finished_at' => null,
            'summary' => 'Не удалось прочитать результат',
            'output' => '',
        ];
    }

    protected function writeResult(string $suite, array $result): void
    {
        File::ensureDirectoryExists(dirname($this->resultPath($suite)));
        File::put($this->resultPath($suite), json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function resultPath(string $suite): string
    {
        return $this->runtimePath() . DIRECTORY_SEPARATOR . 'results' . DIRECTORY_SEPARATOR . $suite . '.json';
    }

    protected function runtimePath(): string
    {
        return base_path('codex-test-runtime');
    }

    protected function resolvePhpBinary(): string
    {
        $candidates = array_filter([
            env('QA_PHP_BINARY'),
            PHP_BINARY,
            PHP_BINDIR . DIRECTORY_SEPARATOR . 'php.exe',
            PHP_BINDIR . DIRECTORY_SEPARATOR . 'php',
            'php',
        ]);

        foreach ($candidates as $candidate) {
            $candidate = (string) $candidate;

            if ($candidate === '') {
                continue;
            }

            $basename = strtolower(basename($candidate));
            if (in_array($basename, ['httpd.exe', 'apache.exe'], true)) {
                continue;
            }

            if ($candidate === 'php' || is_file($candidate)) {
                return $candidate;
            }
        }

        return 'php';
    }
}
