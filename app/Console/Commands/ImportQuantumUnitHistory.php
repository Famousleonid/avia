<?php
namespace App\Console\Commands;

use App\Services\QuantumUnitHistory;
use Illuminate\Console\Command;

class ImportQuantumUnitHistory extends Command
{
    protected $signature = 'quantum-units:import {file}';
    protected $description = 'Import a complete read-only Quantum unit history export';
    public function handle(QuantumUnitHistory $history): int
    {
        $payload = json_decode(file_get_contents($this->argument('file')), true, 512, JSON_THROW_ON_ERROR);
        $history->import($payload);
        $this->info('Imported ' . count($payload['rows']) . ' Quantum workorders.');
        return 0;
    }
}
