<?php

namespace App\Services;

use App\Models\{Code, Component, WoBushing, WoBushingBatch, WoBushingLine, WoBushingProcess, Workorder, WorkorderKitPrlCrossout};
use App\Support\BushingPrlGrouping;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** One selection shared by the bushing editor and the printed KIT. */
class WorkorderBushingSelection
{
    public function families(Workorder $wo)
    {
        $parts = app(WorkorderPartScopeResolver::class)->filterTdrComponents(
            Component::whereIn('manual_id', $wo->usedManualIds())->bushingCandidates()->get(), $wo
        )->filter(fn ($part) => app(ManualIplBranchRuleResolver::class)->allowsComponentForUnit(
            $wo->unit, (string) $part->ipl_num, (int) $part->manual_id));
        return BushingPrlGrouping::groups($parts);
    }

    public function lines(Workorder $wo)
    {
        return WoBushingLine::withoutGlobalScope('selected')->where(function ($q) use ($wo) {
            $q->where('workorder_id', $wo->id)->orWhereHas('woBushing', fn ($b) => $b->where('workorder_id', $wo->id));
        })->with(['processes.process.process_name', 'processes.batch'])->orderBy('id')->get();
    }

    public function snapshot(Workorder $wo): array
    {
        $lines = $this->lines($wo);
        $protected = app(BushingRouteBatches::class)->protectedLineIds($lines);
        $decisions = DB::table('workorder_bushing_decisions')->where('workorder_id', $wo->id)->orderBy('family_key')->get();
        $crossouts = WorkorderKitPrlCrossout::where('workorder_id', $wo->id)->orderBy('component_id')->pluck('component_id');
        $coverage = $wo->isOverhaul() ? app(PartGroupCoverageResolver::class)
            ->coverageForWorkorder($wo, 'prl', true, false) : [];
        $groups = [];
        foreach ($this->families($wo) as $key => $family) {
            $capacity = BushingPrlGrouping::capacity($family);
            $familyLines = $lines->whereIn('component_id', $family->pluck('id'));
            // Do Not Order excludes the chosen part from purchasing, not the
            // size selection: unselected alternatives must remain excluded.
            $decided = $decisions->contains('family_key', $key)
                || $familyLines->contains(fn ($line) => $line->selection_active);
            $options = [];
            foreach ($family as $part) {
                $partLines = $familyLines->where('component_id', $part->id);
                $active = $partLines->where('selection_active', true);
                $supplied = $coverage[$part->id] ?? [];
                $covered = min($capacity, max(0, (int) ($supplied['covered_qty'] ?? 0)));
                $required = $active->where('do_not_order', false)->sum('qty') ?: $capacity;
                $locked = $covered >= $required;
                $history = $partLines->whereIn('id', $protected)->isNotEmpty();
                $selected = $active->isNotEmpty() && !$crossouts->contains($part->id);
                // No selection means no order, including untouched families.
                // Keep "decided" as editing metadata, not a purchasing default.
                $excluded = !$selected;
                $options[] = [
                    'id' => $part->id, 'ipl' => $part->ipl_num, 'part_number' => $part->part_number,
                    'selected' => $selected, 'qty' => (int) ($active->sum('qty') ?: $partLines->sum('qty') ?: 1),
                    'order_qty' => max(0, (int) $active->where('do_not_order', false)->sum('qty') - $covered),
                    'codes_id' => $partLines->first()?->codes_id,
                    'do_not_order' => (bool) $active->first()?->do_not_order,
                    'crossed_out' => $locked || $excluded || ($selected && (bool) $active->first()?->do_not_order),
                    'locked' => $locked, 'history' => $history,
                    'reason' => $locked ? ($supplied['reason'] ?? 'Included in an ordered assembly.') : ($history ? 'This bushing has started processes. Its history is protected.' : ''),
                ];
            }
            $groups[] = ['key' => $key, 'capacity' => $capacity, 'decided' => $decided, 'options' => $options];
        }
        $revision = hash('sha256', serialize([$groups, $decisions->toArray(), $crossouts->all(),
            $lines->map(fn ($line) => [$line->getAttributes(), $line->processes->map(fn ($p) => [$p->getAttributes(), $p->batch?->getAttributes()])->all()])->all()]));
        $bushing = WoBushing::where('workorder_id', $wo->id)->first();
        return ['revision' => $revision, 'groups' => $groups, 'bushing_id' => $bushing?->id,
            'drafts' => $bushing ? app(WoBushingRelationalSync::class)->bushDataFromLines($bushing, true) : [],
            'draft_token' => $bushing ? app(WoBushingRelationalSync::class)->editState($bushing)['token'] : null];
    }

    public function decide(Workorder $wo, string $key): void
    {
        DB::table('workorder_bushing_decisions')->updateOrInsert(
            ['workorder_id' => $wo->id, 'family_key' => $key], ['created_at' => now(), 'updated_at' => now()]);
    }

