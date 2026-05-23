<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Customer;
use App\Models\ExtraProcess;
use App\Models\Instruction;
use App\Models\LogCard;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\Necessary;
use App\Models\Plane;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\StdProcess;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Training;
use App\Models\Transfer;
use App\Models\Vendor;
use App\Models\WoBushing;
use App\Models\WorkorderUnitInspection;
use http\Client\Curl\User;
use Illuminate\Support\Facades\Cache;
use App\Models\Unit;
//use App\Models\Wo_Code;
//use App\Models\WoCode;
use App\Models\Workorder;
use App\Services\LogCardTdrAccessService;
use App\Services\ManualIplBranchRuleResolver;
use App\Services\WoBushingRelationalSync;
use App\Services\WorkorderStdListProcessesService;
use App\Support\LogCardDestructionCertificate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;

class TdrController extends Controller
{
    const DEFAULT_QTY = 1;
    const DEFAULT_PROCESS = 1;
    const PROCESS_TYPE_NDT = 'ndt';
    const PROCESS_TYPE_CAD = 'cad';
    const PROCESS_TYPE_LOG = 'log';

    private function inferTdrTypeFromPayload(array $payload, ?Code $manufactureCode = null, ?Necessary $orderNew = null, ?Necessary $repair = null): string
    {
        return Tdr::query()->make($payload)->inferType(
            $manufactureCode !== null ? (string) $manufactureCode->id : null,
            $orderNew !== null ? (string) $orderNew->id : null,
            $repair !== null ? (string) $repair->id : null
        );
    }

    /**
     * ÐÐ¾Ñ€Ð¼Ð°Ð»Ð¸Ð·ÑƒÐµÑ‚ IPL Ð½Ð¾Ð¼ÐµÑ€, ÑƒÐ±Ð¸Ñ€Ð°Ñ Ð±ÑƒÐºÐ²ÐµÐ½Ð½Ñ‹Ðµ ÑÑƒÑ„Ñ„Ð¸ÐºÑÑ‹ Ð´Ð»Ñ ÑÑ€Ð°Ð²Ð½ÐµÐ½Ð¸Ñ
     * ÐÐ°Ð¿Ñ€Ð¸Ð¼ÐµÑ€: 5-90A -> 5-90, 1-1190B -> 1-1190
     *
     * @param string $iplNum
     * @return string
     */
    private function normalizeIplNum($iplNum)
    {
        if (empty($iplNum)) {
            return '';
        }

        // Ð£Ð±Ð¸Ñ€Ð°ÐµÐ¼ Ð±ÑƒÐºÐ²ÐµÐ½Ð½Ñ‹Ðµ ÑÑƒÑ„Ñ„Ð¸ÐºÑÑ‹ Ð² ÐºÐ¾Ð½Ñ†Ðµ (A, B, C, Ð¸ Ñ‚.Ð´.)
        // ÐŸÐ°Ñ‚Ñ‚ÐµÑ€Ð½: ÑƒÐ´Ð°Ð»ÑÐµÐ¼ Ð±ÑƒÐºÐ²Ñ‹ Ð² ÐºÐ¾Ð½Ñ†Ðµ Ð¿Ð¾ÑÐ»Ðµ Ð¿Ð¾ÑÐ»ÐµÐ´Ð½ÐµÐ³Ð¾ Ð´ÐµÑ„Ð¸ÑÐ° Ð¸Ð»Ð¸ Ð² ÐºÐ¾Ð½Ñ†Ðµ ÑÑ‚Ñ€Ð¾ÐºÐ¸
        return preg_replace('/[A-Z]+$/', '', trim($iplNum));
    }

    /**
     * TDR ÑÐ¾ ÑÑ‚Ð°Ñ‚ÑƒÑÐ°Ð¼Ð¸ Missing / Repair / Order New Ð½Ðµ ÑƒÑ‡Ð°ÑÑ‚Ð²ÑƒÑŽÑ‚ Ð² ÑÑƒÐ¼Ð¼Ðµ Â«ÑƒÐ¶Ðµ Ð² TDRÂ» Ð´Ð»Ñ NDT STD.
     */
    private function tdrRowExcludedForNdtStd(Tdr $tdr, ?Code $missingCode, ?Code $repairCode, ?Necessary $orderNewNecessary): bool
    {
        if ($missingCode !== null && (int) $tdr->codes_id === (int) $missingCode->id) {
            return true;
        }
        if ($repairCode !== null && (int) $tdr->codes_id === (int) $repairCode->id) {
            return true;
        }
        if ($orderNewNecessary !== null && (int) $tdr->necessaries_id === (int) $orderNewNecessary->id) {
            return true;
        }

        return false;
    }

    /**
     * ÐœÐ°Ð¿Ñ‹ Ð´Ð»Ñ NDT STD: excluded (Missing/Repair/Order New) Ð¸ ÑÑƒÐ¼Ð¼Ð° TDR Ð¿Ð¾ Ð½Ð¾Ñ€Ð¼Ð°Ð»Ð¸Ð·Ð¾Ð²Ð°Ð½Ð½Ð¾Ð¼Ñƒ IPL
     * (Ð±ÐµÐ· Â«Ð¸ÑÐºÐ»ÑŽÑ‡Ñ‘Ð½Ð½Ñ‹Ñ…Â» TDR) â€” ÐµÐ´Ð¸Ð½Ñ‹ Ð´Ð»Ñ Ð¿ÐµÑ‡Ð°Ñ‚Ð½Ð¾Ð¹ Ñ„Ð¾Ñ€Ð¼Ñ‹ Ð¸ calcNdtSums.
     *
     * @return array{excluded: array<string, int>, tdr: array<string, int>}
     */
    private function ndtStdExcludedAndTdrQtyByNormalizedIpl(int $workorderId): array
    {
        $excludedQtyByIpl = [];
        $missingCode = Code::where('name', 'Missing')->first();
        $repairCode = Code::where('name', 'Repair')->first();
        $orderNewNecessary = Necessary::where('name', 'Order New')->first();

        $excludedTdrQuery = Tdr::where('workorder_id', $workorderId)
            ->whereNotNull('component_id')
            ->with('component:id,ipl_num');

        $excludedConditions = [];
        if ($missingCode) {
            $excludedConditions[] = ['codes_id', $missingCode->id];
        }
        if ($repairCode) {
            $excludedConditions[] = ['codes_id', $repairCode->id];
        }
        if ($orderNewNecessary) {
            $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
        }

        if (! empty($excludedConditions)) {
            $excludedTdrQuery->where(function ($query) use ($excludedConditions) {
                foreach ($excludedConditions as $condition) {
                    $query->orWhere($condition[0], $condition[1]);
                }
            });

            $excludedTdrs = $excludedTdrQuery->get();
            foreach ($excludedTdrs as $tdr) {
                if ($tdr->component && $tdr->component->ipl_num) {
                    $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                    if (! empty($normalizedIpl)) {
                        if (! isset($excludedQtyByIpl[$normalizedIpl])) {
                            $excludedQtyByIpl[$normalizedIpl] = 0;
                        }
                        $excludedQtyByIpl[$normalizedIpl] += (int) ($tdr->qty ?? 0);
                    }
                }
            }
        }

        $tdrItemsMap = [];
        $allTdrForNdtMap = Tdr::where('workorder_id', $workorderId)
            ->whereNotNull('component_id')
            ->with('component:id,ipl_num')
            ->get();
        foreach ($allTdrForNdtMap as $tdr) {
            if ($this->tdrRowExcludedForNdtStd($tdr, $missingCode, $repairCode, $orderNewNecessary)) {
                continue;
            }
            if (! $tdr->component || empty($tdr->component->ipl_num)) {
                continue;
            }
            $q = (int) ($tdr->qty ?? 0);
            if ($q <= 0) {
                continue;
            }
            $normalizedIplKey = $this->normalizeIplNum($tdr->component->ipl_num);
            if (empty($normalizedIplKey)) {
                continue;
            }
            if (! isset($tdrItemsMap[$normalizedIplKey])) {
                $tdrItemsMap[$normalizedIplKey] = 0;
            }
            $tdrItemsMap[$normalizedIplKey] += $q;
        }

        return [
            'excluded' => $excludedQtyByIpl,
            'tdr' => $tdrItemsMap,
        ];
    }

    /**
     * units_assy Ð¿Ð¾ Ð½Ð¾Ñ€Ð¼Ð°Ð»Ð¸Ð·Ð¾Ð²Ð°Ð½Ð½Ð¾Ð¼Ñƒ IPL (Ð¿Ñ€Ð¸Ð¾Ñ€Ð¸Ñ‚ÐµÑ‚ â€” ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ñ‹ Ð¸Ð· manual Ñ‚ÐµÐºÑƒÑ‰ÐµÐ³Ð¾ Ð·Ð°ÐºÐ°Ð·Ð°).
     *
     * @return array<string, int>
     */
    private function buildUnitsAssyByNormalizedIplMap(Manual $manual): array
    {
        $unitsAssyByIpl = [];
        $allComponents = Component::select('ipl_num', 'units_assy', 'manual_id')
            ->orderByRaw('CASE WHEN manual_id = ? THEN 0 ELSE 1 END', [$manual->id])
            ->get();

        foreach ($allComponents as $component) {
            if ($component->ipl_num) {
                $normalizedIpl = $this->normalizeIplNum($component->ipl_num);
                if (! empty($normalizedIpl)) {
                    if (! isset($unitsAssyByIpl[$normalizedIpl])) {
                        $num = (int) ($component->units_assy ?? 1);
                        $unitsAssyByIpl[$normalizedIpl] = $num > 0 ? $num : 1;
                    }
                }
            }
        }

        return $unitsAssyByIpl;
    }

    /**
     * units_assy Ð¸Ð· Component Ð´Ð»Ñ ÑÑ‚Ñ€Ð¾ÐºÐ¸ NDT STD: manual Ð¸Ð· ÑÐ½Ð¸Ð¼ÐºÐ° Ð¸Ð»Ð¸ Ð¾Ð±Ñ‰Ð°Ñ Ð¼Ð°Ð¿Ð° Ð¿Ð¾ IPL.
     */
    private function resolveNdtStdUnitsAssyForRow(array $component, string $iplNum, string $normalizedIpl, array $unitsAssyByIpl): int
    {
        $unitsAssy = 1;
        if (! empty($component['manual'])) {
            $componentManual = Manual::where('number', $component['manual'])->first();
            if ($componentManual) {
                $componentRecord = Component::where('manual_id', $componentManual->id)
                    ->where('ipl_num', $iplNum)
                    ->first();
                if ($componentRecord && $componentRecord->units_assy) {
                    $num = (int) $componentRecord->units_assy;
                    $unitsAssy = $num > 0 ? $num : 1;
                } else {
                    $unitsAssy = $unitsAssyByIpl[$normalizedIpl] ?? 1;
                }
            } else {
                $unitsAssy = $unitsAssyByIpl[$normalizedIpl] ?? 1;
            }
        } else {
            $unitsAssy = $unitsAssyByIpl[$normalizedIpl] ?? 1;
        }

        return max(1, $unitsAssy);
    }

    /**
     * ITEM / PART / DESCRIPTION Ð´Ð»Ñ paintFormStd: Ð¸Ð· Component â€” assy_ipl_num, assy_part_number (ÐµÑÐ»Ð¸ Ð·Ð°Ð´Ð°Ð½Ñ‹);
     * Ð¸Ð½Ð°Ñ‡Ðµ Ð·Ð½Ð°Ñ‡ÐµÐ½Ð¸Ñ Ð¸Ð· ÑÐ½Ð¸Ð¼ÐºÐ° paint. Ð•ÑÐ»Ð¸ Ð·Ð°Ð´Ð°Ð½ Ñ…Ð¾Ñ‚Ñ Ð±Ñ‹ Ð¾Ð´Ð¸Ð½ assy â€” description Ð±ÐµÑ€Ñ‘Ñ‚ÑÑ Ð¸Ð· name ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ð°.
     *
     * @param  array<string, mixed>  $paintRow
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolvePaintStdAssyDisplayFields(array $paintRow, Manual $defaultManual): array
    {
        $iplNum = $paintRow['ipl_num'] ?? '';
        $item = (string) ($paintRow['ipl_num'] ?? '');
        $part = (string) ($paintRow['part_number'] ?? '');
        $desc = (string) ($paintRow['description'] ?? '');

        $compRec = null;
        if (! empty($paintRow['manual'])) {
            $m = Manual::where('number', $paintRow['manual'])->first();
            if ($m) {
                $compRec = Component::query()
                    ->where('manual_id', $m->id)
                    ->where('ipl_num', $iplNum)
                    ->first(['assy_ipl_num', 'assy_part_number', 'name']);
            }
        }
        if (! $compRec && $iplNum !== '') {
            $compRec = Component::query()
                ->where('manual_id', $defaultManual->id)
                ->where('ipl_num', $iplNum)
                ->first(['assy_ipl_num', 'assy_part_number', 'name']);
        }

        if ($compRec) {
            if (trim((string) ($compRec->assy_ipl_num ?? '')) !== '') {
                $item = (string) $compRec->assy_ipl_num;
            }
            if (trim((string) ($compRec->assy_part_number ?? '')) !== '') {
                $part = (string) $compRec->assy_part_number;
            }
            $hasAssy = trim((string) ($compRec->assy_ipl_num ?? '')) !== ''
                || trim((string) ($compRec->assy_part_number ?? '')) !== '';
            if ($hasAssy && trim((string) ($compRec->name ?? '')) !== '') {
                $desc = (string) $compRec->name;
            }
        }

        return [$item, $part, $desc];
    }

    /**
     * Ð Ð°ÑÑÑ‡Ð¸Ñ‚Ñ‹Ð²Ð°ÐµÑ‚ Ð¿Ð°Ð³Ð¸Ð½Ð°Ñ†Ð¸ÑŽ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ð¾Ð² Ñ ÑƒÑ‡ÐµÑ‚Ð¾Ð¼ manual-ÑÑ‚Ñ€Ð¾Ðº Ð¸ Ð¿ÑƒÑÑ‚Ñ‹Ñ… ÑÑ‚Ñ€Ð¾Ðº
     *
     * @param array $components ÐœÐ°ÑÑÐ¸Ð² ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ð¾Ð²
     * @param int $targetRows Ð¦ÐµÐ»ÐµÐ²Ð¾Ðµ ÐºÐ¾Ð»Ð¸Ñ‡ÐµÑÑ‚Ð²Ð¾ ÑÑ‚Ñ€Ð¾Ðº Ð½Ð° ÑÑ‚Ñ€Ð°Ð½Ð¸Ñ†Ðµ (Ð²ÐºÐ»ÑŽÑ‡Ð°Ñ
     *     manual Ð¸ Ð¿ÑƒÑÑ‚Ñ‹Ðµ)
     * @return array ÐœÐ°ÑÑÐ¸Ð² chunks, ÐºÐ°Ð¶Ð´Ñ‹Ð¹ chunk ÑÐ¾Ð´ÐµÑ€Ð¶Ð¸Ñ‚:
     *   - 'components': Ð¼Ð°ÑÑÐ¸Ð² ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ð¾Ð²
     *   - 'manual_rows': ÐºÐ¾Ð»Ð¸Ñ‡ÐµÑÑ‚Ð²Ð¾ manual-ÑÑ‚Ñ€Ð¾Ðº
     *   - 'data_rows': ÐºÐ¾Ð»Ð¸Ñ‡ÐµÑÑ‚Ð²Ð¾ ÑÑ‚Ñ€Ð¾Ðº Ñ Ð´Ð°Ð½Ð½Ñ‹Ð¼Ð¸
     *   - 'empty_rows': ÐºÐ¾Ð»Ð¸Ñ‡ÐµÑÑ‚Ð²Ð¾ Ð¿ÑƒÑÑ‚Ñ‹Ñ… ÑÑ‚Ñ€Ð¾Ðº Ð´Ð»Ñ Ð´Ð¾Ð±Ð°Ð²Ð»ÐµÐ½Ð¸Ñ
     *   - 'total_rows': Ð¾Ð±Ñ‰ÐµÐµ ÐºÐ¾Ð»Ð¸Ñ‡ÐµÑÑ‚Ð²Ð¾ ÑÑ‚Ñ€Ð¾Ðº
     *   - 'previous_manual': Ð¿Ð¾ÑÐ»ÐµÐ´Ð½Ð¸Ð¹ manual Ð² chunk (Ð´Ð»Ñ ÑÐ»ÐµÐ´ÑƒÑŽÑ‰ÐµÐ³Ð¾ chunk)
     */
    private function paginateComponentsWithEmptyRows($components, $targetRows = 18)
    {
        $chunks = [];
        $currentChunk = [];
        $previousManual = null;
        $previousChunkLastManual = null;

        foreach ($components as $component) {
            $currentManual = $component->manual ?? null;
            $hasManual = ($currentManual !== null && $currentManual !== '' && $currentManual !== $previousManual);

            // ÐŸÐ¾Ð´ÑÑ‡Ð¸Ñ‚Ñ‹Ð²Ð°ÐµÐ¼ ÐºÐ¾Ð»Ð¸Ñ‡ÐµÑÑ‚Ð²Ð¾ ÑÑ‚Ñ€Ð¾Ðº Ð² Ñ‚ÐµÐºÑƒÑ‰ÐµÐ¼ chunk Ð‘Ð•Ð— Ð½Ð¾Ð²Ð¾Ð³Ð¾ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ð°
            $rowsInChunk = count($currentChunk);
            $manualRowsInChunk = 0;
            $tempPreviousManual = $previousChunkLastManual ?? $previousManual;

            // Ð¡Ñ‡Ð¸Ñ‚Ð°ÐµÐ¼ manual-ÑÑ‚Ñ€Ð¾ÐºÐ¸ Ð² Ñ‚ÐµÐºÑƒÑ‰ÐµÐ¼ chunk (ÑƒÐ¶Ðµ Ð´Ð¾Ð±Ð°Ð²Ð»ÐµÐ½Ð½Ñ‹Ñ… ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ð¾Ð²)
            foreach ($currentChunk as $chunkComponent) {
                $chunkManual = $chunkComponent->manual ?? null;
                if ($chunkManual !== null && $chunkManual !== '' && $chunkManual !== $tempPreviousManual) {
                    $manualRowsInChunk++;
                    $tempPreviousManual = $chunkManual;
                } else if ($chunkManual !== null && $chunkManual !== '') {
                    $tempPreviousManual = $chunkManual;
                }
            }

            // Ð•ÑÐ»Ð¸ Ð´Ð¾Ð±Ð°Ð²Ð»ÑÐµÐ¼ ÑÑ‚Ð¾Ñ‚ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚, Ð±ÑƒÐ´ÐµÑ‚ Ð»Ð¸ Ð½Ð¾Ð²Ð°Ñ manual-ÑÑ‚Ñ€Ð¾ÐºÐ°?
            if ($hasManual) {
                $manualRowsInChunk++;
            }

            // ÐžÐ±Ñ‰ÐµÐµ ÐºÐ¾Ð»Ð¸Ñ‡ÐµÑÑ‚Ð²Ð¾ ÑÑ‚Ñ€Ð¾Ðº Ð² chunk Ð¡ Ð½Ð¾Ð²Ñ‹Ð¼ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ð¾Ð¼
            $totalRowsInChunk = $rowsInChunk + $manualRowsInChunk + 1;

            // Ð•ÑÐ»Ð¸ Ð´Ð¾Ð±Ð°Ð²Ð»ÐµÐ½Ð¸Ðµ ÑÑ‚Ð¾Ð³Ð¾ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ð° Ð¿Ñ€ÐµÐ²Ñ‹ÑÐ¸Ñ‚ Ð»Ð¸Ð¼Ð¸Ñ‚, ÑÐ¾Ñ…Ñ€Ð°Ð½ÑÐµÐ¼ Ñ‚ÐµÐºÑƒÑ‰Ð¸Ð¹ chunk
            if ($totalRowsInChunk > $targetRows && !empty($currentChunk)) {
                // Ð Ð°ÑÑÑ‡Ð¸Ñ‚Ñ‹Ð²Ð°ÐµÐ¼ Ð¿ÑƒÑÑ‚Ñ‹Ðµ ÑÑ‚Ñ€Ð¾ÐºÐ¸ Ð´Ð»Ñ Ñ‚ÐµÐºÑƒÑ‰ÐµÐ³Ð¾ chunk
                $chunkInfo = $this->calculateChunkInfo($currentChunk, $targetRows, $previousChunkLastManual ?? $previousManual, false);
                $chunks[] = $chunkInfo;
                $previousChunkLastManual = $chunkInfo['previous_manual'];

                // ÐÐ°Ñ‡Ð¸Ð½Ð°ÐµÐ¼ Ð½Ð¾Ð²Ñ‹Ð¹ chunk
                $currentChunk = [];
                $previousManual = $previousChunkLastManual;
            }

            // Ð”Ð¾Ð±Ð°Ð²Ð»ÑÐµÐ¼ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚ Ð² Ñ‚ÐµÐºÑƒÑ‰Ð¸Ð¹ chunk
            $currentChunk[] = $component;

            // ÐžÐ±Ð½Ð¾Ð²Ð»ÑÐµÐ¼ previousManual Ð´Ð»Ñ ÑÐ»ÐµÐ´ÑƒÑŽÑ‰ÐµÐ¹ Ð¸Ñ‚ÐµÑ€Ð°Ñ†Ð¸Ð¸
            if ($currentManual !== null && $currentManual !== '') {
                $previousManual = $currentManual;
            }
        }

        // Ð”Ð¾Ð±Ð°Ð²Ð»ÑÐµÐ¼ Ð¿Ð¾ÑÐ»ÐµÐ´Ð½Ð¸Ð¹ chunk, ÐµÑÐ»Ð¸ Ð¾Ð½ Ð½Ðµ Ð¿ÑƒÑÑ‚Ð¾Ð¹
        if (!empty($currentChunk)) {
            $chunkInfo = $this->calculateChunkInfo($currentChunk, $targetRows, $previousChunkLastManual ?? $previousManual, true);
            $chunks[] = $chunkInfo;
        }

        return $chunks;
    }

