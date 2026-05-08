<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ExtraProcess;
use App\Models\Paint;
use App\Models\Tdr;
use App\Models\WorkorderUnitInspection;
use App\Services\Ai\AiAgentService;
use App\Services\Ai\Tools\LookupSerialNumberTool;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class AiSerialLookupToolTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_lookup_serial_number_finds_workorders_across_app_sources(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $workorder = $this->createWorkorder(['number' => 123456, 'serial_number' => 'UNIT-SN-123']);
        $manual = $workorder->unit->manual;

        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'PN-123',
            'name' => 'Hydraulic Pump',
            'ipl_num' => '10-20',
            'eff_code' => 'ALL',
        ]);

        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'PART-SN-123',
            'assy_serial_number' => 'ASSY-SN-123',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        WorkorderUnitInspection::query()->create([
            'workorder_id' => $workorder->id,
            'serial_number' => 'UNIT-INSP-SN-123',
            'assy_serial_number' => '',
            'qty' => 1,
        ]);

        ExtraProcess::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_num' => 'EXTRA-SN-123',
            'processes' => [],
            'qty' => 1,
        ]);

        $tool = app(LookupSerialNumberTool::class);

        $tdrResult = $tool->run($admin, ['serial_number' => 'part-sn-123']);
        $this->assertTrue($tdrResult['ok']);
        $this->assertSame(1, $tdrResult['count']);
        $this->assertSame(123456, $tdrResult['matches'][0]['workorder_number']);
        $this->assertSame('PN-123', $tdrResult['matches'][0]['part_number']);

        $workorderResult = $tool->run($admin, ['serial_number' => ' UNIT-SN-123 ']);
        $this->assertTrue($workorderResult['ok']);
        $this->assertSame(123456, $workorderResult['matches'][0]['workorder_number']);

        $extraResult = $tool->run($admin, ['serial_number' => 'EXTRA-SN-123']);
        $this->assertTrue($extraResult['ok']);
        $this->assertSame('extra process part', $extraResult['matches'][0]['source']);
    }

    public function test_lookup_serial_number_reports_paint_records_without_workorder_link(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);

        Paint::query()->create([
            'user_id' => $admin->id,
            'part_number' => 'PAINT-PN',
            'serial_number' => 'PAINT-SN-777',
            'comment' => 'Lost painted part',
        ]);

        $result = app(LookupSerialNumberTool::class)->run($admin, [
            'serial_number' => 'PAINT-SN-777',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['count']);
        $this->assertNull($result['matches'][0]['workorder_number']);
        $this->assertSame('This source has no direct workorder link.', $result['matches'][0]['note']);
    }

    public function test_lookup_serial_number_finds_partial_matches_and_prioritizes_exact_matches(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $workorder = $this->createWorkorder(['number' => 777001, 'serial_number' => 'ABC-777-XYZ']);
        $manual = $workorder->unit->manual;

        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'PN-777',
            'name' => 'Partial Serial Part',
            'ipl_num' => '77-70',
            'eff_code' => 'ALL',
        ]);

        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => '777',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'PART-777-A',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $result = app(LookupSerialNumberTool::class)->run($admin, [
            'serial_number' => '777',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertGreaterThanOrEqual(3, $result['count']);
        $this->assertSame('777', $result['matches'][0]['serial_number']);
        $this->assertSame('exact', $result['matches'][0]['match_type']);
        $this->assertTrue(collect($result['matches'])->contains(fn (array $match) => $match['serial_number'] === 'ABC-777-XYZ' && $match['match_type'] === 'partial'));
        $this->assertTrue(collect($result['matches'])->contains(fn (array $match) => $match['serial_number'] === 'PART-777-A' && $match['match_type'] === 'partial'));
    }

    public function test_ai_agent_handles_serial_lookup_without_waiting_for_model_tool_choice(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $workorder = $this->createWorkorder(['number' => 654321]);
        $manual = $workorder->unit->manual;

        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'PN-333',
            'name' => 'Serial Test Part',
            'ipl_num' => '33-30',
            'eff_code' => 'ALL',
        ]);

        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => '333',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $result = app(AiAgentService::class)->handle(
            user: $admin,
            sessionKey: 'serial-test-session',
            userMessage: 'найди серийник 333',
            pageContext: [],
            confirmAction: []
        );

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('WO 654321', $result['reply']);
        $this->assertStringContainsString('Serial Test Part', $result['reply']);
        $this->assertStringContainsString('S/N 333', $result['reply']);
    }
}