    public function update(Workorder $wo, Component $component, array $items, string $revision): array
    {
        return DB::transaction(function () use ($wo, $component, $items, $revision) {
            Workorder::whereKey($wo->id)->lockForUpdate()->firstOrFail();
            $bushing = WoBushing::where('workorder_id', $wo->id)->lockForUpdate()->first();
            // Serialize quantity changes with edits to the underlying process history.
            // Match the line -> process -> batch lock order used by the full editor.
            $lineIds = WoBushingLine::withoutGlobalScope('selected')->where(function ($q) use ($wo) {
                $q->where('workorder_id', $wo->id)->orWhereHas('woBushing', fn ($b) => $b->where('workorder_id', $wo->id));
            })->orderBy('id')->lockForUpdate()->pluck('id');
            WoBushingProcess::whereIn('wo_bushing_line_id', $lineIds)->orderBy('id')->lockForUpdate()->get();
            WoBushingBatch::where('workorder_id', $wo->id)->orderBy('id')->lockForUpdate()->get();
            $state = $this->snapshot($wo);
            if (!hash_equals($state['revision'], $revision)) {
                $this->fail('Bushing selection changed on another screen. Review the refreshed selection and try again.');
            }
            $group = collect($state['groups'])->first(fn ($g) => collect($g['options'])->contains('id', $component->id));
            if (!$group) $this->fail('This bushing is outside the workorder scope.');
            $options = collect($group['options'])->keyBy('id');
            if (collect(array_keys($items))->diff($options->keys())->isNotEmpty()) $this->fail('Selections must belong to one bushing group.');
            // A partial patch changes only its named variants; other selections remain intact.
            $desired = $options->map(function ($option) use ($items) {
                $item = $items[$option['id']] ?? [];
                return array_merge($option, $item);
            });
            $total = $desired->filter(fn ($o) => $o['selected'] && !$o['do_not_order'])->sum('qty');
            if ($total > $group['capacity']) $this->fail('Total QTY '.$total.' exceeds the group maximum '.$group['capacity'].'. Split the quantity between Original and Oversize.');
            $allLines = $this->lines($wo);
            foreach ($items as $id => $item) {
                $before = $options[$id];
                $after = $desired[$id];
                $selected = (bool) $after['selected'];
                if ($selected && $before['locked']) $this->fail($before['reason']);
                if ($before['history'] && ($selected !== $before['selected'] || (int) $after['qty'] !== $before['qty']
                    || (bool) $after['do_not_order'] !== $before['do_not_order'])) $this->fail($before['reason']);
                $existing = $allLines->where('component_id', $id);
                if (!$selected) {
                    WoBushingLine::withoutGlobalScope('selected')->whereIn('id', $existing->pluck('id'))->update(['selection_active' => false]);
                    continue;
                }
                $qty = max(1, (int) $after['qty']);
                if ($existing->count() > 1 && $existing->sum('qty') !== $qty) $this->fail('This bushing has several process rows. Change its quantity in the bushing editor.');
                if (!$bushing) $bushing = WoBushing::create(['workorder_id' => $wo->id]);
                if ($existing->isEmpty()) {
                    $code = $after['codes_id'] ?: Code::where('code', 'K')->value('id');
                    WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id,
                        'component_id' => $id, 'qty' => $qty, 'qty_remaining' => $qty,
                        'codes_id' => $code, 'do_not_order' => (bool) $after['do_not_order'],
                        'selection_active' => true, 'group_key' => $group['key'], 'sort_order' => $allLines->count()]);
                } else {
                    foreach ($existing as $line) {
                        if ($existing->count() === 1 && $line->qty !== $qty) {
                            // Merely assigning a process does not start it. History is
                            // protected above; keep a draft's full-quantity operations
                            // in sync without recreating their IDs or route batches.
                            if ($line->processes->isNotEmpty()) {
                                if ($line->qty_remaining !== $line->qty || $line->processes->contains(fn ($step) => $step->qty !== $line->qty)) {
                                    $this->fail('This bushing has split or consumed process quantities. Review its process quantities in the bushing editor.');
                                }
                                foreach ($line->processes as $step) {
                                    $step->qty = $qty;
                                    $step->save();
                                }
                            }
                            $line->qty_remaining = max(0, $line->qty_remaining + $qty - $line->qty);
                            $line->qty = $qty;
                        }
                        $line->selection_active = true;
                        $line->do_not_order = (bool) $after['do_not_order'];
                        if (array_key_exists('codes_id', $item)) $line->codes_id = $item['codes_id'];
                        $line->save();
                    }
                }
            }
            // Explicit decisions supersede old manual marks, including "all excluded".
            WoBushingLine::withoutGlobalScope('selected')->whereIn('id', $allLines
                ->whereIn('component_id', $desired->filter(fn ($o) => !$o['selected'])->keys())->pluck('id'))
                ->update(['selection_active' => false]);
            $this->decide($wo, $group['key']);
            WorkorderKitPrlCrossout::where('workorder_id', $wo->id)->whereIn('component_id', $options->keys())->delete();
            $result = $this->snapshot($wo);
            // Choosing a size can redistribute a partial ASSY supply to that size.
            foreach (collect($result['groups'])->flatMap(fn ($g) => $g['options']) as $option) {
                if (!empty($items[$option['id']]['selected']) && $option['locked']) $this->fail($option['reason']);
            }
            return $result;
        });
    }

    private function fail(string $message): void
    {
        throw ValidationException::withMessages(['selection' => $message]);
    }
}
