<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\WoBushing;
use App\Models\WoBushingBatch;
use App\Models\WoBushingLine;
use App\Models\WoBushingProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WoBushingSortingTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_bushing_tab_lists_is_bush_components_by_natural_ipl_before_grouping(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;

        foreach ([
            ['ipl_num' => '6-500', 'part_number' => 'PN-6500', 'bush_ipl_num' => 'GRP-B'],
            ['ipl_num' => '6-490', 'part_number' => 'PN-6490', 'bush_ipl_num' => 'GRP-A'],
            ['ipl_num' => '9A-300', 'part_number' => 'PN-9A300', 'bush_ipl_num' => 'GRP-D'],
            ['ipl_num' => '9A-30', 'part_number' => 'PN-9A030', 'bush_ipl_num' => 'GRP-C'],
            ['ipl_num' => '6-470', 'part_number' => 'NOT-BUSH', 'bush_ipl_num' => 'GRP-Z', 'is_bush' => false],
        ] as $row) {
            Component::query()->create([
                'manual_id' => $manualId,
                'ipl_num' => $row['ipl_num'],
                'part_number' => $row['part_number'],
                'name' => 'Bushing '.$row['ipl_num'],
                'bush_ipl_num' => $row['bush_ipl_num'],
                'is_bush' => $row['is_bush'] ?? true,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('wo_bushings.partial', $workorder->id));

        $response->assertOk();
        $response->assertSeeInOrder(['6-490', '6-500', '9A-30', '9A-300'], false);
        $response->assertDontSee('NOT-BUSH', false);
        $response->assertSee('data-itemized="1"', false);
        $response->assertSee('dir-table bushing-create-table bushing-itemized-table', false);
        $response->assertSee('bushing-col-process', false);
        $response->assertSee('bushing-col-ndt', false);
        $response->assertSee('>Qty</th>', false);
        $response->assertDontSee('WO Qty', false);
        $response->assertSee('group_bushings[GRP-A][items]', false);
        $response->assertDontSee('[components][]', false);
    }

    public function test_update_can_save_selected_bushing_without_processes_for_prl(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $component = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-230',
            'part_number' => '1840-0302',
            'name' => 'Bushing without process route',
            'bush_ipl_num' => '8-230',
            'is_bush' => true,
            'units_assy' => 2,
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->put(route('wo_bushings.update', $woBushing->id), [
                'group_bushings' => [
                    '8-230' => [
                        'items' => [
                            $component->id => [
                                'selected' => '1',
                                'qty' => '2',
                                'need_processes' => '0',
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $line = WoBushingLine::query()
            ->where('wo_bushing_id', $woBushing->id)
            ->where('component_id', $component->id)
            ->first();

        $this->assertNotNull($line);
        $this->assertSame(2, $line->qty);
        $this->assertSame(0, WoBushingProcess::query()->where('wo_bushing_line_id', $line->id)->count());
    }

    public function test_bushing_create_save_is_logged_on_workorder(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;

        $component = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-240',
            'part_number' => '1840-0402',
            'name' => 'Logged bushing',
            'bush_ipl_num' => '8-240',
            'is_bush' => true,
            'units_assy' => 2,
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('wo_bushings.store'), [
                'workorder_id' => $workorder->id,
                'group_bushings' => [
                    '8-240' => [
                        'items' => [
                            $component->id => [
                                'selected' => '1',
                                'qty' => '2',
                                'need_processes' => '0',
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('line_count', 1)
            ->assertJsonPath('process_count', 0);

        $woBushing = WoBushing::query()
            ->where('workorder_id', $workorder->id)
            ->first();

        $this->assertNotNull($woBushing);

        $line = WoBushingLine::query()
            ->where('wo_bushing_id', $woBushing->id)
            ->where('component_id', $component->id)
            ->first();

        $this->assertNotNull($line);
        $this->assertSame(2, $line->qty);
        $this->assertSame(0, WoBushingProcess::query()->where('wo_bushing_line_id', $line->id)->count());

        $activity = Activity::query()
            ->where('log_name', 'workorder')
            ->where('subject_type', $workorder::class)
            ->where('subject_id', $workorder->id)
            ->where('description', 'Bushing data created')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $props = $activity->properties->toArray();
        $this->assertSame('wo_bushings', $props['source'] ?? null);
        $this->assertSame('success', $props['status'] ?? null);
        $this->assertSame(1, $props['snapshot_after']['line_count'] ?? null);
        $this->assertSame(2, $props['snapshot_after']['total_qty'] ?? null);
        $this->assertSame('1840-0402', $props['snapshot_after']['rows'][0]['part_number'] ?? null);
        $this->assertArrayHasKey('bushing_save', $props['attributes'] ?? []);

        $this->actingAs($admin)
            ->get(route('workorders.logs-json', $workorder))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Bushing Save']);
    }

    public function test_edit_bushing_form_limits_ndt_choices_to_ndt_1_and_ndt_4(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-230',
            'part_number' => '1840-0302',
            'name' => 'Bushing',
            'bush_ipl_num' => '8-230',
            'is_bush' => true,
        ]);

        $this->attachProcessToManual($manualId, 'NDT-1', 'NDT one');
        $this->attachProcessToManual($manualId, 'NDT-4', 'NDT four');
        $this->attachProcessToManual($manualId, 'NDT-7', 'NDT seven');

        $response = $this->actingAs($admin)->get(route('wo_bushings.edit', [
            'wo_bushing' => $woBushing->id,
            'fragment' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('NDT-1', false);
        $response->assertSee('NDT-4', false);
        $response->assertDontSee('NDT-7', false);
    }

    public function test_bushing_process_form_uses_batches_and_b_labels_only(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $processName = ProcessName::query()->firstOrCreate(
            ['name' => 'Cad plate'],
            [
                'process_sheet_name' => 'CAD',
                'form_number' => '014',
                'print_form' => true,
                'show_in_process_picker' => true,
            ]
        );
        $processName->forceFill(['print_form' => true])->save();
        $process = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'CAD plating',
        ]);
        ManualProcess::query()->create([
            'manual_id' => $manualId,
            'processes_id' => $process->id,
        ]);

        $batchedComponent = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-230',
            'part_number' => 'BATCH-PN',
            'name' => 'Batched bushing',
            'bush_ipl_num' => '8-230',
            'is_bush' => true,
        ]);
        $looseComponent = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-240',
            'part_number' => 'LOOSE-PN',
            'name' => 'Loose bushing',
            'bush_ipl_num' => '8-240',
            'is_bush' => true,
        ]);
        $secondBatchedComponent = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-250',
            'part_number' => 'BATCH2-PN',
            'name' => 'Second batched bushing',
            'bush_ipl_num' => '8-250',
            'is_bush' => true,
        ]);

        $batchedLine = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $batchedComponent->id,
            'qty' => 2,
            'qty_remaining' => 2,
            'group_key' => '8-230',
            'sort_order' => 1,
        ]);
        $looseLine = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $looseComponent->id,
            'qty' => 1,
            'qty_remaining' => 1,
            'group_key' => '8-240',
            'sort_order' => 2,
        ]);
        $secondBatchedLine = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $secondBatchedComponent->id,
            'qty' => 1,
            'qty_remaining' => 1,
            'group_key' => '8-250',
            'sort_order' => 3,
        ]);
        $batch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $process->id,
            'process_column_key' => 'cad',
        ]);
        $secondBatch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $process->id,
            'process_column_key' => 'cad',
        ]);

        WoBushingProcess::query()->create([
            'wo_bushing_line_id' => $batchedLine->id,
            'process_id' => $process->id,
            'batch_id' => $batch->id,
            'qty' => 2,
        ]);
        WoBushingProcess::query()->create([
            'wo_bushing_line_id' => $secondBatchedLine->id,
            'process_id' => $process->id,
            'batch_id' => $secondBatch->id,
            'qty' => 1,
        ]);
        WoBushingProcess::query()->create([
            'wo_bushing_line_id' => $looseLine->id,
            'process_id' => $process->id,
            'qty' => 1,
        ]);

        $partial = $this->actingAs($admin)->get(route('wo_bushings.partial', $workorder->id));
        $partial->assertOk();
        $partial->assertSee('B1', false);
        $partial->assertDontSee('Grp', false);
        $partial->assertSee(route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $processName->id]), false);
        $partial->assertDontSee('bushing_component_ids[]=', false);

        $processFormWithoutSelection = $this->actingAs($admin)->get(route('wo_bushings.processesForm', [
            'id' => $woBushing->id,
            'processNameId' => $processName->id,
        ]));
        $processFormWithoutSelection->assertOk();
        $processFormWithoutSelection->assertDontSee('BATCH-PN', false);
        $processFormWithoutSelection->assertDontSee('LOOSE-PN', false);

        $processForm = $this->actingAs($admin)->get(route('wo_bushings.processesForm', [
            'id' => $woBushing->id,
            'processNameId' => $processName->id,
            'bushing_batch_ids' => [$batch->id],
        ]));
        $processForm->assertOk();
        $processForm->assertSee('B1', false);
        $processForm->assertSee('BATCH-PN', false);
        $processForm->assertDontSee('LOOSE-PN', false);
        $processForm->assertDontSee('BATCH2-PN', false);

        $processFormWithTwoBatches = $this->actingAs($admin)->get(route('wo_bushings.processesForm', [
            'id' => $woBushing->id,
            'processNameId' => $processName->id,
            'bushing_batch_ids' => [$batch->id, $secondBatch->id],
        ]));
        $processFormWithTwoBatches->assertOk();
        $processFormWithTwoBatches->assertSee('BATCH-PN', false);
        $processFormWithTwoBatches->assertDontSee('BATCH2-PN', false);

        $form = $this->actingAs($admin)->get(route('wo_bushings.specProcessForm', $woBushing->id));
        $form->assertOk();
        $form->assertDontSee('B1', false);
        $form->assertSee('BATCH-PN', false);
        $form->assertSee('LOOSE-PN', false);
        $form->assertSee('BATCH2-PN', false);
    }

    public function test_bushing_spec_process_form_prints_part_number_without_qty_across_batches(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $machining = $this->attachProcessToManual($manualId, 'Machining', 'Machine bushings');
        $ndt = $this->attachProcessToManual($manualId, 'NDT-1', 'NDT bushings');
        $cad = $this->attachProcessToManual($manualId, 'Cad plate', 'CAD plating');

        $component = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '1-561',
            'part_number' => 'MERGED-PN',
            'name' => 'Grouped bushing',
            'bush_ipl_num' => '1-561',
            'is_bush' => true,
        ]);
        $line = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'qty' => 2,
            'qty_remaining' => 2,
            'group_key' => '1-561',
            'sort_order' => 1,
        ]);

        foreach ([
            ['process' => $machining, 'key' => 'machining'],
            ['process' => $ndt, 'key' => 'ndt'],
            ['process' => $cad, 'key' => 'cad'],
        ] as $row) {
            $batch = WoBushingBatch::query()->create([
                'workorder_id' => $workorder->id,
                'process_id' => $row['process']->id,
                'process_column_key' => $row['key'],
            ]);

            WoBushingProcess::query()->create([
                'wo_bushing_line_id' => $line->id,
                'process_id' => $row['process']->id,
                'batch_id' => $batch->id,
                'qty' => 2,
            ]);
        }

        $form = $this->actingAs($admin)->get(route('wo_bushings.specProcessForm', $woBushing->id));

        $form->assertOk();
        $html = $form->getContent();
        $this->assertSame(1, substr_count($html, 'MERGED-PN'));
        $this->assertStringContainsString('>MERGED-PN</div>', $html);
        $this->assertStringContainsString('QTY: 2', $html);
        $this->assertStringNotContainsString('MERGED-PN : 2', $html);
        $this->assertStringNotContainsString('MERGED-PN : 6', $html);
        $this->assertStringNotContainsString('spec-group-label-box">B1</span>', $html);
    }

    public function test_bushing_spec_process_form_groups_six_part_numbers_per_cell_by_process_route(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $machining = $this->attachProcessToManual($manualId, 'Machining', 'Machine bushings');

        foreach (range(1, 7) as $index) {
            $component = Component::query()->create([
                'manual_id' => $manualId,
                'ipl_num' => '9-'.$index,
                'part_number' => sprintf('PACK-PN-%02d', $index),
                'name' => 'Packed bushing '.$index,
                'bush_ipl_num' => '9-'.$index,
                'is_bush' => true,
            ]);

            $line = WoBushingLine::query()->create([
                'wo_bushing_id' => $woBushing->id,
                'workorder_id' => $workorder->id,
                'component_id' => $component->id,
                'qty' => 1,
                'qty_remaining' => 1,
                'group_key' => '9-'.$index,
                'sort_order' => $index,
            ]);

            WoBushingProcess::query()->create([
                'wo_bushing_line_id' => $line->id,
                'process_id' => $machining->id,
                'qty' => 1,
            ]);
        }

        $form = $this->actingAs($admin)->get(route('wo_bushings.specProcessForm', $woBushing->id));

        $form->assertOk();
        $html = $form->getContent();

        $this->assertSame(2, substr_count($html, 'spec-part-no-row'));
        $this->assertStringContainsString('data-process-table-rows-max="13"', $html);
        $this->assertStringContainsString('PACK-PN-01', $html);
        $this->assertStringContainsString('PACK-PN-06', $html);
        $this->assertStringContainsString('PACK-PN-07', $html);
        $this->assertStringContainsString('QTY: 7', $html);
        $this->assertStringNotContainsString('PACK-PN-01 : ', $html);
    }

    public function test_bushing_spec_process_print_settings_allow_fourteen_process_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $response = $this->actingAs($admin)->get(route('wo_bushings.specProcessForm', $woBushing->id));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('id="processTableRows" name="processTableRows"', $html);
        $this->assertStringContainsString('max="14"', $html);
        $this->assertStringContainsString('const PROCESS_TABLE_ROWS_MAX = 14;', $html);
        $this->assertStringContainsString('clampNumber(merged.processTableRows, 1, PROCESS_TABLE_ROWS_MAX, 14)', $html);
        $this->assertSame(7, substr_count($html, 'spec-extra-process-row'));
    }

    public function test_bushing_spec_process_form_ignores_batch_when_grouping_same_part_number(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $machining = $this->attachProcessToManual($manualId, 'Machining', 'Machine bushings');
        $ndt = $this->attachProcessToManual($manualId, 'NDT-4', 'NDT bushings');
        $cad = $this->attachProcessToManual($manualId, 'Cad plate', 'CAD plating');

        $first = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-231',
            'part_number' => '1840-0302RS01',
            'name' => 'First bushing',
            'bush_ipl_num' => '8-230',
            'is_bush' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-361',
            'part_number' => '1840-0302RS01',
            'name' => 'Second bushing',
            'bush_ipl_num' => '8-360',
            'is_bush' => true,
        ]);

        $firstLine = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $first->id,
            'qty' => 2,
            'qty_remaining' => 2,
            'group_key' => '8-230',
            'sort_order' => 1,
        ]);
        $secondLine = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $second->id,
            'qty' => 2,
            'qty_remaining' => 2,
            'group_key' => '8-360',
            'sort_order' => 2,
        ]);

        $machiningBatch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $machining->id,
            'process_column_key' => 'machining',
        ]);
        $firstNdtBatch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $ndt->id,
            'process_column_key' => 'ndt',
        ]);
        $secondNdtBatch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $ndt->id,
            'process_column_key' => 'ndt',
        ]);

        foreach ([
            [$firstLine, $machining, $machiningBatch],
            [$secondLine, $machining, $machiningBatch],
            [$firstLine, $ndt, $firstNdtBatch],
            [$secondLine, $ndt, $secondNdtBatch],
            [$firstLine, $cad, null],
            [$secondLine, $cad, null],
        ] as [$line, $process, $batch]) {
            WoBushingProcess::query()->create([
                'wo_bushing_line_id' => $line->id,
                'process_id' => $process->id,
                'batch_id' => $batch?->id,
                'qty' => 2,
            ]);
        }

        $form = $this->actingAs($admin)->get(route('wo_bushings.specProcessForm', $woBushing->id));

        $form->assertOk();
        $html = $form->getContent();
        $this->assertSame(1, substr_count($html, '1840-0302RS01'));
        $this->assertStringContainsString('QTY: 4', $html);
        $this->assertStringNotContainsString('1840-0302RS01 : 4', $html);
        $this->assertStringNotContainsString('1840-0302RS01 : 2', $html);
    }

    private function attachProcessToManual(int $manualId, string $processName, string $processText): Process
    {
        $name = ProcessName::query()->firstOrCreate(
            ['name' => $processName],
            [
                'process_sheet_name' => 'NDT',
                'form_number' => 'NDT',
                'print_form' => true,
                'show_in_process_picker' => true,
            ]
        );
        $name->forceFill(['print_form' => true])->save();

        $process = Process::query()->create([
            'process_names_id' => $name->id,
            'process' => $processText,
        ]);

        ManualProcess::query()->create([
            'manual_id' => $manualId,
            'processes_id' => $process->id,
        ]);

        return $process;
    }
}
