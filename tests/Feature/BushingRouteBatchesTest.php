<?php

namespace Tests\Feature;

use App\Models\{Component, Process, ProcessName, QuantumRoLine, WoBushing, WoBushingBatch, WoBushingLine, WoBushingProcess};
use App\Services\{BushingRouteBatches, BushingSpecProcessGroups, QuantumRoBufferApplyService, WoBushingRelationalSync};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class BushingRouteBatchesTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function process(string $name): Process
    {
        $type = ProcessName::firstOrCreate(['name' => $name], ['process_sheet_name' => $name, 'form_number' => 'TEST']);
        return Process::create(['process_names_id' => $type->id, 'process' => 'Instruction']);
    }

    private function line(WoBushing $bushing, array $processes, int $qty = 1): WoBushingLine
    {
        $part = Component::create(['manual_id' => $bushing->workorder->unit->manual_id, 'part_number' => 'PN-'.uniqid(), 'ipl_num' => '1-'.uniqid(), 'name' => 'Bushing']);
        $line = WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $bushing->workorder_id, 'component_id' => $part->id, 'qty' => $qty, 'qty_remaining' => $qty]);
        foreach ($processes as $process) WoBushingProcess::create(['wo_bushing_line_id' => $line->id, 'process_id' => $process->id, 'qty' => $qty]);
        return $line;
    }

    public function test_shared_routes_ignore_ndt_variant_keep_quantities_and_stable_numbers(): void
    {
        $wo = $this->createWorkorder();
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $m = $this->process('Machining'); $n1 = $this->process('NDT-1'); $n4 = $this->process('NDT-4'); $p = $this->process('Passivation'); $c = $this->process('Cad plate');
        $this->line($bushing, [$m, $n1, $p, $c], 2);
        $this->line($bushing, [$m, $n4, $p, $c], 3);
        $this->line($bushing, [$n4, $c], 1);
        $service = app(BushingRouteBatches::class);
        $service->rebuild($bushing);
        $labels = $service->labels($wo->id);
        $this->assertSame(['B1', 'B2'], array_values($labels['ndt']));
        $this->assertSame(['B1'], array_values($labels['machining']));
        $groups = app(BushingSpecProcessGroups::class)->build($wo);
        $this->assertCount(2, $groups);
        $this->assertSame([5, 1], array_column($groups, 'total_qty'));
        $this->assertSame(['Machining', 'NDT', 'Passivation', 'CAD'], $groups[0]['processes']);
        $service->rebuild($bushing);
        $this->assertSame($labels, $service->labels($wo->id));
        // NDT B2 is looked up by route, including gaps in a particular operation.
        $target = (new \ReflectionMethod(QuantumRoBufferApplyService::class, 'findBushingBatchTarget'))
            ->invoke(app(QuantumRoBufferApplyService::class), new QuantumRoLine(['bom_ref' => 'B2']), $wo, ['process_key' => 'ndt', 'label' => 'NDT']);
        $this->assertSame(2, (int) $target['model']->route_number);
    }

    public function test_ro_rows_and_connected_groups_survive_rebuild_and_list_save(): void
    {
        $wo = $this->createWorkorder(); $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $n = $this->process('NDT-4'); $c = $this->process('Cad plate');
        $old = $this->line($bushing, [$n, $c], 2);
        $current = $this->line($bushing, [$n, $c]);
        $batch = WoBushingBatch::create(['workorder_id' => $wo->id, 'process_id' => $n->id, 'process_column_key' => 'ndt', 'repair_order' => 'RO-123']);
        $old->processes()->where('process_id', $n->id)->update(['batch_id' => $batch->id]);
        $before = $old->processes()->orderBy('id')->get()->toArray();
        app(BushingRouteBatches::class)->rebuild($bushing);
        $this->assertSame($before, $old->processes()->orderBy('id')->get()->toArray());
        $this->assertSame('RO-123', $batch->fresh()->repair_order);
        $this->assertSame(2, (int) $current->processes()->first()->batch->route_number);
        // Even a stale modal omitting a historical component cannot delete it.
        app(WoBushingRelationalSync::class)->syncFromGroupBushings($bushing, ['one' => ['items' => [$current->component_id => ['selected' => 1, 'qty' => 1, 'need_processes' => 1, 'ndt' => [$n->id], 'cad' => $c->id]]]]);
        $this->assertSame($before, $old->processes()->orderBy('id')->get()->toArray());
        $this->assertNotNull($old->fresh());
        $this->assertSame('B1', app(BushingRouteBatches::class)->labels($wo->id)['ndt'][$batch->id]);
    }

    public function test_started_route_does_not_absorb_new_identical_parts(): void
    {
        $wo = $this->createWorkorder(); $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $n = $this->process('NDT-4'); $c = $this->process('Cad plate');
        $old = $this->line($bushing, [$n, $c]);
        $service = app(BushingRouteBatches::class); $service->rebuild($bushing);
        $old->processes()->first()->batch->update(['date_start' => '2026-09-01']);
        $new = $this->line($bushing, [$n, $c]); $service->rebuild($bushing);
        $this->assertSame([1, 1], $old->processes()->get()->map(fn ($r) => (int) $r->batch->route_number)->all());
        $this->assertSame([2, 2], $new->processes()->get()->map(fn ($r) => (int) $r->batch->route_number)->all());
    }

    public function test_thirteen_automatic_routes_render_three_pages_before_sending(): void
    {
        $admin = $this->createUserWithRole('Admin'); $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $processes = array_map(fn ($name) => $this->process($name), ['Machining', 'NDT-4', 'Passivation', 'Cad plate', 'Stress Relief']);
        foreach (range(1, 13) as $mask) {
            $this->line($bushing, array_values(array_filter($processes, fn ($process, $i) => $mask & (1 << $i), ARRAY_FILTER_USE_BOTH)));
        }
        app(BushingRouteBatches::class)->rebuild($bushing);
        $this->assertSame(3, app(BushingSpecProcessGroups::class)->pageCount($wo));
        $response = $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()])
            ->get(route('wo_bushings.specProcessForm', $bushing->id))->assertOk();
        $response->assertSee('B13')->assertSee('4 of 4');
        $this->assertSame(3, substr_count($response->getContent(), '<div class="container-fluid" data-process-table-rows-max='));
    }
}