    /**
     * Ð Ð°ÑÑÑ‡Ð¸Ñ‚Ñ‹Ð²Ð°ÐµÑ‚ Ð¸Ð½Ñ„Ð¾Ñ€Ð¼Ð°Ñ†Ð¸ÑŽ Ð¾ chunk: ÐºÐ¾Ð»Ð¸Ñ‡ÐµÑÑ‚Ð²Ð¾ manual-ÑÑ‚Ñ€Ð¾Ðº, data-ÑÑ‚Ñ€Ð¾Ðº Ð¸
     * Ð¿ÑƒÑÑ‚Ñ‹Ñ… ÑÑ‚Ñ€Ð¾Ðº
     *
     * @param array $chunk ÐœÐ°ÑÑÐ¸Ð² ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ð¾Ð² Ð² chunk
     * @param int $targetRows Ð¦ÐµÐ»ÐµÐ²Ð¾Ðµ ÐºÐ¾Ð»Ð¸Ñ‡ÐµÑÑ‚Ð²Ð¾ ÑÑ‚Ñ€Ð¾Ðº
     * @param string|null $previousManual Manual Ð¸Ð· Ð¿Ñ€ÐµÐ´Ñ‹Ð´ÑƒÑ‰ÐµÐ³Ð¾ chunk
     * @param bool $isLastPage Ð¯Ð²Ð»ÑÐµÑ‚ÑÑ Ð»Ð¸ ÑÑ‚Ð¾ Ð¿Ð¾ÑÐ»ÐµÐ´Ð½ÐµÐ¹ ÑÑ‚Ñ€Ð°Ð½Ð¸Ñ†ÐµÐ¹
     * @return array
     */
    private function calculateChunkInfo($chunk, $targetRows, $previousManual = null, $isLastPage = false)
    {
        $manualRows = 0;
        $dataRows = count($chunk);
        $tempPreviousManual = $previousManual;
        $lastManual = null;

        // Ð¡Ñ‡Ð¸Ñ‚Ð°ÐµÐ¼ manual-ÑÑ‚Ñ€Ð¾ÐºÐ¸
        foreach ($chunk as $component) {
            $currentManual = $component->manual ?? null;
            if ($currentManual !== null && $currentManual !== '' && $currentManual !== $tempPreviousManual) {
                $manualRows++;
                $tempPreviousManual = $currentManual;
                $lastManual = $currentManual;
            } else if ($currentManual !== null && $currentManual !== '') {
                $tempPreviousManual = $currentManual;
                $lastManual = $currentManual;
            }
        }

        $totalDataRows = $dataRows + $manualRows;

        // Ð”Ð¾Ð±Ð°Ð²Ð»ÑÐµÐ¼ Ð¿ÑƒÑÑ‚Ñ‹Ðµ ÑÑ‚Ñ€Ð¾ÐºÐ¸ Ð´Ð¾ targetRows Ð½Ð° Ð²ÑÐµÑ… ÑÑ‚Ñ€Ð°Ð½Ð¸Ñ†Ð°Ñ… (Ð²ÐºÐ»ÑŽÑ‡Ð°Ñ Ð¿Ð¾ÑÐ»ÐµÐ´Ð½ÑŽÑŽ)
        $emptyRows = max(0, $targetRows - $totalDataRows);

        return [
            'components' => $chunk,
            'manual_rows' => $manualRows,
            'data_rows' => $dataRows,
            'empty_rows' => $emptyRows,
            'total_rows' => $totalDataRows + $emptyRows,
            'previous_manual' => $lastManual ?? $previousManual,
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return
     */
    public function index()
    {
        $orders = Workorder::all();
        $manuals = Manual::all();
        $units = Unit::with('manuals')->get();
        $tdrs = Tdr::all();
        return view('admin.tdrs.index', compact('orders', 'units', 'manuals', 'tdrs'));
    }

    public function create()
    {
        //
    }


    public function inspectionComponent(Request $request, $workorder_id)
    {
        $current_wo = Workorder::findOrFail($workorder_id);
        $manual_id = $current_wo->unit->manual_id;
        $user = Auth::user();

        $canManageAllManualParts = (bool) ($user?->roleIs('Admin') ?? false);
        $allowedManualIds = $canManageAllManualParts
            ? []
            : $user?->permittedManuals()->pluck('manuals.id')->all();

        $manualHasAnyPermissions = DB::table('manual_user_permissions')
            ->where('manual_id', $manual_id)
            ->exists();

        $canManageManualParts = $canManageAllManualParts
            || !$manualHasAnyPermissions
            || in_array((int)$manual_id, array_map('intval', $allowedManualIds ?? []), true);

        // Ð”Ð»Ñ Ñ„Ñ€Ð¾Ð½Ñ‚Ð°: Ñ‡Ñ‚Ð¾Ð±Ñ‹ JS-Ñ€Ð°Ð·Ñ€ÐµÑˆÐµÐ½Ð¸Ñ ÑÐ¾Ð²Ð¿Ð°Ð´Ð°Ð»Ð¸ Ñ Ð¿Ñ€Ð°Ð²Ð¸Ð»Ð¾Ð¼ "ÐµÑÐ»Ð¸ manual Ð½Ðµ Ð·Ð°Ð´Ð°Ð½ Ð½Ð¸ÐºÐ¾Ð¼Ñƒ â€” Ñ€Ð°Ð·Ñ€ÐµÑˆÐµÐ½Ð¾ Ð²ÑÐµÐ¼"
        if (!$canManageAllManualParts && !$manualHasAnyPermissions) {
            $allowedManualIds = array_values(array_unique(array_merge(
                array_map('intval', $allowedManualIds ?? []),
                [(int) $manual_id]
            )));
        }

        // ÐšÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ñ‹ Ð´Ð»Ñ Ð´Ð°Ð½Ð½Ð¾Ð³Ð¾ manual
        $componentsQuery = Component::where('manual_id', $manual_id)
            ->with('assemblies:id,component_id,assy_part_number,assy_ipl_num,units_assy,sort_order')
            ->select('id', 'part_number', 'assy_part_number', 'name', 'ipl_num', 'assy_ipl_num', 'units_assy', 'kit', 'kit_e', 'eff_code');

        if ($request->boolean('exclude_kits')) {
            $componentsQuery
                ->where(function ($query) {
                    $query->where('kit', false)->orWhereNull('kit');
                });
        }

        $components = $this->filterComponentsForUnit(
            $componentsQuery->get(),
            $current_wo
        );

        // Ð£ÑÐ»Ð¾Ð²Ð¸Ñ Ð´Ð»Ñ Component - Ð±ÐµÐ· Ñ„Ð¸Ð»ÑŒÑ‚Ñ€Ð°Ñ†Ð¸Ð¸
        $component_conditions = Condition::where('unit', false)->get();

        // ÐŸÐ¾Ð»ÑƒÑ‡Ð°ÐµÐ¼ ÐºÐ¾Ð´Ñ‹ Ð¸ necessaries
        $codes = Code::all();
        $necessaries = Necessary::all();
        if ($canManageAllManualParts) {
            $manuals = Manual::all();
        } else {
            $manualIdsToShow = array_unique(array_merge(
                array_map('intval', $allowedManualIds ?? []),
                [(int)$manual_id]
            ));
            $manuals = Manual::query()
                ->whereIn('id', $manualIdsToShow)
                ->orderBy('number')
                ->get();
        }

        return view('admin.tdrs.component-inspection', compact('current_wo', 'component_conditions',
            'components', 'codes', 'necessaries', 'manual_id', 'manuals',
            'canManageManualParts', 'canManageAllManualParts', 'allowedManualIds'));
    }

    public function getComponentsByManual(Request $request)
    {
        $manual_id = $request->get('manual_id');

        if (!$manual_id) {
            return response()->json(['components' => []]);
        }

        $componentsQuery = Component::where('manual_id', $manual_id)
            ->with('assemblies:id,component_id,assy_part_number,assy_ipl_num,units_assy,sort_order')
            ->select('id', 'part_number', 'assy_part_number', 'name', 'ipl_num', 'assy_ipl_num', 'units_assy', 'kit', 'kit_e', 'eff_code');

        if ($request->boolean('exclude_kits')) {
            $componentsQuery
                ->where(function ($query) {
                    $query->where('kit', false)->orWhereNull('kit');
                });
        }

        $components = $componentsQuery->get();

        if ($request->filled('workorder_id')) {
            $workorder = Workorder::with('unit')->find($request->integer('workorder_id'));

            if ($workorder && $workorder->unit) {
                $components = $this->filterComponentsForUnit($components, $workorder);
            }
        }

        return response()->json(['components' => $components]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        foreach (['component_id', 'order_component_id', 'conditions_id', 'necessaries_id', 'codes_id'] as $nullableId) {
            if ($request->has($nullableId) && trim((string) $request->input($nullableId)) === '') {
                $request->merge([$nullableId => null]);
            }
        }

        $validated = $request->validate([
            'workorder_id' => 'required|exists:workorders,id',
            'component_id' => 'nullable|exists:components,id',
            'serial_number' => 'nullable|string|max:255',
            'assy_serial_number' => 'nullable|string|max:255',
            'conditions_id' => 'nullable|exists:conditions,id',
            'necessaries_id' => 'nullable|exists:necessaries,id',
            'codes_id' => 'nullable|exists:codes,id',
            'qty' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'order_component_id' => 'nullable|exists:components,id',
            'order_component_assembly_id' => 'nullable|exists:component_assemblies,id',
        ]);

        // Ð£ÑÑ‚Ð°Ð½Ð¾Ð²ÐºÐ° Ð·Ð½Ð°Ñ‡ÐµÐ½Ð¸Ð¹ Ð¿Ð¾ ÑƒÐ¼Ð¾Ð»Ñ‡Ð°Ð½Ð¸ÑŽ Ð´Ð»Ñ Ñ„Ð»Ð°Ð³Ð¾Ð²
        $use_tdr = $request->boolean('use_tdr', false);
        $use_process_forms = $request->boolean('use_process_forms', false);
        $qty = (int)($validated['qty'] ?? 1);

        // Ð—Ð°Ð³Ñ€ÑƒÐ¶Ð°ÐµÐ¼ Ð½ÐµÐ¾Ð±Ñ…Ð¾Ð´Ð¸Ð¼Ñ‹Ðµ ÑÑƒÑ‰Ð½Ð¾ÑÑ‚Ð¸ Ð¾Ð´Ð¸Ð½ Ñ€Ð°Ð·
        $workorder = Workorder::findOrFail($validated['workorder_id']);
        $code = Code::where('name', 'Missing')->first();
        $manufactureCode = Code::where('name', 'Manufacture')->first();
        $necessary = Necessary::where('name', 'Order New')->first();
        $repairNecessary = Necessary::where('name', 'Repair')->first();

        // TODO(tdr-refactor): Remove this legacy tdrs.store compatibility branch after the UI posts unit inspections only to TdrUnitInspectionController.
        $isDetachedUnitInspection = empty($validated['component_id'])
            && empty($validated['order_component_id'])
            && empty($validated['codes_id'])
            && empty($validated['necessaries_id'])
            && ! empty($validated['conditions_id']);

        if ($isDetachedUnitInspection) {
            try {
                WorkorderUnitInspection::query()->updateOrCreate(
                    [
                        'workorder_id' => $workorder->id,
                        'condition_id' => (int) $validated['conditions_id'],
                    ],
                    [
                        'notes' => $validated['description'] ?? null,
                        'qty' => $qty,
                        'serial_number' => $validated['serial_number'] ?? 'NSN',
                        'assy_serial_number' => $validated['assy_serial_number'] ?? ' ',
                        'use_tdr' => $use_tdr,
                        'use_process_forms' => $use_process_forms,
                    ]
                );

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => __('Unit inspection saved successfully.'),
                        'workorder_id' => $workorder->id,
                    ]);
                }

                return redirect()
                    ->route('tdrs.show', ['id' => $workorder->id])
                    ->with('success', __('Unit inspection saved successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['error' => __('Failed to save unit inspection')]);
            }
        }

        // Manufacture: ÑÐ¾Ð·Ð´Ð°Ñ‘Ð¼ 2 Ð·Ð°Ð¿Ð¸ÑÐ¸ (Order New + Repair)
        if ($manufactureCode && $validated['codes_id'] == $manufactureCode->id) {
            if (empty($validated['component_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['component_id' => __('Component ID is required when code is Manufacture')]);
            }

            $manufactureCondition = Condition::where('name', 'Manufacture')->where('unit', false)->first();
            if (!$manufactureCondition) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['codes_id' => __('Condition "Manufacture" not found in database. Please add it to the conditions table.')]);
            }

            try {
                $description = $validated['description'] ?? null;
                $qty = (int)($validated['qty'] ?? 1);

                // Record 1: Order New â€” conditions_id=null, order_component_id=component_id, use_tdr=1, use_process_forms=0
                Tdr::create([
                    'tdr_type' => Tdr::TYPE_MANUFACTURE_ORDER,
                    'workorder_id' => $validated['workorder_id'],
                    'component_id' => $validated['component_id'],
                    'serial_number' => $validated['serial_number'] ?? 'NSN',
                    'assy_serial_number' => $validated['assy_serial_number'] ?? ' ',
                    'codes_id' => $manufactureCode->id,
                    'conditions_id' => null,
                    'necessaries_id' => $necessary->id,
                    'description' => $description,
                    'qty' => $qty,
                    'use_tdr' => true,
                    'use_process_forms' => false,
                    'order_component_id' => $validated['component_id'],
                ]);

                // Record 2: Repair â€” conditions_id=Manufacture, use_tdr=1, use_process_forms=1
                Tdr::create([
                    'tdr_type' => Tdr::TYPE_MANUFACTURE_REPAIR,
                    'workorder_id' => $validated['workorder_id'],
                    'component_id' => $validated['component_id'],
                    'serial_number' => 'NSN',
                    'assy_serial_number' => ' ',
                    'codes_id' => $manufactureCode->id,
                    'conditions_id' => $manufactureCondition->id,
                    'necessaries_id' => $repairNecessary->id,
                    'description' => $description,
                    'qty' => $qty,
                    'use_tdr' => true,
                    'use_process_forms' => true,
                    'order_component_id' => null,
                ]);

                $orderNewCount = Tdr::where('workorder_id', $workorder->id)
                    ->where('necessaries_id', $necessary->id)
                    ->count();
                if ($orderNewCount == 1 || $workorder->new_parts === false || $workorder->new_parts == 0) {
                    $workorder->new_parts = true;
                    $workorder->save();
                }

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => __('TDR records created successfully'),
                        'workorder_id' => $workorder->id,
                    ]);
                }

                return redirect()
                    ->route('tdrs.show', ['id' => $workorder->id])
                    ->with('success', __('TDR records created successfully'));
            } catch (\Exception $e) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['error' => __('Failed to create TDR records')]);
            }
        }

        // Ð’Ð°Ð»Ð¸Ð´Ð°Ñ†Ð¸Ñ: Missing Ñ‚Ñ€ÐµÐ±ÑƒÐµÑ‚ Ð¾Ð±ÑÐ·Ð°Ñ‚ÐµÐ»ÑŒÐ½Ñ‹Ð¹ component_id
        if ($code && $validated['codes_id'] == $code->id) {
            if (empty($validated['component_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['component_id' => 'Component ID is required when code is Missing']);
            }

            // Ð’Ð°Ð»Ð¸Ð´Ð°Ñ†Ð¸Ñ: Missing Ñ‚Ñ€ÐµÐ±ÑƒÐµÑ‚ Ð¾Ð±ÑÐ·Ð°Ñ‚ÐµÐ»ÑŒÐ½Ñ‹Ð¹ necessaries_id = Order New (ID = 2)
            if (empty($validated['necessaries_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['necessaries_id' => 'Necessary is required for Missing code']);
            }

            if (empty($validated['order_component_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['order_component_id' => __('Order component is required for Missing code')]);
            }

            if (!$necessary || $validated['necessaries_id'] != $necessary->id) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['necessaries_id' => 'Missing code can only have Order New necessary']);
            }
        }

        // Ð’Ð°Ð»Ð¸Ð´Ð°Ñ†Ð¸Ñ: Ð´Ð»Ñ Ð´Ñ€ÑƒÐ³Ð¸Ñ… codes (Ð½Ðµ Missing, Ð½Ðµ Manufacture) necessaries_id Ð¾Ð±ÑÐ·Ð°Ñ‚ÐµÐ»ÐµÐ½ Ð¸ Ð´Ð¾Ð»Ð¶ÐµÐ½ Ð±Ñ‹Ñ‚ÑŒ Repair Ð¸Ð»Ð¸ Order New
        $isManufacture = $manufactureCode && $validated['codes_id'] == $manufactureCode->id;
        if ($code && $validated['codes_id'] && $validated['codes_id'] != $code->id && !$isManufacture) {
            if (empty($validated['necessaries_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['necessaries_id' => 'Necessary is required for non-Missing codes']);
            }

            $isValidNecessary = false;
            if ($necessary && $validated['necessaries_id'] == $necessary->id) {
                $isValidNecessary = true;
            }
            if ($repairNecessary && $validated['necessaries_id'] == $repairNecessary->id) {
                $isValidNecessary = true;
            }
            if (!$isValidNecessary) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['necessaries_id' => 'For non-Missing codes, necessary must be Repair or Order New']);
            }
        }

        $validatedCodesId = $validated['codes_id'] ? (int) $validated['codes_id'] : null;
        $validatedNecessaryId = $validated['necessaries_id'] ? (int) $validated['necessaries_id'] : null;
        $codeIdInt = $code ? (int) $code->id : null;
        $necessaryIdInt = $necessary ? (int) $necessary->id : null;

        if (! empty($validated['order_component_assembly_id'])) {
            $assemblyBelongsToOrderComponent = \App\Models\ComponentAssembly::query()
                ->whereKey((int) $validated['order_component_assembly_id'])
                ->where('component_id', (int) ($validated['order_component_id'] ?? 0))
                ->exists();

            if (! $assemblyBelongsToOrderComponent) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['order_component_assembly_id' => __('Selected assembly does not belong to the selected order component')]);
            }
        }

        if (
            $necessaryIdInt !== null
            && $validatedNecessaryId === $necessaryIdInt
            && $codeIdInt !== null
            && $validatedCodesId !== $codeIdInt
            && empty($validated['order_component_id'])
        ) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['order_component_id' => __('Order component is required for Order New')]);
        }

        // ÐŸÑ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼ Ð½Ð°Ð»Ð¸Ñ‡Ð¸Ðµ Ð·Ð°Ð¿Ð¸ÑÐµÐ¹ Ñ Missing Ð´Ð¾ ÑÐ¾Ð·Ð´Ð°Ð½Ð¸Ñ (Ð´Ð»Ñ Ð¾Ð¿Ñ‚Ð¸Ð¼Ð¸Ð·Ð°Ñ†Ð¸Ð¸)
        $hasExistingMissing = false;
        if ($codeIdInt !== null && $validatedCodesId === $codeIdInt) {
            $hasExistingMissing = Tdr::where('workorder_id', $workorder->id)
                ->where('codes_id', $code->id)
                ->exists();
        }

        // Ð•ÑÐ»Ð¸ codes_id Ñ€Ð°Ð²Ð½Ð¾ Missing, Ð°Ð²Ñ‚Ð¾Ð¼Ð°Ñ‚Ð¸Ñ‡ÐµÑÐºÐ¸ ÑƒÑÑ‚Ð°Ð½Ð°Ð²Ð»Ð¸Ð²Ð°ÐµÐ¼ conditions_id=1 (PARTS MISSING UPON ARRIVAL)
        $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
        if ($codeIdInt !== null && $validatedCodesId === $codeIdInt && $missingCondition) {
            // Ð•ÑÐ»Ð¸ conditions_id Ð½Ðµ ÑƒÑÑ‚Ð°Ð½Ð¾Ð²Ð»ÐµÐ½ Ð¸Ð»Ð¸ Ñ€Ð°Ð²ÐµÐ½ null, ÑƒÑÑ‚Ð°Ð½Ð°Ð²Ð»Ð¸Ð²Ð°ÐµÐ¼ ÐµÐ³Ð¾ Ð² missingCondition->id
            if (empty($validated['conditions_id']) || $validated['conditions_id'] === null) {
                $validated['conditions_id'] = $missingCondition->id;
                // \Log::info('Auto-set conditions_id to missingCondition', [
                //     'workorder_id' => $workorder->id,
                //     'codes_id' => $validated['codes_id'],
                //     'conditions_id' => $missingCondition->id
                // ]);
            }
        }

        try {
            // Ð¡Ð¾Ñ…Ñ€Ð°Ð½ÐµÐ½Ð¸Ðµ Ð² Ñ‚Ð°Ð±Ð»Ð¸Ñ†Ðµ tdrs
            $tdrPayload = [
                'workorder_id' => $validated['workorder_id'],
                'component_id' => $validated['component_id'],
                'serial_number' => $validated['serial_number'] ?? 'NSN',
                'assy_serial_number' => $validated['assy_serial_number'],
                'codes_id' => $validated['codes_id'],
                'conditions_id' => $validated['conditions_id'],
                'necessaries_id' => $validated['necessaries_id'],
                'description' => $validated['description'],
                'qty' => $qty,
                'use_tdr' => $use_tdr,
                'use_process_forms' => $use_process_forms,
                'order_component_id' => $validated['order_component_id'],
                'order_component_assembly_id' => $validated['order_component_assembly_id'] ?? null,
            ];
            $tdr = Tdr::create(['tdr_type' => $this->inferTdrTypeFromPayload($tdrPayload, $manufactureCode, $necessary, $repairNecessary)] + $tdrPayload);

            // \Log::info('TDR created', [
            //     'tdr_id' => $tdr->id,
            //     'workorder_id' => $tdr->workorder_id,
            //     'codes_id' => $tdr->codes_id,
            //     'conditions_id' => $tdr->conditions_id,
            //     'component_id' => $tdr->component_id
            // ]);
        } catch (\Exception $e) {
            // \Log::error('Error creating TDR', [
            //     'error' => $e->getMessage(),
            //     'request_data' => $request->all()
            // ]);
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create TDR record']);
        }

        // Ð•ÑÐ»Ð¸ codes_id Ñ€Ð°Ð²Ð½Ð¾ Missing, Ð¾Ð±Ð½Ð¾Ð²Ð»ÑÐµÐ¼ Ð¿Ð¾Ð»Ðµ part_missing Ð² workorders
        // Ð˜ÑÐ¿Ð¾Ð»ÑŒÐ·ÑƒÐµÐ¼ Ð¿Ñ€Ð¸Ð²ÐµÐ´ÐµÐ½Ð¸Ðµ Ñ‚Ð¸Ð¿Ð¾Ð² Ð´Ð»Ñ ÑÑ€Ð°Ð²Ð½ÐµÐ½Ð¸Ñ, Ñ‚.Ðº. codes_id Ð¼Ð¾Ð¶ÐµÑ‚ Ð±Ñ‹Ñ‚ÑŒ ÑÑ‚Ñ€Ð¾ÐºÐ¾Ð¹ Ð¸Ð· Ñ„Ð¾Ñ€Ð¼Ñ‹
        $codesIdInt = $validatedCodesId;

        // \Log::info('Checking if codes_id is Missing', [
        //     'workorder_id' => $workorder->id,
        //     'codes_id' => $validated['codes_id'],
        //     'codes_id_int' => $codesIdInt,
        //     'code_id' => $code ? $code->id : null,
        //     'code_id_int' => $codeIdInt,
        //     'code_found' => $code ? true : false,
        //     'match' => ($code && $codesIdInt === $codeIdInt)
        // ]);

        if ($code && $codesIdInt === $codeIdInt) {
            // ÐŸÑ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼ ÐºÐ¾Ð»Ð¸Ñ‡ÐµÑÑ‚Ð²Ð¾ Ð·Ð°Ð¿Ð¸ÑÐµÐ¹ Ñ Missing Ð¿Ð¾ÑÐ»Ðµ ÑÐ¾Ð·Ð´Ð°Ð½Ð¸Ñ (Ð²ÐºÐ»ÑŽÑ‡Ð°Ñ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ñ‡Ñ‚Ð¾ ÑÐ¾Ð·Ð´Ð°Ð½Ð½ÑƒÑŽ)
            $missingCount = Tdr::where('workorder_id', $workorder->id)
                ->where('codes_id', $code->id)
                ->count();

            // \Log::info('Checking part_missing flag', [
            //     'workorder_id' => $workorder->id,
            //     'missing_count' => $missingCount,
            //     'current_part_missing' => $workorder->part_missing,
            //     'codes_id' => $validated['codes_id'],
            //     'part_missing_type' => gettype($workorder->part_missing)
            // ]);

            // Ð•ÑÐ»Ð¸ ÑÑ‚Ð¾ Ð¿ÐµÑ€Ð²Ð°Ñ Ð·Ð°Ð¿Ð¸ÑÑŒ Ñ Missing (count == 1) Ð¸Ð»Ð¸ Ñ„Ð»Ð°Ð³ ÐµÑ‰Ðµ Ð½Ðµ ÑƒÑÑ‚Ð°Ð½Ð¾Ð²Ð»ÐµÐ½ (0 Ð¸Ð»Ð¸ false)
            if ($missingCount == 1 || $workorder->part_missing == 0 || $workorder->part_missing === false || !$workorder->part_missing) {
                $workorder->part_missing = true;
                $workorder->save();
                // \Log::info('Set part_missing to true', [
                //     'workorder_id' => $workorder->id,
                //     'missing_count' => $missingCount
                // ]);
            } else {
                // \Log::info('part_missing not changed', [
                //     'workorder_id' => $workorder->id,
                //     'missing_count' => $missingCount,
                //     'part_missing' => $workorder->part_missing
                // ]);
            }
        }

        // Ð’Ñ‚Ð¾Ñ€Ð¾Ðµ ÑƒÑÐ»Ð¾Ð²Ð¸Ðµ: ÐµÑÐ»Ð¸ codes_id Ð½Ðµ Ñ€Ð°Ð²Ð½Ð¾ Missing Ð¸ necessaries_id Ñ€Ð°Ð²Ð½Ð¾ Order New
        // new_parts=true ÑƒÑÑ‚Ð°Ð½Ð°Ð²Ð»Ð¸Ð²Ð°ÐµÑ‚ÑÑ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ ÐºÐ¾Ð³Ð´Ð° Ñƒ workorder ÐµÑÑ‚ÑŒ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ñ‹ (tdr Ð·Ð°Ð¿Ð¸ÑÐ¸) Ñ necessary = Order New
        if ($code && $necessary &&
            $codesIdInt !== $codeIdInt &&
            $validatedNecessaryId === $necessaryIdInt) {

            // ÐŸÑ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼ ÐºÐ¾Ð»Ð¸Ñ‡ÐµÑÑ‚Ð²Ð¾ Ð·Ð°Ð¿Ð¸ÑÐµÐ¹ Ñ Order New Ð¿Ð¾ÑÐ»Ðµ ÑÐ¾Ð·Ð´Ð°Ð½Ð¸Ñ (Ð²ÐºÐ»ÑŽÑ‡Ð°Ñ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ñ‡Ñ‚Ð¾ ÑÐ¾Ð·Ð´Ð°Ð½Ð½ÑƒÑŽ)
            $orderNewCount = Tdr::where('workorder_id', $workorder->id)
                ->where('necessaries_id', $necessary->id)
                ->count();

            // Ð•ÑÐ»Ð¸ ÑÑ‚Ð¾ Ð¿ÐµÑ€Ð²Ð°Ñ Ð·Ð°Ð¿Ð¸ÑÑŒ Ñ Order New (count == 1) Ð¸Ð»Ð¸ Ñ„Ð»Ð°Ð³ ÐµÑ‰Ðµ Ð½Ðµ ÑƒÑÑ‚Ð°Ð½Ð¾Ð²Ð»ÐµÐ½
            if ($orderNewCount == 1 || $workorder->new_parts === false || $workorder->new_parts == 0) {
                $workorder->new_parts = true;
                $workorder->save();
                // \Log::info('Set new_parts to true', [
                //     'workorder_id' => $workorder->id,
                //     'order_new_count' => $orderNewCount
                // ]);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'TDR record created successfully',
                'workorder_id' => $workorder->id,
            ]);
        }

        return redirect()
            ->route('tdrs.show', ['id' => $workorder->id])
            ->with('success', 'TDR record created successfully');
    }
    public function store_old(Request $request)
    {
        $validated = $request->validate([
            'workorder_id' => 'required|exists:workorders,id',
            'component_id' => 'nullable|exists:components,id',
            'serial_number' => 'nullable|string|max:255',
            'assy_serial_number' => 'nullable|string|max:255',
            'conditions_id' => 'nullable|exists:conditions,id',
            'necessaries_id' => 'nullable|exists:necessaries,id',
            'codes_id' => 'nullable|exists:codes,id',
            'qty' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'order_component_id' => 'nullable|exists:components,id',
        ]);

        // Ð£ÑÑ‚Ð°Ð½Ð¾Ð²ÐºÐ° Ð·Ð½Ð°Ñ‡ÐµÐ½Ð¸Ð¹ Ð¿Ð¾ ÑƒÐ¼Ð¾Ð»Ñ‡Ð°Ð½Ð¸ÑŽ Ð´Ð»Ñ Ñ„Ð»Ð°Ð³Ð¾Ð²
        $use_tdr = $request->boolean('use_tdr', false);
        $use_process_forms = $request->boolean('use_process_forms', false);
        $qty = (int)($validated['qty'] ?? 1);

        // Ð—Ð°Ð³Ñ€ÑƒÐ¶Ð°ÐµÐ¼ Ð½ÐµÐ¾Ð±Ñ…Ð¾Ð´Ð¸Ð¼Ñ‹Ðµ ÑÑƒÑ‰Ð½Ð¾ÑÑ‚Ð¸ Ð¾Ð´Ð¸Ð½ Ñ€Ð°Ð·
        $workorder = Workorder::findOrFail($validated['workorder_id']);
        $code = Code::where('name', 'Missing')->first();
        $necessary = Necessary::where('name', 'Order New')->first();

        // ÐŸÑ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼ Ð½Ð°Ð»Ð¸Ñ‡Ð¸Ðµ Ð·Ð°Ð¿Ð¸ÑÐµÐ¹ Ñ Missing Ð´Ð¾ ÑÐ¾Ð·Ð´Ð°Ð½Ð¸Ñ (Ð´Ð»Ñ Ð¾Ð¿Ñ‚Ð¸Ð¼Ð¸Ð·Ð°Ñ†Ð¸Ð¸)
        $hasExistingMissing = false;
        if ($code && $validated['codes_id'] === $code->id) {
            $hasExistingMissing = Tdr::where('workorder_id', $workorder->id)
                ->where('codes_id', $code->id)
                ->exists();
        }

        // Ð•ÑÐ»Ð¸ codes_id Ñ€Ð°Ð²Ð½Ð¾ Missing, Ð°Ð²Ñ‚Ð¾Ð¼Ð°Ñ‚Ð¸Ñ‡ÐµÑÐºÐ¸ ÑƒÑÑ‚Ð°Ð½Ð°Ð²Ð»Ð¸Ð²Ð°ÐµÐ¼ conditions_id=1 (PARTS MISSING UPON ARRIVAL)
        $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
        if ($code && $validated['codes_id'] === $code->id && $missingCondition) {
            // Ð•ÑÐ»Ð¸ conditions_id Ð½Ðµ ÑƒÑÑ‚Ð°Ð½Ð¾Ð²Ð»ÐµÐ½, ÑƒÑÑ‚Ð°Ð½Ð°Ð²Ð»Ð¸Ð²Ð°ÐµÐ¼ ÐµÐ³Ð¾ Ð² missingCondition->id
            if (empty($validated['conditions_id'])) {
                $validated['conditions_id'] = $missingCondition->id;
            }
        }

        try {
            // Ð¡Ð¾Ñ…Ñ€Ð°Ð½ÐµÐ½Ð¸Ðµ Ð² Ñ‚Ð°Ð±Ð»Ð¸Ñ†Ðµ tdrs
            $tdrPayload = [
                'workorder_id' => $validated['workorder_id'],
                'component_id' => $validated['component_id'],
                'serial_number' => $validated['serial_number'] ?? 'NSN',
                'assy_serial_number' => $validated['assy_serial_number'],
                'codes_id' => $validated['codes_id'],
                'conditions_id' => $validated['conditions_id'],
                'necessaries_id' => $validated['necessaries_id'],
                'description' => $validated['description'],
                'qty' => $qty,
                'use_tdr' => $use_tdr,
                'use_process_forms' => $use_process_forms,
                'order_component_id' => $validated['order_component_id'],
            ];
            $tdr = Tdr::create(['tdr_type' => $this->inferTdrTypeFromPayload($tdrPayload, null, $necessary)] + $tdrPayload);
        } catch (\Exception $e) {
            // \Log::error('Error creating TDR', [
            //     'error' => $e->getMessage(),
            //     'request_data' => $request->all()
            // ]);
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create TDR record']);
        }

        // Ð•ÑÐ»Ð¸ codes_id Ñ€Ð°Ð²Ð½Ð¾ Missing, Ð¾Ð±Ð½Ð¾Ð²Ð»ÑÐµÐ¼ Ð¿Ð¾Ð»Ðµ part_missing Ð² workorders
        if ($code && $validated['codes_id'] === $code->id) {
            // ÐŸÑ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼ ÐºÐ¾Ð»Ð¸Ñ‡ÐµÑÑ‚Ð²Ð¾ Ð·Ð°Ð¿Ð¸ÑÐµÐ¹ Ñ Missing Ð¿Ð¾ÑÐ»Ðµ ÑÐ¾Ð·Ð´Ð°Ð½Ð¸Ñ (Ð²ÐºÐ»ÑŽÑ‡Ð°Ñ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ñ‡Ñ‚Ð¾ ÑÐ¾Ð·Ð´Ð°Ð½Ð½ÑƒÑŽ)
            $missingCount = Tdr::where('workorder_id', $workorder->id)
                ->where('codes_id', $code->id)
                ->count();

            // Ð•ÑÐ»Ð¸ ÑÑ‚Ð¾ Ð¿ÐµÑ€Ð²Ð°Ñ Ð·Ð°Ð¿Ð¸ÑÑŒ Ñ Missing (count == 1) Ð¸Ð»Ð¸ Ñ„Ð»Ð°Ð³ ÐµÑ‰Ðµ Ð½Ðµ ÑƒÑÑ‚Ð°Ð½Ð¾Ð²Ð»ÐµÐ½
            if ($missingCount == 1 || $workorder->part_missing === false) {
                $workorder->part_missing = true;
                $workorder->save();
            }
        }

        // Ð’Ñ‚Ð¾Ñ€Ð¾Ðµ ÑƒÑÐ»Ð¾Ð²Ð¸Ðµ: ÐµÑÐ»Ð¸ codes_id Ð½Ðµ Ñ€Ð°Ð²Ð½Ð¾ Missing Ð¸ necessaries_id Ñ€Ð°Ð²Ð½Ð¾ Order New
        if ($code && $necessary &&
            $validated['codes_id'] !== $code->id &&
            $validated['necessaries_id'] === $necessary->id) {

            if ($workorder->new_parts === false) {
                $workorder->new_parts = true;
                $workorder->save();
            }
        }

        return redirect()
            ->route('tdrs.show', ['id' => $workorder->id])
            ->with('success', 'TDR record created successfully');
    }
    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Application|Factory|View
     */



    /**
     * Ð¡Ñ‚Ñ€Ð¾ÐºÐ¸ Ð¼Ð¾Ð´Ð°Ð»ÐºÐ¸ Group Process Forms Ð´Ð»Ñ Ð²ÐºÐ»Ð°Ð´ÐºÐ¸ All Parts Processes.
     * ÐÐµ-NDT: Ð´ÐµÑ‚Ð°Ð»Ð¸ Ñ Ð¾Ð´Ð½Ð¸Ð¼ Ð¸ Ñ‚ÐµÐ¼ Ð¶Ðµ Ñ‚Ð¸Ð¿Ð¾Ð¼ Ð³Ñ€ÑƒÐ¿Ð¿Ð¾Ð²Ð¾Ð¹ Ñ„Ð¾Ñ€Ð¼Ñ‹ (process_names / merge Machining), â‰¥2 Ð´ÐµÑ‚Ð°Ð»ÐµÐ¹;
     * Ð¿Ð¾Ð»Ð½Ñ‹Ð¹ Ð¼Ð°Ñ€ÑˆÑ€ÑƒÑ‚ Ð¼Ð¾Ð¶ÐµÑ‚ Ð¾Ñ‚Ð»Ð¸Ñ‡Ð°Ñ‚ÑŒÑÑ (ÐºÐ°Ðº Ñƒ Ñ€Ð°Ð·Ð½Ñ‹Ñ… NDT Ð¸ Paint), Ð¾Ð±Ñ‰Ð¸Ð¹ ÑˆÐ°Ð³ â€” Ð½Ð°Ð¿Ñ€Ð¸Ð¼ÐµÑ€ Silver plate.
     * NDT: Ð¾Ð´Ð½Ð° ÑÑ‚Ñ€Ð¾ÐºÐ°, Ð²ÑÐµ Ð´ÐµÑ‚Ð°Ð»Ð¸ Ñ Ð»ÑŽÐ±Ñ‹Ð¼ NDT (Ð² Ð¼Ð¾Ð´Ð°Ð»ÐºÐµ â€” Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ñ‡ÐµÐºÐ±Ð¾ÐºÑÑ‹ Ð¿Ð¾ Ð´ÐµÑ‚Ð°Ð»ÑÐ¼).
     * totalQty Ð² Ñ€ÐµÐ·ÑƒÐ»ÑŒÑ‚Ð°Ñ‚Ðµ â€” ÑÑƒÐ¼Ð¼Ð° position_count Ð¿Ð¾ ÑÑ‚Ñ€Ð¾ÐºÐ°Ð¼ (Ð¿Ð¾Ð·Ð¸Ñ†Ð¸Ð¸ TDR), Ð½Ðµ ÑÑƒÐ¼Ð¼Ð° qty Ð´ÐµÑ‚Ð°Ð»ÐµÐ¹.
     *
     * @param  \Illuminate\Support\Collection  $tdrProcesses
     * @return array{processGroups: list<array<string, mixed>>, totalQty: int}
     */
    private function buildAllPartsProcessGroupModalRows($tdrs, $tdrProcesses): array
    {
        $rows = [];
        $totalQty = 0;

        $standardBuckets = [];

        foreach ($tdrs as $tdr) {
            if (!$tdr->component) {
                continue;
            }
            $tdrProcessesForTdr = $tdrProcesses->where('tdrs_id', $tdr->id);

            foreach ($tdrProcessesForTdr as $tdrProcess) {
                if (!$tdrProcess->processName) {
                    continue;
                }
                if (ProcessName::hasNoProcessForm($tdrProcess->processName)) {
                    continue;
                }
                $modalGroupKey = ProcessName::groupFormsGroupKey($tdrProcess->processName, true);
                if ($modalGroupKey === 'NDT_GROUP') {
                    continue;
                }
                $bucketId = is_string($modalGroupKey) ? $modalGroupKey : (string) (int) $modalGroupKey;
                if (!isset($standardBuckets[$bucketId])) {
                    $standardBuckets[$bucketId] = [
                        'modal_group_key' => $modalGroupKey,
                        'tdr_ids' => [],
                    ];
                }
                if (!in_array($tdr->id, $standardBuckets[$bucketId]['tdr_ids'], true)) {
                    $standardBuckets[$bucketId]['tdr_ids'][] = $tdr->id;
                }
            }
        }

        foreach ($standardBuckets as $bucketId => $bucket) {
            if (count($bucket['tdr_ids']) < 2) {
                continue;
            }
            $modalKey = $bucket['modal_group_key'];
            if ($modalKey === ProcessName::GROUP_KEY_MERGE_MACHINING_MEC) {
                $repPn = ProcessName::machiningMachiningEcRepresentative();
            } else {
                $repPn = ProcessName::find((int) $modalKey);
            }
            if (!$repPn) {
                continue;
            }
            $repId = $repPn->id;
            $displayName = $repPn->name;

            $components = [];
            $partsQty = 0;
            foreach ($bucket['tdr_ids'] as $tid) {
                $tdr = $tdrs->firstWhere('id', $tid);
                if (!$tdr || !$tdr->component) {
                    continue;
                }
                $ck = sprintf(
                    '%s_%s_%s',
                    $tdr->component->ipl_num ?? '',
                    $tdr->component->part_number ?? '',
                    $tdr->serial_number ?? ''
                );
                $orderQty = (int) ($tdr->qty ?? 1);
                $components[$ck] = [
                    'id' => $tdr->component->id,
                    'name' => $tdr->component->name,
                    'ipl_num' => $tdr->component->ipl_num,
                    'part_number' => $tdr->component->part_number,
                    'serial_number' => $tdr->serial_number,
                    'tdr_id' => $tdr->id,
                    'qty' => $orderQty,
                ];
                $partsQty += 1;
            }
            $components = array_values($components);
            if (count($components) < 2) {
                continue;
            }

            $rowUid = 'std_'.$repId.'_'.substr(sha1($bucketId), 0, 10);
            $rows[] = [
                'row_uid' => $rowUid,
                'row_kind' => 'standard',
                'display_name' => $displayName,
                'representative_process_name_id' => $repId,
                'process_name' => $repPn,
                'count' => count($components),
                'position_count' => count($components),
                'qty' => $partsQty,
                'components' => $components,
            ];
            $totalQty += $partsQty;
        }

        $ndtTdrIds = [];
        foreach ($tdrs as $tdr) {
            if (!$tdr->component) {
                continue;
            }
            $hasNdt = $tdrProcesses->where('tdrs_id', $tdr->id)->contains(function ($tp) {
                return $tp->processName
                    && !ProcessName::hasNoProcessForm($tp->processName)
                    && ($tp->processName->process_sheet_name ?? '') === 'NDT';
            });
            if ($hasNdt) {
                $ndtTdrIds[] = $tdr->id;
            }
        }

        if (count($ndtTdrIds) >= 2) {
            $ndtProcessName = ProcessName::where('process_sheet_name', 'NDT')->first()
                ?? ProcessName::where('name', 'like', 'NDT-%')->orderBy('id')->first();
            if ($ndtProcessName) {
                $ndtComponents = [];
                $partsQty = 0;
                foreach ($ndtTdrIds as $tid) {
                    $tdr = $tdrs->firstWhere('id', $tid);
                    if (!$tdr || !$tdr->component) {
                        continue;
                    }
                    $hasNdtLine = false;
                    $sorted = $tdrProcesses->where('tdrs_id', $tdr->id)->sortBy('sort_order');
                    foreach ($sorted as $tp) {
                        if (!$tp->processName || ProcessName::hasNoProcessForm($tp->processName)) {
                            continue;
                        }
                        if (($tp->processName->process_sheet_name ?? '') !== 'NDT') {
                            continue;
                        }
                        $raw = $tp->processes;
                        $processData = is_array($raw) ? $raw : json_decode((string) $raw, true);
                        if (!is_array($processData)) {
                            $processData = [];
                        }
                        if ($processData === []) {
                            continue;
                        }
                        $hasNdtLine = true;
                        break;
                    }
                    if (!$hasNdtLine) {
                        continue;
                    }
                    $ck = sprintf(
                        '%s_%s_%s',
                        $tdr->component->ipl_num ?? '',
                        $tdr->component->part_number ?? '',
                        $tdr->serial_number ?? ''
                    );
                    $orderQty = (int) ($tdr->qty ?? 1);
                    $ndtComponents[$ck] = [
                        'id' => $tdr->component->id,
                        'name' => $tdr->component->name,
                        'ipl_num' => $tdr->component->ipl_num,
                        'part_number' => $tdr->component->part_number,
                        'serial_number' => $tdr->serial_number,
                        'tdr_id' => $tdr->id,
                        'qty' => $orderQty,
                    ];
                    $partsQty += 1;
                }
                $ndtComponents = array_values($ndtComponents);
                if (count($ndtComponents) >= 2) {
                    $rowUid = 'ndt_all_'.substr(sha1(implode(',', $ndtTdrIds)), 0, 10);
                    $rows[] = [
                        'row_uid' => $rowUid,
                        'row_kind' => 'ndt',
                        'display_name' => 'NDT',
                        'representative_process_name_id' => $ndtProcessName->id,
                        'process_name' => $ndtProcessName,
                        'count' => count($ndtComponents),
                        'position_count' => count($ndtComponents),
                        'qty' => $partsQty,
                        'components' => $ndtComponents,
                    ];
                    $totalQty += $partsQty;
                }
            }
        }

        usort($rows, function (array $a, array $b): int {
            $aNdt = ($a['row_kind'] ?? '') === 'ndt';
            $bNdt = ($b['row_kind'] ?? '') === 'ndt';
            if ($aNdt !== $bNdt) {
                return $aNdt ? -1 : 1;
            }

            return strcasecmp((string) ($a['display_name'] ?? ''), (string) ($b['display_name'] ?? ''));
        });

        return ['processGroups' => $rows, 'totalQty' => $totalQty];
    }

    public function processesPartial($id)
    {

        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;
        $necessary = Necessary::where('name', 'Order New')->first();

        $manuals = Manual::all();  // Ð¸Ð»Ð¸ Ð¼Ð¾Ð¶Ð½Ð¾ Ð¾Ñ‚Ñ„Ð¸Ð»ÑŒÑ‚Ñ€Ð¾Ð²Ð°Ñ‚ÑŒ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ñ‚Ð¾Ñ‚, ÐºÐ¾Ñ‚Ð¾Ñ€Ñ‹Ð¹ ÑÐ²ÑÐ·Ð°Ð½ Ñ unit

        // Ð˜Ð·Ð²Ð»ÐµÐºÐ°ÐµÐ¼ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ñ‹, ÐºÐ¾Ñ‚Ð¾Ñ€Ñ‹Ðµ ÑÐ²ÑÐ·Ð°Ð½Ñ‹ Ñ ÑÑ‚Ð¸Ð¼ manual_id
        $components = $this->filterComponentsForUnit(Component::where('manual_id', $manual_id)
            ->where(function ($query) {
                $query->where('kit', false)->orWhereNull('kit');
            })
            ->with('assemblies:id,component_id,assy_part_number,assy_ipl_num,units_assy,sort_order')
            ->get(), $current_wo);

        // ÐžÐ³Ñ€Ð°Ð½Ð¸Ñ‡Ð¸Ð²Ð°ÐµÐ¼ Ð¿Ñ€Ð¾Ñ†ÐµÑÑÑ‹ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ñ‚ÐµÐºÑƒÑ‰Ð¸Ð¼ Workorder: Ð±ÐµÑ€Ñ‘Ð¼ id ÑÐ²ÑÐ·Ð°Ð½Ð½Ñ‹Ñ… TDR
        $tdrIds = Tdr::where('workorder_id', $current_wo->id)
            ->pluck('id');

        // Ð—Ð°Ð³Ñ€ÑƒÐ¶Ð°ÐµÐ¼ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ð¿Ñ€Ð¾Ñ†ÐµÑÑÑ‹ Ð´Ð»Ñ ÑÑ‚Ð¸Ñ… TDR, Ñ ÑÐ¾Ñ€Ñ‚Ð¸Ñ€Ð¾Ð²ÐºÐ¾Ð¹ Ð¸ Ð½Ð°Ð·Ð²Ð°Ð½Ð¸ÐµÐ¼ Ð¿Ñ€Ð¾Ñ†ÐµÑÑÐ°
        $tdrProcessesQuery = TdrProcess::query()
            ->whereIn('tdrs_id', $tdrIds);
        $this->applyStdListProcessesVisibilityForWorkorder($current_wo, $tdrProcessesQuery);
        $tdrProcesses = $tdrProcessesQuery->with('processName')
            ->orderBy('sort_order')
            ->get();

        $proces = Process::all()->keyBy('id');
        $vendors = Vendor::all();

        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->where('component_id', '!=',null)
            ->when($necessary, function ($query) use ($necessary) {
                return $query->where('necessaries_id', '!=', $necessary->id);
            })
            ->where('use_process_forms', true)
            ->with('component')
            ->get();

        $built = $this->buildAllPartsProcessGroupModalRows($tdrs, $tdrProcesses);
        $processGroups = $built['processGroups'];
        $totalQty = $built['totalQty'];

        return view('admin.tdrs.partials.all-parts-processes', compact('current_wo',
            'tdrs','components',
            'manuals','tdrProcesses','proces','vendors','processGroups','totalQty'
        ));
    }

    public function processes(Request $request, $id)
    {

        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;
        $necessary = Necessary::where('name', 'Order New')->first();

        $manuals = Manual::all();  // Ð¸Ð»Ð¸ Ð¼Ð¾Ð¶Ð½Ð¾ Ð¾Ñ‚Ñ„Ð¸Ð»ÑŒÑ‚Ñ€Ð¾Ð²Ð°Ñ‚ÑŒ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ñ‚Ð¾Ñ‚, ÐºÐ¾Ñ‚Ð¾Ñ€Ñ‹Ð¹ ÑÐ²ÑÐ·Ð°Ð½ Ñ unit

        // Ð˜Ð·Ð²Ð»ÐµÐºÐ°ÐµÐ¼ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ñ‹, ÐºÐ¾Ñ‚Ð¾Ñ€Ñ‹Ðµ ÑÐ²ÑÐ·Ð°Ð½Ñ‹ Ñ ÑÑ‚Ð¸Ð¼ manual_id
        $components = $this->filterComponentsForUnit(
            Component::where('manual_id', $manual_id)
                ->with('assemblies:id,component_id,assy_part_number,assy_ipl_num,units_assy,sort_order')
                ->select('id', 'manual_id', 'part_number', 'assy_part_number', 'name', 'ipl_num', 'assy_ipl_num', 'units_assy', 'eff_code', 'kit', 'kit_e')
                ->get(),
            $current_wo
        );

        // ÐžÐ³Ñ€Ð°Ð½Ð¸Ñ‡Ð¸Ð²Ð°ÐµÐ¼ Ð¿Ñ€Ð¾Ñ†ÐµÑÑÑ‹ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ñ‚ÐµÐºÑƒÑ‰Ð¸Ð¼ Workorder: Ð±ÐµÑ€Ñ‘Ð¼ id ÑÐ²ÑÐ·Ð°Ð½Ð½Ñ‹Ñ… TDR
        $tdrIds = Tdr::where('workorder_id', $current_wo->id)
            ->pluck('id');

        // Ð—Ð°Ð³Ñ€ÑƒÐ¶Ð°ÐµÐ¼ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ð¿Ñ€Ð¾Ñ†ÐµÑÑÑ‹ Ð´Ð»Ñ ÑÑ‚Ð¸Ñ… TDR, Ñ ÑÐ¾Ñ€Ñ‚Ð¸Ñ€Ð¾Ð²ÐºÐ¾Ð¹ Ð¸ Ð½Ð°Ð·Ð²Ð°Ð½Ð¸ÐµÐ¼ Ð¿Ñ€Ð¾Ñ†ÐµÑÑÐ°
        $tdrProcessesQuery = TdrProcess::query()
            ->whereIn('tdrs_id', $tdrIds);
        $this->applyStdListProcessesVisibilityForWorkorder($current_wo, $tdrProcessesQuery);
        $tdrProcesses = $tdrProcessesQuery->with('processName')
            ->orderBy('sort_order')
            ->get();

        $proces = Process::all()->keyBy('id');
        $vendors = Vendor::all();

        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->where('component_id', '!=',null)
            ->when($necessary, function ($query) use ($necessary) {
                return $query->where('necessaries_id', '!=', $necessary->id);
            })
            ->where('use_process_forms', true)
            ->with('component')
            ->get();

        $built = $this->buildAllPartsProcessGroupModalRows($tdrs, $tdrProcesses);
        $processGroups = $built['processGroups'];
        $totalQty = $built['totalQty'];

        if ($request->ajax() || $request->wantsJson()) {
            return view('admin.tdrs.partials.all-parts-processes', compact('current_wo',
                'tdrs','components',
                'manuals','tdrProcesses','proces','vendors','processGroups','totalQty' ));
        }

        return view('admin.tdrs.processes', compact('current_wo',
            'tdrs','components',
            'manuals','tdrProcesses','proces','vendors','processGroups','totalQty'
        ));
    }

    /**
     * Display grouped forms for all TDR processes by process name.
     *
     * @param  int  $id
     * @param  int  $processNameId
     * @param  Request  $request
     * @return Application|Factory|View
     */
    public function showGroupForms($id, $processNameId, Request $request)
    {
        $current_wo = Workorder::findOrFail($id);
        $processName = ProcessName::findOrFail($processNameId);
        if (ProcessName::hasNoProcessForm($processName)) {
            abort(404, __('There is no process form for EC.'));
        }
        $manual_id = $current_wo->unit->manual_id;
        $necessary = Necessary::where('name', 'Order New')->first();

        // ÐŸÑ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼, Ð¿ÐµÑ€ÐµÐ´Ð°Ð½ Ð»Ð¸ tdrId Ð´Ð»Ñ Ñ„Ð¸Ð»ÑŒÑ‚Ñ€Ð°Ñ†Ð¸Ð¸ Ð¿Ð¾ ÐºÐ¾Ð½ÐºÑ€ÐµÑ‚Ð½Ð¾Ð¼Ñƒ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ñƒ
        $tdrId = $request->input('tdrId');

        if ($tdrId) {
            // Ð•ÑÐ»Ð¸ Ð¿ÐµÑ€ÐµÐ´Ð°Ð½ tdrId, Ñ„Ð¸Ð»ÑŒÑ‚Ñ€ÑƒÐµÐ¼ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ð¿Ñ€Ð¾Ñ†ÐµÑÑÑ‹ Ð´Ð»Ñ ÑÑ‚Ð¾Ð³Ð¾ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ð°
            $tdr = Tdr::findOrFail($tdrId);
            // ÐŸÑ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼, Ñ‡Ñ‚Ð¾ TDR Ð¿Ñ€Ð¸Ð½Ð°Ð´Ð»ÐµÐ¶Ð¸Ñ‚ ÑÑ‚Ð¾Ð¼Ñƒ workorder
            if ($tdr->workorder_id != $current_wo->id) {
                abort(403, 'TDR does not belong to this workorder');
            }
            $tdrIds = collect([$tdrId]);
        } else {
            // ÐŸÐ¾Ð»ÑƒÑ‡Ð°ÐµÐ¼ Ð²ÑÐµ TDR Ð´Ð»Ñ ÑÑ‚Ð¾Ð³Ð¾ work order
            $tdrIds = Tdr::where('workorder_id', $current_wo->id)
                ->where('component_id', '!=', null)
                ->when($necessary, function ($query) use ($necessary) {
                    return $query->where('necessaries_id', '!=', $necessary->id);
                })
                ->where('use_process_forms', true)
                ->pluck('id');
        }

        // ÐŸÐ¾Ð»ÑƒÑ‡Ð°ÐµÐ¼ Ð²ÑÐµ TdrProcess Ð´Ð»Ñ ÑÑ‚Ð¸Ñ… TDR
        $tdrProcessesQuery = TdrProcess::query()
            ->whereIn('tdrs_id', $tdrIds);
        $this->applyStdListProcessesVisibilityForWorkorder($current_wo, $tdrProcessesQuery);
        $tdrProcesses = $tdrProcessesQuery->with(['tdr.component', 'processName'])
            ->orderBy('sort_order')
            ->get();

        // Ð¤Ð¸Ð»ÑŒÑ‚Ñ€ÑƒÐµÐ¼ TdrProcess: NDT â€” Ð²ÑÐµ Ð¸Ð¼ÐµÐ½Ð° Ñ Ð»Ð¸ÑÑ‚Ð¾Ð¼ NDT; Machining + Machining (EC) â€” Ð¾Ð´Ð½Ð° Ð³Ñ€ÑƒÐ¿Ð¿Ð°; Ð¸Ð½Ð°Ñ‡Ðµ â€” ÑÑ‚Ñ€Ð¾Ð³Ð¾ Ð²Ñ‹Ð±Ñ€Ð°Ð½Ð½Ñ‹Ð¹ process_name_id.
        $filteredTdrProcesses = collect();
        $isNdtGroup = $processName->process_sheet_name == 'NDT';
        $ndtProcessNameIds = $isNdtGroup ? ProcessName::where('process_sheet_name', 'NDT')->pluck('id')->toArray() : [];
        $isMachiningMergeGroup = ProcessName::isMachiningMachiningEcMergeMember($processName);
        $machiningMergeNameIds = $isMachiningMergeGroup ? ProcessName::machiningMachiningEcMergeProcessNameIds() : [];

        foreach ($tdrProcesses as $tdrProcess) {
            if (!$tdrProcess->tdr || !$tdrProcess->tdr->component || !$tdrProcess->processName) {
                continue;
            }

            $currentProcessName = $tdrProcess->processName;

            if ($isNdtGroup) {
                if (in_array($currentProcessName->id, $ndtProcessNameIds)) {
                    $filteredTdrProcesses->push($tdrProcess);
                }
            } elseif ($isMachiningMergeGroup && in_array((int) $currentProcessName->id, $machiningMergeNameIds, true)) {
                $filteredTdrProcesses->push($tdrProcess);
            } elseif ((int) $currentProcessName->id === (int) $processNameId) {
                $filteredTdrProcesses->push($tdrProcess);
            }
        }

        // ÐŸÐ¾Ð»ÑƒÑ‡Ð°ÐµÐ¼ ÑÐ²ÑÐ·Ð°Ð½Ð½Ñ‹Ðµ Ð´Ð°Ð½Ð½Ñ‹Ðµ
        $components = $this->filterComponentsForUnit(
            Component::where('manual_id', $manual_id)
                ->with('assemblies:id,component_id,assy_part_number,assy_ipl_num,units_assy,sort_order')
                ->get(),
            $current_wo
        );
        $manualProcesses = ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        // ÐŸÐ¾Ð»ÑƒÑ‡Ð°ÐµÐ¼ Ð²Ñ‹Ð±Ñ€Ð°Ð½Ð½Ð¾Ð³Ð¾ vendor (ÐµÑÐ»Ð¸ Ð¿ÐµÑ€ÐµÐ´Ð°Ð½)
        $selectedVendor = null;
        $vendorId = $request->input('vendor_id');
        if ($vendorId) {
            $selectedVendor = Vendor::find($vendorId);
        }

        // Ð¤Ð¸Ð»ÑŒÑ‚Ñ€ÑƒÐµÐ¼ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ñ‹ Ð¿Ð¾ Ð²Ñ‹Ð±Ñ€Ð°Ð½Ð½Ñ‹Ð¼ component_ids Ð¸ serial_numbers (ÐµÑÐ»Ð¸ Ð¿ÐµÑ€ÐµÐ´Ð°Ð½Ñ‹)
        // Ð¢ÐµÐ¿ÐµÑ€ÑŒ ÑƒÑ‡Ð¸Ñ‚Ñ‹Ð²Ð°ÐµÐ¼ Ð½Ðµ Ñ‚Ð¾Ð»ÑŒÐºÐ¾ component_id, Ð½Ð¾ Ð¸ serial_number Ð´Ð»Ñ Ñ‚Ð¾Ñ‡Ð½Ð¾Ð¹ Ð¸Ð´ÐµÐ½Ñ‚Ð¸Ñ„Ð¸ÐºÐ°Ñ†Ð¸Ð¸
        // Ð•ÑÐ»Ð¸ Ð¿ÐµÑ€ÐµÐ´Ð°Ð½ tdrId, Ñ‚Ð¾ Ñ„Ð¸Ð»ÑŒÑ‚Ñ€Ð°Ñ†Ð¸Ñ Ð¿Ð¾ component_ids Ð½Ðµ Ð½ÑƒÐ¶Ð½Ð°, Ñ‚Ð°Ðº ÐºÐ°Ðº ÑƒÐ¶Ðµ Ñ„Ð¸Ð»ÑŒÑ‚Ñ€ÑƒÐµÐ¼ Ð¿Ð¾ Ð¾Ð´Ð½Ð¾Ð¼Ñƒ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ñƒ
        $componentIds = $request->input('component_ids');
        $serialNumbers = $request->input('serial_numbers');
        $iplNums = $request->input('ipl_nums');
        $partNumbers = $request->input('part_numbers');

        // Ð•ÑÐ»Ð¸ Ð¿ÐµÑ€ÐµÐ´Ð°Ð½ tdrId, Ð¿Ñ€Ð¾Ð¿ÑƒÑÐºÐ°ÐµÐ¼ Ñ„Ð¸Ð»ÑŒÑ‚Ñ€Ð°Ñ†Ð¸ÑŽ Ð¿Ð¾ component_ids, Ñ‚Ð°Ðº ÐºÐ°Ðº ÑƒÐ¶Ðµ Ñ„Ð¸Ð»ÑŒÑ‚Ñ€ÑƒÐµÐ¼ Ð¿Ð¾ Ð¾Ð´Ð½Ð¾Ð¼Ñƒ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ñƒ
        if ($componentIds && !$tdrId) {
            // Ð Ð°Ð·Ð±Ð¸Ð²Ð°ÐµÐ¼ ÑÑ‚Ñ€Ð¾ÐºÐ¸ Ð½Ð° Ð¼Ð°ÑÑÐ¸Ð²Ñ‹
            $filteredComponentIds = is_array($componentIds)
                ? array_map('intval', $componentIds)
                : array_map('intval', explode(',', $componentIds));

            $filteredSerialNumbers = [];
            if ($serialNumbers) {
                $filteredSerialNumbers = is_array($serialNumbers)
                    ? $serialNumbers
                    : explode(',', $serialNumbers);
            }

            $filteredIplNums = [];
            if ($iplNums) {
                $filteredIplNums = is_array($iplNums)
                    ? $iplNums
                    : explode(',', $iplNums);
            }

            $filteredPartNumbers = [];
            if ($partNumbers) {
                $filteredPartNumbers = is_array($partNumbers)
                    ? $partNumbers
                    : explode(',', $partNumbers);
            }

            // Ð¤Ð¸Ð»ÑŒÑ‚Ñ€ÑƒÐµÐ¼ TdrProcess Ð¿Ð¾ Ð²Ñ‹Ð±Ñ€Ð°Ð½Ð½Ñ‹Ð¼ component_id, ipl_num, part_number Ð¸ serial_number
            $filteredTdrProcesses = $filteredTdrProcesses->filter(function($tdrProcess) use (
                $filteredComponentIds,
                $filteredSerialNumbers,
                $filteredIplNums,
                $filteredPartNumbers
            ) {
                if (!$tdrProcess->tdr || !$tdrProcess->tdr->component) {
                    return false;
                }

                // ÐŸÑ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼, ÑÐ¾Ð¾Ñ‚Ð²ÐµÑ‚ÑÑ‚Ð²ÑƒÐµÑ‚ Ð»Ð¸ component_id
                if (!in_array($tdrProcess->tdr->component->id, $filteredComponentIds)) {
                    return false;
                }

                // Ð•ÑÐ»Ð¸ Ð¿ÐµÑ€ÐµÐ´Ð°Ð½Ñ‹ serial_numbers, Ð¿Ñ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼ Ð¸Ñ…
                if (!empty($filteredSerialNumbers)) {
                    $tdrSerialNumber = $tdrProcess->tdr->serial_number ?? '';
                    if (!in_array($tdrSerialNumber, $filteredSerialNumbers)) {
                        return false;
                    }
                }

                // Ð•ÑÐ»Ð¸ Ð¿ÐµÑ€ÐµÐ´Ð°Ð½Ñ‹ ipl_nums, Ð¿Ñ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼ Ð¸Ñ…
                if (!empty($filteredIplNums)) {
                    $tdrIplNum = $tdrProcess->tdr->component->ipl_num ?? '';
                    if (!in_array($tdrIplNum, $filteredIplNums)) {
                        return false;
                    }
                }

                // Ð•ÑÐ»Ð¸ Ð¿ÐµÑ€ÐµÐ´Ð°Ð½Ñ‹ part_numbers, Ð¿Ñ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼ Ð¸Ñ…
                if (!empty($filteredPartNumbers)) {
                    $tdrPartNumber = $tdrProcess->tdr->component->part_number ?? '';
                    if (!in_array($tdrPartNumber, $filteredPartNumbers)) {
                        return false;
                    }
                }

                return true;
            });
        }

        // ID Ð¾Ð¿ÐµÑ€Ð°Ñ†Ð¸Ð¹ Ð¸Ð· ÑÐ¿Ñ€Ð°Ð²Ð¾Ñ‡Ð½Ð¸ÐºÐ° processes, Ñ€ÐµÐ°Ð»ÑŒÐ½Ð¾ Ð½Ð°Ð·Ð½Ð°Ñ‡ÐµÐ½Ð½Ñ‹Ñ… Ð² JSON Ð¼Ð°Ñ€ÑˆÑ€ÑƒÑ‚Ð° (Ð¾Ñ‚Ñ„Ð¸Ð»ÑŒÑ‚Ñ€Ð¾Ð²Ð°Ð½Ð½Ñ‹Ñ… TdrProcess)
        $assignedCatalogProcessIds = $filteredTdrProcesses->flatMap(function ($tp) {
            return TdrProcess::normalizeStoredProcessIds($tp->processes);
        })->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();

        // ÐœÐ¾Ð´Ð°Ð»ÐºÐ° Ð¿ÐµÑ€ÐµÐ´Ð°Ñ‘Ñ‚ process_ids (id Ð¸Ð· ÑÐ¿Ñ€Ð°Ð²Ð¾Ñ‡Ð½Ð¸ÐºÐ° processes) â€” Ñ‚Ð¾Ð»ÑŒÐºÐ¾ Ð²Ñ‹Ð±Ñ€Ð°Ð½Ð½Ñ‹Ðµ ÑÑ‚Ñ€Ð¾ÐºÐ¸ Ð½Ð° Ñ„Ð¾Ñ€Ð¼Ðµ
        $allowedCatalogProcessIds = $assignedCatalogProcessIds;
        if ($request->has('process_ids')) {
            $rawIds = $request->input('process_ids');
            if ($rawIds === null || $rawIds === '') {
                $allowedCatalogProcessIds = [];
            } else {
                $requested = is_array($rawIds)
                    ? array_map('intval', $rawIds)
                    : array_map('intval', array_filter(explode(',', (string) $rawIds), static fn ($s) => $s !== ''));
                $allowedCatalogProcessIds = array_values(array_intersect($assignedCatalogProcessIds, $requested));
            }
        }

        $ndtComponentsForForm = $filteredTdrProcesses;
        $processTdrComponentsForForm = $filteredTdrProcesses;
        if ($request->has('process_ids')) {
            $ndtComponentsForForm = $filteredTdrProcesses->filter(function ($tp) use ($allowedCatalogProcessIds) {
                if (count($allowedCatalogProcessIds) === 0) {
                    return false;
                }
                $raw = TdrProcess::normalizeStoredProcessIds($tp->processes);
                foreach ($raw as $id) {
                    if (in_array((int) $id, $allowedCatalogProcessIds, true)) {
                        return true;
                    }
                }

                return false;
            })->values();

            $processTdrComponentsForForm = collect();
            foreach ($filteredTdrProcesses as $tp) {
                $raw = TdrProcess::normalizeStoredProcessIds($tp->processes);
                $filteredJson = array_values(array_filter(
                    $raw,
                    static fn ($pid) => in_array((int) $pid, $allowedCatalogProcessIds, true)
                ));
                if (count($filteredJson) === 0) {
                    continue;
                }
                $clone = clone $tp;
                $clone->setAttribute('processes', $filteredJson);
                $processTdrComponentsForForm->push($clone);
            }
        }

        // Ð‘Ð°Ð·Ð¾Ð²Ñ‹Ðµ Ð´Ð°Ð½Ð½Ñ‹Ðµ Ð´Ð»Ñ Ð¿Ñ€ÐµÐ´ÑÑ‚Ð°Ð²Ð»ÐµÐ½Ð¸Ñ (Ð´Ð»Ñ Ð¾Ð±ÑŠÐµÐ´Ð¸Ð½Ñ‘Ð½Ð½Ð¾Ð¹ Ð³Ñ€ÑƒÐ¿Ð¿Ñ‹ Machining / Machining (EC) Ð·Ð°Ð³Ð¾Ð»Ð¾Ð²Ð¾Ðº â€” Â«MachiningÂ»)
        $displayProcessName = $isMachiningMergeGroup
            ? (ProcessName::machiningMachiningEcRepresentative() ?? $processName)
            : $processName;
        $viewData = [
            'module' => 'tdr-processes',
            'current_wo' => $current_wo,
            'components' => $components,
            'manuals' => Manual::where('id', $manual_id)->get(),
            'manual_id' => $manual_id,
            'process_name' => $displayProcessName,
            'selectedVendor' => $selectedVendor,
            'tdrs' => $tdrIds->toArray(),
            'machining_header_manual_libs' => ProcessName::isMachiningPrintedForm($displayProcessName)
                ? Manual::orderedLibValuesForManualIds(Manual::manualIdsForWorkorder((int) $current_wo->id))
                : [],
        ];

        // Ð”Ð¾Ð±Ð°Ð²Ð»ÑÐµÐ¼ Ð¿ÐµÑ€Ð²Ñ‹Ð¹ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚ Ð´Ð»Ñ Ð·Ð°Ð³Ð¾Ð»Ð¾Ð²ÐºÐ° Ñ„Ð¾Ñ€Ð¼Ñ‹ (ÐµÑÐ»Ð¸ ÐµÑÑ‚ÑŒ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ñ‹)
        $firstTdrProcess = $processTdrComponentsForForm->first() ?? $filteredTdrProcesses->first();
        if ($firstTdrProcess && $firstTdrProcess->tdr && $firstTdrProcess->tdr->component) {
            $viewData['component'] = $firstTdrProcess->tdr->component;
        } else {
            // Ð•ÑÐ»Ð¸ Ð½ÐµÑ‚ ÐºÐ¾Ð¼Ð¿Ð¾Ð½ÐµÐ½Ñ‚Ð¾Ð², ÑÐ¾Ð·Ð´Ð°ÐµÐ¼ Ð¿ÑƒÑÑ‚Ð¾Ð¹ Ð¾Ð±ÑŠÐµÐºÑ‚
            $viewData['component'] = (object)[
                'name' => 'Multiple Components',
                'part_number' => 'Various',
                'ipl_num' => 'Various'
            ];
        }

        // ÐžÐ±Ñ€Ð°Ð±Ð¾Ñ‚ÐºÐ° NDT Ñ„Ð¾Ñ€Ð¼Ñ‹ (ÐµÑÐ»Ð¸ Ð½ÑƒÐ¶Ð½Ð¾)
        if ($processName->process_sheet_name == 'NDT') {
            $processNames = ProcessName::whereIn('name', [
                'NDT-1', 'NDT-2', 'NDT-3', 'NDT-4', 'NDT-5', 'NDT-6', 'NDT-7', 'NDT-8',
                'Eddy Current Test', 'BNI'
            ])->pluck('id', 'name');

            $ndt_ids = [
                'ndt1_name_id' => $processNames['NDT-1'] ?? null,
                'ndt2_name_id' => $processNames['NDT-2'] ?? null,
                'ndt3_name_id' => $processNames['NDT-3'] ?? null,
                'ndt4_name_id' => $processNames['NDT-4'] ?? null,
                'ndt5_name_id' => $processNames['BNI'] ?? $processNames['NDT-5'] ?? null,
                'ndt6_name_id' => $processNames['Eddy Current Test'] ?? $processNames['NDT-6'] ?? null,
                'ndt7_name_id' => $processNames['NDT-7'] ?? null,
                'ndt8_name_id' => $processNames['NDT-8'] ?? null,
            ];
            $ndt_ids_filtered = array_filter($ndt_ids);

            $ndt_processes_query = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $ndt_ids_filtered);
            if (count($allowedCatalogProcessIds) > 0) {
                $ndt_processes_query->whereIn('id', $allowedCatalogProcessIds);
            } else {
                $ndt_processes_query->whereRaw('1 = 0');
            }
            $ndt_processes = $ndt_processes_query->get();

            return view('admin.tdr-processes.processesForm', array_merge($viewData, [
                'ndt_processes' => $ndt_processes,
                'selectedVendor' => $selectedVendor,
                'ndt_components' => $ndtComponentsForForm,
                'current_ndt_id' => $processName->id
            ], $ndt_ids));
        }

        // ÐžÐ±Ñ‹Ñ‡Ð½Ñ‹Ðµ Ð¿Ñ€Ð¾Ñ†ÐµÑÑÑ‹: ÑÐ¿Ñ€Ð°Ð²Ð¾Ñ‡Ð½Ð¸Ðº Ð´Ð»Ñ Ð²Ñ‹Ð±Ñ€Ð°Ð½Ð½Ð¾Ð³Ð¾ process_names_id (Ð¸Ð»Ð¸ Machining + Machining (EC) Ð²Ð¼ÐµÑÑ‚Ðµ) Ð¸ Ð¾Ð¿ÐµÑ€Ð°Ñ†Ð¸Ð¸ Ð¸Ð· Ð¼Ð°Ñ€ÑˆÑ€ÑƒÑ‚Ð° (JSON)
        $process_components_query = Process::whereIn('id', $manualProcesses);
        if ($isMachiningMergeGroup && count($machiningMergeNameIds) > 0) {
            $process_components_query->whereIn('process_names_id', $machiningMergeNameIds);
        } else {
            $process_components_query->where('process_names_id', $processNameId);
        }
        if (count($allowedCatalogProcessIds) > 0) {
            $process_components_query->whereIn('id', $allowedCatalogProcessIds);
        } else {
            $process_components_query->whereRaw('1 = 0');
        }
        $process_components = $process_components_query->get();

        return view('admin.tdr-processes.processesForm', array_merge($viewData, [
            'process_components' => $process_components,
            'selectedVendor' => $selectedVendor,
            'process_tdr_components' => $processTdrComponentsForForm
        ]));
    }

    /**
     * Display TDR Report (tabbed layout).
     *
     * @param int $id Workorder ID
     * @return Application|Factory|View
     */
    public function show($id)
    {
        $viewData = $this->getShowData($id);
        return view('admin.tdrs.show', $viewData);
    }

    /**
     * Prepare data for TDR show view.
     *
     * @param int $id Workorder ID
     * @return array
     */
    private function getShowData($id)
    {
        $current_wo = Workorder::with(['unit.manuals.builder', 'instruction'])->findOrFail($id);
        $units = Unit::all();
        $user = Auth::user();
        $user_wo = $current_wo->user_id;
        $customers = Customer::all();
        $manual_id = $current_wo->unit->manual_id;

        $form_type = 112;
        $trainings = Training::where('manuals_id', $manual_id)
            ->where('user_id', $user_wo)
            ->where('form_type', $form_type)
            ->orderBy('date_training', 'desc')
            ->first();

        $canManageAllManualParts = (bool) ($user?->roleIs('Admin') ?? false);
        $allowedManualIds = $canManageAllManualParts
            ? []
            : $user?->permittedManuals()->pluck('manuals.id')->all();

        $manualHasAnyPermissions = DB::table('manual_user_permissions')
            ->where('manual_id', $manual_id)
            ->exists();

        $canManageManualParts = $canManageAllManualParts
            || !$manualHasAnyPermissions
            || in_array((int)$manual_id, array_map('intval', $allowedManualIds ?? []), true);

        if (!$canManageAllManualParts && !$manualHasAnyPermissions) {
            $allowedManualIds = array_values(array_unique(array_merge(
                array_map('intval', $allowedManualIds ?? []),
                [(int) $manual_id]
            )));
        }

        if ($canManageAllManualParts) {
            $manuals = Manual::all();
        } else {
            $manualIdsToShow = array_unique(array_merge(
                array_map('intval', $allowedManualIds ?? []),
                [(int)$manual_id]
            ));
            $manuals = Manual::query()
                ->whereIn('id', $manualIdsToShow)
                ->orderBy('number')
                ->get();
        }
        $log_card = LogCard::where('workorder_id', $current_wo->id)->first();
        $logCardTdrAccess = app(LogCardTdrAccessService::class)->forWorkorder($current_wo, $user);
        $showDestructionCert = LogCardDestructionCertificate::availableFor($current_wo);
        $woBushing = WoBushing::where('workorder_id', $current_wo->id)->first();
        $hasBushings = Component::where('manual_id', $manual_id)->where('is_bush', 1)->exists();
        $components = $this->filterComponentsForUnit(
            Component::where('manual_id', $manual_id)
                ->with('assemblies:id,component_id,assy_part_number,assy_ipl_num,units_assy,sort_order')
                ->get(),
            $current_wo
        );
        $bushingPrlCount = $woBushing ? $woBushing->lines()->whereHas('component')->count() : 0;
        $kitComponents = $components->filter(fn ($component): bool => ! (bool) ($component->is_bush ?? false));
        $kitPrlCount = $this->countKitPrlGroups($kitComponents->where('kit', true))
            + $this->countKitPrlGroups($kitComponents->where('kit_e', true));
        $stdFormCounts = [
            'ndt' => $this->countStdFormQty($current_wo, StdProcess::STD_NDT),
            'cad' => $this->countStdFormQty($current_wo, StdProcess::STD_CAD),
            'stress' => $this->countStdFormQty($current_wo, StdProcess::STD_STRESS),
            'paint' => $this->countPaintStdFormQty($current_wo),
        ];
        $spFormColumnsCount = $this->countSpecProcessFormColumns($current_wo);
        $bushingSpFormColumnsCount = $this->countBushingSpecProcessColumns($woBushing);
        $rmFormRowsCount = $this->countRmFormRows($current_wo);
        $tdrFormRowsCount = $this->countTdrFormRows($current_wo);
        $code = Code::where('name', 'Missing')->first();
        $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
        $conditions = Condition::all();
        $necessary = Necessary::where('name', 'Order New')->first();

        $processParts = Tdr::where('workorder_id', $current_wo->id)
            ->where('component_id', '!=', null)
            ->when($necessary, function ($query) use ($necessary) {
                return $query->where('necessaries_id', '!=', $necessary->id);
            })
            ->with(['component' => function($query) {
                $query->select('id', 'name', 'part_number', 'ipl_num');
            }])
            ->get();

        $hasProcessFormTdrs = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->exists();

        $inspectsUnit = WorkorderUnitInspection::where('workorder_id', $current_wo->id)
            ->with(['condition' => function($query) { $query->select('id', 'name'); }])
            ->orderBy('id')
            ->get();

        $missingParts = Tdr::where('workorder_id', $current_wo->id)
            ->where(function($query) use ($code) {
                if ($code) {
                    $query->where('codes_id', $code->id);
                } else {
                    $query->where('codes_id', 7);
                }
            })
            ->with([
                'component' => function($query) { $query->withTrashed()->select('id', 'name', 'part_number', 'ipl_num', 'assy_part_number', 'deleted_at'); },
                'orderComponent' => function($query) { $query->withTrashed()->select('id', 'name', 'part_number', 'ipl_num', 'assy_part_number', 'deleted_at'); },
                'orderComponentAssembly' => function($query) { $query->select('id', 'component_id', 'assy_part_number', 'assy_ipl_num'); }
            ])
            ->get();
        $missingParts = $this->sortTdrsByDisplayedIpl($missingParts);
        $missingPartsCount = $missingParts->sum('qty');
        $hasMissingParts = $missingPartsCount > 0;

        $missingCodeId = $code ? $code->id : 7;
        $orderNewNecessaryId = $necessary ? $necessary->id : 2;

        $ordersParts = Tdr::where('workorder_id', $current_wo->id)
            ->where('codes_id', '!=', $missingCodeId)
            ->where('necessaries_id', $orderNewNecessaryId)
            ->with(['codes', 'component' => function($query) {
                $query->withTrashed()->select('id', 'name', 'part_number', 'ipl_num', 'deleted_at');
            }])
            ->get();
        $ordersParts = $this->sortTdrsByDisplayedIpl($ordersParts);

        $orderedPartsTdrs = Tdr::where('workorder_id', $current_wo->id)
            ->whereNotNull('component_id')
            ->where('codes_id', '!=', $missingCodeId)
            ->where('necessaries_id', $orderNewNecessaryId)
            ->get();
        $orderedPartsCount = $orderedPartsTdrs->sum('qty');
        $hasOrderedParts = $orderedPartsCount > 0;

        $ordersPartsNew = Tdr::where('workorder_id', $current_wo->id)
            ->where('codes_id', '!=', $missingCodeId)
            ->where('necessaries_id', $orderNewNecessaryId)
            ->whereNotNull('order_component_id')
            ->with(['codes', 'orderComponent' => function($query) {
                $query->withTrashed()->select('id', 'name', 'part_number', 'ipl_num', 'deleted_at');
            }, 'orderComponentAssembly' => function($query) {
                $query->select('id', 'component_id', 'assy_part_number', 'assy_ipl_num');
            }])
            ->get();
        $ordersPartsNew = $this->sortTdrsByDisplayedIpl($ordersPartsNew);

        $prl_parts = Tdr::where('workorder_id', $current_wo->id)
            ->where('necessaries_id', $orderNewNecessaryId)
            ->with([
                'component' => function($query) { $query->select('id', 'name', 'part_number', 'ipl_num'); },
                'orderComponent' => function($query) { $query->select('id', 'name', 'part_number', 'ipl_num'); },
                'orderComponentAssembly' => function($query) { $query->select('id', 'component_id', 'assy_part_number', 'assy_ipl_num'); }
            ])
            ->get();

        $planes = Plane::all();
        $builders = Builder::all();
        $instruction = Instruction::all();
        $dimensionFigures = \App\Models\ManualDimensionFigure::where('manual_id', $manual_id)
            ->orderBy('sort_order')->get(['id', 'title', 'figure_type']);
        $necessaries = Necessary::all();
        $unit_conditions = Condition::where('unit', true)->get();
        $component_conditions = Condition::where('unit', false)->get();
        $codes = Code::all();

        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->with([
                'component' => function($query) { $query->select('id', 'name', 'part_number', 'ipl_num'); },
                'conditions'
            ])
            ->get();

        $tdr_proc = TdrProcess::where('ec', 1)->get();

        $hasTransfers = Transfer::where('workorder_id', $current_wo->id)
            ->orWhere('workorder_source', $current_wo->id)
            ->exists();

        $transfersIncomingGroupsWithMultiple = collect();
        $transfersHasOutgoingGroup = false;
        if ($hasTransfers) {
            $incomingTransfersHeader = Transfer::with('workorderSource')->where('workorder_id', $current_wo->id)->get();
            $outgoingTransfersHeader = Transfer::where('workorder_source', $current_wo->id)->get();
            $transfersIncomingGroupsWithMultiple = $incomingTransfersHeader->groupBy('workorder_source')->filter(function ($group) {
                return $group->count() > 1;
            });
            $transfersHasOutgoingGroup = $outgoingTransfersHeader->count() > 1;
        }

        $hasExtraProcessRecords = ExtraProcess::where('workorder_id', $current_wo->id)->exists();
        $hasExtraProcessRecordsMoreThanOne = ExtraProcess::where('workorder_id', $current_wo->id)->count() > 1;

        $logCardInstructions = ['60M Iinspection', '96M Iinspection', 'Overhaul'];
        $showLogCardTab = $current_wo->instruction
            ? in_array($current_wo->instruction->name, $logCardInstructions, true)
            : false;

        return compact(
            'current_wo', 'tdrs', 'units', 'components', 'user', 'customers',
            'manuals', 'builders', 'planes', 'instruction', 'necessary',
            'necessaries', 'unit_conditions', 'component_conditions',
            'codes', 'conditions', 'missingParts', 'ordersParts', 'inspectsUnit',
            'processParts', 'ordersPartsNew', 'trainings', 'user_wo', 'manual_id', 'log_card', 'woBushing', 'hasBushings', 'bushingPrlCount', 'kitPrlCount', 'prl_parts', 'tdr_proc', 'hasTransfers',
            'transfersIncomingGroupsWithMultiple', 'transfersHasOutgoingGroup',
            'hasMissingParts', 'missingCondition', 'missingPartsCount', 'orderedPartsCount', 'hasOrderedParts', 'hasProcessFormTdrs',
            'stdFormCounts', 'spFormColumnsCount', 'bushingSpFormColumnsCount', 'rmFormRowsCount', 'tdrFormRowsCount',
            'hasExtraProcessRecords', 'hasExtraProcessRecordsMoreThanOne', 'showLogCardTab',
            'showDestructionCert', 'logCardTdrAccess',
            'allowedManualIds', 'canManageManualParts', 'canManageAllManualParts',
            'dimensionFigures'
        );
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {

        $current_tdr = Tdr::findOrFail($id);

        $manuals = Manual::all();
        $units = Unit::all();

        $workorder = Workorder::where('id', $current_tdr) ->get();


        $necessaries = Necessary::all();
        $conditions = Condition::all();
        $codes = Code::all();
        $components =Tdr::where('id', 'component_id')
            ->with('codes')
            ->with('component')
            ->with('necessaries')
            ->with('conditions')
            ->get();

//            $current_wo = $current_tdr->workorder->id;


        return view('admin.tdrs.edit', compact('current_tdr', 'workorder', 'units', 'necessaries', 'conditions', 'codes','components','manuals'));

    }

    /**
     * Return edit form partial for modal (AJAX).
     *
     * @param int $id TDR id
     * @return \Illuminate\View\View
     */
    public function editForm($id)
    {
        $current_tdr = Tdr::with(['workorder.unit', 'component'])->findOrFail($id);
        $codes = Code::all();
        $necessaries = Necessary::all();
        $manuals = Manual::all();
        $canReplaceTdrComponent = (bool) (Auth::user()?->isSystemAdmin() ?? false);
        $components = collect();

        if ($canReplaceTdrComponent && $current_tdr->workorder?->unit?->manual_id) {
            $components = $this->filterComponentsForUnit(
                Component::query()
                    ->where('manual_id', $current_tdr->workorder->unit->manual_id)
                    ->select('id', 'part_number', 'name', 'ipl_num', 'kit', 'kit_e', 'eff_code')
                    ->get(),
                $current_tdr->workorder
            );

            if ($current_tdr->component && ! $components->contains('id', $current_tdr->component->id)) {
                $components->push($current_tdr->component);
            }

            $components = $components
                ->sort(function (Component $left, Component $right): int {
                    $iplCompare = StdProcess::compareIplValues((string) $left->ipl_num, (string) $right->ipl_num);

                    if ($iplCompare !== 0) {
                        return $iplCompare;
                    }

                    return strnatcasecmp((string) $left->part_number, (string) $right->part_number);
                })
                ->values();
        }

        return view('admin.tdrs.partials.component-inspection-edit-form', compact(
            'current_tdr',
            'codes',
            'necessaries',
            'manuals',
            'canReplaceTdrComponent',
            'components'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // ÐÐ°Ñ…Ð¾Ð´Ð¸Ð¼ Ð·Ð°Ð¿Ð¸ÑÑŒ Tdr Ð¿Ð¾ ID
        $tdr = Tdr::findOrFail($id);
        $canReplaceTdrComponent = (bool) ($request->user()?->isSystemAdmin() ?? false);

        // Ð’Ð°Ð»Ð¸Ð´Ð°Ñ†Ð¸Ñ Ð²Ñ…Ð¾Ð´Ð½Ñ‹Ñ… Ð´Ð°Ð½Ð½Ñ‹Ñ…
        $rules = [
            'assy_serial_number' => 'nullable|string',
            'codes_id' => 'nullable|exists:codes,id',
            'necessaries_id' => 'nullable|exists:necessaries,id',
            'description' => 'nullable|string',
            'qty' => 'sometimes|nullable|integer|min:1|max:999999',
        ];

        if ($canReplaceTdrComponent) {
            $rules['component_id'] = 'nullable|exists:components,id';
            $rules['serial_number'] = 'nullable|string';
        }

        $validated = $request->validate($rules);

        if (! $canReplaceTdrComponent) {
            unset($validated['component_id'], $validated['serial_number']);
        }

        // ÐŸÑ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼, ÐµÑÐ»Ð¸ Ð²Ñ‹Ð±Ñ€Ð°Ð½ Ð½ÐµÐ¾Ð±Ñ…Ð¾Ð´Ð¸Ð¼Ñ‹Ð¹ Ð¿ÑƒÐ½ÐºÑ‚ "Order New"
        $necessary = Necessary::where('name', 'Order New')->first();

        if ($necessary && isset($validated['necessaries_id']) && (int) $validated['necessaries_id'] === (int) $necessary->id) {
            $validated['use_process_forms'] = false; // Ð˜ÑÐ¿Ñ€Ð°Ð²Ð»ÐµÐ½Ð¾ Ð¿Ñ€Ð¸ÑÐ²Ð°Ð¸Ð²Ð°Ð½Ð¸Ðµ
        }

        // ÐžÐ±Ð½Ð¾Ð²Ð»ÑÐµÐ¼ Ð·Ð°Ð¿Ð¸ÑÑŒ Tdr
        $tdr->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'redirect' => route('tdrs.show', ['id' => $request->workorder_id])]);
        }

        // ÐŸÐµÑ€ÐµÐ½Ð°Ð¿Ñ€Ð°Ð²Ð»ÑÐµÐ¼ Ð½Ð° ÑÑ‚Ñ€Ð°Ð½Ð¸Ñ†Ñƒ Ð¿Ñ€Ð¾ÑÐ¼Ð¾Ñ‚Ñ€Ð° Ñ ÑÐ¾Ð¾Ð±Ñ‰ÐµÐ½Ð¸ÐµÐ¼ Ð¾Ð± ÑƒÑÐ¿ÐµÑ…Ðµ
        return redirect()
            ->route('tdrs.show', ['id' => $request->workorder_id])
            ->with('success', 'TDR for Component updated successfully');
    }





    // ÐÐµ Ð·Ð°Ð±ÑƒÐ´ÑŒÑ‚Ðµ Ð´Ð¾Ð±Ð°Ð²Ð¸Ñ‚ÑŒ use League\Csv\Reader; Ð²Ð²ÐµÑ€Ñ…Ñƒ Ñ„Ð°Ð¹Ð»Ð°!






    /**
     * Ð Ð°Ð·Ð±Ð¸ÐµÐ½Ð¸Ðµ ÑÑ‚Ñ€Ð¾Ðº Stress/CAD std-Ñ„Ð¾Ñ€Ð¼ Ð¿Ð¾ ÑÑ‚Ñ€Ð°Ð½Ð¸Ñ†Ð°Ð¼ Ð½Ð° ÑÐµÑ€Ð²ÐµÑ€Ðµ.
     * Ð­Ð»ÐµÐ¼ÐµÐ½Ñ‚Ñ‹: kind = manual | data | empty.
     *
     * @param  array<int, object>  $components  Ð¾Ð±ÑŠÐµÐºÑ‚Ñ‹ Ñ Ð¿Ð¾Ð»ÐµÐ¼ manual (ÐºÐ°Ðº Ð² stress/cad)
     * @return array<int, array<int, array<string, mixed>>>
     */




    /**
     * ÐÐ°Ñ…Ð¾Ð´Ð¸Ñ‚ Ð¸Ð½Ð´ÐµÐºÑ ÐºÐ¾Ð»Ð¾Ð½ÐºÐ¸ Ð¿Ð¾ Ð²Ð¾Ð·Ð¼Ð¾Ð¶Ð½Ñ‹Ð¼ Ð½Ð°Ð·Ð²Ð°Ð½Ð¸ÑÐ¼
     */















    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        // Ð›Ð¾Ð³Ð¸Ñ€ÑƒÐµÐ¼ Ð½Ð°Ñ‡Ð°Ð»Ð¾ Ð¼ÐµÑ‚Ð¾Ð´Ð°
        // Log::info('ÐÐ°Ñ‡Ð°Ð»Ð¾ ÑƒÐ´Ð°Ð»ÐµÐ½Ð¸Ñ Ð·Ð°Ð¿Ð¸ÑÐ¸ TDR Ñ ID: ' . $id);

        // ÐÐ°Ð¹Ñ‚Ð¸ Ð·Ð°Ð¿Ð¸ÑÑŒ Tdr Ð¿Ð¾ ID
        $tdr = Tdr::findOrFail($id);

        // Ð—Ð°Ð¿Ð¾Ð¼Ð½Ð¸Ñ‚ÑŒ workorder_id Ð¸ codes_id Ð´Ð»Ñ Ð´Ð°Ð»ÑŒÐ½ÐµÐ¹ÑˆÐµÐ³Ð¾ Ð¸ÑÐ¿Ð¾Ð»ÑŒÐ·Ð¾Ð²Ð°Ð½Ð¸Ñ
        $workorderId = $tdr->workorder_id;
        $tdrCodesId = $tdr->codes_id;

        // Ð›Ð¾Ð³Ð¸Ñ€ÑƒÐµÐ¼ workorder_id
        // Log::info('Workorder ID: ' . $workorderId);

        // Ð£Ð´Ð°Ð»Ð¸Ñ‚ÑŒ ÑÐ²ÑÐ·Ð°Ð½Ð½Ñ‹Ðµ Ð·Ð°Ð¿Ð¸ÑÐ¸ Ð¸Ð· tdr_processes
        TdrProcess::where('tdrs_id', $id)
            ->get()
            ->each
            ->delete();
        // Log::info('Ð£Ð´Ð°Ð»ÐµÐ½Ñ‹ ÑÐ²ÑÐ·Ð°Ð½Ð½Ñ‹Ðµ Ð¿Ñ€Ð¾Ñ†ÐµÑÑÑ‹ Ð´Ð»Ñ TDR Ñ ID: ' . $id);

        // ÐžÐ¿Ñ€ÐµÐ´ÐµÐ»ÑÐµÐ¼ component_id Ð´Ð»Ñ Ð¿Ð¾Ð¸ÑÐºÐ° transfers
        $componentId = $tdr->order_component_id ?? $tdr->component_id;

        // Ð£Ð´Ð°Ð»Ð¸Ñ‚ÑŒ ÑÐ²ÑÐ·Ð°Ð½Ð½Ñ‹Ðµ Ð·Ð°Ð¿Ð¸ÑÐ¸ Ð¸Ð· transfers Ð¸ ÐºÐ»Ð¾Ð½Ð¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ñ‹Ðµ TDR Ð² WO-Ð¸ÑÑ‚Ð¾Ñ‡Ð½Ð¸ÐºÐ°Ñ… (ÐµÑÐ»Ð¸ ÐµÑÑ‚ÑŒ)
        if ($componentId) {
            // ÐÐ°Ñ…Ð¾Ð´Ð¸Ð¼ Ð²ÑÐµ transfers, ÑÐ²ÑÐ·Ð°Ð½Ð½Ñ‹Ðµ Ñ ÑÑ‚Ð¸Ð¼ TDR
            $transfers = Transfer::where('workorder_id', $workorderId)
                ->where('component_id', $componentId)
                ->get();

            $deletedTransfers = 0;
            $deletedClonedTdrs = 0;

            // ÐšÐ¾Ð´ Missing (Ð´Ð»Ñ ÑƒÐ¿Ñ€Ð°Ð²Ð»ÐµÐ½Ð¸Ñ Ñ„Ð»Ð°Ð³Ð¾Ð¼ part_missing Ð² workorders Ð¸ÑÑ‚Ð¾Ñ‡Ð½Ð¸ÐºÐ¾Ð²)
            $missingCode = Code::where('name', 'Missing')->first();

            foreach ($transfers as $transfer) {
                // Ð”Ð»Ñ ÐºÐ°Ð¶Ð´Ð¾Ð³Ð¾ transfer Ð¿Ñ‹Ñ‚Ð°ÐµÐ¼ÑÑ ÑƒÐ´Ð°Ð»Ð¸Ñ‚ÑŒ "ÐºÐ»Ð¾Ð½Ð¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ñ‹Ð¹" TDR Ð² workorder_source
                if ($transfer->workorder_source) {
                    $cloned = Tdr::where('workorder_id', $transfer->workorder_source)
                        ->where(function ($q) use ($tdr) {
                            // ÐŸÑ‹Ñ‚Ð°ÐµÐ¼ÑÑ Ð½Ð°Ð¹Ñ‚Ð¸ Ð·Ð°Ð¿Ð¸ÑÑŒ, Ð¼Ð°ÐºÑÐ¸Ð¼Ð°Ð»ÑŒÐ½Ð¾ Ð¿Ð¾Ñ…Ð¾Ð¶ÑƒÑŽ Ð½Ð° Ð¸ÑÑ…Ð¾Ð´Ð½Ñ‹Ð¹ TDR
                            $q->where('component_id', $tdr->component_id)
                                ->where('order_component_id', $tdr->order_component_id)
                                ->where('codes_id', $tdr->codes_id)
                                ->where('conditions_id', $tdr->conditions_id)
                                ->where('necessaries_id', $tdr->necessaries_id)
                                ->where('qty', $tdr->qty)
                                ->where('serial_number', $tdr->serial_number);
                        })
                        ->where('id', '!=', $tdr->id)
                        ->orderByDesc('id') // Ð±ÐµÑ€Ñ‘Ð¼ ÑÐ°Ð¼ÑƒÑŽ "ÑÐ²ÐµÐ¶ÑƒÑŽ" ÐºÐ°Ðº Ð²ÐµÑ€Ð¾ÑÑ‚Ð½Ñ‹Ð¹ ÐºÐ»Ð¾Ð½
                        ->first();

                    if ($cloned) {
                        $cloned->delete();
                        $deletedClonedTdrs++;
                        // Log::info('Ð£Ð´Ð°Ð»Ñ‘Ð½ ÐºÐ»Ð¾Ð½Ð¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ñ‹Ð¹ TDR Ñ ID: ' . $cloned->id . ' Ð² WO-Ð¸ÑÑ‚Ð¾Ñ‡Ð½Ð¸ÐºÐµ: ' . $transfer->workorder_source);

                        // Ð•ÑÐ»Ð¸ ÑÑ‚Ð¾ Ð±Ñ‹Ð»Ð° Ð·Ð°Ð¿Ð¸ÑÑŒ Ñ ÐºÐ¾Ð´Ð¾Ð¼ Missing, Ð²Ð¾Ð·Ð¼Ð¾Ð¶Ð½Ð¾ Ð½ÑƒÐ¶Ð½Ð¾ Ð¾Ð±Ð½Ð¾Ð²Ð¸Ñ‚ÑŒ part_missing Ð´Ð»Ñ WO-Ð¸ÑÑ‚Ð¾Ñ‡Ð½Ð¸ÐºÐ°
                        if ($missingCode && $tdr->codes_id === $missingCode->id) {
                            $remainingMissingForSource = Tdr::where('workorder_id', $transfer->workorder_source)
                                ->where('codes_id', $missingCode->id)
                                ->count();

                            if ($remainingMissingForSource === 0) {
                                $sourceWo = Workorder::find($transfer->workorder_source);
                                if ($sourceWo && $sourceWo->part_missing) {
                                    $sourceWo->part_missing = false;
                                    $sourceWo->save();
                                    // Log::info('Ð¤Ð»Ð°Ð³ part_missing Ð´Ð»Ñ WO-Ð¸ÑÑ‚Ð¾Ñ‡Ð½Ð¸ÐºÐ° ' . $transfer->workorder_source . ' Ð¾Ð±Ð½Ð¾Ð²Ð»Ñ‘Ð½ Ð½Ð° false (Ð¿Ð¾ÑÐ»Ðµ ÑƒÐ´Ð°Ð»ÐµÐ½Ð¸Ñ ÐºÐ»Ð¾Ð½Ð¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ð¾Ð³Ð¾ Missing TDR).');
                                }

                            }
                        }
                    }
                }

                $transfer->delete();
                $deletedTransfers++;
            }

            if ($deletedTransfers > 0) {
                // Log::info('Ð£Ð´Ð°Ð»ÐµÐ½Ñ‹ ÑÐ²ÑÐ·Ð°Ð½Ð½Ñ‹Ðµ transfers Ð´Ð»Ñ TDR Ñ ID: ' . $id . ' (ÑƒÐ´Ð°Ð»ÐµÐ½Ð¾ transfers: ' . $deletedTransfers . ', ÑƒÐ´Ð°Ð»ÐµÐ½Ð¾ ÐºÐ»Ð¾Ð½Ð¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ñ‹Ñ… TDR: ' . $deletedClonedTdrs . ')');
            }
        }

        // Ð£Ð´Ð°Ð»Ð¸Ñ‚ÑŒ Ð·Ð°Ð¿Ð¸ÑÑŒ Tdr
        $tdr->delete();
        // Log::info('Ð—Ð°Ð¿Ð¸ÑÑŒ Tdr Ñ ID: ' . $id . ' Ð±Ñ‹Ð»Ð° ÑƒÐ´Ð°Ð»ÐµÐ½Ð°.');



        // ÐÐ°Ð¹Ñ‚Ð¸ necessary Ñ Ð¸Ð¼ÐµÐ½ÐµÐ¼ 'Missing'
        $necessary = Necessary::where('name', 'Order New')->first();
        // Log::info('ÐÐ°Ð¹Ð´ÐµÐ½ necessary Ñ Ð¸Ð¼ÐµÐ½ÐµÐ¼ "Order New": ' . ($necessary ? 'Ð”Ð°' : 'ÐÐµÑ‚'));

        if ($necessary) {
            // ÐŸÑ€Ð¾Ð²ÐµÑ€Ð¸Ñ‚ÑŒ, ÐµÑÐ»Ð¸ ÑÑ‚Ð¾ Ð¿Ð¾ÑÐ»ÐµÐ´Ð½ÑÑ Ð·Ð°Ð¿Ð¸ÑÑŒ Ñ necessaries_id = $necessary->id
            $remainingPartsWithNecessary = Tdr::where('workorder_id', $workorderId)
                ->where('necessaries_id', $necessary->id)
                ->count();
            // Log::info('ÐžÑÑ‚Ð°Ð²ÑˆÐ¸ÐµÑÑ Ð·Ð°Ð¿Ð¸ÑÐ¸ Ñ ÐºÐ¾Ð´Ð¾Ð¼ Order New Ð´Ð»Ñ workorder_id ' . $workorderId . ': ' .
            //     $remainingPartsWithNecessary);
            if ($remainingPartsWithNecessary == 0) {
                // ÐžÐ±Ð½Ð¾Ð²Ð»ÑÐµÐ¼ Ð¿Ð¾Ð»Ðµ part_missing Ð² workorder
                $workorder = Workorder::find($workorderId);
                if ($workorder && $workorder->new_parts == true) {
                    // ÐœÐµÐ½ÑÐµÐ¼ Ð½Ð° false, ÐµÑÐ»Ð¸ part_missing Ñ€Ð°Ð²Ð½Ð¾ true
                    $workorder->new_parts = false;
                    $workorder->save();
                    // Log::info('ÐŸÐ¾Ð»Ðµ new_parts Ð´Ð»Ñ workorder_id ' . $workorderId . ' Ð¾Ð±Ð½Ð¾Ð²Ð»ÐµÐ½Ð¾ Ð½Ð° false');
                } else {
                    // Log::info('ÐŸÐ¾Ð»Ðµ new_parts Ð´Ð»Ñ workorder_id ' . $workorderId . ' ÑƒÐ¶Ðµ false Ð¸Ð»Ð¸ workorder Ð½Ðµ Ð½Ð°Ð¹Ð´ÐµÐ½.');
                }

            }
        }

        // ÐÐ°Ð¹Ñ‚Ð¸ ÐºÐ¾Ð´ Ñ Ð¸Ð¼ÐµÐ½ÐµÐ¼ 'Missing'
        $code = Code::where('name', 'Missing')->first();
        // Log::info('ÐÐ°Ð¹Ð´ÐµÐ½ ÐºÐ¾Ð´ Ñ Ð¸Ð¼ÐµÐ½ÐµÐ¼ "Missing": ' . ($code ? 'Ð”Ð°' : 'ÐÐµÑ‚'));

        // ÐŸÑ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼, Ð±Ñ‹Ð»Ð° Ð»Ð¸ ÑƒÐ´Ð°Ð»ÑÐµÐ¼Ð°Ñ Ð·Ð°Ð¿Ð¸ÑÑŒ Ñ ÐºÐ¾Ð´Ð¾Ð¼ Missing
        $wasMissingRecord = $code && $tdrCodesId === $code->id;
        // Log::info('Ð£Ð´Ð°Ð»ÑÐµÐ¼Ð°Ñ Ð·Ð°Ð¿Ð¸ÑÑŒ Ð±Ñ‹Ð»Ð° Ñ ÐºÐ¾Ð´Ð¾Ð¼ Missing: ' . ($wasMissingRecord ? 'Ð”Ð°' : 'ÐÐµÑ‚') . ' (codes_id: ' . $tdrCodesId . ')');

        if ($code) {
            // ÐŸÑ€Ð¾Ð²ÐµÑ€Ð¸Ñ‚ÑŒ, ÐµÑÐ»Ð¸ ÑÑ‚Ð¾ Ð¿Ð¾ÑÐ»ÐµÐ´Ð½ÑÑ Ð·Ð°Ð¿Ð¸ÑÑŒ Ñ codes_id = $code->id
            // Ð—Ð°Ð¿Ð¸ÑÑŒ ÑƒÐ¶Ðµ ÑƒÐ´Ð°Ð»ÐµÐ½Ð° Ð²Ñ‹ÑˆÐµ, Ð¿Ð¾ÑÑ‚Ð¾Ð¼Ñƒ Ð¿Ñ€Ð¾Ð²ÐµÑ€ÑÐµÐ¼ Ð¾ÑÑ‚Ð°Ð²ÑˆÐ¸ÐµÑÑ
            $remainingPartsWithCodes7 = Tdr::where('workorder_id', $workorderId)
                ->where('codes_id', $code->id)
                ->count();

            // Log::info('ÐžÑÑ‚Ð°Ð²ÑˆÐ¸ÐµÑÑ Ð·Ð°Ð¿Ð¸ÑÐ¸ Ñ ÐºÐ¾Ð´Ð¾Ð¼ Missing Ð´Ð»Ñ workorder_id ' . $workorderId . ': ' . $remainingPartsWithCodes7);

            // Ð•ÑÐ»Ð¸ ÑÑ‚Ð¾ Ð±Ñ‹Ð»Ð° Ð¿Ð¾ÑÐ»ÐµÐ´Ð½ÑÑ Ð·Ð°Ð¿Ð¸ÑÑŒ Ñ Ñ‚Ð°ÐºÐ¸Ð¼ ÐºÐ¾Ð´Ð¾Ð¼, Ð¾Ð±Ð½Ð¾Ð²Ð»ÑÐµÐ¼ Ð¿Ð¾Ð»Ðµ part_missing Ð² workorder
            if ($remainingPartsWithCodes7 == 0) {
                // ÐžÐ±Ð½Ð¾Ð²Ð»ÑÐµÐ¼ Ð¿Ð¾Ð»Ðµ part_missing Ð² workorder
                $workorder = Workorder::find($workorderId);

                if ($workorder && $workorder->part_missing === true) {
                    // ÐœÐµÐ½ÑÐµÐ¼ Ð½Ð° false, ÐµÑÐ»Ð¸ part_missing Ñ€Ð°Ð²Ð½Ð¾ true
                    $workorder->part_missing = false;
                    $workorder->save();
                    // Log::info('ÐŸÐ¾Ð»Ðµ part_missing Ð´Ð»Ñ workorder_id ' . $workorderId . ' Ð¾Ð±Ð½Ð¾Ð²Ð»ÐµÐ½Ð¾ Ð½Ð° false');
                } else {
                    // Log::info('ÐŸÐ¾Ð»Ðµ part_missing Ð´Ð»Ñ workorder_id ' . $workorderId . ' ÑƒÐ¶Ðµ false Ð¸Ð»Ð¸ workorder Ð½Ðµ Ð½Ð°Ð¹Ð´ÐµÐ½.');
                }

                // Ð£Ð´Ð°Ð»ÑÐµÐ¼ ÑÑ‚Ð°Ñ€Ñ‹Ðµ Ð¿ÑƒÑÑ‚Ñ‹Ðµ Ð·Ð°Ð¿Ð¸ÑÐ¸ Ñ missingCondition (ÑÐ¾Ð·Ð´Ð°Ð½Ð½Ñ‹Ðµ Ð´Ð¾ Ð¸Ð·Ð¼ÐµÐ½ÐµÐ½Ð¸Ð¹)
                $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
                if ($missingCondition) {
                    $emptyMissingRecords = Tdr::where('workorder_id', $workorderId)
                        ->unitInspections()
                        ->where('conditions_id', $missingCondition->id)
                        ->whereNull('codes_id')
                        ->get();

                    foreach ($emptyMissingRecords as $emptyRecord) {
                        $emptyRecord->delete();
                        // Log::info('Ð£Ð´Ð°Ð»ÐµÐ½Ð° ÑÑ‚Ð°Ñ€Ð°Ñ Ð¿ÑƒÑÑ‚Ð°Ñ Ð·Ð°Ð¿Ð¸ÑÑŒ Ñ condition_id ' . $missingCondition->id . ' Ð´Ð»Ñ workorder_id ' . $workorderId);
                    }
                }
            }
        }

        return redirect()->route('tdrs.show', ['id' => $workorderId])
            ->with('success', 'Record deleted successfully.');
    }

    /**
     * Ð Ð°ÑÑ‡ÐµÑ‚ ÑÑƒÐ¼Ð¼ NDT Ð¸Ð· Ð´Ð°Ð½Ð½Ñ‹Ñ… CSV Ð´Ð»Ñ Ñ€Ð°Ð±Ð¾Ñ‡ÐµÐ³Ð¾ Ð·Ð°ÐºÐ°Ð·Ð°
     * Ð¢Ð° Ð¶Ðµ Ð»Ð¾Ð³Ð¸ÐºÐ°, Ñ‡Ñ‚Ð¾ Ð¸ ndtStd: min(QTY Ð¸Ð· ÑÑ‚Ñ€Ð¾ÐºÐ¸ STD, units_assy), excludedQty, tdrQty
     *
     * @param int $workorder_id ID Ñ€Ð°Ð±Ð¾Ñ‡ÐµÐ³Ð¾ Ð·Ð°ÐºÐ°Ð·Ð°
     * @return array{total: int, mpi: int, fpi: int} ÐœÐ°ÑÑÐ¸Ð² Ñ Ð¾Ð±Ñ‰Ð¸Ð¼Ð¸ ÑÑƒÐ¼Ð¼Ð°Ð¼Ð¸,
     *     MPI Ð¸ FPI
     */












    /**
     * ÐŸÑ€Ð¸Ð²Ð¾Ð´Ð¸Ñ‚ ÑÑ‚Ñ€Ð¾ÐºÑƒ Ðº ÑÐ¾Ð¿Ð¾ÑÑ‚Ð°Ð²Ð¸Ð¼Ð¾Ð¼Ñƒ Ð²Ð¸Ð´Ñƒ: Ð·Ð°Ð¼ÐµÐ½ÑÐµÑ‚ ÐºÐ¸Ñ€Ð¸Ð»Ð»Ð¸Ñ†Ñƒ Ð½Ð° Ð»Ð°Ñ‚Ð¸Ð½Ð¸Ñ†Ñƒ,
     * Ð¿ÐµÑ€ÐµÐ²Ð¾Ð´Ð¸Ñ‚ Ð² Ð²ÐµÑ€Ñ…Ð½Ð¸Ð¹ Ñ€ÐµÐ³Ð¸ÑÑ‚Ñ€ Ð¸ ÑƒÐ´Ð°Ð»ÑÐµÑ‚ Ð²ÑÐµ Ð½Ðµ Ð±ÑƒÐºÐ²ÐµÐ½Ð½Ð¾-Ñ†Ð¸Ñ„Ñ€Ð¾Ð²Ñ‹Ðµ ÑÐ¸Ð¼Ð²Ð¾Ð»Ñ‹
     */

    /**
     * ÐŸÑ€Ð¾Ð²ÐµÑ€ÑÐµÑ‚, Ð½ÑƒÐ¶Ð½Ð¾ Ð»Ð¸ Ð¿Ñ€Ð¾Ð¿ÑƒÑÑ‚Ð¸Ñ‚ÑŒ ÑÐ»ÐµÐ¼ÐµÐ½Ñ‚ Ð½Ð° Ð¾ÑÐ½Ð¾Ð²Ðµ ÑÑƒÑ‰ÐµÑÑ‚Ð²ÑƒÑŽÑ‰Ð¸Ñ… IPL Ð½Ð¾Ð¼ÐµÑ€Ð¾Ð²
     */

    /**
     * ÐžÐ±Ð½Ð¾Ð²Ð»ÐµÐ½Ð¸Ðµ po_num Ð¸Ð»Ð¸ received Ð´Ð»Ñ Ð·Ð°Ð¿Ð¸ÑÐ¸ Tdr
     */
    public function updatePartField(Request $request, $id)
    {
        $request->validate([
            'field' => 'required|in:po_num,received',
            'value' => 'nullable|string'
        ]);

        $tdr = Tdr::findOrFail($id);

        $field = $request->input('field');
        $value = $request->input('value');

        // Ð•ÑÐ»Ð¸ Ð¿Ð¾Ð»Ðµ received Ð¸ Ð·Ð½Ð°Ñ‡ÐµÐ½Ð¸Ðµ Ð¿ÑƒÑÑ‚Ð¾Ðµ, ÑƒÑÑ‚Ð°Ð½Ð°Ð²Ð»Ð¸Ð²Ð°ÐµÐ¼ null
        if ($field === 'received' && empty($value)) {
            $tdr->received = null;
        } else {
            $tdr->$field = $value;
        }

        $tdr->save();

        return response()->json([
            'success' => true,
            'message' => 'Field updated successfully'
        ]);
    }

    /**
     * Ð§ÐµÑ‚Ñ‹Ñ€Ðµ Ð¿Ñ€Ð¾Ñ†ÐµÑÑÐ° STD List (Ð¸Ð¼ÐµÐ½Ð° Ð¸Ð· WorkorderStdListProcessesService) Ð² Ð²Ñ‹Ð±Ð¾Ñ€ÐºÐµ â€”
     * Ñ‚Ð¾Ð»ÑŒÐºÐ¾ ÐµÑÐ»Ð¸ Ð²Ð¾Ñ€ÐºÐ¾Ñ€Ð´ÐµÑ€ Overhaul (ÐºÐ°Ðº main / Paint).
     */
    private function applyStdListProcessesVisibilityForWorkorder(Workorder $currentWo, EloquentBuilder $query): void
    {
        $overhaulId = Instruction::overhaulId();
        if ($overhaulId !== null && (int) $currentWo->instruction_id === (int) $overhaulId) {
            return;
        }

        $stdNames = array_values(WorkorderStdListProcessesService::NAME_BY_KEY);
        $query->where(function ($q) use ($stdNames) {
            $q->whereDoesntHave('processName')
                ->orWhereHas('processName', function ($pn) use ($stdNames) {
                    $pn->whereNotIn('name', $stdNames);
                });
        });
    }

    private function sortTdrsByDisplayedIpl($tdrs)
    {
        return $tdrs
            ->sort(function (Tdr $left, Tdr $right): int {
                $iplCompare = StdProcess::compareIplValues(
                    $this->displayIplForTdrModalRow($left),
                    $this->displayIplForTdrModalRow($right)
                );

                if ($iplCompare !== 0) {
                    return $iplCompare;
                }

                $partCompare = strnatcasecmp(
                    $this->displayPartNumberForTdrModalRow($left),
                    $this->displayPartNumberForTdrModalRow($right)
                );

                if ($partCompare !== 0) {
                    return $partCompare;
                }

                return ((int) $left->id) <=> ((int) $right->id);
            })
            ->values();
    }

    private function displayIplForTdrModalRow(Tdr $tdr): string
    {
        return trim((string) (
            $tdr->orderComponentAssembly?->assy_ipl_num
            ?? $tdr->orderComponent?->ipl_num
            ?? $tdr->component?->ipl_num
            ?? ''
        ));
    }

    private function displayPartNumberForTdrModalRow(Tdr $tdr): string
    {
        return trim((string) (
            $tdr->orderComponentAssembly?->assy_part_number
            ?? $tdr->orderComponent?->part_number
            ?? $tdr->component?->part_number
            ?? ''
        ));
    }

    private function countStdFormQty(Workorder $workorder, string $std): int
    {
        return $this->sumStdRowsQty(
            StdProcess::snapshotComponentsForWorkorder($workorder, $std)
        );
    }

    private function countPaintStdFormQty(Workorder $workorder): int
    {
        $excludedIplNums = [];
        $missingCode = Code::where('name', 'Missing')->first();
        $repairCode = Code::where('name', 'Repair')->first();
        $orderNewNecessary = Necessary::where('name', 'Order New')->first();

        $excludedTdrQuery = Tdr::where('workorder_id', $workorder->id)
            ->whereNotNull('component_id')
            ->with('component:id,ipl_num');

        if ($missingCode || $repairCode || $orderNewNecessary) {
            $excludedTdrQuery->where(function ($query) use ($missingCode, $repairCode, $orderNewNecessary): void {
                if ($missingCode) {
                    $query->orWhere('codes_id', $missingCode->id);
                }
                if ($repairCode) {
                    $query->orWhere('codes_id', $repairCode->id);
                }
                if ($orderNewNecessary) {
                    $query->orWhere('necessaries_id', $orderNewNecessary->id);
                }
            });

            foreach ($excludedTdrQuery->get() as $tdr) {
                $ipl = (string) ($tdr->component->ipl_num ?? '');
                $normalizedIpl = $this->normalizeIplNum($ipl);
                if ($normalizedIpl !== '') {
                    $excludedIplNums[$normalizedIpl] = true;
                }
            }
        }

        $rows = collect(StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_PAINT))
            ->filter(function (array $row) use ($excludedIplNums): bool {
                $normalizedIpl = $this->normalizeIplNum((string) ($row['ipl_num'] ?? ''));

                return ! isset($excludedIplNums[$normalizedIpl]);
            })
            ->all();

        return $this->sumStdRowsQty($rows);
    }

    private function sumStdRowsQty(array $rows): int
    {
        $singleRowsQty = 0;
        $groupedQtyByKey = [];

        foreach ($rows as $row) {
            $qty = max(1, (int) ($row['qty'] ?? 1));
            $groupKey = $this->stdSuffixVariantCountGroupKey($row);

            if ($groupKey === null) {
                $singleRowsQty += $qty;
                continue;
            }

            if (! array_key_exists($groupKey, $groupedQtyByKey)) {
                $groupedQtyByKey[$groupKey] = $qty;
            }
        }

        return $singleRowsQty + array_sum($groupedQtyByKey);
    }

    private function stdSuffixVariantCountGroupKey(array $row): ?string
    {
        $ipl = trim((string) ($row['ipl_num'] ?? ''));

        if (! preg_match('/^(\d+[A-Za-z]*-\d+)([A-Za-z]+)$/', $ipl, $matches)) {
            return null;
        }

        return implode('|', [
            trim((string) ($row['manual'] ?? '')),
            strtoupper((string) ($matches[1] ?? '')),
            trim((string) ($row['process'] ?? '')),
        ]);
    }

    private function countKitPrlGroups($components): int
    {
        return collect($components)
            ->groupBy(fn ($component): string => $this->kitNumericIplGroupKey((string) ($component->ipl_num ?? ''), (int) ($component->id ?? 0)))
            ->count();
    }

    private function kitNumericIplGroupKey(string $ipl, ?int $componentId = null): string
    {
        $normalized = strtoupper(trim($ipl));
        $withoutSuffix = preg_replace('/([0-9])[^0-9-]*$/', '$1', $normalized) ?? $normalized;
        $digitsOnly = preg_replace('/[^0-9]+/', '-', $withoutSuffix) ?? $withoutSuffix;
        $digitsOnly = trim($digitsOnly, '-');

        if ($digitsOnly !== '') {
            return $digitsOnly;
        }

        return $normalized !== '' ? $normalized : 'component-' . (string) ($componentId ?? 0);
    }

    private function countSpecProcessFormColumns(Workorder $workorder): int
    {
        $quarantineProcessNameId = ProcessName::where('name', 'Quarantine')->value('id');

        return Tdr::where('workorder_id', $workorder->id)
            ->where('use_process_forms', true)
            ->with('tdrProcesses:id,tdrs_id,process_names_id')
            ->get()
            ->sum(function (Tdr $tdr) use ($quarantineProcessNameId): int {
                if ($quarantineProcessNameId && $tdr->tdrProcesses->contains('process_names_id', (int) $quarantineProcessNameId)) {
                    return 2;
                }

                return 1;
            });
    }

    private function countBushingSpecProcessColumns(?WoBushing $woBushing): int
    {
        if (! $woBushing) {
            return 0;
        }

        $bushData = app(WoBushingRelationalSync::class)->resolveBushDataForViews($woBushing);
        $groups = [];
        $processOrder = [
            'Machining' => 'machining',
            'Bake (Stress relief)' => 'stress_relief',
            'NDT' => 'ndt',
            'Passivation' => 'passivation',
            'CAD' => 'cad',
            'Anodizing' => 'anodizing',
            'Xylan' => 'xylan',
        ];

        foreach ($bushData as $bushItem) {
            if (! isset($bushItem['bushing'], $bushItem['processes'])) {
                continue;
            }

            if (! Component::whereKey((int) $bushItem['bushing'])->exists()) {
                continue;
            }

            $activeProcesses = [];
            $processes = $bushItem['processes'];
            foreach ($processOrder as $processType => $processKey) {
                if ($processKey === 'ndt') {
                    $ndtValue = $processes['ndt'] ?? null;
                    if (is_array($ndtValue) && $ndtValue !== []) {
                        $activeProcesses[] = $processType;
                    }
                    continue;
                }

                if (isset($processes[$processKey]) && $processes[$processKey] !== null && $processes[$processKey] !== '') {
                    $activeProcesses[] = $processType;
                }
            }

            $groups[implode('|', $activeProcesses)] = true;
        }

        return count($groups);
    }

    private function countRmFormRows(Workorder $workorder): int
    {
        $savedData = $workorder->rm_report ? json_decode($workorder->rm_report, true) : null;
        $recordIds = collect($savedData['rm_records'] ?? [])
            ->pluck('id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($recordIds === []) {
            return 0;
        }

        return \App\Models\RmReport::whereIn('id', $recordIds)->count();
    }

    private function countTdrFormRows(Workorder $workorder): int
    {
        $necessary = Necessary::where('name', 'Order New')->first();
        $missingCode = Code::where('name', 'Missing')->first();
        $missingConditionName = 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST';

        $unitInspections = WorkorderUnitInspection::query()
            ->with('condition:id,name')
            ->where('workorder_id', $workorder->id)
            ->where(function ($query): void {
                $query->where('use_tdr', true)
                    ->orWhereNull('use_tdr');
            })
            ->orderBy('id')
            ->get();

        $unitInspectionRows = $unitInspections
            ->filter(function (WorkorderUnitInspection $inspection) use ($missingConditionName): bool {
                $conditionName = trim((string) ($inspection->condition->name ?? ''));
                $notes = trim((string) ($inspection->notes ?? ''));

                if (strcasecmp($conditionName, $missingConditionName) === 0) {
                    return false;
                }

                return $conditionName !== '' || $notes !== '';
            })
            ->count();

        $unitInspectionSourceTdrIds = $unitInspections
            ->pluck('source_tdr_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $nullComponentRows = 0;
        $groupedByConditions = [];
        $necessaryComponentRows = 0;
        $hasMissingComponents = false;

        $tdrs = Tdr::where('workorder_id', $workorder->id)
            ->with(['component', 'conditions', 'necessaries', 'codes'])
            ->get();

        foreach ($tdrs as $tdr) {
            if ($missingCode && (int) $tdr->codes_id === (int) $missingCode->id) {
                $hasMissingComponents = true;
                continue;
            }

            if ($tdr->component_id === null) {
                if (in_array((int) $tdr->id, $unitInspectionSourceTdrIds, true)) {
                    continue;
                }

                $condition = $tdr->conditions;
                if (! $condition) {
                    continue;
                }

                $description = trim((string) $tdr->description);
                $isNoteCondition = preg_match('/^note\s+\d+$/i', (string) $condition->name);
                if ($isNoteCondition && $description === '') {
                    continue;
                }

                $nullComponentRows++;
                continue;
            }

            if ($necessary && (int) $tdr->necessaries_id === (int) $necessary->id) {
                $component = $tdr->component;
                $condition = $tdr->conditions;
                if (! $component || ! $condition) {
                    continue;
                }

                $componentString = sprintf(
                    '(%s%s)<b> %s </b>%s',
                    strtoupper((string) $component->ipl_num),
                    (int) $tdr->qty === 1 ? '' : ', ' . $tdr->qty . 'pcs',
                    strtoupper((string) $component->name),
                    trim((string) $tdr->description) !== '' ? ': ( ' . strtoupper((string) $tdr->description) . ')' : ' '
                );
                $conditionName = (string) $condition->name;
                $groupedByConditions[$conditionName] ??= [];
                $lastKey = count($groupedByConditions[$conditionName]) - 1;
                $lastString = $lastKey >= 0 ? $groupedByConditions[$conditionName][$lastKey] : '';

                if (strlen($lastString . ', ' . $componentString) <= 120) {
                    if ($lastKey >= 0) {
                        $groupedByConditions[$conditionName][$lastKey] .= ', ' . $componentString;
                    } else {
                        $groupedByConditions[$conditionName][] = $conditionName . ' (scrap): ' . $componentString;
                    }
                } else {
                    $groupedByConditions[$conditionName][] = $conditionName . ' (scrap): ' . $componentString;
                }
                continue;
            }

            if ($tdr->component && $tdr->necessaries && $tdr->codes) {
                $necessaryComponentRows++;
            }
        }

        $groupedRows = collect($groupedByConditions)->sum(fn (array $rows): int => count($rows));

        return $unitInspectionRows
            + $nullComponentRows
            + $groupedRows
            + $necessaryComponentRows
            + ($hasMissingComponents ? 1 : 0);
    }

    private function filterComponentsForUnit($components, Workorder $workorder)
    {
        $resolver = app(ManualIplBranchRuleResolver::class);
        $manualId = (int) ($workorder->unit->manual_id ?? 0);
        $unitEff = (string) ($workorder->unit->eff_code ?? '');
        $effCodedBaseIpls = $this->effCodedBaseIplsForComponents($components);

        return $components
            ->filter(function (Component $component) use ($resolver, $workorder, $manualId, $unitEff, $effCodedBaseIpls): bool {
                if (! $resolver->allowsComponentForUnit(
                    $workorder->unit,
                    (string) ($component->ipl_num ?? ''),
                    $manualId
                )) {
                    return false;
                }

                $componentEff = (string) ($component->eff_code ?? '');
                if (! StdProcess::stdRowEffMatchesUnit($componentEff, $unitEff)) {
                    return false;
                }

                if (StdProcess::effCodeTokens($componentEff) === []) {
                    foreach ($this->baseIplKeysForTdrComponent((string) ($component->ipl_num ?? '')) as $baseKey) {
                        if (isset($effCodedBaseIpls[$baseKey])) {
                            return false;
                        }
                    }
                }

                return true;
            })
            ->values();
    }

    private function effCodedBaseIplsForComponents($components): array
    {
        $keys = [];

        foreach ($components as $component) {
            if (! $component instanceof Component) {
                continue;
            }

            if (StdProcess::effCodeTokens((string) ($component->eff_code ?? '')) === []) {
                continue;
            }

            foreach ($this->baseIplKeysForTdrComponent((string) ($component->ipl_num ?? '')) as $baseKey) {
                $keys[$baseKey] = true;
            }
        }

        return $keys;
    }

    private function baseIplKeysForTdrComponent(string $ipl): array
    {
        $lines = preg_split('/\R+/', trim($ipl)) ?: [];
        $keys = [];

        foreach ($lines as $line) {
            $line = strtoupper(trim($line));
            if ($line === '') {
                continue;
            }

            if (preg_match('/^(\d+[A-Z]*-\d+)[A-Z]+$/', $line, $matches)) {
                $keys[] = $matches[1];
                continue;
            }

            $keys[] = $line;
        }

        return array_values(array_unique($keys));
    }

}
