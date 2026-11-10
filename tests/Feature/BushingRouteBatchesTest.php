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

    public function test_manual_split_survives_list_save_print_and_has_independent_history(): void
    {
        $this->actingAs($this->createUserWithRole('Technician'));
        $wo = $this->createWorkorder();
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $m = $this->process('Machining'); $n = $this->process('NDT-1');
        $lines = collect(range(1, 5))->map(fn () => $this->line($bushing, [$m, $n], 2));
        $service = app(BushingRouteBatches::class);
        $service->rebuild($bushing);
        $ids = $lines->take(3)->map(fn ($line) => $line->processes()->first()->id)->all();
        $this->postJson(route('wo_bushings.batches.split', $bushing), ['wo_bushing_process_ids' => $ids])
            ->assertOk()->assertJson(['label' => 'B1.1']);
        $payload = [];
        foreach ($lines as $line) $payload[$line->component_id] = ['selected' => 1, 'qty' => 2, 'need_processes' => 1, 'machining' => $m->id, 'ndt' => [$n->id]];
        app(WoBushingRelationalSync::class)->syncFromGroupBushings($bushing, ['one' => ['items' => $payload]]);
        $labels = $service->labels($wo->id);
        $this->assertSame(['B1', 'B1.1'], array_values($labels['ndt']));
        $this->assertSame(['B1', 'B1.1'], array_values($labels['machining']));
        $groups = collect(app(BushingSpecProcessGroups::class)->build($wo))->keyBy('batch_label');
        $this->assertSame(6, $groups['B1.1']['total_qty']);
        $this->assertSame(4, $groups['B1']['total_qty']);
        $target = (new \ReflectionMethod(QuantumRoBufferApplyService::class, 'findBushingBatchTarget'))
            ->invoke(app(QuantumRoBufferApplyService::class), new QuantumRoLine(['bom_ref' => 'B1.1']), $wo, ['process_key' => 'ndt', 'label' => 'NDT']);
        $this->assertSame('B1.1', $target['model']->route_label);
        $target['model']->update(['date_start' => '2026-11-05']);
        $loaded = $bushing->lines()->with(['processes.process.process_name', 'processes.batch.process.process_name'])->get();
        $this->assertCount(3, $service->protectedLineIds($loaded));
        $remaining = $loaded->firstWhere('component_id', $lines[3]->component_id);
        $this->assertSame('B1.2', $service->split($bushing, [$remaining->processes->first()->id]));
        $p = $this->process('Passivation');
        $extra = $this->line($bushing, [$p]);
        $service->rebuild($bushing);
        $this->assertSame('B2', $extra->processes()->first()->batch->route_label);
        $this->assertDatabaseHas('activity_log', ['description' => 'Bushing batch split', 'causer_id' => auth()->id()]);
    }

    public function test_split_rejects_whole_batch_foreign_rows_and_started_history(): void
    {
        $this->actingAs($this->createUserWithRole('Technician'));
        $wo = $this->createWorkorder(); $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $n = $this->process('NDT-1');
        $one = $this->line($bushing, [$n]); $two = $this->line($bushing, [$n]);
        app(BushingRouteBatches::class)->rebuild($bushing);
        $ids = [$one->processes()->first()->id, $two->processes()->first()->id];
        $url = route('wo_bushings.batches.split', $bushing);
        $this->postJson($url, ['wo_bushing_process_ids' => $ids])->assertStatus(422);
        $this->postJson($url, ['wo_bushing_process_ids' => [99999999]])->assertStatus(422);
        $one->processes()->first()->batch->update(['repair_order' => 'RO-123']);
        $this->postJson($url, ['wo_bushing_process_ids' => [$ids[0]]])->assertStatus(422);
        $this->assertSame(0, WoBushingBatch::where('workorder_id', $wo->id)->where('manual_partition', true)->count());
    }

    private function line(WoBushing $bushing, array $processes, int $qty = 1): WoBushingLine
    {
        $part = Component::create(['manual_id' => $bushing->workorder->unit->manual_id, 'part_number' => 'PN-'.uniqid(), 'ipl_num' => '1-'.uniqid(), 'name' => 'Bushing']);
        $line = WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $bushing->workorder_id, 'component_id' => $part->id, 'qty' => $qty, 'qty_remaining' => $qty]);
        foreach ($processes as $process) WoBushingProcess::create(['wo_bushing_line_id' => $line->id, 'process_id' => $process->id, 'qty' => $qty]);
        return $line;
    }

    public function test_manual_membership_survives_replacing_all_processes_disabling_and_reselecting(): void
    {
        $this->actingAs($this->createUserWithRole('Technician'));
        $wo = $this->createWorkorder(); $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $m = $this->process('Machining'); $n = $this->process('NDT-1'); $p = $this->process('Passivation');
        $one = $this->line($bushing, [$m, $n], 3); $two = $this->line($bushing, [$m, $n], 2);
        $service = app(BushingRouteBatches::class); $sync = app(WoBushingRelationalSync::class);
        $service->rebuild($bushing);
        $stale = $sync->editState($bushing)['token'];
        $service->split($bushing, $one->processes()->pluck('id')->all());
        $payload = [$one->component_id => ['selected' => 1, 'qty' => 3, 'need_processes' => 1, 'passivation' => $p->id],
            $two->component_id => ['selected' => 1, 'qty' => 2, 'need_processes' => 1, 'machining' => $m->id, 'ndt' => [$n->id]]];
        try {
            $sync->syncFromGroupBushings($bushing, ['g' => ['items' => $payload]], $stale);
            $this->fail('Stale editor must not overwrite the split');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
        $save = fn () => $sync->syncFromGroupBushings($bushing, ['g' => ['items' => $payload]]);
        $save();
        $row = fn () => $bushing->lines()->where('component_id', $one->component_id)->firstOrFail();
        $this->assertSame('B1.1', $row()->processes()->first()->batch->route_label);
        $payload[$one->component_id]['need_processes'] = 0;
        $sync->syncFromGroupBushings($bushing, ['g' => ['items' => $payload]]);
        $this->assertSame(0, $row()->processes()->count());
        $this->assertNotNull($row()->manual_route_number);
        $payload[$one->component_id]['selected'] = 0;
        $sync->syncFromGroupBushings($bushing, ['g' => ['items' => $payload]]);
        $payload[$one->component_id]['selected'] = 1;
        $payload[$one->component_id]['need_processes'] = 1;
        $sync->syncFromGroupBushings($bushing, ['g' => ['items' => $payload]]);
        $this->assertSame('B1.1', $row()->processes()->first()->batch->route_label);
        $this->assertSame(3, $row()->qty);
        $this->assertSame(3, $row()->qty_remaining);
        $this->assertSame('B1', $bushing->lines()->where('component_id', $two->component_id)->first()->processes()->first()->batch->route_label);
    }

    public function test_split_deduplicates_multiple_operations_and_multiple_ndts_without_changing_quantity(): void
    {
        $this->actingAs($this->createUserWithRole('Technician'));
        $wo = $this->createWorkorder(); $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $m = $this->process('Machining'); $n = $this->process('NDT-1'); $n4 = $this->process('NDT-4');
        $one = $this->line($bushing, [$m, $n, $n4], 3); $two = $this->line($bushing, [$m, $n], 2);
        $service = app(BushingRouteBatches::class); $service->rebuild($bushing);
        $one->processes()->where('process_id', $m->id)->first()->batch->update(['repair_order' => 'AT', 'date_start' => null]);
        $this->assertSame('B1.1', $service->split($bushing, $one->processes()->pluck('id')->all()));
        $this->assertSame(3, $one->fresh()->qty);
        $this->assertSame(3, $one->processes()->count());
        $this->assertSame([3], $one->processes()->pluck('qty')->unique()->values()->all());
        $this->assertSame(2, $one->processes()->pluck('batch_id')->unique()->count());
        $this->assertSame(2, $two->fresh()->qty);
        $this->assertSame(0, $one->processes()->whereNotNull('repair_order')->count());
        $groups = collect(app(BushingSpecProcessGroups::class)->build($wo))->keyBy('batch_label');
        $this->assertSame(3, $groups['B1.1']['total_qty']);
        $this->assertSame(2, $groups['B1']['total_qty']);
    }

    public function test_changed_process_columns_do_not_merge_manual_parties_or_reuse_their_labels(): void
    {
        $this->actingAs($this->createUserWithRole('Technician'));
        $wo = $this->createWorkorder(); $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $n = $this->process('NDT-1'); $p = $this->process('Passivation');
        $lines = collect(range(1, 3))->map(fn () => $this->line($bushing, [$n]));
        $service = app(BushingRouteBatches::class); $service->rebuild($bushing);
        $service->split($bushing, $lines->take(2)->map(fn ($line) => $line->processes()->first()->id)->all());
        $payload = [];
        foreach ($lines as $line) $payload[$line->component_id] = ['selected' => 1, 'qty' => 1, 'need_processes' => 1, 'ndt' => [$n->id]];
        $payload[$lines[0]->component_id]['ndt'] = [];
        $payload[$lines[0]->component_id]['passivation'] = $p->id;
        $sync = app(WoBushingRelationalSync::class);
        $sync->syncFromGroupBushings($bushing, ['g' => ['items' => $payload]]);
        $labels = fn () => $bushing->lines()->orderBy('sort_order')->get()->map(fn ($line) => $line->processes()->first()->batch->route_label)->all();
        $this->assertSame(['B1.1', 'B1.2', 'B1'], $labels());
        $payload[$lines[0]->component_id]['ndt'] = [$n->id];
        unset($payload[$lines[0]->component_id]['passivation']);
        $sync->syncFromGroupBushings($bushing, ['g' => ['items' => $payload]]);
        $this->assertSame(['B1.1', 'B1.2', 'B1'], $labels());
        $ids = $bushing->lines()->get()->map(fn ($line) => $line->processes()->first()->id)->all();
        $this->postJson(route('wo_bushings.batches.split', $bushing), ['wo_bushing_process_ids' => array_slice($ids, 0, 2)])->assertStatus(422);
        $this->assertSame(['B1.1', 'B1.2', 'B1'], $labels());
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
        foreach ([$m, $n1, $n4, $p, $c] as $process) {
            $process->process_name->update(['name' => 'Renamed operation '.$process->process_names_id]);
        }
        $service->rebuild($bushing);
        $this->assertSame($labels, $service->labels($wo->id));
        // NDT B2 is looked up by route, including gaps in a particular operation.
        $target = (new \ReflectionMethod(QuantumRoBufferApplyService::class, 'findBushingBatchTarget'))
            ->invoke(app(QuantumRoBufferApplyService::class), new QuantumRoLine(['bom_ref' => 'B2']), $wo, ['process_key' => 'ndt', 'label' => 'NDT']);
        $this->assertSame(2, (int) $target['model']->route_number);
    }

    public function test_clearing_machining_sent_date_unlocks_route_unless_another_operation_has_history(): void
    {
        $wo = $this->createWorkorder();
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $m = $this->process('Machining (AT)');
        $n = $this->process('NDT-1');
        $first = $this->line($bushing, [$m, $n]);
        $second = $this->line($bushing, [$m, $n]);
        $service = app(BushingRouteBatches::class);
        $service->rebuild($bushing);
        $lines = fn () => $bushing->lines()->with(['processes.process.process_name', 'processes.batch.process.process_name'])->get();
        $batch = $first->processes()->where('process_id', $m->id)->first()->batch;
        $batch->update(['repair_order' => 'AT', 'date_start' => '2026-11-03']);
        $this->assertCount(2, $service->protectedLineIds($lines()));
        $batch->update(['date_start' => null]);
        $this->assertSame([], $service->protectedLineIds($lines()));
        $this->assertSame('AT', $batch->fresh()->repair_order);
        $ndt = $first->processes()->where('process_id', $n->id)->first()->batch;
        $ndt->update(['repair_order' => 'RO-123']);
        $this->assertCount(2, $service->protectedLineIds($lines()));
        $ndt->update(['repair_order' => null, 'date_finish' => '2026-11-03']);
        $this->assertCount(2, $service->protectedLineIds($lines()));
        $ndt->update(['date_finish' => null]);
        $row = $first->processes()->where('process_id', $m->id)->first();
        $row->update(['repair_order' => 'AT', 'date_start' => '2026-11-03']);
        $this->assertCount(2, $service->protectedLineIds($lines()));
        $row->update(['date_start' => null]);
        $this->assertSame([], $service->protectedLineIds($lines()));
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

    public function test_removed_draft_is_cleaned_and_readdition_reuses_trailing_number(): void
    {
        $wo = $this->createWorkorder();
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $n = $this->process('NDT-4'); $m = $this->process('Machining');
        $service = app(BushingRouteBatches::class);
        $kept = $this->line($bushing, [$n]);
        $removed = $this->line($bushing, [$n, $m]);
        $service->rebuild($bushing);
        $oldIds = $removed->processes()->pluck('batch_id');
        $removed->delete();
        $service->rebuild($bushing);
        $this->assertSame(0, WoBushingBatch::whereIn('id', $oldIds)->count());
        $added = $this->line($bushing, [$n, $m]);
        $service->rebuild($bushing);
        $this->assertSame([2, 2], $added->processes()->get()->map(fn ($row) => (int) $row->batch->route_number)->all());
        $this->assertSame(1, (int) $kept->processes()->first()->batch->route_number);
        // A changed draft route can also leave an operation empty during rebuild itself.
        $added->processes()->where('process_id', $m->id)->delete();
        $service->rebuild($bushing);
        $this->assertSame(0, WoBushingBatch::where('workorder_id', $wo->id)->whereDoesntHave('woBushingProcesses')->count());
    }

    public function test_empty_batches_with_history_or_tracking_data_survive_cleanup(): void
    {
        $wo = $this->createWorkorder();
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $m = $this->process('Machining');
        $ids = [];
        foreach ([['repair_order' => 'R123'], ['date_start' => '2026-09-01'], ['date_finish' => '2026-09-02'], ['date_promise' => '2026-09-03'], ['working_steps_count' => 2], []] as $index => $attributes) {
            $batch = WoBushingBatch::create(array_merge(['workorder_id' => $wo->id, 'process_id' => $m->id, 'process_column_key' => 'machining', 'route_number' => $index + 1], $attributes));
            $ids[] = $batch->id;
        }
        $batch->machiningWorkSteps()->create(['step_index' => 1, 'description' => 'Existing work']);
        app(BushingRouteBatches::class)->rebuild($bushing);
        $this->assertSame(count($ids), WoBushingBatch::whereIn('id', $ids)->count());
        $this->assertSame(1, $batch->machiningWorkSteps()->count());
    }
}
