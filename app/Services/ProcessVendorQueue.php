<?php

namespace App\Services;

use App\Models\{Process, ProcessName, ProcessVendorCheck, ProcessVendorRepairOrder, TdrProcess, Workorder};
use App\Support\WoBushingProcessColumnKey;
use Illuminate\Support\Collection;

/** Read-only projection. Shipping checks never set process Sent/Returned dates. */
class ProcessVendorQueue
{
    public const READINESS_VERSION = '2026-11-20-shimmy-early-release';
    private const EARLY_RELEASE_PROCESS = 'Shimmy Damper O/H';
    private const STD_ORDER = ['stress', 'ndt', 'cad', 'paint'];
    private const BUSHING_ORDER = ['machining', 'stress_relief', 'ndt', 'passivation', 'cad', 'anodizing', 'xylan'];
    private array $specifications = [];
    private array $processSheets = [];

    public function workorders()
    {
        return Workorder::query()->whereNull('done_at')
            ->where(fn ($q) => $q->whereNotNull('approve_at')->orWhereHas('main', fn ($q) => $q
                ->whereNotNull('date_finish')->whereHas('task', fn ($q) => $q->whereRaw('LOWER(TRIM(name)) = ?', ['post disassembly inspection'])))
                ->orWhereHas('tdrs.tdrProcesses.processName', fn ($q) => $q
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(self::EARLY_RELEASE_PROCESS)])));
    }

    public function rows(?array $workorderIds = null): Collection
    {
        $this->specifications = Process::query()->pluck('process', 'id')->all();
        $this->processSheets = ProcessName::pluck('process_sheet_name', 'name')->all();
        $guard = new ProcessSequenceGuard();
        $workorders = $this->workorders()
            ->when($workorderIds !== null, fn ($q) => $q->whereIn('id', $workorderIds))
            ->with(['user', 'customer', 'unit.manuals', 'instruction', 'main.task', 'stdProcesses.processName',
                'tdrs.component.manual', 'tdrs.tdrProcesses.processName',
                'woBushingLines.component.manual', 'woBushingLines.processes.process.process_name', 'woBushingLines.processes.batch'])
            ->orderBy('number')->get();
        $out = collect();
        $stdNames = ProcessName::whereIdentityNames(array_values(WorkorderStdListProcessesService::NAME_BY_KEY))->get()
            ->keyBy(fn ($name) => $name->identityName());
        foreach ($workorders as $wo) {
            $approval = $wo->approve_at && $wo->approve_at->lte(now());
            $inspection = $wo->main->first(fn ($main) => !$main->ignore_row && $main->date_finish
                && $main->date_finish->lte(today())
                && preg_replace('/\s+/', ' ', mb_strtolower(trim($main->task?->name ?? ''))) === 'post disassembly inspection');
            if (strcasecmp(trim($wo->instruction?->name ?? ''), 'Overhaul') === 0) {
                $previousComplete = true;
                foreach (self::STD_ORDER as $type) {
                    $name = $stdNames->get(WorkorderStdListProcessesService::NAME_BY_KEY[$type]);
                    if (!$name) continue;
                    $source = $wo->stdProcesses->firstWhere('std_type', $type)
                        ?? app(WorkorderStdListProcessesService::class)->findPreferredStdListProcessForWorkorder($wo, $name->id);
                    if ($source?->ignore_row) continue;
                    if ($this->complete($source)) continue;
                    // includeExcluded=true calculates a fresh list without rebuilding stored snapshots.
                    $parts = collect(app(WorkorderStdProcessItemsService::class)->snapshotRowsForWorkorder($wo, $type, true))
                        ->filter(fn ($part) => empty($part['group_crossed_out']) && ($part['qty'] ?? 0) > 0);
                    if ($parts->isEmpty()) continue;
                    $released = $type === 'ndt' ? (bool) $inspection : $approval;
                    if ($released && $previousComplete && $this->unsent($source)) {
                        foreach ($parts->groupBy(fn ($part) => PartVariantGrouping::stdKey($part) ?? 'component:'.$part['component_id']) as $variantKey => $variants) {
                            $part = $variants->first();
                            foreach (['ipl_num', 'part_number', 'description', 'process'] as $field) {
                                $part[$field] = $variants->pluck($field)->filter()->unique()->implode(' / ');
                            }
                            $part['qty'] = $variants->max('qty');
                            $part['reservation_tokens'] = $variants->map(fn ($variant) => 'std:'.$wo->id.':'.$type.':'.$variant['component_id'])->all();
                            $key = 'std:'.$wo->id.':'.$type.':'.substr(hash('sha256', (string) $variantKey), 0, 24);
                            $out->push($this->row($wo, $key, ['stress' => 'Stress relief', 'ndt' => 'NDT', 'cad' => 'CAD', 'paint' => 'Paint'][$type], 'STD '.strtoupper($type), $part,
                                $type === 'ndt' ? 'Post Disassembly inspection' : 'Approved',
                                ['approval' => $wo->approve_at?->toISOString(), 'inspection' => $inspection?->date_finish?->toDateString(), 'source' => $source?->id,
                                    'components' => $variants->pluck('component_id')->all()]));
                        }
                    }
                    $previousComplete = $previousComplete && $this->complete($source);
                    if (!$previousComplete) break;
                }
            }
            foreach ($wo->tdrs as $tdr) {
                if (!$tdr->component || $tdr->result_status === 'scrapped' || (int) $tdr->qty < 1) continue;
                $processes = $tdr->tdrProcesses->filter(fn ($p) => !$p->ignore_row && $p->processName
                    && $p->processName->show_in_process_picker !== false
                    && !in_array($p->processName->identityName(), WorkorderStdListProcessesService::NAME_BY_KEY, true));
                foreach ($processes->groupBy(fn ($p) => $p->in_traveler ? 'traveler:'.($p->traveler_group ?: 1) : 'part:'.$p->id) as $groupKey => $group) {
                    $group = $group->sortBy([['sort_order', 'asc'], ['id', 'asc']]);
                    $leader = $group->first();
                    // A mixed Traveler must not release other operations before approval.
                    $earlyRelease = $group->every(fn ($p) => strcasecmp(trim($p->processName->name), self::EARLY_RELEASE_PROCESS) === 0);
                    if (!$approval && !$earlyRelease) continue;
                    if (!$group->every(fn ($p) => $this->unsent($p))) continue;
                    $state = $leader->in_traveler ? $guard->travelerGroupInputState($tdr, $leader->traveler_group ?: 1) : $guard->tdrInputState($leader);
                    // Do not use the legacy later-dates exception to advertise readiness.
                    if (!$state['previous_complete']) continue;
                    $dateCheck = $leader->in_traveler
                        ? $guard->validateTravelerGroupDateUpdate($tdr, $leader->traveler_group ?: 1, ['date_start' => today()->toDateString()])
                        : $guard->validateTdrProcessDateUpdate($leader, ['date_start' => today()->toDateString()]);
                    if ($dateCheck) continue;
                    $spec = $group->map(fn ($p) => trim($p->processName->name.': '.$this->specText($p)))->implode("\n");
                    $out->push($this->row($wo, 'tdr:'.$tdr->id.':'.$groupKey, $leader->in_traveler ? 'Traveler' : $leader->processName->name,
                        $leader->in_traveler ? 'Traveler group '.($leader->traveler_group ?: 1) : 'Part process', [
                            'component_id' => $tdr->component_id, 'ipl_num' => $tdr->component->ipl_num,
                            'part_number' => $tdr->component->part_number, 'description' => $tdr->component->name,
                            'serial_number' => $tdr->serial_number, 'qty' => $tdr->qty,
                            'manual' => $tdr->component->manual?->number, 'process' => $spec,
                            'reservation_tokens' => $group->map(fn ($operation) => 'tdr-process:'.$operation->id)->values()->all(),
                        ], $earlyRelease ? 'Shimmy Damper O/H — no approval or inspection required' : 'Approved',
                        ['approval' => $earlyRelease ? null : $wo->approve_at->toISOString(), 'route' => $tdr->tdrProcesses->map->only(['id', 'sort_order', 'in_traveler', 'traveler_group', 'ignore_row', 'date_start', 'date_finish'])->all()]));
                }
            }
            if (!$approval) continue;
            foreach ($wo->woBushingLines as $line) {
                if (!$line->component) continue;
                $ordered = $line->processes->sortBy(fn ($p) => $this->bushingRank($p));
                $previousComplete = true;
                foreach ($ordered as $p) {
                    $source = $p->batch ?? $p;
                    if ($previousComplete && $this->unsent($source) && $this->unsent($p) && $p->qty > 0 && $p->process?->process_name) {
                        $out->push($this->row($wo, 'bushing:'.$p->id, $p->process->process_name->name,
                            'Bushing'.($p->batch ? ' · '.($p->batch->route_label ?: 'B'.($p->batch->route_number ?: $p->batch->id)) : ''), [
                                'component_id' => $line->component_id, 'ipl_num' => $line->component->ipl_num,
                                'part_number' => $line->component->part_number, 'description' => $line->component->name,
                                'qty' => $p->qty, 'manual' => $line->component->manual?->number, 'process' => $p->process->process,
                                'reservation_tokens' => ['bushing:'.$p->id],
                            ], 'Approved', ['approval' => $wo->approve_at->toISOString(), 'batch' => $p->batch_id, 'process_id' => $p->process_id,
                                'route' => $ordered->map(fn ($operation) => [$operation->id, $operation->process_id, $operation->qty, $operation->batch_id,
                                    ($operation->batch ?? $operation)->date_start?->toDateString(), ($operation->batch ?? $operation)->date_finish?->toDateString()])->values()->all()]));
                    }
                    $previousComplete = $previousComplete && $this->complete($source);
                }
            }
        }
        return $this->shippingState($out);
    }

    public static function hiddenByDefault(?string $name, ?string $sheet = null): bool
    {
        return (bool) preg_match('/\\b(ec|bake|baking|machin\\w*|paint\\w*|stress[\\s_-]*relief|out(?:coming|going)[\\s_-]*inspection)\\b/i', ($name ?? '').' '.($sheet ?? ''));
    }

    public static function groupName(string $name, ?string $sheet = null): string
    {
        return preg_match('/(^|[^a-z])(ndt|eddy)([^a-z]|$)/i', $name.' '.($sheet ?? '')) ? 'NDT' : $name;
    }

    /** Overlay live Shipping state without recalculating process readiness. */
    public function shippingState(Collection $out): Collection
    {
        $out = $out->values();
        $checks = ProcessVendorCheck::whereIn('item_key', $out->pluck('key'))->with('checkedBy')->get()->keyBy('item_key');
        $reservations = [];
        foreach (ProcessVendorRepairOrder::whereNull('cancelled_at')->whereIn('id', ProcessVendorCheck::whereIn('workorder_id', $out->pluck('workorder_id'))->whereNotNull('repair_order_id')->select('repair_order_id'))->get() as $order) {
            foreach ($order->document['items'] ?? [] as $item) {
                foreach ($item['reservation_tokens'] ?? [] as $token) $reservations[$token] = $order->id;
            }
        }
        return $out->map(function ($row) use ($checks, $reservations) {
            $check = $checks->get($row['key']);
            $valid = $check && hash_equals($check->fingerprint, $row['fingerprint']);
            $reservedOrder = collect($row['reservation_tokens'])->map(fn ($token) => $reservations[$token] ?? null)->filter()->first();
            return array_merge($row, ['checked' => $valid && $check->checked_at !== null, 'checked_by' => $valid ? $check->checkedBy?->name : null,
                'repair_order_id' => $check?->repair_order_id ?? $reservedOrder, 'changed' => ($check && !$valid) || ($reservedOrder && !$valid)]);
        })->values();
    }

    private function unsent($p): bool
    {
        return !$p || (!$p->date_start && !$p->date_finish && trim((string) $p->repair_order) === '');
    }

    private function complete($p): bool
    {
        return $p && $p->date_start && $p->date_finish && $p->date_finish->lte(today()) && $p->date_start->lte($p->date_finish);
    }

    private function bushingRank($p): string
    {
        $rank = array_search(WoBushingProcessColumnKey::fromProcess($p->process), self::BUSHING_ORDER, true);
        return sprintf('%04d:%012d', $rank === false ? 999 : $rank, $p->id);
    }

    private function specText($p): string
    {
        return collect(TdrProcess::normalizeStoredProcessIds($p->processes))->map(fn ($id) => trim(($this->specifications[$id] ?? '').' '.$p->descriptionForSpecification($id)))->filter()->implode('; ');
    }

    private function row($wo, string $key, string $name, string $source, array $part, string $release, array $revision): array
    {
        $row = ['key' => $key, 'workorder_id' => $wo->id, 'wo' => (string) $wo->number, 'technician' => $wo->user?->name ?? '',
            'customer' => $wo->customer?->name ?? '', 'unit_pn' => $wo->unit?->part_number ?? '', 'unit_sn' => $wo->serial_number,
            'process_name' => $name, 'process_group' => self::groupName($name, $this->processSheets[$name] ?? null), 'source' => $source, 'release' => $release,
            'component_id' => $part['component_id'] ?? null, 'ipl' => $part['ipl_num'] ?? '',
            'part_number' => $part['part_number'] ?? '', 'description' => $part['description'] ?? '',
            'serial' => $part['serial_number'] ?? '', 'qty' => (int) ($part['qty'] ?? 0),
            'manual' => $part['manual'] ?? '', 'specification' => $part['process'] ?? '', 'reservation_tokens' => $part['reservation_tokens'] ?? []];
        $row['fingerprint'] = hash('sha256', json_encode([$row, $revision], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $row;
    }
}
