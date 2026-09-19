<?php

namespace Tests\Feature;

use App\Models\ManualPartGroup;
use App\Models\ManualServiceBulletin;
use App\Models\RmReport;
use App\Models\StdProcess;
use App\Models\Unit;
use App\Services\WorkorderPartScopeResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class RmReportAssemblyModificationTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_selected_sb_conversion_keeps_received_scope_and_uses_modified_assy_parts(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => '32-11-15RM']);
        $receivedRoot = $this->createComponent($manual, ['part_number' => '2821A0200-02', 'ipl_num' => '13-1A']);
        $receivedPart = $this->createComponent($manual, ['part_number' => '2821-0221', 'ipl_num' => '13-180A', 'ndt_list' => true]);
        $modifiedRoot = $this->createComponent($manual, ['part_number' => '2821A0200-03', 'ipl_num' => '13-1B']);
        $modifiedPart = $this->createComponent($manual, ['part_number' => '2821-0222', 'ipl_num' => '13-180B', 'ndt_list' => true]);

        $receivedGroup = $this->createAssyGroup($manual->id, 'RECEIVED-02');
        $receivedOption = $receivedGroup->options()->create([
            'component_id' => $receivedRoot->id,
            'part_number' => '2821A0200-02',
            'ipl_num' => '13-1A',
            'option_kind' => ManualPartGroup::TYPE_ASSY,
            'is_default' => true,
        ]);
        $receivedOption->coverages()->create([
            'component_id' => $receivedPart->id,
            'qty' => 1,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);

        $modifiedGroup = $this->createAssyGroup($manual->id, 'MODIFIED-03');
        $modifiedOption = $modifiedGroup->options()->create([
            'component_id' => $modifiedRoot->id,
            'part_number' => '2821A0200-03',
            'ipl_num' => '13-1B',
            'option_kind' => ManualPartGroup::TYPE_ASSY,
            'is_default' => true,
        ]);
        $modifiedOption->coverages()->create([
            'component_id' => $modifiedPart->id,
            'qty' => 1,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);

        $bulletin = ManualServiceBulletin::query()->create([
            'manual_id' => $manual->id,
            'ac_mfg_service_bulletin_no' => 'SB 32-11-15-03',
            'description' => 'Convert -02 assembly to -03',
            'is_active' => true,
        ]);
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => '2821A0200-02',
            'default_scope_type' => Unit::SCOPE_PART_GROUP_OPTION,
            'default_scope_part_group_option_id' => $receivedOption->id,
        ]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get(route('rm_reports.partial', $workorder->id))
            ->assertOk()
            ->assertSee('name="manual_service_bulletin_id"', false)
            ->assertSee('name="source_assy_option_id"', false)
            ->assertSee('name="target_assy_option_id"', false)
            ->assertSeeText('2821A0200-02')
            ->assertSeeText('2821A0200-03');
        $this->actingAs($admin)
            ->get(route('rm_reports.show', $workorder->id))
            ->assertOk()
            ->assertSee('name="target_assy_option_id"', false);

        $createResponse = $this->actingAs($admin)->postJson(route('rm_reports.store'), [
            'workorder_id' => $workorder->id,
            'part_description' => 'Sliding Tube ASSY',
            'mod_repair' => 'SB',
            'mod_repair_description' => 'Incorporate SB and vibro peen new P/N',
            'ident_method' => 'Vibro peen',
            'manual_service_bulletin_id' => $bulletin->id,
            'source_assy_option_id' => $receivedOption->id,
            'target_assy_option_id' => $modifiedOption->id,
        ]);
        $createResponse->assertOk()
            ->assertJsonPath('data.changes_assembly_scope', true)
            ->assertJsonPath('data.target_assy_part_number', '2821A0200-03');

        $recordId = (int) $createResponse->json('data.id');
        $applyResponse = $this->actingAs($admin)->putJson(route('rm_reports.update', $workorder->id), [
            'workorder_id' => $workorder->id,
            'selected_records' => json_encode([$recordId]),
            'notes' => [],
        ]);
        $applyResponse->assertOk()
            ->assertJsonPath('modification.modified', '2821A0200-03')
            ->assertJsonPath('modification.target_option_id', $modifiedOption->id);

        $workorder->refresh();
        $this->assertSame(Unit::SCOPE_PART_GROUP_OPTION, $workorder->scope_type);
        $this->assertSame($receivedOption->id, (int) $workorder->scope_part_group_option_id);
        $this->assertSame('2821A0200-02', $workorder->unit->part_number);
        $this->assertSame('2821A0200-03', $workorder->modified);
        $this->assertSame($modifiedOption->id, (int) $workorder->modified_scope_part_group_option_id);
        $this->assertSame($recordId, (int) $workorder->modified_scope_rm_report_id);

        $this->actingAs($admin)
            ->put(route('workorders.update', $workorder), [
                'number' => $workorder->number,
                'unit_id' => $unit->id,
                'customer_id' => $workorder->customer_id,
                'instruction_id' => $workorder->instruction_id,
                'user_id' => $admin->id,
                'open_at' => now()->format('d/M/Y'),
                'scope_type' => 'part_assembly',
                'scope_target_id' => 'part_group_option:'.$receivedOption->id,
                'modified' => 'TAMPERED-PN',
            ])
            ->assertRedirect(route('workorders.index'))
            ->assertSessionHasNoErrors();
        $this->assertSame('2821A0200-03', $workorder->fresh()->modified);
        $this->actingAs($admin)
            ->get(route('workorders.edit', $workorder))
            ->assertOk()
            ->assertSee('id="modified"', false)
            ->assertSee('readonly', false)
            ->assertSeeText('Controlled by the selected R&M Service Bulletin conversion.');

        $quantities = app(WorkorderPartScopeResolver::class)->componentQuantities($workorder);
        $this->assertSame([
            $modifiedRoot->id => 1,
            $modifiedPart->id => 1,
        ], $quantities);
        $this->assertArrayNotHasKey($receivedPart->id, $quantities);
        $this->assertSame(
            ['2821-0222'],
            array_column(StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT), 'part_number')
        );

        $pickerResponse = $this->actingAs($admin)->getJson(route('api.get-components-by-manual', [
            'manual_id' => $manual->id,
            'workorder_id' => $workorder->id,
        ]));
        $pickerResponse->assertOk();
        $pickerPartNumbers = collect($pickerResponse->json('components'))->pluck('part_number')->all();
        $this->assertContains('2821-0222', $pickerPartNumbers);
        $this->assertNotContains('2821-0221', $pickerPartNumbers);

        $this->actingAs($admin)->putJson(route('rm_reports.update', $workorder->id), [
            'workorder_id' => $workorder->id,
            'selected_records' => json_encode([]),
            'notes' => [],
        ])->assertOk()
            ->assertJsonPath('modification.target_option_id', null);

        $workorder->refresh();
        $this->assertNull($workorder->modified_scope_part_group_option_id);
        $this->assertNull($workorder->modified_scope_rm_report_id);
        $this->assertNull($workorder->modified);
        $this->assertArrayHasKey($receivedPart->id, app(WorkorderPartScopeResolver::class)->componentQuantities($workorder));
    }

    public function test_conversion_requires_complete_same_manual_sb_and_assy_mapping(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $otherManual = $this->createManual();
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $this->createUnit(['manual_id' => $manual->id])->id,
        ]);
        $source = $this->createAssyGroup($manual->id, 'SOURCE')->options()->create([
            'part_number' => 'ASSY-OLD',
            'option_kind' => ManualPartGroup::TYPE_ASSY,
        ]);
        $outsideTarget = $this->createAssyGroup($otherManual->id, 'OUTSIDE')->options()->create([
            'part_number' => 'ASSY-OUTSIDE',
            'option_kind' => ManualPartGroup::TYPE_ASSY,
        ]);
        $bulletin = ManualServiceBulletin::query()->create([
            'manual_id' => $manual->id,
            'ac_mfg_service_bulletin_no' => 'SB-VALIDATION',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->postJson(route('rm_reports.store'), [
            'workorder_id' => $workorder->id,
            'part_description' => 'Assembly',
            'mod_repair' => 'SB',
            'mod_repair_description' => 'Invalid cross-manual conversion',
            'manual_service_bulletin_id' => $bulletin->id,
            'source_assy_option_id' => $source->id,
            'target_assy_option_id' => $outsideTarget->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('source_assy_option_id');

        $this->actingAs($admin)->postJson(route('rm_reports.store'), [
            'workorder_id' => $workorder->id,
            'part_description' => 'Assembly',
            'mod_repair' => 'Repair',
            'mod_repair_description' => 'Ordinary repair remains valid',
        ])->assertOk()
            ->assertJsonPath('data.changes_assembly_scope', false);
    }

    public function test_active_conversion_cannot_be_deleted_until_deselected(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $source = $this->createAssyGroup($manual->id, 'DELETE-SOURCE')->options()->create([
            'part_number' => 'DELETE-OLD',
            'option_kind' => ManualPartGroup::TYPE_ASSY,
        ]);
        $target = $this->createAssyGroup($manual->id, 'DELETE-TARGET')->options()->create([
            'part_number' => 'DELETE-NEW',
            'option_kind' => ManualPartGroup::TYPE_ASSY,
        ]);
        $bulletin = ManualServiceBulletin::query()->create([
            'manual_id' => $manual->id,
            'ac_mfg_service_bulletin_no' => 'SB-DELETE',
            'is_active' => true,
        ]);
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => 'DELETE-OLD',
            'default_scope_type' => Unit::SCOPE_PART_GROUP_OPTION,
            'default_scope_part_group_option_id' => $source->id,
        ]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $report = RmReport::query()->create([
            'manual_id' => $manual->id,
            'manual_service_bulletin_id' => $bulletin->id,
            'source_assy_option_id' => $source->id,
            'target_assy_option_id' => $target->id,
            'part_description' => 'Assembly',
            'mod_repair' => 'SB',
            'description' => 'Conversion',
        ]);

        $this->actingAs($admin)->putJson(route('rm_reports.update', $workorder->id), [
            'workorder_id' => $workorder->id,
            'selected_records' => json_encode([$report->id]),
            'notes' => [],
        ])->assertOk();

        $this->actingAs($admin)->deleteJson(route('rm_reports.destroy', $report), [
            'workorder_id' => $workorder->id,
        ])->assertUnprocessable();
        $this->assertDatabaseHas('rm_reports', ['id' => $report->id]);
    }

    private function createAssyGroup(int $manualId, string $code): ManualPartGroup
    {
        return ManualPartGroup::query()->create([
            'manual_id' => $manualId,
            'code' => $code.'-'.uniqid(),
            'name' => $code,
            'behavior' => ManualPartGroup::BEHAVIOR_BUNDLE,
            'type' => ManualPartGroup::TYPE_ASSY,
            'applies_to' => ManualPartGroup::validScopes(),
        ]);
    }
}
