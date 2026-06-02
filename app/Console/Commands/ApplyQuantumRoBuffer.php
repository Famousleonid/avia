<?php

namespace App\Console\Commands;

use App\Services\QuantumRoBufferApplyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ApplyQuantumRoBuffer extends Command
{
    protected $signature = 'quantum-ro:apply {--limit=200} {--dry-run}';

    protected $description = 'Apply staged Quantum RO rows from quantum_ro_lines to avia target tables.';

    public function handle(QuantumRoBufferApplyService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $stats = $service->apply($limit, $dryRun);
        $status = $stats['errors'] > 0 ? 'error' : 'ok';

        $line = sprintf(
            '[%s] status=%s scanned=%d applied=%d unchanged=%d unresolved=%d not_applicable=%d errors=%d result="%s"',
            now()->format('Y-m-d H:i:s'),
            $status,
            $stats['scanned'],
            $stats['applied'],
            $stats['unchanged'],
            $stats['unresolved'],
            $stats['not_applicable'] ?? 0,
            $stats['errors'],
            $this->resultText($stats)
        );

        $logPath = storage_path('logs/quantum_ro_apply.log');
        File::ensureDirectoryExists(dirname($logPath));
        file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND | LOCK_EX);

        if (! $this->output->isQuiet()) {
            $this->line($line);
        }

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resultText(array $stats): string
    {
        if ($stats['dry_run']) {
            return 'dry run';
        }

        if ($stats['scanned'] === 0) {
            return 'nothing to parse';
        }

        if ($stats['applied'] > 0) {
            return 'rows applied';
        }

        if ($stats['unresolved'] > 0) {
            return 'unresolved rows';
        }

        if (($stats['not_applicable'] ?? 0) > 0) {
            return 'N/A rows';
        }

        return 'nothing changed';
    }
}
