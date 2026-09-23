<?php

namespace Tests\Feature;

use App\Models\{Component, Process, ProcessName, WoBushing, WoBushingBatch, WoBushingLine, WoBushingProcess};
use App\Services\BushingSpecProcessGroups;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class BushingSpecBatchColumnsTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_sent_batches_split_routes_exclude_unsent_parts_and_use_batch_quantities(): void
    {
        $wo = $this->createWorkorder();
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $ndt = $this->process('NDT-4');
        $machining = $this->process('Machining');
        $passivation = $this->process('Passivation');
        $batch = WoBushingBatch::create(['workorder_id' => $wo->id, 'process_id' => $ndt->id, 'process_column_key' => 'ndt', 'date_start' => '2026-09-01']);
        $draft = WoBushingBatch::create(['workorder_id' => $wo->id, 'process_id' => $ndt->id, 'process_column_key' => 'ndt']);
        foreach (range(1, 5) as $i) {
            $part = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'PN-'.$i, 'ipl_num' => '1-'.$i, 'name' => 'Bushing']);
            $line = WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id, 'component_id' => $part->id, 'qty' => 9, 'qty_remaining' => 9, 'sort_order' => $i]);
            WoBushingProcess::create(['wo_bushing_line_id' => $line->id, 'process_id' => $ndt->id, 'batch_id' => $i <= 3 ? $batch->id : ($i === 4 ? $draft->id : null), 'qty' => 2]);
            WoBushingProcess::create(['wo_bushing_line_id' => $line->id, 'process_id' => $machining->id, 'qty' => 9]);
            if ($i === 3) {
                WoBushingProcess::create(['wo_bushing_line_id' => $line->id, 'process_id' => $passivation->id, 'qty' => 9]);
            }
        }
        $groups = app(BushingSpecProcessGroups::class)->build($wo);
        $this->assertCount(2, $groups);
        $this->assertSame([4, 2], array_column($groups, 'total_qty'));
        $this->assertSame(['PN-1', 'PN-2'], $groups[0]['part_numbers']);
        $this->assertSame(['PN-3'], $groups[1]['part_numbers']);
        $this->assertSame('B1', $groups[0]['batch_label']);
        $this->assertSame(['Machining', 'NDT'], $groups[0]['processes']);
        $this->assertSame(['Machining', 'NDT', 'Passivation'], $groups[1]['processes']);
        $draft->update(['date_start' => '2026-09-02']);
        $groups = app(BushingSpecProcessGroups::class)->build($wo);
        $this->assertCount(3, $groups);
        $this->assertSame('B2', $groups[2]['batch_label']);
        $this->assertSame(['PN-4'], $groups[2]['part_numbers']);
    }

    public function test_thirteen_sent_batches_print_on_three_pages(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $process = $this->process('NDT-4');
        foreach (range(1, 13) as $i) {
            $part = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'PAGE-PN-'.$i, 'ipl_num' => '1-'.$i, 'name' => 'Bushing']);
            $line = WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id, 'component_id' => $part->id, 'qty' => 1, 'qty_remaining' => 1, 'sort_order' => $i]);
            $batch = WoBushingBatch::create(['workorder_id' => $wo->id, 'process_id' => $process->id, 'process_column_key' => 'ndt', 'date_start' => '2026-09-01']);
            WoBushingProcess::create(['wo_bushing_line_id' => $line->id, 'process_id' => $process->id, 'batch_id' => $batch->id, 'qty' => 1]);
        }
        $this->assertSame(3, app(BushingSpecProcessGroups::class)->pageCount($wo));
        $response = $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()])
            ->get(route('wo_bushings.specProcessForm', $bushing->id))->assertOk();
        $response->assertSee('Bush B13')->assertSee('PAGE-PN-13')->assertSee('4 of 4');
        $this->assertSame(3, substr_count($response->getContent(), '<div class="container-fluid" data-process-table-rows-max='));
        $this->assertSame(2, substr_count($response->getContent(), '<div style="page-break-after: always;"></div>'));
    }

    public function test_same_part_number_does_not_merge_different_historical_shipments(): void
    {
        $wo = $this->createWorkorder();
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $ndt = $this->process('NDT-4'); $cad = $this->process('Cad plate');
        $ndtBatch = WoBushingBatch::create(['workorder_id' => $wo->id, 'process_id' => $ndt->id,
            'process_column_key' => 'ndt', 'repair_order' => 'NDT-RO']);
        foreach ([1, 2] as $i) {
            $part = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'SAME-PN', 'ipl_num' => '1-'.$i, 'name' => 'Bushing']);
            $line = WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id,
                'component_id' => $part->id, 'qty' => 1, 'qty_remaining' => 1]);
            $cadBatch = WoBushingBatch::create(['workorder_id' => $wo->id, 'process_id' => $cad->id,
                'process_column_key' => 'cad', 'repair_order' => 'CAD-RO-'.$i]);
            foreach ([[$ndt, $ndtBatch], [$cad, $cadBatch]] as [$process, $batch]) {
                WoBushingProcess::create(['wo_bushing_line_id' => $line->id, 'process_id' => $process->id,
                    'batch_id' => $batch->id, 'qty' => 1]);
            }
        }
        // RO without date is history too; each physical set appears just once.
        $groups = app(BushingSpecProcessGroups::class)->build($wo);
        $this->assertCount(2, $groups);
        $this->assertSame([1, 1], array_column($groups, 'total_qty'));
        $this->assertSame([['SAME-PN'], ['SAME-PN']], array_column($groups, 'part_numbers'));
        $this->assertNotSame($groups[0]['components'][0]['line_id'], $groups[1]['components'][0]['line_id']);
    }

    private function process(string $name): Process
    {
        $processName = ProcessName::firstOrCreate(['name' => $name], ['process_sheet_name' => $name, 'form_number' => 'TEST']);
        return Process::create(['process_names_id' => $processName->id, 'process' => 'Test instruction']);
    }
}
