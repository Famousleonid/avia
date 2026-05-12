<?php

namespace App\Console\Commands;

use App\Models\Manual;
use App\Models\ManualServiceBulletin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportManualServiceBulletins extends Command
{
    protected $signature = 'manual-service-bulletins:import
        {manual_id : Manual ID to attach rows to}
        {csv_path : Path to the Service Bulletin CSV}
        {--replace : Soft-delete existing SB rows for this manual before import}';

    protected $description = 'Import Service Bulletin Log rows into manual_service_bulletins.';

    public function handle(): int
    {
        $manual = Manual::find((int) $this->argument('manual_id'));
        if (! $manual) {
            $this->error('Manual not found.');

            return self::FAILURE;
        }

        $path = (string) $this->argument('csv_path');
        if (! is_readable($path)) {
            $this->error('CSV file is not readable: '.$path);

            return self::FAILURE;
        }

        $rows = $this->readRows($path);
        if ($rows === []) {
            $this->warn('No Service Bulletin rows found in CSV.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($manual, $rows) {
            if ($this->option('replace')) {
                ManualServiceBulletin::query()
                    ->where('manual_id', $manual->id)
                    ->delete();
            }

            foreach ($rows as $index => $row) {
                ManualServiceBulletin::create([
                    'manual_id' => $manual->id,
                    'sort_order' => $index + 1,
                    'year_introduced' => $row['year_introduced'],
                    'ac_mfg_service_bulletin_no' => $row['ac_mfg_service_bulletin_no'],
                    'oem_service_bulletin_no' => $row['oem_service_bulletin_no'],
                    'awd_no' => $row['awd_no'],
                    'identification_method' => $row['identification_method'],
                    'description' => $row['description'],
                    'default_requirement' => $row['default_requirement'],
                    'is_active' => true,
                ]);
            }
        });

        $this->info("Imported ".count($rows)." Service Bulletin rows for manual {$manual->id} ({$manual->number}).");

        return self::SUCCESS;
    }

    private function readRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            return [];
        }

        $rows = [];
        $headerFound = false;

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_pad($row, 12, '');
            $firstCell = $this->clean($row[0]);

            if (! $headerFound) {
                $headerFound = strcasecmp($firstCell, 'Year Introduced') === 0;
                continue;
            }

            $sourceCells = array_slice($row, 0, 6);
            $hasSourceData = collect($sourceCells)
                ->map(fn ($value) => $this->clean($value))
                ->filter(fn ($value) => $value !== '')
                ->isNotEmpty();

            if (! $hasSourceData) {
                continue;
            }

            $rows[] = [
                'year_introduced' => $this->clean($row[0]),
                'ac_mfg_service_bulletin_no' => $this->clean($row[1]),
                'oem_service_bulletin_no' => $this->clean($row[2]),
                'awd_no' => $this->clean($row[3]),
                'identification_method' => $this->clean($row[4]),
                'description' => $this->clean($row[5]),
                'default_requirement' => $this->requirementFromRow($row),
            ];
        }

        fclose($handle);

        return $rows;
    }

    private function requirementFromRow(array $row): ?string
    {
        if (strtoupper($this->clean($row[11] ?? '')) === 'X') {
            return ManualServiceBulletin::REQUIREMENT_MANDATORY;
        }

        if (strtoupper($this->clean($row[10] ?? '')) === 'X') {
            return ManualServiceBulletin::REQUIREMENT_RECOMMENDED;
        }

        if (strtoupper($this->clean($row[9] ?? '')) === 'X') {
            return ManualServiceBulletin::REQUIREMENT_OPTIONAL;
        }

        return null;
    }

    private function clean(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    }
}
