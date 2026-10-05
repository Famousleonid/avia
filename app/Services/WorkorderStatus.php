<?php

namespace App\Services;

use App\Models\{ProcessName, Tdr, Workorder, WoBushingProcess};
use Illuminate\Support\Collection;

class WorkorderStatus
{
    public function __construct(private WorkorderPartsList $partsList, private WorkorderCompletionForecast $forecast) {}

    public function forWorkorder(Workorder $wo): array
    {
        $parts = $this->partsList->rows($wo)->reject(fn ($r) => $r->crossed_out);
        $pendingParts = $parts->map(function ($r) {
            $ordered = max(0, (int) $r->qty);
            $received = min($ordered, max(0, (int) $r->received_qty));
            return ['ipl' => $r->component->ipl_num, 'pn' => $r->component->part_number,
                'name' => $r->component->name, 'kit' => $r->component->kit,
                'ordered' => $ordered, 'received' => $received, 'missing' => $ordered - $received];
        })->where('missing', '>', 0)->values();

        $pendingProcesses = collect();
        $forecastRoutes = collect();
        $forecastRoutes->put('STD lists', $this->forecast->stdOperations($wo->stdProcesses()->with('processName')->get()));
        $traveler = ProcessName::whereIdentityName(ProcessName::SYSTEM_TRAVELER_NAME)->first();
        $processTotalQty = 0;
        $tdrs = Tdr::where('workorder_id', $wo->id)->with(['component', 'tdrProcesses.processName', 'tdrProcesses.vendor'])->get();
        foreach ($tdrs as $tdr) {
            $forecastRoutes->put('Part #'.$tdr->id.' / ('.$tdr->component?->ipl_num.') '.$tdr->component?->part_number, $this->forecast->tdrOperations($tdr->tdrProcesses, $traveler));
            $processes = $tdr->tdrProcesses->sortBy([['sort_order', 'asc'], ['id', 'asc']])->filter(fn ($p) => !$p->ignore_row && $this->included($p->processName));
            if ($processes->isNotEmpty()) {
                $processTotalQty += max(0, (int) $tdr->qty);
            }
            $pending = $processes->filter(fn ($p) => !$p->date_finish);
            if ($pending->isEmpty()) continue;
            $pendingProcesses->push(['ipl' => $tdr->component?->ipl_num, 'pn' => $tdr->component?->part_number,
                'name' => $tdr->component?->name, 'qty' => $tdr->qty, 'kind' => 'Part',
                'done' => $processes->count() - $pending->count(), 'total' => $processes->count(),
                'pending' => $pending->map(fn ($p) => ['name' => $p->processName->name, 'days' => max(0, (int) $p->processName->std_days),
                    'sent' => (bool) $p->date_start, 'ro' => $p->repair_order,
                    'vendor' => $p->vendor?->name, 'since' => $p->date_start])->values()]);
        }
        $bushings = WoBushingProcess::whereHas('line', fn ($q) => $q->where('workorder_id', $wo->id))
            ->whereHas('batch')->with(['line.component', 'process.process_name', 'batch.vendor'])->get();
        foreach ($bushings->groupBy(fn ($p) => $p->wo_bushing_line_id.':'.($p->batch->route_number ?? 'legacy-'.$p->batch_id)) as $route) {
            $route = $route->sortBy(fn ($p) => $p->process?->process_name ? $this->bushingOrder($p->process->process_name) : 999999);
            $firstRoute = $route->first();
            $forecastRoutes->put('Bushing #'.$firstRoute->wo_bushing_line_id.' / ('.$firstRoute->line->component?->ipl_num.') '.$firstRoute->line->component?->part_number.' / B'.($firstRoute->batch->route_number ?? $firstRoute->batch_id), $this->forecast->bushingOperations($route));
        }
        $bushings = $bushings->filter(fn ($p) => $this->included($p->process?->process_name));
        // The route number identifies a portion across its process-specific batches.
        foreach ($bushings->groupBy(fn ($p) => $p->wo_bushing_line_id.':'.($p->batch->route_number ?? 'legacy-'.$p->batch_id)) as $group) {
            $processTotalQty += max(0, (int) $group->max('qty'));
            $group = $group->sortBy(fn ($p) => $this->bushingOrder($p->process->process_name));
            $pending = $group->filter(fn ($p) => !$p->batch->date_finish);
            if ($pending->isEmpty()) continue;
            $first = $group->first();
            $c = $first->line->component;
            $pendingProcesses->push(['ipl' => $c?->ipl_num, 'pn' => $c?->part_number, 'name' => $c?->name,
                'qty' => $group->max('qty'), 'kind' => 'Bushing'.($first->batch->route_number ? ' · B'.$first->batch->route_number : ''),
                'done' => $group->count() - $pending->count(), 'total' => $group->count(),
                'pending' => $pending->map(fn ($p) => ['name' => $p->process->process_name->name, 'days' => max(0, (int) $p->process->process_name->std_days),
                    'sent' => (bool) $p->batch->date_start, 'ro' => $p->batch->repair_order,
                    'vendor' => $p->batch->vendor?->name, 'since' => $p->batch->date_start])->values()]);
        }
        $pendingProcesses = $pendingProcesses->map(function ($row) {
            $pending = $row['pending']->values();
            $index = $pending->keys()->filter(fn ($i) => $pending[$i]['sent'])->last() ?? 0;
            $row['current'] = $pending[$index];
            $row['ahead'] = $pending->count() - $index - 1;
            $row['earlier_pending'] = $index;
            return $row;
        })->sortByDesc('ahead')->values();
        // Count a part/route portion once, even when several operations are pending.
        $sentProcesses = $pendingProcesses->filter(fn ($r) => $r['pending']->contains('sent', true))->values();
        $completion = $this->forecast->calculate($wo, $forecastRoutes);
        $ecRows = \App\Models\TdrProcess::query()
            ->whereHas('tdr', fn ($q) => $q->where('workorder_id', $wo->id))
            ->whereHas('processName', fn ($q) => $q->whereIdentityName('EC'))
            ->with(['tdr.component', 'tdr.orderComponent'])->orderBy('id')->get();
        return ['workorder' => $wo, 'parts' => $pendingParts, 'processes' => $pendingProcesses, 'ec' => $ecRows,
            'duration_days' => $completion['days'],
            'forecast_routes' => $completion['routes'],
            'isd' => $completion['date'],
            'process_total_qty' => $processTotalQty,
            'ordered_qty' => $parts->sum(fn ($r) => max(0, (int) $r->qty)),
            'sent_processes' => $sentProcesses,
            'sent_qty' => $sentProcesses->sum('qty'), 'unfinished_qty' => $pendingProcesses->sum('qty'),
            'missing_qty' => $pendingParts->sum('missing'), 'pending_operations' => $pendingProcesses->sum(fn ($r) => $r['pending']->count())];
    }

    private function bushingOrder(ProcessName $name): int
    {
        $key = ProcessName::normalizedNameKey($name->identityName());
        if (str_starts_with($key, 'machining')) return 0;
        if (str_starts_with($key, 'ndt')) return 10;
        return match ($key) { 'passivation' => 20, 'cadplate' => 30, 'anodizing', 'anodizingat' => 40, 'xylan', 'xylancoating' => 50, default => 100 };
    }

    private function included(?ProcessName $name): bool
    {
        return $name && $name->show_in_process_picker !== false
            && !in_array(ProcessName::normalizedNameKey($name->identityName()), ['paint', 'stdpaintlist', 'stressrelief', 'stdstressrelieflist', 'inspect', 'traveler'], true);
    }
}
