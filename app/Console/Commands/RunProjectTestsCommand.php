<?php

namespace App\Console\Commands;

use App\Services\TestSuiteRunnerService;
use Illuminate\Console\Command;

class RunProjectTestsCommand extends Command
{
    protected $signature = 'qa:run-tests {suite=smoke : smoke|feature}';

    protected $description = 'Run project test suite and persist the latest result.';

    public function handle(TestSuiteRunnerService $runner): int
    {
        $suite = (string) $this->argument('suite');
        $result = $runner->run($suite);

        $this->line($result['label'] . ': ' . $result['summary']);
        $this->line('Status: ' . $result['status']);
        $this->line('Duration: ' . ($result['duration_ms'] ?? 0) . ' ms');

        return $result['status'] === 'passed' ? self::SUCCESS : self::FAILURE;
    }
}
