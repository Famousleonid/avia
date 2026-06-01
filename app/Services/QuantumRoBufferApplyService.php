<?php

namespace App\Services;

use App\Models\ProcessName;
use App\Models\QuantumRoLine;
use App\Models\StdProcess;
use App\Models\TdrProcess;
use App\Models\Vendor;
use App\Models\Workorder;
use App\Models\WorkorderStdProcess;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuantumRoBufferApplyService
{
    public function apply(int $limit = 200, bool $dryRun = false): array
    {
        $stats = [
            'scanned' => 0,
            'applied' => 0,
            'unchanged' => 0,
            'unresolved' => 0,
            'errors' => 0,
            'dry_run' => $dryRun,
        ];

        $codeMap = $this->processNameCodeMap();

        $lines = QuantumRoLine::query()
            ->whereNotNull('bom_ref')
            ->where('bom_ref', '<>', '')
            ->where(function ($query): void {
                $query
                    ->whereNull('apply_status')
                    ->orWhere('apply_status', '<>', 'applied')
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
                $this->markLine($line, 'error', $e->getMessage(), null, null, $dryRun);
            }
        }

        return $stats;
    }

    private function applyLine(QuantumRoLine $line, Collection $codeMap, bool $dryRun): array
    {
        $normalizedCode = $this->normalizeCode($line->bom_ref);
        $processNames = $codeMap->get($normalizedCode, collect());

        if ($processNames->count() !== 1) {
            $message = $processNames->isEmpty()
                ? "No process_names.code matched REF [{$line->bom_ref}]"
                : "Multiple process_names.code matched REF [{$line->bom_ref}]";

            $this->markLine($line, 'unresolved', $message, null, null, $dryRun);

            return ['status' => 'unresolved'];
        }

        $workorder = $this->findWorkorder($line->wo_number);
        if (! $workorder) {
            $this->markLine($line, 'unresolved', "Workorder not found: {$line->wo_number}", null, null, $dryRun);

            return ['status' => 'unresolved'];
        }

        $vendor = $this->findVendor($line->vendor_name);
        if (! $vendor) {
            $this->markLine($line, 'unresolved', "Vendor not found: {$line->vendor_name}", null, null, $dryRun);

            return ['status' => 'unresolved'];
        }

        /** @var ProcessName $processName */
        $processName = $processNames->first();
        $target = $this->findTarget($line, $workorder, $processName);

        if ($target['status'] !== 'ok') {
            $this->markLine($line, 'unresolved', $target['message'], null, null, $dryRun);

            return ['status' => 'unresolved'];
        }

        /** @var TdrProcess|WorkorderStdProcess $model */
        $model = $target['model'];
        $table = $model->getTable();
        $before = $model->getAttributes();

        $this->fillTarget($model, $line, $vendor);

        if (! $dryRun && $model->isDirty()) {
            $model->save();
        }

        $changed = $model->isDirty() || $before != $model->getAttributes();
        $message = ($changed ? 'Applied' : 'Already current') . " to {$table}:{$model->getKey()}";

        $this->markLine($line, 'applied', $message, $table, (int) $model->getKey(), $dryRun);

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

    private function findWorkorder(?string $woNumber): ?Workorder
    {
        $number = preg_replace('/\D+/', '', (string) $woNumber);

        if ($number === '') {
            return null;
        }

        return Workorder::query()->where('number', (int) $number)->first();
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

    private function findTarget(QuantumRoLine $line, Workorder $workorder, ProcessName $processName): array
    {
        $tdrCandidates = TdrProcess::query()
            ->select('tdr_processes.*')
            ->join('tdrs', 'tdrs.id', '=', 'tdr_processes.tdrs_id')
            ->where('tdrs.workorder_id', $workorder->id)
            ->where('tdr_processes.process_names_id', $processName->id);

        if ($this->shouldMatchComponentPart($line)) {
            $tdrCandidates
                ->join('components', 'components.id', '=', 'tdrs.component_id')
                ->whereRaw(
                    "REPLACE(UPPER(TRIM(components.part_number)), ' ', '') = ?",
                    [$this->normalizePartNumber($line->pn)]
                );
        }

        $tdrMatches = $tdrCandidates->limit(2)->get();

        if ($tdrMatches->count() === 1) {
            return ['status' => 'ok', 'model' => $tdrMatches->first()];
        }

        if ($tdrMatches->count() > 1) {
            return [
                'status' => 'unresolved',
                'message' => "Multiple TDR process targets for WO {$line->wo_number}, REF {$line->bom_ref}",
            ];
        }

        $stdMatches = $this->findStdTargets($workorder, $processName);

        if ($stdMatches->count() === 1) {
            return ['status' => 'ok', 'model' => $stdMatches->first()];
        }

        if ($stdMatches->count() > 1) {
            return [
                'status' => 'unresolved',
                'message' => "Multiple STD process targets for WO {$line->wo_number}, REF {$line->bom_ref}",
            ];
        }

        return [
            'status' => 'unresolved',
            'message' => "No target process for WO {$line->wo_number}, REF {$line->bom_ref}, process {$processName->name}",
        ];
    }

    private function findStdTargets(Workorder $workorder, ProcessName $processName): Collection
    {
        $stdType = $this->stdTypeForProcessName($processName);

        if ($stdType !== null) {
            $matches = WorkorderStdProcess::query()
                ->where('workorder_id', $workorder->id)
                ->where('std_type', $stdType)
                ->limit(2)
                ->get();

            if ($matches->isNotEmpty()) {
                return $matches;
            }
        }

        return WorkorderStdProcess::query()
            ->where('workorder_id', $workorder->id)
            ->where('process_name_id', $processName->id)
            ->limit(2)
            ->get();
    }

    private function stdTypeForProcessName(ProcessName $processName): ?string
    {
        $name = trim((string) $processName->name);
        $sheet = strtoupper(trim((string) $processName->process_sheet_name));

        if ($sheet === 'NDT'
            || preg_match('/^NDT-\d+$/i', $name)
            || in_array($name, ['Eddy Current Test', 'BNI'], true)) {
            return StdProcess::STD_NDT;
        }

        if ($name === 'Cad plate') {
            return StdProcess::STD_CAD;
        }

        if (in_array($name, ['Bake (Stress relief)', 'Stress Relief'], true)) {
            return StdProcess::STD_STRESS;
        }

        if ($name === 'Paint'
            || $name === 'STD Paint List'
            || $sheet === 'PAINT APPLICATION') {
            return StdProcess::STD_PAINT;
        }

        return match ($name) {
            'STD NDT List' => StdProcess::STD_NDT,
            'STD CAD List' => StdProcess::STD_CAD,
            'STD Stress relief List' => StdProcess::STD_STRESS,
            default => null,
        };
    }

    private function fillTarget(Model $model, QuantumRoLine $line, Vendor $vendor): void
    {
        $model->repair_order = $line->ro_number;
        $model->vendor_id = $vendor->id;

        if ($line->out_date) {
            $model->date_start = Carbon::parse($line->out_date)->toDateString();
        }

        $firstReturnedDate = $this->firstReturnedDate($line);
        if ($firstReturnedDate) {
            $model->date_finish = Carbon::parse($firstReturnedDate)->toDateString();
        }
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

    private function shouldMatchComponentPart(QuantumRoLine $line): bool
    {
        $pn = strtoupper(trim((string) $line->pn));

        return $pn !== ''
            && ! in_array($pn, ['NDT', 'CAD'], true)
            && (string) $line->class === 'DETAIL_PART';
    }

    private function normalizeCode(?string $value): string
    {
        return preg_replace('/\s+/', '', strtoupper(trim((string) $value)));
    }

    private function normalizePartNumber(?string $value): string
    {
        return str_replace(' ', '', strtoupper(trim((string) $value)));
    }
}
