<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\WoBushing;
use App\Models\WoBushingLine;
use App\Models\WoBushingProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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

    private function attachProcessToManual(int $manualId, string $processName, string $processText): Process
    {
        $name = ProcessName::query()->firstOrCreate(
            ['name' => $processName],
            [
                'process_sheet_name' => 'NDT',
                'form_number' => 'NDT',
                'show_in_process_picker' => true,
            ]
        );

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
