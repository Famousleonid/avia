<?php

namespace Tests\Feature;

use App\Models\{Component, Process, ProcessName, WoBushing, WoBushingBatch, WoBushingLine, WoBushingProcess};
use App\Services\{BushingSpecProcessGroups, WoBushingRelationalSync};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class BushingHistoryRecalculationTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_production_history_is_not_duplicated_and_additions_recalculate_until_ro(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/w107736-bushing-history.json')), true);
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $processes = []; $batches = []; $lines = [];
        foreach ($fixture['processes'] as $row) {
            if (isset($processes[$row['process_id']])) continue;
            $name = ProcessName::whereIdentityName($row['identity_name'])->first()
                ?? ProcessName::create(['name' => $row['identity_name'], 'process_sheet_name' => 'Test', 'form_number' => 'TEST']);
            $processes[$row['process_id']] = Process::create(['process_names_id' => $name->id, 'process' => 'Fixture instruction']);
        }
        foreach ($fixture['batches'] as $row) {
            $id = $row['id']; unset($row['id']);
            $row['process_id'] = $processes[$row['process_id']]->id;
            $batches[$id] = WoBushingBatch::create($row + ['workorder_id' => $wo->id]);
        }
        foreach ($fixture['lines'] as $row) {
            $part = Component::create(['manual_id' => $wo->unit->manual_id, 'name' => 'Bushing',
                'ipl_num' => $row['ipl_num'], 'part_number' => $row['part_number']]);
            $lines[$row['id']] = WoBushingLine::create(['wo_bushing_id' => $bushing->id,
                'workorder_id' => $wo->id, 'component_id' => $part->id, 'qty' => $row['qty'],
                'qty_remaining' => $row['qty'], 'sort_order' => $row['sort_order']]);
        }
        foreach ($fixture['processes'] as $row) {
            WoBushingProcess::create(['wo_bushing_line_id' => $lines[$row['wo_bushing_line_id']]->id,
                'process_id' => $processes[$row['process_id']]->id, 'qty' => $row['qty'],
                'batch_id' => $row['batch_id'] ? $batches[$row['batch_id']]->id : null]);
        }
        $sp = app(BushingSpecProcessGroups::class);
        $this->assertSame([9, 4], array_column($sp->build($wo), 'total_qty'));
        $historicalIds = collect($lines)->except([1513, 1514, 1517, 1518])->pluck('id');
        $history = WoBushingProcess::whereIn('wo_bushing_line_id', $historicalIds)->orderBy('id')->get()->toArray();
        $sync = app(WoBushingRelationalSync::class);
        $payload = [];
        foreach ($sync->bushDataFromLines($bushing) as $row) {
            $payload[$row['bushing']] = array_merge($row['processes'], ['selected' => 1, 'need_processes' => 1, 'qty' => $row['qty']]);
        }
        $save = fn () => $sync->syncFromGroupBushings($bushing, ['fixture' => ['items' => $payload]]);
        $save();
        $groups = $sp->build($wo);
        $this->assertCount(3, $groups);
        $this->assertEqualsCanonicalizing([9, 4, 5], array_column($groups, 'total_qty'));
        $draft = collect($groups)->firstWhere('route_number', 2);
        $this->assertSame(5, $draft['total_qty']);
        $response = $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword()])->get(route('wo_bushings.specProcessForm', $bushing->id))->assertOk();
        $this->assertSame(3, substr_count($response->getContent(), 'class="spec-batch-title"'));
        $response->assertSee('QTY: 9')->assertSee('QTY: 4')->assertSee('QTY: 5');
        $oldDraftBatches = WoBushingBatch::where('workorder_id', $wo->id)->where('route_number', 2)->pluck('id')->all();
        $add = function (string $pn, int $qty) use ($wo, &$payload, $lines) {
            $part = Component::create(['manual_id' => $wo->unit->manual_id, 'name' => 'Bushing', 'part_number' => $pn, 'ipl_num' => $pn]);
            $payload[$part->id] = array_merge($payload[$lines[1513]->component_id], ['qty' => $qty]);
        };
        $add('NEW-BEFORE-RO', 2);
        $sync->syncFromGroupBushings($bushing, ['fixture' => ['items' => $payload]]);
        $this->assertEqualsCanonicalizing([9, 4, 7], array_column($sp->build($wo), 'total_qty'));
        $this->assertSame($oldDraftBatches, WoBushingBatch::where('workorder_id', $wo->id)->where('route_number', 2)->pluck('id')->all());
        // RO alone protects the complete route, even before dates are entered.
        $startedBatch = WoBushingBatch::whereIn('id', $oldDraftBatches)->where('process_column_key', 'ndt')->firstOrFail();
        $startedBatch->update(['repair_order' => 'NEW-RO']);
        $routeRows = WoBushingProcess::whereIn('batch_id', $oldDraftBatches)->orderBy('id')->get()->toArray();
        $add('NEW-AFTER-RO', 1);
        $sync->syncFromGroupBushings($bushing, ['fixture' => ['items' => $payload]]);
        $groups = $sp->build($wo);
        $this->assertCount(4, $groups);
        $this->assertEqualsCanonicalizing([9, 4, 7, 1], array_column($groups, 'total_qty'));
        $this->assertSame(1, collect($groups)->firstWhere('route_number', 3)['total_qty']);
        $this->assertSame($routeRows, WoBushingProcess::whereIn('batch_id', $oldDraftBatches)->orderBy('id')->get()->toArray());
        $this->assertSame($history, WoBushingProcess::whereIn('wo_bushing_line_id', $historicalIds)->orderBy('id')->get()->toArray());
        foreach ([141, 180, 181] as $id) {
            $this->assertSame($batches[$id]->repair_order, $batches[$id]->fresh()->repair_order);
            $this->assertEquals($batches[$id]->date_start, $batches[$id]->fresh()->date_start);
            $this->assertEquals($batches[$id]->date_finish, $batches[$id]->fresh()->date_finish);
        }
        // Reopening / saving again neither duplicates quantities nor creates extra columns.
        $sync->syncFromGroupBushings($bushing, ['fixture' => ['items' => $payload]]);
        $summary = fn ($items) => array_map(fn ($group) => array_intersect_key($group,
            array_flip(['batch_id', 'route_number', 'batch_label', 'part_numbers', 'total_qty', 'process_numbers'])), $items);
        $this->assertSame($summary($groups), $summary($sp->build($wo)));
    }
}
