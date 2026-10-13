<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ProcessName;
use App\Models\QuantumRoLine;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Ai\AiAgentService;
use App\Services\Ai\Tools\DiagnoseQuantumRoTool;
use App\Services\QuantumRoBufferApplyService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class AiQuantumRoDiagnosisTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_diagnosis_traces_rebuilt_traveler_with_actor_and_field_evidence_without_writes(): void
    {
        [$admin, $line, $old, $kept, $added] = $this->fixture();
        $queries = [];
        DB::listen(function ($query) use (&$queries) { $queries[] = $query->sql; });
        $result = app(DiagnoseQuantumRoTool::class)->run($admin, ['workorder_number' => $line->wo_number, 'ro_number' => 'R99374']);
        $this->assertTrue($result['ok']);
        $this->assertCount(1, $result['reports']);
        $report = $result['reports'][0];
        $this->assertSame(['tdr_processes:'.$old->id], $report['mapping']['removed_target_keys']);
        $this->assertSame(['tdr_processes:'.$added->id], $report['mapping']['added_target_keys']);
        $previous = collect($report['mapping']['previous_targets'])->firstWhere('id', $old->id);
        $this->assertSame(['date_start'], array_column($previous['differences'], 'field'));
        $this->assertSame('date_start', $report['mapping']['automatic_release_blockers'][0]['blocking_fields'][0]['field']);
        $this->assertTrue($report['mapping']['rebind']['available']);
        $this->assertFalse($report['admin_action']['agent_can_execute']);
        $this->assertArrayNotHasKey('token', $report['mapping']['rebind']);
        $ungroup = collect($report['audit']['events'])->firstWhere('event', 'traveler_ungrouped');
        $this->assertSame('Traveler Editor', $ungroup['actor']);
        $this->assertContains('date_start', $ungroup['cleared_fields']);
        $create = collect($report['audit']['events'])->last(fn ($row) => $row['event'] === 'traveler_created');
        $this->assertSame([$kept->id, $added->id], array_column($create['processes'], 'id'));
        foreach ($queries as $sql) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|create|drop)\b|for update/i', $sql);
        }
        $this->assertSame('R99374', $old->fresh()->repair_order);
        $this->assertSame('unresolved', $line->fresh()->apply_status);
    }

    public function test_applied_status_does_not_hide_target_drift_and_deleted_target_audit_is_retained(): void
    {
        [$admin, $line, $old] = $this->fixture();
        $line->update(['apply_status' => 'applied']);
        $report = app(DiagnoseQuantumRoTool::class)->run($admin, ['source_uid' => $line->source_uid])['reports'][0];
        $this->assertTrue($report['apply']['source_matches_last_attempt']);
        $this->assertNotEmpty($report['mapping']['previous_targets'][0]['differences']);
        $this->assertFalse($report['admin_action']['available']);
        $line->update(['apply_status' => 'unresolved']);
        $oldId = $old->id;
        $old->delete();
        $report = app(DiagnoseQuantumRoTool::class)->run($admin, ['source_uid' => $line->source_uid])['reports'][0];
        $this->assertFalse(collect($report['mapping']['previous_targets'])->firstWhere('id', $oldId)['exists']);
        $this->assertFalse($report['admin_action']['available']);
        $this->assertTrue(collect($report['audit']['events'])->contains('event', 'deleted'));
    }

    public function test_same_ro_details_are_not_merged_and_missing_history_is_not_invented(): void
    {
        [$admin, $line] = $this->fixture();
        $other = $line->replicate();
        $other->source_uid = 'rod:other:'.uniqid();
        $other->pn = 'OTHER-PN';
        $other->serial_number = 'OTHER-SN';
        $other->applied_targets = null;
        $other->applied_target_table = null;
        $other->applied_target_id = null;
        $other->returned_date = '2026-10-01';
        $other->save();
        $result = app(DiagnoseQuantumRoTool::class)->run($admin, ['ro_number' => 'R99374']);
        $this->assertSame(2, $result['total_source_rows']);
        $this->assertCount(2, $result['reports']);
        $second = collect($result['reports'])->first(fn ($r) => $r['source']['pn'] === 'OTHER-PN');
        $this->assertSame([], $second['mapping']['previous_targets']);
        $this->assertSame([], $second['audit']['events']);
        $this->assertFalse($second['admin_action']['available']);
        $this->assertNull($result['reports'][0]['source']['returned_date']);
        $filtered = app(DiagnoseQuantumRoTool::class)->run($admin, ['ro_number' => '99374', 'part_number' => 'OTHER-PN']);
        $this->assertSame(1, $filtered['total_source_rows']);
    }

    public function test_audit_pagination_can_reach_later_events_without_unrelated_workorder_logs(): void
    {
        [$admin, $line, $old] = $this->fixture();
        for ($i = 0; $i < 45; $i++) {
            Activity::query()->create(['log_name' => 'tdr_process', 'event' => 'updated', 'description' => 'test',
                'subject_type' => TdrProcess::class, 'subject_id' => $old->id, 'properties' => ['attributes' => ['repair_order' => 'R99374']]]);
        }
        Activity::query()->create(['log_name' => 'tdr_traveler', 'event' => 'unrelated', 'description' => 'other WO',
            'properties' => ['tdr_id' => 999999999, 'process_ids' => [$old->id]]]);
        $first = app(DiagnoseQuantumRoTool::class)->run($admin, ['source_uid' => $line->source_uid])['reports'][0]['audit'];
        $this->assertTrue($first['has_more']);
        $second = app(DiagnoseQuantumRoTool::class)->run($admin, ['source_uid' => $line->source_uid, 'audit_page' => $first['next_page']])['reports'][0]['audit'];
        $this->assertFalse($second['has_more']);
        $this->assertSame($first['total_events'], count($first['events']) + count($second['events']));
        $this->assertNotContains('unrelated', array_column(array_merge($first['events'], $second['events']), 'event'));
    }

    public function test_tool_and_agent_reject_roles_without_audit_privilege(): void
    {
        $tool = app(DiagnoseQuantumRoTool::class);
        foreach (['Manager', 'Technician'] as $role) {
            $this->assertFalse($tool->run($this->createUserWithRole($role), ['ro_number' => 'R99374'])['ok']);
        }
        $adminWithoutSystem = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $this->assertFalse($tool->run($adminWithoutSystem, ['ro_number' => 'R99374'])['ok']);
        Http::fake(['https://api.openai.com/v1/responses' => Http::response(['output_text' => 'No diagnostic access.'])]);
        app(AiAgentService::class)->handle($adminWithoutSystem, 'quantum-role-test', 'Why is R99374 unresolved?');
        Http::assertSent(fn ($request) => ! collect($request->data()['tools'])->contains('name', 'diagnoseQuantumRo'));
    }

    public function test_agent_dispatches_diagnosis_with_current_wo_even_when_question_contains_serial(): void
    {
        [$admin, $line] = $this->fixture();
        Http::fakeSequence()->push(['id' => 'quantum-test-1', 'output' => [[
            'type' => 'function_call', 'call_id' => 'quantum-call', 'name' => 'diagnoseQuantumRo', 'arguments' => '{}',
        ]]])->push(['id' => 'quantum-test-2', 'output_text' => 'Traveler membership changed; review Rebind.']);
        $reply = app(AiAgentService::class)->handle($admin, 'quantum-diagnostic-test',
            'Why is the current Quantum Traveler unresolved? S/N DIAG-SN.',
            ['current_workorder' => ['number' => preg_replace('/^W/', '', $line->wo_number)]]);
        $this->assertFalse($reply['requires_confirmation']);
        $this->assertSame('Traveler membership changed; review Rebind.', $reply['reply']);
        Http::assertSent(function ($request): bool {
            $prompt = data_get($request->data(), 'input.0.content', '');
            return collect($request->data()['tools'])->contains('name', 'diagnoseQuantumRo')
                && str_contains($prompt, 'rebuilding a local Traveler') && str_contains($prompt, 'Never execute Rebind');
        });
        Http::assertSent(function ($request) use ($line): bool {
            $outputs = collect($request->data()['input'])->where('type', 'function_call_output');
            return $outputs->contains(fn ($out) => str_contains($out['output'], $line->source_uid)
                && str_contains($out['output'], 'traveler_ungrouped'));
        });
    }

    private function fixture(): array
    {
        $admin = $this->createUserWithRole('Admin');
        $actor = $this->createUserWithRole('Technician', ['name' => 'Traveler Editor']);
        $wo = $this->createWorkorder();
        $vendor = Vendor::query()->create(['name' => 'Diagnosis Vendor '.uniqid()]);
        $component = Component::query()->create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'DIAG-'.uniqid(),
            'name' => 'Cylinder', 'ipl_num' => '1-1', 'eff_code' => 'ALL']);
        $tdr = Tdr::query()->create(['workorder_id' => $wo->id, 'component_id' => $component->id,
            'serial_number' => 'DIAG-SN', 'qty' => 1, 'use_tdr' => true, 'use_process_forms' => true]);
        $rows = collect(['Silver plating', 'NDT', 'Stress Relief'])->map(function ($name, $i) use ($tdr) {
            $pn = ProcessName::query()->create(['name' => $name.' '.uniqid(), 'process_sheet_name' => 'TEST', 'form_number' => '001']);
            return TdrProcess::query()->create(['tdrs_id' => $tdr->id, 'process_names_id' => $pn->id,
                'in_traveler' => $i < 2, 'traveler_group' => $i < 2 ? 1 : null, 'sort_order' => $i]);
        });
        [$old, $kept, $added] = $rows->all();
        $line = QuantumRoLine::query()->create(['source_uid' => 'rod:diag:'.uniqid(), 'ro_number' => 'R99374',
            'wo_number' => 'W'.$wo->number, 'vendor_name' => $vendor->name, 'pn' => $component->part_number,
            'serial_number' => 'DIAG-SN', 'class' => 'DETAIL_PART', 'bom_ref' => 'T1', 'out_date' => '2026-09-17',
            'source_last_modified' => '1990-01-01', 'source_hash' => hash('sha256', uniqid()), 'last_seen_at' => now()]);
        app(QuantumRoBufferApplyService::class)->apply(1);
        $this->assertSame('applied', $line->fresh()->apply_status);
        Activity::query()->create(['log_name' => 'tdr_traveler', 'event' => 'traveler_ungrouped', 'description' => 'Ungrouped',
            'causer_type' => User::class, 'causer_id' => $actor->id,
            'properties' => ['tdr_id' => $tdr->id, 'traveler_groups' => [1], 'process_ids' => [$old->id, $kept->id],
                'cleared_fields' => ['date_start'], 'before' => [['id' => $old->id, 'repair_order' => 'R99374', 'date_start' => '2026-09-17']]]]);
        $old->refresh()->update(['in_traveler' => false, 'traveler_group' => null, 'date_start' => null]);
        $kept->refresh()->update(['repair_order' => null, 'date_start' => null]);
        $added->update(['in_traveler' => true, 'traveler_group' => 1]);
        Activity::query()->create(['log_name' => 'tdr_traveler', 'event' => 'traveler_created', 'description' => 'Recreated',
            'causer_type' => User::class, 'causer_id' => $actor->id,
            'properties' => ['tdr_id' => $tdr->id, 'traveler_group' => 1, 'process_ids' => [$kept->id, $added->id], 'cleared_fields' => ['repair_order']]]);
        $line->refresh()->update(['apply_status' => 'pending']);
        app(QuantumRoBufferApplyService::class)->apply(1);
        return [$admin, $line->fresh(), $old->fresh(), $kept->fresh(), $added->fresh()];
    }
}
