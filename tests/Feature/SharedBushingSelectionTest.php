<?php

namespace Tests\Feature;

use App\Models\{Component, ManualPartGroup, Necessary, Process, ProcessName, Tdr, WoBushingBatch, WoBushingLine, WoBushingProcess};
use App\Services\WorkorderBushingSelection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class SharedBushingSelectionTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function fixture(): array
    {
        $admin = $this->createUserWithRole('Admin'); $this->actingAs($admin);
        $wo = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $parts = [];
        foreach (['original', 'oversize'] as $i => $kind) {
            $parts[] = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '6-'.(40 + $i),
                'bush_ipl_num' => '6-40', 'part_number' => 'SHARED-'.$kind, 'name' => 'BUSHING', 'is_bush' => true,
                'units_assy' => 2, 'kit' => false]);
        }
        return [$wo, ...$parts];
    }

    private function state($wo): array { return $this->getJson(route('workorders.bushing-selection.show', $wo))->assertOk()->json(); }
    private function save($wo, $part, array $items, ?string $revision = null)
    {
        return $this->patchJson(route('workorders.bushing-selection.update', [$wo, $part]),
            ['revision' => $revision ?? $this->state($wo)['revision'], 'items' => $items]);
    }
    private function printed($wo)
    {
        return collect($this->get(route('tdrs.kitForm', $wo))->assertOk()->viewData('ordersParts'))
            ->flatMap(fn ($r) => $r['prl_part_numbers'] ?? [])->keyBy('component_id');
    }

    private function draftProcessFixture(): array
    {
        [$wo, $part] = $this->fixture();
        $part->update(['ipl_num' => '10-37', 'bush_ipl_num' => '10-37', 'part_number' => '190-70748-301']);
        $this->save($wo, $part, [$part->id => ['selected' => true, 'qty' => 1]])->assertOk();
        $line = WoBushingLine::where('workorder_id', $wo->id)->firstOrFail();
        foreach (['Machining' => 'machining', 'Anodizing' => 'anodizing', 'NDT-4' => 'ndt'] as $name => $column) {
            $type = ProcessName::firstOrCreate(['name' => $name], ['process_sheet_name' => 'Test', 'form_number' => 'TEST']);
            $process = Process::create(['process_names_id' => $type->id, 'process' => 'Draft quantity test']);
            $process->manuals()->attach($part->manual_id);
            $batch = WoBushingBatch::create(['workorder_id' => $wo->id, 'process_id' => $process->id,
                'process_column_key' => $column, 'route_number' => 3]);
            $line->processes()->create(['process_id' => $process->id, 'batch_id' => $batch->id, 'qty' => 1]);
        }
        return [$wo, $part, $line];
    }

    public function test_unstarted_assigned_process_quantities_follow_bushing_qty_without_replacing_rows(): void
    {
        [$wo, $part, $line] = $this->draftProcessFixture();
        $steps = $line->processes()->with('batch')->get();
        $before = $steps->mapWithKeys(fn ($step) => [$step->id => $step->getAttributes()]);
        $batches = $steps->mapWithKeys(fn ($step) => [$step->batch_id => $step->batch->getAttributes()]);
        foreach ([2, 1] as $qty) {
            $response = $this->save($wo, $part, [$part->id => ['selected' => true, 'qty' => $qty]])->assertOk();
            if ($qty === 2) $revision = $response->json('revision');
            $this->assertSame($qty, $line->fresh()->qty);
            $this->assertSame($qty, $line->fresh()->qty_remaining);
            $draft = collect($response->json('drafts'))->firstWhere('bushing', $part->id);
            $this->assertSame($qty, $draft['qty']);
            $this->assertCount(3, $line->processes()->get());
            foreach ($steps as $step) {
                $actual = $step->fresh();
                $this->assertSame($qty, $actual->qty);
                $this->assertSame(collect($before[$step->id])->except(['qty', 'updated_at'])->all(),
                    collect($actual->getAttributes())->except(['qty', 'updated_at'])->all());
                $this->assertSame($batches[$step->batch_id], $actual->batch->getAttributes());
            }
        }
        $this->save($wo, $part, [$part->id => ['selected' => true, 'qty' => 2]], $revision)->assertUnprocessable();
        $this->save($wo, $part, [$part->id => ['selected' => true, 'qty' => 3]])->assertUnprocessable();
        $this->assertSame(1, $line->fresh()->qty);
    }

    public function test_qty_change_keeps_started_route_and_split_process_quantities_protected(): void
    {
        [$wo, $part, $line] = $this->draftProcessFixture();
        $step = $line->processes()->firstOrFail();
        foreach (['repair_order' => 'RO-STARTED', 'date_start' => '2026-10-26', 'date_finish' => '2026-10-26'] as $field => $value) {
            $step->batch->update([$field => $value]);
            $this->save($wo, $part, [$part->id => ['selected' => true, 'qty' => 2]])->assertUnprocessable();
            $step->batch->update([$field => null]);
            $step->update([$field => $value]);
            $this->save($wo, $part, [$part->id => ['selected' => true, 'qty' => 2]])->assertUnprocessable();
            $step->update([$field => null]);
        }
        $line->update(['qty' => 2, 'qty_remaining' => 2]);
        // A process assigned to just one of two parts must not be silently expanded.
        $this->save($wo, $part, [$part->id => ['selected' => true, 'qty' => 1]])->assertUnprocessable();
        $this->assertSame(2, $line->fresh()->qty);
        $this->assertSame([1, 1, 1], $line->processes()->pluck('qty')->all());
        $line->update(['qty' => 1, 'qty_remaining' => 0]);
        $this->save($wo, $part, [$part->id => ['selected' => true, 'qty' => 2]])->assertUnprocessable();
        $line->update(['qty_remaining' => 1]);
        $step->machiningWorkSteps()->create(['step_index' => 1, 'description' => 'Existing work']);
        $this->save($wo, $part, [$part->id => ['selected' => true, 'qty' => 2]])->assertUnprocessable();
        $this->assertSame(1, $line->fresh()->qty);
        $this->assertSame([1, 1, 1], $line->processes()->pluck('qty')->all());
    }

    public function test_existing_do_not_order_selection_excludes_other_sizes_in_kit_and_extra_parts(): void
    {
        [$wo, $original, $oversize] = $this->fixture();
        $bushing = \App\Models\WoBushing::create(['workorder_id' => $wo->id]);
        // Existing selections predate shared decisions, as on W108041 / 1-550.
        $line = WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id,
            'component_id' => $original->id, 'qty' => 1, 'qty_remaining' => 1,
            'selection_active' => true, 'do_not_order' => true]);
        $name = ProcessName::firstOrCreate(['name' => 'NDT-4'], ['process_sheet_name' => 'Test', 'form_number' => 'TEST']);
        $process = Process::create(['process_names_id' => $name->id, 'process' => 'Selection history test']);
        $history = WoBushingProcess::create(['wo_bushing_line_id' => $line->id,
            'process_id' => $process->id, 'qty' => 1, 'repair_order' => 'R-HISTORY']);
        $this->assertDatabaseMissing('workorder_bushing_decisions', ['workorder_id' => $wo->id]);

        foreach ([false, true] as $kit) {
            $original->update(['kit' => $kit]);
            $line->update(['do_not_order' => true]);
            $group = $this->state($wo)['groups'][0];
            $options = collect($group['options'])->keyBy('id');
            $this->assertTrue($group['decided']);
            $this->assertTrue($options[$original->id]['selected']);
            $this->assertTrue($options[$original->id]['history']);
            $this->assertFalse($options[$oversize->id]['selected']);
            $printed = $this->printed($wo);
            $this->assertTrue($printed[$original->id]['crossed_out']);
            $this->assertTrue($printed[$oversize->id]['crossed_out']);

            // Without Do Not Order, only the chosen Original remains in the list.
            $line->update(['do_not_order' => false]);
            $printed = $this->printed($wo);
            $this->assertFalse($printed[$original->id]['crossed_out']);
            $this->assertTrue($printed[$oversize->id]['crossed_out']);
        }
        $this->assertDatabaseHas('wo_bushing_processes', ['id' => $history->id,
            'wo_bushing_line_id' => $line->id, 'repair_order' => 'R-HISTORY']);
    }

    public function test_unselected_split_selection_and_all_excluded_are_shared(): void
    {
        [$wo, $original, $oversize] = $this->fixture();
        $this->assertFalse($this->state($wo)['groups'][0]['decided']);
        $this->assertTrue($this->printed($wo)->every(fn ($o) => $o['crossed_out']));
        $this->save($wo, $original, [$original->id => ['selected' => true, 'qty' => 1]])->assertOk();
        $this->assertTrue($this->printed($wo)[$oversize->id]['crossed_out']);
        $this->save($wo, $oversize, [$oversize->id => ['selected' => true, 'qty' => 1]])->assertOk();
        $this->assertFalse($this->printed($wo)[$oversize->id]['crossed_out']);
        $this->save($wo, $oversize, [$oversize->id => ['selected' => true, 'qty' => 2]])->assertUnprocessable();
        foreach ([$original, $oversize] as $part) {
            $this->patchJson(route('tdrs.kit-crossouts.update', [$wo, $part]), ['crossed_out' => true])->assertOk();
        }
        $state = $this->state($wo);
        $this->assertTrue($state['groups'][0]['decided']);
        $this->assertFalse($state['groups'][0]['options'][0]['selected']);
        $this->assertSame(0, WoBushingLine::where('workorder_id', $wo->id)->count());
        $this->assertTrue($this->printed($wo)->every(fn ($o) => $o['crossed_out']));
        $this->save($wo, $oversize, [$oversize->id => ['selected' => true, 'qty' => 1]])->assertOk();
        $this->assertSame(1, WoBushingLine::where('workorder_id', $wo->id)->count());
        $this->assertFalse($this->printed($wo)[$oversize->id]['crossed_out']);
    }

    public function test_untouched_1586_family_is_crossed_out_and_can_be_selected_from_print(): void
    {
        [$wo, $original, $oversize] = $this->fixture();
        $original->update(['ipl_num' => '1-586', 'bush_ipl_num' => '1-586', 'part_number' => '5035-0403', 'units_assy' => 1]);
        $oversize->update(['ipl_num' => '1-587', 'bush_ipl_num' => '1-586', 'part_number' => '5035-0403FS01', 'units_assy' => 1]);
        $third = Component::create(['manual_id' => $original->manual_id, 'ipl_num' => '1-588',
            'bush_ipl_num' => '1-586', 'part_number' => '5035-0403FS02', 'name' => 'BUSHING',
            'is_bush' => true, 'units_assy' => 1, 'kit' => false]);
        $bolt = Component::create(['manual_id' => $original->manual_id, 'ipl_num' => '1-590',
            'part_number' => 'UNCHANGED-BOLT', 'name' => 'BOLT', 'units_assy' => 1, 'kit' => true]);
        foreach ([false, true] as $kit) {
            $original->update(['kit' => $kit]);
            $state = $this->state($wo);
            $this->assertFalse($state['groups'][0]['decided']);
            $this->assertTrue(collect($state['groups'][0]['options'])->every(fn ($o) => !$o['selected'] && $o['crossed_out']));
            $response = $this->get(route('tdrs.kitForm', $wo))->assertOk();
            $rows = collect($response->viewData('ordersParts'));
            $row = $rows->first(fn ($r) => collect($r['prl_part_numbers'] ?? [])->contains('component_id', $original->id));
            $this->assertSame($kit ? 'KIT' : 'K', $row['codes']['code']);
            $this->assertSame(1, $row['bushing_kit_capacity']);
            $this->assertTrue($row['prl_crossed_out']);
            $this->assertCount(3, $row['prl_part_numbers']);
            $this->assertTrue(collect($row['prl_part_numbers'])->every(fn ($o) => $o['crossed_out']));
            $boltRow = $rows->first(fn ($r) => collect($r['kit_component_options'] ?? [])->contains('component_id', $bolt->id));
            $this->assertFalse($boltRow['prl_crossed_out']);
        }
        $this->assertDatabaseMissing('wo_bushing_lines', ['workorder_id' => $wo->id]);
        $this->assertDatabaseMissing('workorder_bushing_decisions', ['workorder_id' => $wo->id]);
        $this->patchJson(route('tdrs.kit-crossouts.update', [$wo, $third]), ['crossed_out' => false])->assertOk();
        $printed = $this->printed($wo);
        $this->assertFalse($printed[$third->id]['crossed_out']);
        $this->assertTrue($printed[$original->id]['crossed_out']);
        $this->assertTrue($printed[$oversize->id]['crossed_out']);
        $this->patchJson(route('tdrs.kit-crossouts.update', [$wo, $third]), ['crossed_out' => true])->assertOk();
        $this->assertTrue($this->printed($wo)->every(fn ($o) => $o['crossed_out']));
    }

    public function test_deselection_retains_process_identity_and_rejects_stale_writes(): void
    {
        [$wo, $original] = $this->fixture();
        $old = $this->state($wo)['revision'];
        $this->save($wo, $original, [$original->id => ['selected' => true, 'qty' => 2]], $old)->assertOk();
        $line = WoBushingLine::where('workorder_id', $wo->id)->firstOrFail();
        $name = ProcessName::firstOrCreate(['name' => 'Machining'], ['process_sheet_name' => 'Test', 'form_number' => 'TEST']);
        $process = Process::create(['process_names_id' => $name->id, 'process' => 'Test shared selection']);
        $row = WoBushingProcess::create(['wo_bushing_line_id' => $line->id, 'process_id' => $process->id, 'qty' => 2]);
        $this->save($wo, $original, [$original->id => ['selected' => false, 'qty' => 2]], $old)->assertUnprocessable();
        $this->save($wo, $original, [$original->id => ['selected' => false, 'qty' => 2]])->assertOk();
        $this->assertDatabaseHas('wo_bushing_processes', ['id' => $row->id, 'wo_bushing_line_id' => $line->id]);
        $this->assertSame(0, $wo->woBushingProcesses()->count());
        $this->save($wo, $original, [$original->id => ['selected' => true, 'qty' => 2]])->assertOk();
        $this->assertSame($line->id, WoBushingLine::where('workorder_id', $wo->id)->first()->id);
        $this->assertSame(1, $wo->woBushingProcesses()->count());
        $row->update(['repair_order' => 'R-TEST']);
        $this->save($wo, $original, [$original->id => ['selected' => false, 'qty' => 2]])->assertUnprocessable();
    }

    public function test_ordered_assembly_is_locked_on_both_endpoints_and_other_wo_is_rejected(): void
    {
        [$wo, $original] = $this->fixture();
        $head = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '6-30', 'part_number' => 'SHARED-ASSY', 'name' => 'ASSY']);
        $group = ManualPartGroup::create(['manual_id' => $head->manual_id, 'code' => 'SHARED-ASSY', 'name' => 'ASSY',
            'type' => 'assy', 'behavior' => 'bundle', 'applies_to' => ['prl']]);
        $option = $group->options()->create(['component_id' => $head->id, 'part_number' => $head->part_number, 'ipl_num' => $head->ipl_num, 'option_kind' => 'assy']);
        $option->coverages()->create(['component_id' => $original->id, 'qty' => 2, 'expand_ipl_family' => false, 'applies_to' => ['prl']]);
        Tdr::create(['workorder_id' => $wo->id, 'component_id' => $head->id, 'order_component_id' => $head->id,
            'qty' => 1, 'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id]);
        $this->assertTrue($this->state($wo)['groups'][0]['options'][0]['locked']);
        $this->save($wo, $original, [$original->id => ['selected' => true, 'qty' => 1]])->assertUnprocessable();
        $this->patchJson(route('tdrs.kit-crossouts.update', [$wo, $original]), ['crossed_out' => false])->assertUnprocessable();
        // A partial supply is allocated after choosing a size; a fully supplied
        // requested quantity must not be restored through that timing gap.
        $option->coverages()->update(['qty' => 1]);
        $this->save($wo, $original, [$original->id => ['selected' => true, 'qty' => 1]])->assertUnprocessable();
        $this->save($wo, $original, [$original->id => ['selected' => true, 'qty' => 2]])->assertOk();
        $this->save($wo, $original, [$original->id => ['selected' => false, 'qty' => 2]])->assertOk();
        $other = $this->createWorkorder();
        $otherPart = Component::create(['manual_id' => $other->unit->manual_id, 'ipl_num' => '6-40',
            'part_number' => 'OUTSIDE', 'name' => 'BUSHING', 'is_bush' => true, 'units_assy' => 1]);
        $this->save($wo, $otherPart, [$otherPart->id => ['selected' => true, 'qty' => 1]])->assertUnprocessable();
        $this->assertSame(0, WoBushingLine::where('workorder_id', $wo->id)->count());
    }
}
