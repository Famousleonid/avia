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
                'command' => [$phpBinary, base_path('artisan'), 'test', 'tests/Feature', '--group=smoke'],
            ],
            'feature' => [
                'label' => 'Feature Suite',
                'description' => 'Полный feature-набор проекта.',
                'command' => [$phpBinary, base_path('artisan'), 'test', 'tests/Feature'],
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
            $this->processEnvironment()
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

    public function start(string $suite): array
    {
        $suites = $this->suites();
        abort_unless(isset($suites[$suite]), 404);

        $runtimePath = $this->runtimePath();
        File::ensureDirectoryExists($runtimePath);
        File::ensureDirectoryExists($runtimePath . DIRECTORY_SEPARATOR . 'tmp');

        $this->writeResult($suite, [
            'status' => 'running',
            'exit_code' => null,
            'duration_ms' => null,
            'finished_at' => now()->toDateTimeString(),
            'summary' => 'Running...',
            'output' => 'Test suite is running in the background.',
        ]);

        try {
            $this->spawnDetachedSuiteRunner($suite);
        } catch (\Throwable $e) {
            $this->writeResult($suite, [
                'status' => 'failed',
                'exit_code' => null,
                'duration_ms' => null,
                'finished_at' => now()->toDateTimeString(),
                'summary' => 'Failed to start',
                'output' => $e->getMessage(),
            ]);

            throw $e;
        }

        return array_merge($suites[$suite], $this->readResult($suite));
    }

    protected function extractSummary(string $output, bool $successful): string
    {
        if (preg_match('/OK \(([^)]+)\)/', $output, $matches)) {
            return 'OK (' . $matches[1] . ')';
        }

        if (preg_match('/Tests:\s+([^\r\n]+)/i', $output, $matches)) {
            return trim($matches[0]);
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

    protected function spawnDetachedSuiteRunner(string $suite): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $process = new Process($this->windowsDetachedCommand($suite), base_path());
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
            }

            return;
        }

        $process = new Process(
            [$this->resolvePhpBinary(), base_path('artisan'), 'qa:run-tests', $suite],
            base_path(),
            $this->processEnvironment()
        );

        $process->disableOutput();
        $process->setTimeout(null);
        $process->start();
    }

    protected function windowsDetachedCommand(string $suite): array
    {
        $phpBinary = $this->psQuote($this->resolvePhpBinary());
        $artisan = $this->psQuote(base_path('artisan'));
        $workingDirectory = $this->psQuote(base_path());

        $envAssignments = [];
        foreach ($this->processEnvironment() as $key => $value) {
            $envAssignments[] = '$env:' . $key . '=' . $this->psQuote((string) $value);
        }

        $script = implode('; ', $envAssignments)
            . '; Start-Process -FilePath ' . $phpBinary
            . ' -ArgumentList @(' . $artisan . ', \'qa:run-tests\', ' . $this->psQuote($suite) . ')'
            . ' -WorkingDirectory ' . $workingDirectory
            . ' -WindowStyle Hidden';

        return [
            'powershell.exe',
            '-NoProfile',
            '-NonInteractive',
            '-ExecutionPolicy',
            'Bypass',
            '-Command',
            $script,
        ];
    }

    protected function psQuote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    protected function processEnvironment(): array
    {
        $runtimePath = $this->runtimePath();
        $testEnvironment = $this->testEnvironment();

        return [
            'APP_ENV' => 'testing',
            'LOG_CHANNEL' => 'stderr',
            'SESSION_DRIVER' => 'array',
            'DB_CONNECTION' => $testEnvironment['DB_CONNECTION'],
            'DB_HOST' => $testEnvironment['DB_HOST'],
            'DB_PORT' => $testEnvironment['DB_PORT'],
            'DB_DATABASE' => $testEnvironment['DB_DATABASE'],
            'DB_USERNAME' => $testEnvironment['DB_USERNAME'],
            'DB_PASSWORD' => $testEnvironment['DB_PASSWORD'],
            'VIEW_COMPILED_PATH' => str_replace('\\', '/', $runtimePath . DIRECTORY_SEPARATOR . 'views'),
            'TMP' => $runtimePath . DIRECTORY_SEPARATOR . 'tmp',
            'TEMP' => $runtimePath . DIRECTORY_SEPARATOR . 'tmp',
            'CACHE_DRIVER' => false,
            'QUEUE_CONNECTION' => false,
            'MAIL_MAILER' => false,
        ];
    }

    protected function testEnvironment(): array
    {
        $testing = $this->readEnvFile(base_path('.env.testing'));
        $default = $this->readEnvFile(base_path('.env'));

        return [
            'DB_CONNECTION' => $testing['DB_CONNECTION'] ?? env('DB_CONNECTION', 'mysql'),
            'DB_HOST' => $testing['DB_HOST'] ?? env('DB_HOST', '127.0.0.1'),
            'DB_PORT' => $testing['DB_PORT'] ?? env('DB_PORT', '3306'),
            'DB_DATABASE' => $testing['DB_DATABASE'] ?? env('DB_DATABASE', ''),
            'DB_USERNAME' => $testing['DB_USERNAME'] ?? env('DB_USERNAME', ''),
            'DB_PASSWORD' => $testing['DB_PASSWORD'] !== ''
                ? $testing['DB_PASSWORD']
                : ($default['DB_PASSWORD'] ?? env('DB_PASSWORD', '')),
        ];
    }

    protected function readEnvFile(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        $values = [];

        foreach (preg_split('/\r\n|\r|\n/', (string) File::get($path)) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }

        return $values;
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
