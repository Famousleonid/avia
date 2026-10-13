<?php

namespace Tests\Feature;

use App\Models\{Component, ManualPartGroup, Necessary, Process, ProcessName, Tdr, WoBushingLine, WoBushingProcess};
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

    public function test_pending_split_selection_and_all_excluded_are_distinct_and_shared(): void
    {
        [$wo, $original, $oversize] = $this->fixture();
        $this->assertFalse($this->state($wo)['groups'][0]['decided']);
        $this->assertFalse($this->printed($wo)[$original->id]['crossed_out']);
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
