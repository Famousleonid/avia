<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class EcPageTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_ec_page_renders_ec_process_rows_as_listing_table(): void
    {
        $user = $this->createUserWithRole('Technician', ['ec_access' => true]);
        $plane = $this->createPlane(['type' => 'ATR72']);
        $manual = $this->createManual([
            'unit_name_training' => 'CRJ700/900',
            'planes_id' => $plane->id,
        ]);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'number' => 104703,
            'unit_id' => $unit->id,
        ]);
        $component = Component::query()->create([
            'name' => 'Axle',
            'part_number' => '46108-5',
            'assy_part_number' => 'SHOULD-NOT-SHOW',
            'ipl_num' => '1',
            'eff_code' => 'DASH-8',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'EC',
            'process_sheet_name' => 'EC',
            'form_number' => 'EC',
        ]);

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'date_start' => '2022-11-10',
            'date_finish' => '2022-11-14',
            'notes' => 'Check bore before release.',
            'ec' => true,
        ]);

        $response = $this->actingAs($user)->get(route('ec.index', ['show_all' => 1]));

        $response->assertOk();
        $response->assertSee('1 rows');
        $response->assertSee('Show all');
        $response->assertSee('Repair / Modification Part Description');
        $response->assertSee('Approval No.');
        $response->assertSee('aria-label="Approval No. for WO 104703"', false);
        $response->assertSee('Axle');
        $response->assertSee('CRJ700/900');
        $response->assertDontSee('SHOULD-NOT-SHOW');
        $response->assertSee('46108-5');
        $response->assertSee('ATR72');
        $response->assertDontSee('DASH-8');
        $response->assertSee('10/Nov/2022');
        $response->assertSee('14/Nov/2022');
        $response->assertSee('104703');
        $response->assertSee('Check bore before release.');
    }

    public function test_ec_page_defaults_to_in_work_rows_and_show_all_includes_completed_rows(): void
    {
        $user = $this->createUserWithRole('Technician', ['ec_access' => true]);
        $unit = $this->createUnit();
        $processName = ProcessName::query()->create([
            'name' => 'EC',
            'process_sheet_name' => 'EC',
            'form_number' => 'EC',
        ]);

        $openWorkorder = $this->createWorkorder([
            'number' => 130001,
            'unit_id' => $unit->id,
        ]);
        $openComponent = Component::query()->create([
            'name' => 'Open EC Part',
            'part_number' => 'OPEN-EC',
            'ipl_num' => 'OPEN',
        ]);
        $openTdr = Tdr::query()->create([
            'workorder_id' => $openWorkorder->id,
            'component_id' => $openComponent->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $openTdr->id,
            'process_names_id' => $processName->id,
            'date_start' => '2026-05-09',
        ]);

        $completedWorkorder = $this->createWorkorder([
            'number' => 130002,
            'unit_id' => $unit->id,
        ]);
        $completedComponent = Component::query()->create([
            'name' => 'Completed EC Part',
            'part_number' => 'DONE-EC',
            'ipl_num' => 'DONE',
        ]);
        $completedTdr = Tdr::query()->create([
            'workorder_id' => $completedWorkorder->id,
            'component_id' => $completedComponent->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $completedTdr->id,
            'process_names_id' => $processName->id,
            'date_start' => '2026-05-01',
            'date_finish' => '2026-05-02',
        ]);

        $inWorkResponse = $this->actingAs($user)->get(route('ec.index'));
        $inWorkResponse->assertOk();
        $inWorkResponse->assertSee('OPEN-EC');
        $inWorkResponse->assertDontSee('DONE-EC');

        $allResponse = $this->actingAs($user)->get(route('ec.index', ['show_all' => 1]));
        $allResponse->assertOk();
        $allResponse->assertSee('OPEN-EC');
        $allResponse->assertSee('DONE-EC');
    }

    public function test_ec_page_loads_next_rows_for_infinite_scroll(): void
    {
        $user = $this->createUserWithRole('Technician', ['ec_access' => true]);
        $manual = $this->createManual(['unit_name_training' => 'AUTOLOAD-UNIT']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $processName = ProcessName::query()->create([
            'name' => 'EC',
            'process_sheet_name' => 'EC',
            'form_number' => 'EC',
        ]);

        for ($i = 1; $i <= 101; $i++) {
            $workorder = $this->createWorkorder([
                'number' => 120000 + $i,
                'unit_id' => $unit->id,
            ]);
            $component = Component::query()->create([
                'name' => 'EC Component ' . $i,
                'part_number' => 'PN-' . $i,
                'ipl_num' => 'IPL-' . $i,
            ]);
            $tdr = Tdr::query()->create([
                'workorder_id' => $workorder->id,
                'component_id' => $component->id,
                'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            ]);

            TdrProcess::query()->create([
                'tdrs_id' => $tdr->id,
                'process_names_id' => $processName->id,
                'date_finish' => '2022-11-14',
                'ec' => true,
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->getJson(route('ec.index', ['page' => 2]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk();
        $response->assertJsonStructure(['html', 'next_page_url']);
        $response->assertJsonFragment(['next_page_url' => null]);
        $this->assertStringContainsString('AUTOLOAD-UNIT', $response->json('html'));
    }

    public function test_ec_page_shows_only_ec_process_rows_and_excludes_machining_ec(): void
    {
        $user = $this->createUserWithRole('Technician', ['ec_access' => true]);
        $manual = $this->createManual(['unit_name_training' => 'DATE-SOURCE-UNIT']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'number' => 100501,
            'unit_id' => $unit->id,
        ]);
        $component = Component::query()->create([
            'name' => 'BRACKET',
            'part_number' => '66280',
            'ipl_num' => '1',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
        ]);
        $machiningEcName = ProcessName::query()->create([
            'name' => 'Machining (EC)',
            'process_sheet_name' => 'MACHINING',
            'form_number' => '018',
        ]);
        $ecName = ProcessName::query()->create([
            'name' => 'EC',
            'process_sheet_name' => 'EC',
            'form_number' => 'EC',
        ]);

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $machiningEcName->id,
            'processes' => [287],
            'date_start' => '2026-05-01',
            'ec' => true,
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $ecName->id,
            'processes' => [287],
            'date_start' => '2026-05-09',
            'ec' => false,
        ]);

        $response = $this->actingAs($user)->get(route('ec.index'));

        $response->assertOk();
        $response->assertSee('09/May/2026');
        $response->assertDontSee('01/May/2026');
        $this->assertSame(1, substr_count($response->getContent(), '66280'));
    }
}
