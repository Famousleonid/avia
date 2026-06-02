<?php

namespace App\Services;

use App\Models\ProcessName;
use App\Models\QuantumRoLine;
use App\Models\StdProcess;
use App\Models\TdrProcess;
use App\Models\Vendor;
use App\Models\WoBushingBatch;
use App\Models\Workorder;
use App\Models\WorkorderStdProcess;
use App\Support\WoBushingProcessColumnKey;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuantumRoBufferApplyService
{
    private const DATE_USER_NAME = 'Quantum';
    private const STATUS_APPLIED = 'applied';
    private const STATUS_ERROR = 'error';
    private const STATUS_NA = 'N/A';
    private const STATUS_PENDING = 'pending';
    private const STATUS_UNRESOLVED = 'unresolved';
    private const STATUS_WO_NOT_FOUND_OLD = 'WO not found: old';
    private const OLD_WORKORDER_NUMBER_CUTOFF = 107000;
    private const TARGET_STD = 'std';
    private const TARGET_BUSHING_BATCH = 'bushing_batch';
    private const TARGET_TDR = 'tdr';
    private const TARGET_UNSUPPORTED = 'unsupported';

    private array $columnExistsCache = [];

    public function apply(int $limit = 200, bool $dryRun = false): array
    {
        $stats = [
            'scanned' => 0,
            'applied' => 0,
            'unchanged' => 0,
            'unresolved' => 0,
            'not_applicable' => 0,
            'errors' => 0,
            'dry_run' => $dryRun,
        ];

        $codeMap = $this->processNameCodeMap();

        $lines = QuantumRoLine::query()
            ->where(function ($query): void {
                $query
                    ->where(function ($withPn): void {
                        $withPn
                            ->whereNotNull('pn')
                            ->where('pn', '<>', '');
                    })
                    ->orWhere(function ($withRef): void {
                        $withRef
                            ->whereNotNull('bom_ref')
                            ->where('bom_ref', '<>', '');
                    });
            })
            ->where(function ($query): void {
                $query
                    ->whereNull('apply_status')
                    ->orWhere('apply_status', '')
                    ->orWhere('apply_status', self::STATUS_PENDING)
                    ->orWhereIn('apply_status', [self::STATUS_UNRESOLVED, self::STATUS_ERROR])
                    ->orWhereColumn('applied_source_hash', '<>', 'source_hash')
                    ->orWhereNull('applied_source_hash');
            })
            ->orderBy('source_last_modified')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($lines as $line) {
            $stats['scanned']++;

            try {
                $result = DB::transaction(fn (): array => $this->applyLine($line->fresh(), $codeMap, $dryRun));
                $stats[$result['status']]++;
            } catch (\Throwable $e) {
                $stats['errors']++;
                $this->markLine($line, self::STATUS_ERROR, $e->getMessage(), null, null, $dryRun);
            }
        }

        return $stats;
    }

    private function applyLine(QuantumRoLine $line, Collection $codeMap, bool $dryRun): array
    {
        $route = $this->routeForLine($line);

        if ($route['type'] === self::TARGET_UNSUPPORTED) {
            $this->markLine($line, self::STATUS_UNRESOLVED, $route['message'], null, null, $dryRun);

            return ['status' => 'unresolved'];
        }

        $workorder = $this->findWorkorder($line->wo_number);
        if (! $workorder) {
            $status = $this->isOldWorkorderNumber($line->wo_number)
                ? self::STATUS_WO_NOT_FOUND_OLD
                : self::STATUS_NA;
            $message = $status === self::STATUS_WO_NOT_FOUND_OLD
                ? "WO not found: old ({$line->wo_number})"
                : "Workorder not found: {$line->wo_number}";

            $this->markLine($line, $status, $message, null, null, $dryRun);

            return ['status' => 'not_applicable'];
        }

        $vendor = $this->findVendor($line->vendor_name);
        if (! $vendor) {
            $this->markLine($line, self::STATUS_UNRESOLVED, "Vendor not found: {$line->vendor_name}", null, null, $dryRun);

            return ['status' => 'unresolved'];
        }

        $target = $this->findTarget($line, $workorder, $route, $codeMap);

        if ($target['status'] !== 'ok') {
            $this->markLine($line, self::STATUS_UNRESOLVED, $target['message'], null, null, $dryRun);

            return ['status' => 'unresolved'];
        }

        /** @var TdrProcess|WorkorderStdProcess|WoBushingBatch $model */
        $model = $target['model'];
        $table = $model->getTable();
        $before = $model->getAttributes();

        $this->fillTarget($model, $line, $vendor);

        if (! $dryRun && $model->isDirty()) {
            $model->save();
        }

        $changed = $model->isDirty() || $before != $model->getAttributes();
        $message = ($changed ? 'Applied' : 'Already current') . " to {$table}:{$model->getKey()}";

        $this->markLine($line, self::STATUS_APPLIED, $message, $table, (int) $model->getKey(), $dryRun);

        return ['status' => $changed ? 'applied' : 'unchanged'];
    }

    private function processNameCodeMap(): Collection
    {
        return ProcessName::query()
            ->whereNotNull('code')
            ->where('code', '<>', '')
            ->get()
            ->groupBy(fn (ProcessName $processName): string => $this->normalizeCode($processName->code));
    }

    private function routeForLine(QuantumRoLine $line): array
    {
        $pn = trim((string) $line->pn);
        $normalizedPn = $this->normalizeQuantumPn($pn);

        if ($normalizedPn === 'NDT') {
            return [
                'type' => self::TARGET_STD,
                'std_type' => StdProcess::STD_NDT,
                'label' => 'NDT',
            ];
        }

        if ($normalizedPn === 'CAD') {
            return [
                'type' => self::TARGET_STD,
                'std_type' => StdProcess::STD_CAD,
                'label' => 'CAD',
            ];
        }

        $bushingMap = [
            'NDTB' => ['key' => 'ndt', 'label' => 'NDTB'],
            'CADB' => ['key' => 'cad', 'label' => 'CADB'],
            'ANODIZING' => ['key' => 'anodizing', 'label' => 'Anodizing'],
            'ANODISING' => ['key' => 'anodizing', 'label' => 'Anodizing'],
            'PASSIVATION' => ['key' => 'passivation', 'label' => 'Passivation'],
        ];

        if (isset($bushingMap[$normalizedPn])) {
            return [
                'type' => self::TARGET_BUSHING_BATCH,
                'process_key' => $bushingMap[$normalizedPn]['key'],
                'label' => $bushingMap[$normalizedPn]['label'],
            ];
        }

        if ($this->isDetailPartPn($line)) {
            return [
                'type' => self::TARGET_TDR,
                'label' => $pn,
            ];
        }

        $displayPn = $pn !== '' ? $pn : '--';

        return [
            'type' => self::TARGET_UNSUPPORTED,
            'message' => "Unsupported Quantum PN [{$displayPn}]",
        ];
    }

    private function findWorkorder(?string $woNumber): ?Workorder
    {
        $number = preg_replace('/\D+/', '', (string) $woNumber);

        if ($number === '') {
            return null;
        }

        return Workorder::query()->where('number', (int) $number)->first();
    }

    private function isOldWorkorderNumber(?string $woNumber): bool
    {
        $number = preg_replace('/\D+/', '', (string) $woNumber);

        return $number !== ''
            && (int) $number < self::OLD_WORKORDER_NUMBER_CUTOFF;
    }

    private function findVendor(?string $vendorName): ?Vendor
    {
        $name = trim((string) $vendorName);

        if ($name === '') {
            return null;
        }

        return Vendor::query()
            ->whereRaw('LOWER(TRIM(name)) = LOWER(TRIM(?))', [$name])
            ->first();
    }

    private function findTarget(QuantumRoLine $line, Workorder $workorder, array $route, Collection $codeMap): array
    {
        return match ($route['type']) {
            self::TARGET_STD => $this->findStdTarget($line, $workorder, $route),
            self::TARGET_BUSHING_BATCH => $this->findBushingBatchTarget($line, $workorder, $route),
            self::TARGET_TDR => $this->findTdrTarget($line, $workorder, $codeMap),
            default => [
                'status' => 'unresolved',
                'message' => "Unsupported Quantum PN [{$line->pn}]",
            ],
        };
    }

    private function findStdTarget(QuantumRoLine $line, Workorder $workorder, array $route): array
    {
        $matches = WorkorderStdProcess::query()
            ->where('workorder_id', $workorder->id)
            ->where('std_type', $route['std_type'])
            ->limit(2)
            ->get();

        if ($matches->count() === 1) {
            return ['status' => 'ok', 'model' => $matches->first()];
        }

        if ($matches->count() > 1) {
            return [
                'status' => 'unresolved',
                'message' => "Multiple STD process targets for WO {$line->wo_number}, PN {$route['label']}",
            ];
        }

        return [
            'status' => 'unresolved',
            'message' => "No STD process target for WO {$line->wo_number}, PN {$route['label']}",
        ];
    }

    private function findBushingBatchTarget(QuantumRoLine $line, Workorder $workorder, array $route): array
    {
        $batchNumber = $this->batchNumberFromRef($line->bom_ref);

        if ($batchNumber === null) {
            $ref = trim((string) $line->bom_ref);
            $displayRef = $ref !== '' ? $ref : '--';

            return [
                'status' => 'unresolved',
                'message' => "Bushing REF must be batch B1/B2/... for WO {$line->wo_number}, PN {$route['label']}; got [{$displayRef}]",
            ];
        }

        $batches = WoBushingBatch::query()
            ->where('workorder_id', $workorder->id)
            ->with('process.process_name')
            ->orderBy('id')
            ->get()
            ->filter(fn (WoBushingBatch $batch): bool => $this->bushingBatchProcessKey($batch) === $route['process_key'])
            ->values();

        if ($batches->isEmpty()) {
            return [
                'status' => 'unresolved',
                'message' => "No bushing batches for WO {$line->wo_number}, process {$route['label']}",
            ];
        }

        $target = $batches->get($batchNumber - 1);

        if (! $target) {
            return [
                'status' => 'unresolved',
                'message' => "Bushing batch not found: B{$batchNumber} for WO {$line->wo_number}, process {$route['label']}",
            ];
        }

        return ['status' => 'ok', 'model' => $target];
    }

    private function bushingBatchProcessKey(WoBushingBatch $batch): string
    {
        $stored = trim((string) $batch->process_column_key);

        if ($stored !== '') {
            return $stored;
        }

        return WoBushingProcessColumnKey::fromProcess($batch->process);
    }

    private function batchNumberFromRef(?string $ref): ?int
    {
        if (! preg_match('/^B\s*(\d+)$/i', trim((string) $ref), $matches)) {
            return null;
        }

        $number = (int) $matches[1];

        return $number > 0 ? $number : null;
    }

    private function findTdrTarget(QuantumRoLine $line, Workorder $workorder, Collection $codeMap): array
    {
        $ref = trim((string) $line->bom_ref);

        if ($ref === '') {
            return [
                'status' => 'unresolved',
                'message' => "Missing REF for detail part PN {$line->pn}, WO {$line->wo_number}",
            ];
        }

        $normalizedCode = $this->normalizeCode($ref);
        $processNames = $codeMap->get($normalizedCode, collect());

        if ($processNames->count() !== 1) {
            $message = $processNames->isEmpty()
                ? "No process_names.code matched REF [{$line->bom_ref}]"
                : "Multiple process_names.code matched REF [{$line->bom_ref}]";

            return [
                'status' => 'unresolved',
                'message' => $message,
            ];
        }

        /** @var ProcessName $processName */
        $processName = $processNames->first();

        $tdrMatches = TdrProcess::query()
            ->select('tdr_processes.*')
            ->join('tdrs', 'tdrs.id', '=', 'tdr_processes.tdrs_id')
            ->join('components', 'components.id', '=', 'tdrs.component_id')
            ->where('tdrs.workorder_id', $workorder->id)
            ->where('tdr_processes.process_names_id', $processName->id)
            ->whereRaw(
                "REPLACE(UPPER(TRIM(components.part_number)), ' ', '') = ?",
                [$this->normalizePartNumber($line->pn)]
            )
            ->limit(2)
            ->get();

        if ($tdrMatches->count() === 1) {
            return ['status' => 'ok', 'model' => $tdrMatches->first()];
        }

        if ($tdrMatches->count() > 1) {
            return [
                'status' => 'unresolved',
                'message' => "Multiple TDR process targets for WO {$line->wo_number}, REF {$line->bom_ref}",
            ];
        }

        return [
            'status' => 'unresolved',
            'message' => "No TDR process target for WO {$line->wo_number}, REF {$line->bom_ref}, PN {$line->pn}, process {$processName->name}",
        ];
    }

    private function fillTarget(Model $model, QuantumRoLine $line, Vendor $vendor): void
    {
        $model->repair_order = $line->ro_number;
        $model->vendor_id = $vendor->id;

        if ($line->out_date) {
            $model->date_start = Carbon::parse($line->out_date)->toDateString();
            $this->setColumnIfExists($model, 'date_start_user_id', null);
            $this->setColumnIfExists($model, 'date_start_user', self::DATE_USER_NAME);
        }

        $firstReturnedDate = $this->firstReturnedDate($line);
        if ($firstReturnedDate) {
            $model->date_finish = Carbon::parse($firstReturnedDate)->toDateString();
            $this->setColumnIfExists($model, 'date_finish_user_id', null);
            $this->setColumnIfExists($model, 'date_finish_user', self::DATE_USER_NAME);
        }
    }

    private function setColumnIfExists(Model $model, string $column, mixed $value): void
    {
        if ($this->hasColumn($model, $column)) {
            $model->{$column} = $value;
        }
    }

    private function hasColumn(Model $model, string $column): bool
    {
        $table = $model->getTable();
        $connectionName = $model->getConnectionName() ?: 'default';
        $cacheKey = "{$connectionName}.{$table}.{$column}";

        if (! array_key_exists($cacheKey, $this->columnExistsCache)) {
            $this->columnExistsCache[$cacheKey] = $model
                ->getConnection()
                ->getSchemaBuilder()
                ->hasColumn($table, $column);
        }

        return $this->columnExistsCache[$cacheKey];
    }

    private function firstReturnedDate(QuantumRoLine $line): mixed
    {
        if (! $line->ro_number) {
            return $line->returned_date;
        }

        return QuantumRoLine::query()
            ->where('ro_number', $line->ro_number)
            ->whereNotNull('returned_date')
            ->min('returned_date') ?: $line->returned_date;
    }

    private function markLine(
        QuantumRoLine $line,
        string $status,
        string $message,
        ?string $targetTable,
        ?int $targetId,
        bool $dryRun
    ): void {
        if ($dryRun) {
            return;
        }

        $line->forceFill([
            'apply_status' => $status,
            'apply_message' => mb_substr($message, 0, 5000),
            'applied_target_table' => $targetTable,
            'applied_target_id' => $targetId,
            'applied_source_hash' => $line->source_hash,
            'applied_at' => now(),
        ])->save();
    }

    private function isDetailPartPn(QuantumRoLine $line): bool
    {
        $pn = trim((string) $line->pn);

        return $pn !== ''
            && ((string) $line->class === 'DETAIL_PART' || preg_match('/\d/', $pn) === 1);
    }

    private function normalizeCode(?string $value): string
    {
        return (string) preg_replace('/\s+/', '', strtoupper(trim((string) $value)));
    }

    private function normalizePartNumber(?string $value): string
    {
        return str_replace(' ', '', strtoupper(trim((string) $value)));
    }

    private function normalizeQuantumPn(?string $value): string
    {
        return (string) preg_replace('/[\s_-]+/', '', strtoupper(trim((string) $value)));
    }
}
