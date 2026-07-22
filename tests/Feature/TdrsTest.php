<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\Component;
use App\Models\ComponentAssembly;
use App\Models\Condition;
use App\Models\LogCard;
use App\Models\ManualProcess;
use App\Models\ManualIplBranchRule;
use App\Models\Necessary;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\PrintMark;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\UserUiSetting;
use App\Models\WoBushing;
use App\Models\WoBushingLine;
use App\Models\WorkorderUnitInspection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TdrsTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_admin_can_create_tdr_record_with_valid_payload(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'CMP-100',
            'name' => 'QA Component',
            'ipl_num' => '1-10',
        ]);
        $code = Code::query()->firstOrCreate(
            ['name' => 'Repairable'],
            ['code' => 'R']
        );
        $necessary = Necessary::query()->firstOrCreate([
            'name' => 'Repair',
        ]);

        $response = $this->actingAs($admin)->post(route('tdrs.store'), [
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'SN-TDR',
            'assy_serial_number' => 'ASSY-TDR',
            'codes_id' => $code->id,
            'conditions_id' => null,
            'necessaries_id' => $necessary->id,
            'qty' => 2,
            'description' => 'Created TDR',
            'order_component_id' => null,
        ]);

        $response->assertRedirect(route('tdrs.show', ['id' => $workorder->id]));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tdrs', [
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'codes_id' => $code->id,
            'necessaries_id' => $necessary->id,
            'serial_number' => 'SN-TDR',
            'qty' => 2,
        ]);
    }

    public function test_tdr_process_store_rejects_catalog_process_without_process_name(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'Guard Store Process ' . uniqid(),
            'process_sheet_name' => 'TEST',
            'form_number' => '000',
        ]);
        $orphanCatalogProcess = Process::query()->create([
            'process_names_id' => null,
            'process' => 'Orphan catalog process',
        ]);

        $response = $this->actingAs($admin)->postJson(route('tdr-processes.store'), [
            'tdrs_id' => $tdr->id,
            'processes' => [[
                'process_names_id' => $processName->id,
                'processes' => [$orphanCatalogProcess->id],
            ]],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('error', 'Selected process specification is missing a process name.');

        $this->assertDatabaseMissing('tdr_processes', [
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
        ]);
    }

    public function test_tdr_process_store_copies_fig_and_zone_requirements_from_manual_process(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'REQ-CMP-' . uniqid(),
            'name' => 'Requirement Component',
            'ipl_num' => '1-10',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'Requirement Process ' . uniqid(),
            'process_sheet_name' => 'TEST',
            'form_number' => '000',
        ]);
        $catalogProcess = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'Requirement specification',
        ]);
        ManualProcess::query()->create([
            'manual_id' => $component->manual_id,
            'processes_id' => $catalogProcess->id,
            'requires_fig' => true,
            'requires_zone' => true,
        ]);

        $payload = [
            'tdrs_id' => $tdr->id,
            'processes' => [[
                'process_names_id' => $processName->id,
                'processes' => [$catalogProcess->id],
                'description' => 'FIG. 6010',
            ]],
        ];

        $this->actingAs($admin)->postJson(route('tdr-processes.store'), $payload)
            ->assertStatus(422)
            ->assertJsonPath('incomplete_requirements.0.missing.0', 'ZONE');

        $response = $this->actingAs($admin)->postJson(route('tdr-processes.store'), $payload + [
            'confirm_incomplete_requirements' => true,
        ]);

        $response->assertOk();
        $tdrProcess = TdrProcess::query()->where('tdrs_id', $tdr->id)->firstOrFail();
        $this->assertTrue($tdrProcess->requires_fig);
        $this->assertTrue($tdrProcess->requires_zone);
        $this->assertSame(['ZONE'], $tdrProcess->missingDescriptionRequirements());
    }

    public function test_tdr_process_update_rejects_catalog_process_without_process_name(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'Guard Update Process ' . uniqid(),
            'process_sheet_name' => 'TEST',
            'form_number' => '000',
        ]);
        $validCatalogProcess = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'Valid catalog process',
        ]);
        $orphanCatalogProcess = Process::query()->create([
            'process_names_id' => null,
            'process' => 'Orphan catalog process',
        ]);
        $tdrProcess = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'processes' => [$validCatalogProcess->id],
        ]);

        $response = $this->actingAs($admin)->patchJson(route('tdr-processes.update', $tdrProcess), [
            'tdrs_id' => $tdr->id,
            'processes' => [[
                'process_names_id' => $processName->id,
                'process' => [$orphanCatalogProcess->id],
            ]],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('processes');

        $tdrProcess->refresh();
        $this->assertSame([$validCatalogProcess->id], TdrProcess::normalizeStoredProcessIds($tdrProcess->processes));
    }

    public function test_store_from_inspection_redirects_with_standard_errors_when_part_ipl_already_exists(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);
        $this->createComponent($manual, [
            'ipl_num' => '8-888',
            'part_number' => 'PN-EXIST',
            'name' => 'Existing Part',
        ]);

        $sourceUrl = route('tdrs.show', ['id' => $workorder->id]);

        $response = $this->actingAs($admin)
            ->from($sourceUrl)
            ->post(route('components.storeFromInspection'), [
                'name' => 'Duplicate Part',
                'manual_id' => $manual->id,
                'part_number' => 'PN-DUP',
                'ipl_num' => '8-888',
                'current_wo' => $workorder->id,
                'redirect' => $sourceUrl,
            ]);

        $response->assertRedirect($sourceUrl);
        $response->assertSessionHasErrors(['ipl_num']);
        $this->assertSame(
            1,
            Component::query()
                ->where('manual_id', $manual->id)
                ->where('ipl_num', '8-888')
                ->count()
        );
    }

    public function test_tdr_order_component_picker_marks_np_parts_and_store_rejects_them_for_missing_and_order_new(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);
        $inspectedComponent = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'TDR-INSP',
            'name' => 'Inspected Component',
            'ipl_num' => '1-10',
        ]);
        $npComponent = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'TDR-NP',
            'name' => 'NP Component',
            'ipl_num' => '1-20',
            'np' => true,
        ]);
        $missingCode = Code::query()->firstOrCreate(['name' => 'Missing'], ['code' => 'M']);
        $repairCode = Code::query()->firstOrCreate(['name' => 'Repairable'], ['code' => 'R']);
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);
        Condition::query()->firstOrCreate([
            'name' => 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST',
        ], [
            'unit' => false,
        ]);

        $componentsResponse = $this->actingAs($admin)->get(route('api.get-components-by-manual', [
            'manual_id' => $manual->id,
            'workorder_id' => $workorder->id,
            'exclude_kits' => 1,
        ]));

        $componentsResponse->assertOk();
        $npPayload = collect($componentsResponse->json('components'))
            ->firstWhere('id', $npComponent->id);
        $this->assertSame(true, $npPayload['np'] ?? null);

        $showResponse = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));
        $showResponse->assertOk();
        $this->assertStringContainsString('data-np="1"', $showResponse->getContent());

        $missingResponse = $this->actingAs($admin)->post(route('tdrs.store'), [
            'workorder_id' => $workorder->id,
            'component_id' => $inspectedComponent->id,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'codes_id' => $missingCode->id,
            'necessaries_id' => $orderNew->id,
            'qty' => 1,
            'description' => 'Missing NP order attempt',
            'order_component_id' => $npComponent->id,
        ]);
        $missingResponse->assertSessionHasErrors('order_component_id');

        $orderNewResponse = $this->actingAs($admin)->post(route('tdrs.store'), [
            'workorder_id' => $workorder->id,
            'component_id' => $inspectedComponent->id,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'codes_id' => $repairCode->id,
            'necessaries_id' => $orderNew->id,
            'qty' => 1,
            'description' => 'Order New NP order attempt',
            'order_component_id' => $npComponent->id,
        ]);
        $orderNewResponse->assertSessionHasErrors('order_component_id');

        $this->assertDatabaseMissing('tdrs', [
            'workorder_id' => $workorder->id,
            'order_component_id' => $npComponent->id,
        ]);
    }

    public function test_update_part_field_updates_po_num(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $tdr = Tdr::query()->create([
            'workorder_id' => $this->createWorkorder(['user_id' => $admin->id])->id,
            'serial_number' => 'SN-1',
            'qty' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('tdrs.updatePartField', $tdr->id), [
            'field' => 'po_num',
            'value' => 'PO-999',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $tdr->refresh();
        $this->assertSame('PO-999', $tdr->po_num);
    }

    public function test_non_system_admin_can_update_serial_number_but_cannot_replace_tdr_component_from_edit(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $workorder = $this->createWorkorder(['user_id' => $roleOnlyAdmin->id]);
        $firstComponent = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'PN-OLD',
            'name' => 'Old Component',
            'ipl_num' => '1-10',
        ]);
        $secondComponent = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'PN-NEW',
            'name' => 'New Component',
            'ipl_num' => '1-20',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $firstComponent->id,
            'serial_number' => 'SN-OLD',
            'description' => 'Before',
            'qty' => 1,
        ]);

        $response = $this->actingAs($roleOnlyAdmin)->put(route('tdrs.update', $tdr->id), [
            'workorder_id' => $workorder->id,
            'component_id' => $secondComponent->id,
            'serial_number' => 'SN-NEW',
            'description' => 'After',
        ]);

        $response->assertRedirect(route('tdrs.show', ['id' => $workorder->id]));

        $tdr->refresh();
        $this->assertSame($firstComponent->id, $tdr->component_id);
        $this->assertSame('SN-NEW', $tdr->serial_number);
        $this->assertSame('After', $tdr->description);
    }

    public function test_system_admin_can_replace_tdr_component_and_serial_number_from_edit(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $workorder = $this->createWorkorder(['user_id' => $systemAdmin->id]);
        $firstComponent = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'PN-OLD-SYS',
            'name' => 'Old System Component',
            'ipl_num' => '2-10',
        ]);
        $secondComponent = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'PN-NEW-SYS',
            'name' => 'New System Component',
            'ipl_num' => '2-20',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $firstComponent->id,
            'serial_number' => 'SN-SYS-OLD',
            'description' => 'Before',
            'qty' => 1,
        ]);

        $response = $this->actingAs($systemAdmin)->put(route('tdrs.update', $tdr->id), [
            'workorder_id' => $workorder->id,
            'component_id' => $secondComponent->id,
            'serial_number' => 'SN-SYS-NEW',
            'description' => 'After',
        ]);

        $response->assertRedirect(route('tdrs.show', ['id' => $workorder->id]));

        $tdr->refresh();
        $this->assertSame($secondComponent->id, $tdr->component_id);
        $this->assertSame('SN-SYS-NEW', $tdr->serial_number);
        $this->assertSame('After', $tdr->description);
    }

    public function test_get_components_by_manual_filters_parts_by_workorder_unit_ipl_branch_rule(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => '2802A0000-03',
        ]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);

        ManualIplBranchRule::query()->create([
            'manual_id' => $manual->id,
            'is_default' => true,
            'unit_match_value' => null,
            'include_prefix' => '9A-',
            'exclude_prefix' => '9-',
        ]);

        $hiddenComponent = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'PN-9',
            'name' => 'Hidden 9 dash part',
            'ipl_num' => '9-70',
        ]);
        $visibleNineAComponent = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'PN-9A',
            'name' => 'Visible 9A part',
            'ipl_num' => '9A-70',
        ]);
        $visibleOtherComponent = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'PN-10',
            'name' => 'Visible other part',
            'ipl_num' => '10-70',
        ]);

        $response = $this->actingAs($admin)->getJson(route('api.get-components-by-manual', [
            'manual_id' => $manual->id,
            'workorder_id' => $workorder->id,
        ]));

        $response->assertOk();

        $componentIds = collect($response->json('components'))->pluck('id')->all();

        $this->assertNotContains($hiddenComponent->id, $componentIds);
        $this->assertContains($visibleNineAComponent->id, $componentIds);
        $this->assertContains($visibleOtherComponent->id, $componentIds);
    }

    public function test_get_components_by_manual_filters_eff_mismatches_but_keeps_no_eff_parts_and_assemblies(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'eff_code' => 'R2',
        ]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);

        $left = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '6-190A',
            'part_number' => 'PN-LH',
            'name' => 'Eff Left Part',
            'eff_code' => 'L1, L2',
        ]);
        $right = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '6-190B',
            'part_number' => 'PN-RH',
            'name' => 'Eff Right Part',
            'eff_code' => 'R1, R2',
        ]);
        $universalSameBase = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => "6-190A\n6-190B",
            'part_number' => "PN-LH\nPN-RH",
            'name' => 'Universal Same Base',
        ]);
        $noEffA = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-240A',
            'part_number' => 'PN-240A',
            'name' => 'No Eff Letter Part',
        ]);
        $noEffB = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '8-240B',
            'part_number' => 'PN-240B',
            'name' => 'No Eff Letter Part',
        ]);

        $assembly = ComponentAssembly::query()->create([
            'component_id' => $noEffA->id,
            'assy_part_number' => 'ASSY-240A',
            'assy_ipl_num' => '8-240A-ASSY',
            'units_assy' => '1',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->getJson(route('api.get-components-by-manual', [
            'manual_id' => $manual->id,
            'workorder_id' => $workorder->id,
        ]));

        $response->assertOk();

        $components = collect($response->json('components'));
        $componentIds = $components->pluck('id')->all();

        $this->assertNotContains($left->id, $componentIds);
        $this->assertContains($right->id, $componentIds);
        $this->assertContains($universalSameBase->id, $componentIds);
        $this->assertContains($noEffA->id, $componentIds);
        $this->assertContains($noEffB->id, $componentIds);

        $noEffAResponse = $components->firstWhere('id', $noEffA->id);
        $this->assertSame($assembly->id, $noEffAResponse['assemblies'][0]['id'] ?? null);
        $this->assertSame('ASSY-240A', $noEffAResponse['assemblies'][0]['assy_part_number'] ?? null);
    }

    public function test_get_components_by_manual_keeps_no_eff_letter_variant_when_base_ipl_has_eff_code(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'eff_code' => 'ACE',
        ]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);

        $effBase = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '2-2160',
            'part_number' => '55201-101',
            'name' => 'Eff coded outer cylinder',
            'eff_code' => 'ACE',
        ]);
        $letterVariant = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '2-2160A',
            'part_number' => '55201-102',
            'name' => 'No eff letter outer cylinder',
        ]);

        $response = $this->actingAs($admin)->getJson(route('api.get-components-by-manual', [
            'manual_id' => $manual->id,
            'workorder_id' => $workorder->id,
        ]));

        $response->assertOk();

        $componentIds = collect($response->json('components'))->pluck('id')->all();

        $this->assertContains($effBase->id, $componentIds);
        $this->assertContains($letterVariant->id, $componentIds);
    }

    public function test_tdr_add_part_component_lists_sort_by_ipl_rule(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);

        foreach (['10-10', '2-10', '1-20A', '11-10', '1-20', '1-10'] as $ipl) {
            Component::query()->create([
                'manual_id' => $manual->id,
                'ipl_num' => $ipl,
                'part_number' => 'PN-'.$ipl,
                'name' => 'Part '.$ipl,
            ]);
        }

        $expectedOrder = ['1-10', '1-20', '1-20A', '2-10', '10-10', '11-10'];

        $apiResponse = $this->actingAs($admin)->getJson(route('api.get-components-by-manual', [
            'manual_id' => $manual->id,
            'workorder_id' => $workorder->id,
        ]));

        $apiResponse->assertOk();
        $this->assertSame($expectedOrder, collect($apiResponse->json('components'))->pluck('ipl_num')->all());

        $showResponse = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));
        $showResponse->assertOk();

        $content = $showResponse->getContent();
        $inlineSelect = $this->htmlBetween($content, 'id="tdr_inline_component_id"', '</select>');
        $modalSelect = $this->htmlBetween($content, 'id="i_component_id"', '</select>');

        foreach ([$inlineSelect, $modalSelect] as $selectHtml) {
            $previousPosition = -1;
            foreach ($expectedOrder as $ipl) {
                $position = strpos($selectHtml, 'data-ipl="'.$ipl.'"');
                $this->assertNotFalse($position, "IPL [{$ipl}] was not rendered in the add-part select.");
                $this->assertGreaterThan($previousPosition, $position, "IPL [{$ipl}] is not in IPL sort order.");
                $previousPosition = $position;
            }
        }
    }

    public function test_show_missing_and_ordered_parts_modals_sort_by_ipl_rule(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
            'part_missing' => true,
        ]);
        $missingCode = Code::query()->firstOrCreate(['name' => 'Missing'], ['code' => 'M']);
        $repairCode = Code::query()->firstOrCreate(['name' => 'Repair'], ['code' => 'R']);
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);
        $missingCondition = Condition::query()->firstOrCreate([
            'name' => 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST',
        ], [
            'unit' => false,
        ]);

        $components = [];
        foreach (['6-500', '6-490', '6-840', '6-830'] as $ipl) {
            $components[$ipl] = Component::query()->create([
                'manual_id' => $manual->id,
                'ipl_num' => $ipl,
                'part_number' => 'PN-'.$ipl,
                'name' => 'Part '.$ipl,
            ]);
        }

        foreach (['6-500', '6-490', '6-840', '6-830'] as $ipl) {
            Tdr::query()->create([
                'workorder_id' => $workorder->id,
                'component_id' => $components[$ipl]->id,
                'codes_id' => $missingCode->id,
                'conditions_id' => $missingCondition->id,
                'qty' => 1,
                'use_tdr' => true,
                'use_process_forms' => false,
            ]);
        }
        $components['6-500']->delete();

        foreach (['6-840', '6-500', '6-830', '6-490'] as $ipl) {
            Tdr::query()->create([
                'tdr_type' => Tdr::TYPE_ORDER_NEW,
                'workorder_id' => $workorder->id,
                'component_id' => $components[$ipl]->id,
                'order_component_id' => $components[$ipl]->id,
                'codes_id' => $repairCode->id,
                'necessaries_id' => $orderNew->id,
                'qty' => 1,
                'use_tdr' => true,
                'use_process_forms' => false,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));

        $response->assertOk();
        $content = $response->getContent();
        $missingModal = $this->htmlBetween($content, 'id="missingModal'.$workorder->number.'"', 'id="orderModal'.$workorder->number.'"');
        $orderedModal = $this->htmlBetween($content, 'id="orderModal'.$workorder->number.'"', 'id="pdfModal"');

        $this->assertStringContainsString('6-490', $missingModal);
        $this->assertStringContainsString('PN-6-500', $missingModal);
        $this->assertLessThan(strpos($missingModal, '6-500'), strpos($missingModal, '6-490'));
        $this->assertLessThan(strpos($missingModal, '6-830'), strpos($missingModal, '6-500'));
        $this->assertLessThan(strpos($missingModal, '6-840'), strpos($missingModal, '6-830'));

        $this->assertStringContainsString('6-490', $orderedModal);
        $this->assertLessThan(strpos($orderedModal, '6-500'), strpos($orderedModal, '6-490'));
        $this->assertLessThan(strpos($orderedModal, '6-830'), strpos($orderedModal, '6-500'));
        $this->assertLessThan(strpos($orderedModal, '6-840'), strpos($orderedModal, '6-830'));
    }

    public function test_log_card_partial_has_include_checkbox_only_before_creation(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LC-100',
            'name' => 'Log Card Component',
            'ipl_num' => '1-10',
            'log_card' => true,
        ]);
        $componentTwo = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LC-101',
            'name' => 'Second Log Card Component',
            'ipl_num' => '1-10A',
            'log_card' => true,
        ]);
        Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LC-200',
            'name' => 'Standalone Log Card Component',
            'ipl_num' => '2-10',
            'log_card' => true,
        ]);

        $draft = $this->actingAs($admin)->get(route('log_card.partial', $workorder->id));

        $draft->assertOk();
        $draft->assertSee('lc-include-toggle-all', false);
        $draft->assertDontSee('type="radio"', false);
        $draft->assertSee('name="lc_include[1-10]"', false);
        $draft->assertSee('name="lc_include[2-10]"', false);
        $draft->assertSee('value="'.$component->id.'"', false);
        $draft->assertSee('value="'.$componentTwo->id.'"', false);
        $draftContent = $draft->getContent();
        $this->assertDoesNotMatchRegularExpression(
            '/class="form-check-input lc-include-toggle-all"[^>]*\bchecked\b/s',
            $draftContent
        );
        $this->assertDoesNotMatchRegularExpression(
            '/class="form-check-input lc-include-checkbox"[^>]*\bchecked\b/s',
            $draftContent
        );

        $show = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));
        $show->assertOk();
        $show->assertSee('Create Log Card', false);
        $show->assertSee('Select at least one component for Log Card.', false);
        $show->assertSee('if (!saved) syncLogCardToolbarFromPartial();', false);
        $show->assertDontSee('Отметьте хотя бы один компонент для Log Card.', false);

        LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                [
                    'component_id' => $component->id,
                    'included' => '1',
                    'serial_number' => 'SN-LC',
                ],
            ]),
        ]);

        $saved = $this->actingAs($admin)->get(route('log_card.partial', $workorder->id));

        $saved->assertOk();
        $saved->assertDontSee('name="included"', false);
        $saved->assertDontSee('lc-include-toggle-all', false);

        $savedShow = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));
        $savedShow->assertOk();
        $savedShow->assertSee('Update Log Card', false);
        $savedShow->assertSee('Updating...', false);
        $savedShow->assertDontSee('Reset Log Card', false);
        $savedShow->assertDontSee('function logCardTabReset', false);
    }

    public function test_tdr_show_persists_measurements_tab_id(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '~var PERSISTENT_TAB_IDS = \[[\s\S]*\'tab-measurements\'[\s\S]*\];~',
            $response->getContent()
        );
        $response->assertSee('function showRestoredTabBeforeReveal(tabButton)', false);
        $response->assertSee("tabButton.addEventListener('shown.bs.tab', done, { once: true });", false);
        $response->assertSee('await showRestoredTabBeforeReveal(savedTabBtn);', false);
    }

    public function test_log_card_can_load_components_from_another_manual(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $extraManual = $this->createManual([
            'number' => 'LC-EXTRA',
            'title' => 'Extra Log Manual',
        ]);
        $extraComponent = Component::query()->create([
            'manual_id' => $extraManual->id,
            'part_number' => 'LC-EXTRA-100',
            'name' => 'Extra Manual Log Part',
            'ipl_num' => '3-10',
            'log_card' => true,
        ]);
        Component::query()->create([
            'manual_id' => $extraManual->id,
            'part_number' => 'LC-NOLOG-100',
            'name' => 'Extra Manual No Log Part',
            'ipl_num' => '3-20',
            'log_card' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('log_card.manual-components', [
            'workorder' => $workorder->id,
            'manual' => $extraManual->id,
        ]));

        $response->assertOk();
        $response->assertSee('Manual: LC-EXTRA Extra Log Manual', false);
        $response->assertSee('Extra Manual Log Part', false);
        $response->assertSee('value="'.$extraComponent->id.'"', false);
        $response->assertDontSee('Extra Manual No Log Part', false);
    }

    public function test_log_card_extra_manual_select_lists_manuals_without_log_card_components(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualWithoutLogRows = $this->createManual([
            'number' => '32-51-03',
            'title' => 'Steering Actuator Assy NLG',
        ]);
        Component::query()->create([
            'manual_id' => $manualWithoutLogRows->id,
            'part_number' => 'NO-LOG-100',
            'name' => 'No Log Card Part',
            'ipl_num' => '1-10',
            'log_card' => false,
        ]);

        $partial = $this->actingAs($admin)->get(route('log_card.partial', $workorder->id));

        $partial->assertOk();
        $partial->assertSee('32-51-03 Steering Actuator Assy NLG', false);

        $manualRows = $this->actingAs($admin)->get(route('log_card.manual-components', [
            'workorder' => $workorder->id,
            'manual' => $manualWithoutLogRows->id,
        ]));

        $manualRows->assertOk();
        $manualRows->assertSee('Manual: 32-51-03 Steering Actuator Assy NLG', false);
        $manualRows->assertDontSee('No Log Card Part', false);
    }

    public function test_log_card_extra_manual_select_sorts_manuals_by_manual_number_naturally(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $this->createManual(['number' => '32-51-03', 'title' => 'Manual Fifty One']);
        $this->createManual(['number' => '32-2-10', 'title' => 'Manual Two']);
        $this->createManual(['number' => '32-11-05', 'title' => 'Manual Eleven']);

        $response = $this->actingAs($admin)->get(route('log_card.partial', $workorder->id));

        $response->assertOk();
        $response->assertSeeInOrder([
            '32-2-10 Manual Two',
            '32-11-05 Manual Eleven',
            '32-51-03 Manual Fifty One',
        ], false);
    }

    public function test_log_card_store_accepts_manual_separator_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $primaryComponent = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LC-PRIMARY-100',
            'name' => 'Primary Log Part',
            'ipl_num' => '1-10',
            'log_card' => true,
        ]);
        $extraManual = $this->createManual([
            'number' => 'LC-EXTRA',
            'title' => 'Extra Log Manual',
        ]);
        $extraComponent = Component::query()->create([
            'manual_id' => $extraManual->id,
            'part_number' => 'LC-EXTRA-100',
            'name' => 'Extra Manual Log Part',
            'ipl_num' => '3-10',
            'log_card' => true,
        ]);

        $payload = [
            [
                'row_type' => 'manual',
                'manual_id' => (string) $workorder->unit->manual_id,
                'manual_label' => 'Primary Manual',
            ],
            [
                'component_id' => (string) $primaryComponent->id,
                'manual_id' => (string) $workorder->unit->manual_id,
                'included' => '1',
                'serial_number' => 'SN-PRIMARY',
            ],
            [
                'row_type' => 'manual',
                'manual_id' => (string) $extraManual->id,
                'manual_label' => 'LC-EXTRA Extra Log Manual',
            ],
            [
                'component_id' => (string) $extraComponent->id,
                'manual_id' => (string) $extraManual->id,
                'included' => '1',
                'serial_number' => 'SN-EXTRA',
            ],
        ];

        $response = $this->actingAs($admin)->postJson(route('log_card.store'), [
            'workorder_id' => $workorder->id,
            'component_data' => json_encode($payload),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $rows = json_decode((string) LogCard::where('workorder_id', $workorder->id)->firstOrFail()->component_data, true);
        $this->assertSame('manual', $rows[0]['row_type']);
        $this->assertSame($primaryComponent->id, (int) $rows[1]['component_id']);
        $this->assertSame('manual', $rows[2]['row_type']);
        $this->assertSame($extraComponent->id, (int) $rows[3]['component_id']);

        $saved = $this->actingAs($admin)->get(route('log_card.partial', $workorder->id));
        $saved->assertOk();
        $saved->assertSee('Manual: LC-EXTRA Extra Log Manual', false);
        $saved->assertSee('Extra Manual Log Part', false);

        $print = $this->actingAs($admin)->get(route('log_card.logCardForm', $workorder->id));
        $print->assertOk();
        $print->assertSee('LC-EXTRA', false);
        $print->assertSee('Print Settings', false);
        $print->assertSee('log_card_print_settings', false);
        $print->assertSee('--log-card-table-font-size', false);
        $print->assertSee('font-size: var(--log-card-table-font-size, 14px) !important;', false);
        $print->assertSee('window.UserScopedStorage', false);
        $print->assertDontSee('MANUAL:', false);
        $print->assertDontSee('Extra Log Manual', false);
    }

    public function test_single_manual_log_card_print_hides_manual_separator_number(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manual = $workorder->unit->manuals;
        $manual->update(['number' => 'LC-SINGLE-ONLY']);
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'LC-SINGLE-100',
            'name' => 'Single Manual Log Part',
            'ipl_num' => '1-10',
            'log_card' => true,
        ]);

        LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                [
                    'row_type' => 'manual',
                    'manual_id' => (string) $manual->id,
                    'manual_label' => 'LC-SINGLE-ONLY Single Manual',
                ],
                [
                    'component_id' => (string) $component->id,
                    'manual_id' => (string) $manual->id,
                    'included' => '1',
                    'serial_number' => 'SN-SINGLE',
                ],
            ]),
        ]);

        $print = $this->actingAs($admin)->get(route('log_card.logCardForm', $workorder->id));

        $print->assertOk();
        $print->assertSee('Single Manual Log Part', false);
        $print->assertSee('SN-SINGLE', false);
        $print->assertDontSee('LC-SINGLE-ONLY', false);
    }

    public function test_log_card_print_keeps_record_rows_together_for_page_breaks(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $rows = [];

        for ($i = 1; $i <= 30; $i++) {
            $component = Component::query()->create([
                'manual_id' => $workorder->unit->manual_id,
                'part_number' => 'LC-PAGE-'.$i,
                'name' => 'Long Print Row '.$i,
                'ipl_num' => '1-'.$i,
                'log_card' => true,
            ]);

            $rows[] = [
                'component_id' => (string) $component->id,
                'manual_id' => (string) $workorder->unit->manual_id,
                'included' => '1',
                'serial_number' => 'SN-'.$i,
            ];
        }

        LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode($rows),
        ]);

        $response = $this->actingAs($admin)->get(route('log_card.logCardForm', $workorder->id));
        $html = $response->getContent();

        $response->assertOk();
        $this->assertStringContainsString('.log-card-record-row', $html);
        $this->assertStringContainsString('log-card-continuation-section', $html);
        $this->assertStringContainsString('break-inside: avoid-page;', $html);
        $this->assertStringContainsString('page-break-inside: avoid;', $html);
        $this->assertStringContainsString('Print Settings', $html);
        $this->assertStringContainsString('log_card_print_settings', $html);
        $this->assertStringContainsString('--log-card-table-font-size', $html);
        $this->assertStringContainsString('font-size: var(--log-card-table-font-size, 14px) !important;', $html);
        $this->assertStringContainsString('window.UserScopedStorage', $html);
        $this->assertGreaterThanOrEqual(30, substr_count($html, 'class="log-card-record-row"'));
        $this->assertStringContainsString('1 of 3', $html);
        $this->assertStringContainsString('2 of 3', $html);
        $this->assertStringContainsString('3 of 3', $html);

        $firstContinuationPosition = strpos($html, '<section class="log-card-print-page log-card-continuation-section">');
        $secondContinuationPosition = strpos($html, '<section class="log-card-print-page log-card-continuation-section">', $firstContinuationPosition + 1);
        $this->assertNotFalse($firstContinuationPosition);
        $this->assertNotFalse($secondContinuationPosition);
        $this->assertLessThan($firstContinuationPosition, strpos($html, 'Long Print Row 9'));
        $this->assertGreaterThan($firstContinuationPosition, strpos($html, 'Long Print Row 10'));
        $this->assertLessThan($secondContinuationPosition, strpos($html, 'Long Print Row 29'));
        $this->assertGreaterThan($secondContinuationPosition, strpos($html, 'Long Print Row 30'));
    }

    public function test_log_card_partial_filters_components_by_ipl_branch_rule(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => '2801A0000-02',
        ]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);

        ManualIplBranchRule::query()->create([
            'manual_id' => $manual->id,
            'is_default' => true,
            'unit_match_value' => null,
            'include_prefix' => '9A-',
            'exclude_prefix' => '9-',
        ]);
        ManualIplBranchRule::query()->create([
            'manual_id' => $manual->id,
            'is_default' => false,
            'unit_match_value' => '2801A0000-02',
            'include_prefix' => '9-',
            'exclude_prefix' => '9A-',
        ]);

        $allowed = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'LC-9',
            'name' => 'Allowed Log Card Component',
            'ipl_num' => '9-10',
            'log_card' => true,
        ]);
        $excluded = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'LC-9A',
            'name' => 'Excluded Log Card Component',
            'ipl_num' => '9A-10',
            'log_card' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('log_card.partial', $workorder->id));

        $response->assertOk();
        $response->assertSee('value="'.$allowed->id.'"', false);
        $response->assertSee('Allowed Log Card Component', false);
        $response->assertDontSee('value="'.$excluded->id.'"', false);
        $response->assertDontSee('Excluded Log Card Component', false);
    }

    public function test_log_card_ipl_branch_cleanup_command_removes_disallowed_json_rows(): void
    {
        $manual = $this->createManual();
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => '2801A0000-02',
        ]);
        $workorder = $this->createWorkorder(['unit_id' => $unit->id]);

        ManualIplBranchRule::query()->create([
            'manual_id' => $manual->id,
            'is_default' => true,
            'unit_match_value' => null,
            'include_prefix' => '9A-',
            'exclude_prefix' => '9-',
        ]);
        ManualIplBranchRule::query()->create([
            'manual_id' => $manual->id,
            'is_default' => false,
            'unit_match_value' => '2801A0000-02',
            'include_prefix' => '9-',
            'exclude_prefix' => '9A-',
        ]);

        $allowed = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'LC-9',
            'name' => 'Allowed Log Card Component',
            'ipl_num' => '9-10',
            'log_card' => true,
        ]);
        $excluded = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'LC-9A',
            'name' => 'Excluded Log Card Component',
            'ipl_num' => '9A-10',
            'log_card' => true,
        ]);

        $logCard = LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                ['component_id' => $allowed->id, 'serial_number' => 'SN-9'],
                ['component_id' => $excluded->id, 'serial_number' => 'SN-9A'],
            ]),
            'component_data_out' => [
                ['component_id' => $allowed->id, 'serial_number' => 'OUT-9'],
                ['component_id' => $excluded->id, 'serial_number' => 'OUT-9A'],
            ],
        ]);

        $this->artisan('log-cards:clean-ipl-branch-json', [
            '--workorder' => $workorder->id,
        ])->assertExitCode(0);

        $dryRunRows = json_decode((string) $logCard->fresh()->component_data, true);
        $this->assertCount(2, $dryRunRows);

        $this->artisan('log-cards:clean-ipl-branch-json', [
            '--workorder' => $workorder->id,
            '--commit' => true,
        ])->assertExitCode(0);

        $logCard->refresh();
        $receivedRows = json_decode((string) $logCard->component_data, true);
        $dispatchedRows = $logCard->component_data_out;

        $this->assertSame([$allowed->id], array_map('intval', array_column($receivedRows, 'component_id')));
        $this->assertSame([$allowed->id], array_map('intval', array_column($dispatchedRows, 'component_id')));
    }

    public function test_log_card_inline_include_checkbox_state_is_saved(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LC-200',
            'name' => 'Included Component',
            'ipl_num' => '1-20',
            'log_card' => true,
        ]);
        $logCard = LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                [
                    'component_id' => $component->id,
                    'included' => '1',
                    'serial_number' => 'SN-200',
                ],
            ]),
        ]);

        $response = $this->actingAs($admin)->patch(route('log_card.inline_field.update', $logCard), [
            'row' => 0,
            'field' => 'included',
            'value' => '0',
        ]);

        $response->assertOk();
        $rows = json_decode($logCard->fresh()->component_data, true);
        $this->assertSame('0', $rows[0]['included']);
    }

    public function test_store_unit_inspections_does_not_delete_blank_workorder_level_tdr(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $blankTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'conditions_id' => null,
            'codes_id' => null,
            'necessaries_id' => null,
            'description' => null,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        $condition = Condition::query()->create([
            'name' => 'BUSHINGS WORN BEYOND LIMITS AS INDICATED ON PARTS LIST',
            'unit' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('tdrs.store.unit-inspections'), [
            'workorder_id' => $workorder->id,
            'conditions' => [
                $condition->id => [
                    'selected' => '1',
                    'notes' => '',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('tdrs', [
            'id' => $blankTdr->id,
        ]);
        $this->assertDatabaseHas('workorder_unit_inspections', [
            'workorder_id' => $workorder->id,
            'condition_id' => $condition->id,
            'notes' => '',
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);
        $this->assertSame(0, Tdr::query()
            ->where('workorder_id', $workorder->id)
            ->unitInspections()
            ->count());
    }

    public function test_legacy_unit_inspection_store_writes_to_workorder_unit_inspections(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $condition = Condition::query()->create([
            'name' => 'Legacy Single Unit Inspection ' . uniqid(),
            'unit' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('tdrs.store'), [
            'workorder_id' => $workorder->id,
            'component_id' => '',
            'order_component_id' => '',
            'serial_number' => ' ',
            'assy_serial_number' => ' ',
            'codes_id' => '',
            'conditions_id' => $condition->id,
            'necessaries_id' => ' ',
            'qty' => 1,
            'description' => 'legacy unit note',
            'use_tdr' => '1',
            'use_process_forms' => '0',
        ]);

        $response->assertRedirect(route('tdrs.show', ['id' => $workorder->id]));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('workorder_unit_inspections', [
            'workorder_id' => $workorder->id,
            'condition_id' => $condition->id,
            'notes' => 'legacy unit note',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);
        $this->assertSame(0, Tdr::query()
            ->where('workorder_id', $workorder->id)
            ->unitInspections()
            ->count());
        $this->assertSame(1, WorkorderUnitInspection::query()
            ->where('workorder_id', $workorder->id)
            ->where('condition_id', $condition->id)
            ->count());
    }

    public function test_unit_inspection_form_excludes_existing_workorder_unit_inspections(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $existingCondition = Condition::query()->create([
            'name' => 'Already Selected Unit Condition ' . uniqid(),
            'unit' => 1,
        ]);
        $availableCondition = Condition::query()->create([
            'name' => 'Available Unit Condition ' . uniqid(),
            'unit' => 1,
        ]);

        WorkorderUnitInspection::query()->create([
            'workorder_id' => $workorder->id,
            'condition_id' => $existingCondition->id,
            'notes' => 'already there',
            'qty' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.inspection.unit', ['workorder_id' => $workorder->id]));

        $response->assertOk();
        $response->assertDontSee($existingCondition->name);
        $response->assertSee($availableCondition->name);
    }

    public function test_tdr_form_order_new_reason_comes_from_code_and_omits_scrap_for_customer_request(): void
    {
        // Order New rows print their REASON from the code (the field edited in the
        // "Ordered Parts" modal), not from conditions. "Customer Request" is not a
        // scrap reason, so its line must omit the "(scrap)" label.
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;

        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);
        Code::query()->firstOrCreate(['name' => 'Missing'], ['code' => 'M']);
        $cracked = Code::query()->firstOrCreate(['name' => 'Cracked'], ['code' => 'C']);
        $customerRequest = Code::query()->firstOrCreate(['name' => 'Customer Request'], ['code' => 'CR']);
        // A condition that used to drive the printed label — it must no longer leak in.
        $sbc = Condition::query()->firstOrCreate(['name' => 'SERVICE BULLETIN CHANGE'], ['unit' => 1]);

        $crackedPart = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'CMP-CRACK-' . uniqid(),
            'name' => 'Bushing',
            'ipl_num' => '1-30',
        ]);
        $customerPart = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'CMP-CR-' . uniqid(),
            'name' => 'End, Rod',
            'ipl_num' => '1-170',
        ]);

        foreach ([[$crackedPart, $cracked], [$customerPart, $customerRequest]] as [$part, $code]) {
            Tdr::query()->create([
                'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
                'workorder_id' => $workorder->id,
                'component_id' => $part->id,
                'codes_id' => $code->id,
                'conditions_id' => $sbc->id, // set, but must be ignored by the print
                'necessaries_id' => $orderNew->id,
                'serial_number' => 'NSN',
                'assy_serial_number' => ' ',
                'qty' => 1,
                'use_tdr' => true,
                'use_process_forms' => false,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('tdrs.tdrForm', ['id' => $workorder->id]));

        $response->assertOk();
        $html = $response->getContent();

        // Reason is taken from the code, defect codes keep "(scrap)".
        $this->assertStringContainsString('Cracked (scrap): (1-30)', $html);
        // Customer Request: reason printed, but WITHOUT the scrap label.
        $this->assertStringContainsString('Customer Request: (1-170)', $html);
        $this->assertStringNotContainsString('Customer Request (scrap)', $html);
        // The condition name must no longer be used as the Order New reason.
        $this->assertStringNotContainsString('SERVICE BULLETIN CHANGE (scrap)', $html);
    }

    public function test_inspect_button_shows_only_for_parts_with_measurable_parameter(): void
    {
        // The 📏 Inspect button must appear only when the part's inspection
        // component has a MEASURABLE parameter (orig/wear dims or a repair rule).
        // A part whose parameter has no dimensions/rules gets no button.
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder(['user_id' => $admin->id, 'unit_id' => $unit->id]);

        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);
        $corroded = Code::query()->firstOrCreate(['name' => 'Corroded'], ['code' => 'C']);

        // Measurable: parameter carries orig dimensions.
        $icMeasurable = $this->createInspectionComponent($manual, 'Bolt 6-50');
        $compMeasurable = $this->createComponent($manual, ['ipl_num' => '6-50', 'name' => 'BOLT']);
        $this->attachComponentToIc($icMeasurable, $compMeasurable);
        $this->createParameter($manual, $icMeasurable, [
            'description' => 'OD', 'orig_dim_min' => 1.0000, 'orig_dim_max' => 1.0010,
        ]);

        // Non-measurable: parameter exists but has no dims and no repair rules.
        $icBare = $this->createInspectionComponent($manual, 'Bolt 6-70');
        $compBare = $this->createComponent($manual, ['ipl_num' => '6-70', 'name' => 'PIN']);
        $this->attachComponentToIc($icBare, $compBare);
        $this->createParameter($manual, $icBare, ['description' => 'OD']);

        foreach ([$compMeasurable, $compBare] as $c) {
            Tdr::query()->create([
                'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
                'workorder_id' => $workorder->id,
                'component_id' => $c->id,
                'codes_id' => $corroded->id,
                'conditions_id' => null,
                'necessaries_id' => $repair->id,
                'serial_number' => 'SN',
                'assy_serial_number' => ' ',
                'qty' => 1,
                'use_tdr' => true,
                'use_process_forms' => true,
            ]);
        }

        $html = $this->actingAs($admin)->get(route('tdrs.show', ['id' => $workorder->id]))
            ->assertOk()
            ->getContent();

        // Button present for the measurable part, absent for the bare one.
        $this->assertStringContainsString('data-ic-id="' . $icMeasurable->id . '"', $html);
        $this->assertStringNotContainsString('data-ic-id="' . $icBare->id . '"', $html);
    }

    public function test_backfill_tdr_conditions_re_derives_condition_from_code(): void
    {
        // Legacy rows whose condition came from the old JS mapping (default 39 =
        // SERVICE BULLETIN CHANGE) are re-derived by code name; Missing → PARTS
        // MISSING; Manufacture and null-component rows are left untouched.
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;

        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);
        $missingCode = Code::query()->firstOrCreate(['name' => 'Missing'], ['code' => 'M']);
        $cracked = Code::query()->firstOrCreate(['name' => 'Cracked'], ['code' => 'C']);
        $customerRequest = Code::query()->firstOrCreate(['name' => 'Customer Request'], ['code' => 'CR']);
        $manufacture = Code::query()->firstOrCreate(['name' => 'Manufacture'], ['code' => 'MF']);

        $sbc = Condition::query()->firstOrCreate(['name' => 'SERVICE BULLETIN CHANGE'], ['unit' => 1]);
        $crackedCond = Condition::query()->firstOrCreate(['name' => 'Cracked'], ['unit' => 1]);
        $partsMissing = Condition::query()->firstOrCreate(
            ['name' => Condition::NAME_PARTS_MISSING],
            ['unit' => false]
        );

        $mkComp = function (string $ipl) use ($manualId) {
            return Component::query()->create([
                'manual_id' => $manualId,
                'part_number' => 'CMP-' . uniqid(),
                'name' => 'Part ' . $ipl,
                'ipl_num' => $ipl,
            ]);
        };

        // Polluted: Customer Request stored as SBC → should become null (no same-named condition).
        $crTdr = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR, 'workorder_id' => $workorder->id,
            'component_id' => $mkComp('1-170')->id, 'codes_id' => $customerRequest->id,
            'conditions_id' => $sbc->id, 'necessaries_id' => $orderNew->id,
            'serial_number' => 'SN', 'assy_serial_number' => ' ', 'qty' => 1,
            'use_tdr' => true, 'use_process_forms' => false,
        ]);
        // Polluted: Cracked stored as SBC → should become the "Cracked" condition.
        $crackTdr = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR, 'workorder_id' => $workorder->id,
            'component_id' => $mkComp('1-30')->id, 'codes_id' => $cracked->id,
            'conditions_id' => $sbc->id, 'necessaries_id' => $orderNew->id,
            'serial_number' => 'SN', 'assy_serial_number' => ' ', 'qty' => 1,
            'use_tdr' => true, 'use_process_forms' => false,
        ]);
        // Missing with a wrong condition → should become PARTS MISSING.
        $missTdr = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR, 'workorder_id' => $workorder->id,
            'component_id' => $mkComp('2-10')->id, 'codes_id' => $missingCode->id,
            'conditions_id' => $sbc->id, 'necessaries_id' => $orderNew->id,
            'serial_number' => 'SN', 'assy_serial_number' => ' ', 'qty' => 1,
            'use_tdr' => true, 'use_process_forms' => false,
        ]);
        // Manufacture → untouched.
        $mfTdr = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR, 'workorder_id' => $workorder->id,
            'component_id' => $mkComp('3-10')->id, 'codes_id' => $manufacture->id,
            'conditions_id' => $sbc->id, 'necessaries_id' => $orderNew->id,
            'serial_number' => 'SN', 'assy_serial_number' => ' ', 'qty' => 1,
            'use_tdr' => true, 'use_process_forms' => false,
        ]);
        // Null-component (STD carrier) → out of scope, untouched.
        $nullTdr = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_STD_LIST_CARRIER, 'workorder_id' => $workorder->id,
            'component_id' => null, 'codes_id' => null,
            'conditions_id' => $sbc->id, 'necessaries_id' => null, 'description' => 'carrier',
            'serial_number' => 'NSN', 'assy_serial_number' => ' ', 'qty' => 1,
            'use_tdr' => true, 'use_process_forms' => false,
        ]);

        $this->artisan('tdrs:backfill-conditions', ['--workorder' => $workorder->id, '--force' => true])
            ->assertExitCode(0);

        $this->assertNull($crTdr->fresh()->conditions_id);
        $this->assertSame($crackedCond->id, $crackTdr->fresh()->conditions_id);
        $this->assertSame($partsMissing->id, $missTdr->fresh()->conditions_id);
        $this->assertSame($sbc->id, $mfTdr->fresh()->conditions_id);   // Manufacture untouched
        $this->assertSame($sbc->id, $nullTdr->fresh()->conditions_id); // null-component untouched
    }

    public function test_tdr_inspection_lines_builder_is_single_source_for_render_and_count(): void
    {
        // The builder feeds both the renderer (tdrForm) and the row counter
        // (countTdrFormRows); count(build()) must equal the rendered lines, and
        // Order New lines must come from the code (Customer Request without scrap).
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;

        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);
        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);
        Code::query()->firstOrCreate(['name' => 'Missing'], ['code' => 'M']);
        $cracked = Code::query()->firstOrCreate(['name' => 'Cracked'], ['code' => 'C']);
        $customerRequest = Code::query()->firstOrCreate(['name' => 'Customer Request'], ['code' => 'CR']);
        $corroded = Code::query()->firstOrCreate(['name' => 'Corroded'], ['code' => 'CR2']);
        $sbc = Condition::query()->firstOrCreate(['name' => 'SERVICE BULLETIN CHANGE'], ['unit' => 1]);
        $bent = Condition::query()->firstOrCreate(['name' => 'BENT'], ['unit' => 1]);
        $unitCond = Condition::query()->firstOrCreate(['name' => 'SEAL(S) WORN'], ['unit' => 1]);

        // 1 unit inspection line.
        WorkorderUnitInspection::query()->create([
            'workorder_id' => $workorder->id,
            'condition_id' => $unitCond->id,
            'notes' => 'left side',
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        // 1 null-component condition line.
        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_STD_LIST_CARRIER,
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'codes_id' => null,
            'conditions_id' => $bent->id,
            'necessaries_id' => null,
            'description' => '',
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        $mk = function (string $name, string $ipl, $code, $necessary) use ($manualId, $workorder, $sbc) {
            $component = Component::query()->create([
                'manual_id' => $manualId,
                'part_number' => 'CMP-' . uniqid(),
                'name' => $name,
                'ipl_num' => $ipl,
            ]);
            Tdr::query()->create([
                'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
                'workorder_id' => $workorder->id,
                'component_id' => $component->id,
                'codes_id' => $code->id,
                'conditions_id' => $sbc->id,
                'necessaries_id' => $necessary->id,
                'serial_number' => 'SN',
                'assy_serial_number' => ' ',
                'qty' => 1,
                'use_tdr' => true,
                'use_process_forms' => false,
            ]);
        };
        $mk('Bushing', '1-30', $cracked, $orderNew);          // grouped, scrap
        $mk('End, Rod', '1-170', $customerRequest, $orderNew); // grouped, no scrap
        $mk('Sleeve', '2-40', $corroded, $repair);             // "is necessary"

        $lines = app(\App\Services\TdrInspectionLinesBuilder::class)->build($workorder);
        $joined = implode("\n", $lines);

        // 1 unit + 1 null-component + 2 grouped (distinct codes) + 1 necessary.
        $this->assertCount(5, $lines);
        $this->assertStringContainsString('SEAL(S) WORN (left side)', $joined);
        $this->assertStringContainsString('BENT', $joined);
        $this->assertStringContainsString('Cracked (scrap): (1-30)', $joined);
        $this->assertStringContainsString('Customer Request: (1-170)', $joined);
        $this->assertStringNotContainsString('Customer Request (scrap)', $joined);
        $this->assertStringContainsString('IS NECESSARY', $joined);
    }

    public function test_tdr_form_prints_teardown_inspection_rows_before_tdr_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $unitCondition = Condition::query()->create([
            'name' => 'SEAL(S) WORN',
            'unit' => 1,
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'CMP-TDR-FORM-' . uniqid(),
            'name' => 'Repair Sleeve',
            'ipl_num' => '1-20',
        ]);
        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);
        Necessary::query()->firstOrCreate(['name' => 'Order New']);
        Code::query()->firstOrCreate(['name' => 'Missing'], ['code' => 'M']);
        $corroded = Code::query()->firstOrCreate(['name' => 'Corroded'], ['code' => 'C']);

        WorkorderUnitInspection::query()->create([
            'workorder_id' => $workorder->id,
            'condition_id' => $unitCondition->id,
            'notes' => 'left side note',
            'qty' => 1,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'codes_id' => $corroded->id,
            'conditions_id' => null,
            'necessaries_id' => $repair->id,
            'serial_number' => 'SN-REPAIR',
            'assy_serial_number' => ' ',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.tdrForm', ['id' => $workorder->id]));

        $response->assertOk();
        $html = $response->getContent();
        $unitPosition = strpos($html, 'SEAL(S) WORN (left side note)');
        $tdrPosition = strpos($html, 'REPAIR SLEEVE');

        $this->assertNotFalse($unitPosition);
        $this->assertNotFalse($tdrPosition);
        $this->assertLessThan($tdrPosition, $unitPosition);
    }

    public function test_deleting_last_missing_part_does_not_delete_explicit_std_list_carrier(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id, 'part_missing' => true]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'CMP-MISSING-' . uniqid(),
            'name' => 'Missing Component',
            'ipl_num' => '1-20',
        ]);
        $missingCode = Code::query()->firstOrCreate(['name' => 'Missing'], ['code' => 'M']);
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);
        $missingCondition = Condition::query()->firstOrCreate([
            'name' => 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST',
        ], [
            'unit' => 1,
        ]);

        $missingTdr = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'codes_id' => $missingCode->id,
            'conditions_id' => $missingCondition->id,
            'necessaries_id' => $orderNew->id,
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);

        $stdCarrier = Tdr::query()->create([
            'tdr_type' => Tdr::TYPE_STD_LIST_CARRIER,
            'workorder_id' => $workorder->id,
            'component_id' => null,
            'codes_id' => null,
            'conditions_id' => $missingCondition->id,
            'necessaries_id' => null,
            'description' => 'STD List carrier',
            'serial_number' => 'NSN',
            'assy_serial_number' => ' ',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => false,
        ]);
        $stdProcess = TdrProcess::query()->create([
            'tdrs_id' => $stdCarrier->id,
            'repair_order' => 'RO-KEEP',
        ]);

        $response = $this->actingAs($admin)->delete(route('tdrs.destroy', $missingTdr->id));

        $response->assertRedirect(route('tdrs.show', ['id' => $workorder->id]));
        $this->assertDatabaseMissing('tdrs', ['id' => $missingTdr->id]);
        $this->assertDatabaseHas('tdrs', [
            'id' => $stdCarrier->id,
            'tdr_type' => Tdr::TYPE_STD_LIST_CARRIER,
        ]);
        $this->assertDatabaseHas('tdr_processes', [
            'id' => $stdProcess->id,
            'tdrs_id' => $stdCarrier->id,
            'repair_order' => 'RO-KEEP',
        ]);
    }

    public function test_bushing_prl_and_kit_forms_render_prl_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $bushingA = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'BUSH-PN-100',
            'name' => 'Bushing First',
            'ipl_num' => '1-100',
            'bush_ipl_num' => '1-100',
            'is_bush' => true,
        ]);
        $bushingB = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'BUSH-PN-110',
            'name' => 'Bushing Second',
            'ipl_num' => '1-110',
            'bush_ipl_num' => '1-100',
            'is_bush' => true,
        ]);
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);
        WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $bushingA->id,
            'qty' => 2,
            'qty_remaining' => 2,
            'sort_order' => 1,
        ]);
        WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $bushingB->id,
            'qty' => 1,
            'qty_remaining' => 1,
            'sort_order' => 2,
        ]);

        Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'KIT-PN-50A',
            'name' => 'Kit Same Numeric A',
            'ipl_num' => '2-50A',
            'units_assy' => 1,
            'kit' => true,
        ]);
        Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'KIT-PN-50B',
            'name' => 'Kit Same Numeric B',
            'ipl_num' => '2-50B',
            'units_assy' => 2,
            'kit' => true,
        ]);
        Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'KIT-PN-60',
            'name' => 'Kit Other',
            'ipl_num' => '2-60',
            'units_assy' => 1,
            'kit' => true,
        ]);
        Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'KIT-BUSH-PN-80',
            'name' => 'Kit Bushing Excluded',
            'ipl_num' => '2-80',
            'units_assy' => 1,
            'kit' => true,
            'is_bush' => true,
        ]);
        Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'KIT-E-PN-70',
            'name' => 'Kit Extra',
            'ipl_num' => '2-70',
            'units_assy' => 1,
            'kit_e' => true,
        ]);

        $bushingResponse = $this->actingAs($admin)->get(route('tdrs.bushingPrlForm', ['id' => $workorder->id]));
        $bushingResponse->assertOk();
        $bushingResponse->assertSee('PARTS REPLACEMENT LIST');
        $bushingResponse->assertDontSee('BUSHING PARTS REPLACEMENT LIST');
        $bushingResponse->assertSee('const PRINT_SETTINGS_PROFILE = "bush-prl";', false);
        $bushingResponse->assertSee('BUSH-PN-100');
        $bushingResponse->assertSee('BUSH-PN-110');
        $bushingResponse->assertSee('KIT-BUSH-PN-80');
        $this->assertSame(1, substr_count($bushingResponse->getContent(), 'data-prl-bushing-group="1-100"'));
        $this->assertMatchesRegularExpression(
            '/data-prl-bushing-group="1-100".*?<div class="col-1 prl-col-qty[^>]*>.*?data-prl-option-qty="2"[^>]*>2<\/span>.*?data-prl-option-qty="1"[^>]*>1<\/span>/s',
            $bushingResponse->getContent()
        );
        $this->assertSame(1, substr_count($bushingResponse->getContent(), 'data-prl-crossed-out="1"'));
        $bushingResponse->assertSee('<h6>K</h6>', false);

        $bushResponse = $this->actingAs($admin)->get(route('tdrs.bushPrlForm', ['id' => $workorder->id]));
        $bushResponse->assertOk();
        $bushResponse->assertSee('PARTS REPLACEMENT LIST');
        $bushResponse->assertSee('const PRINT_SETTINGS_PROFILE = "bush-prl";', false);
        $bushResponse->assertSee('BUSH-PN-100');
        $bushResponse->assertSee('BUSH-PN-110');
        $bushResponse->assertSee('KIT-BUSH-PN-80');
        $this->assertSame(1, substr_count($bushResponse->getContent(), 'data-prl-crossed-out="1"'));
        $bushResponse->assertSee('<h6>K</h6>', false);

        $kitResponse = $this->actingAs($admin)->get(route('tdrs.kitForm', ['id' => $workorder->id]));
        $kitResponse->assertOk();
        $kitResponse->assertDontSee('system-print-qr');
        $kitResponse->assertSee('PARTS REPLACEMENT LIST - KIT');
        $kitResponse->assertSee('const PRINT_SETTINGS_PROFILE = "kit";', false);
        $kitResponse->assertDontSee('KIT PARTS REPLACEMENT LIST');
        $kitResponse->assertDontSee('2<br />' . "\n" . '2', false);
        $kitResponse->assertSee('50A');
        $kitResponse->assertSee('50B');
        $kitResponse->assertSee('KIT-PN-50A');
        $kitResponse->assertSee('KIT-PN-50B');
        $this->assertMatchesRegularExpression(
            '/50A<br \/>\\s*50B.*?KIT-PN-50A<br \/>\\s*KIT-PN-50B.*?<div class="col-1 prl-col-qty[^"]*"[^>]*>\\s*<h6>2<\\/h6>/s',
            $kitResponse->getContent()
        );
        $kitResponse->assertDontSee('<h6>3</h6>', false);
        $kitResponse->assertSee('KIT-PN-60');
        $kitResponse->assertDontSee('KIT-BUSH-PN-80');
        $kitResponse->assertDontSee('EXTRA PARTS');
        $kitResponse->assertDontSee('KIT-E-PN-70');
        $kitResponse->assertSee('<h6>KIT</h6>', false);

        $showResponse = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));
        $showResponse->assertOk();
        $showResponse->assertSee(route('tdrs.kitForm', ['id' => $workorder->id]), false);
        $showResponse->assertSee(route('tdrs.bushPrlForm', ['id' => $workorder->id]), false);
        $showResponse->assertDontSee(route('tdrs.bushingPrlForm', ['id' => $workorder->id]), false);
        // Order is asserted via the buttons' route URLs rather than their text
        // labels: labels like "Log Card" recur many times across the page (tabs,
        // modals, inline scripts), which makes label-based ordered matching jump
        // to the wrong occurrence. Only URLs that appear exactly once are used as
        // anchors — Log Card / KIT / PRL / Bush PRL URLs are echoed in more than
        // one place (their presence is asserted separately above).
        $showResponse->assertSeeInOrder([
            route('tdrs.tdrForm', ['id' => $workorder->id]),
            route('rm_reports.rmRecordForm', ['id' => $workorder->id]),
            route('tdrs.specProcessFormEmp', ['id' => $workorder->id]),
            route('tdrs.serviceBulletinLog', ['workorder' => $workorder->id]),
            route('tdrs.ndtStd', ['workorder_id' => $workorder->id]),
            route('tdrs.cadStd', ['workorder_id' => $workorder->id]),
            route('tdrs.stressStd', ['workorder_id' => $workorder->id]),
            route('tdrs.paintStd', ['workorder_id' => $workorder->id]),
        ], false);
        $showResponse->assertSee('>2</span>', false);
        $bushingResponse->assertSee('`prlForm_print_settings:${PRINT_SETTINGS_PROFILE}`', false);

        $bushSettings = json_encode(['prlTableRows' => '9']);
        $prlSettings = json_encode(['prlTableRows' => '17']);
        foreach ([
            'prlForm_print_settings:bush-prl' => $bushSettings,
            'prlForm_print_settings:prl' => $prlSettings,
        ] as $key => $value) {
            $this->actingAs($admin)->postJson(route('user-ui-settings.store'), [
                'scope' => 'browser-storage',
                'key' => $key,
                'value' => $value,
            ])->assertOk();
        }

        $savedPrintSettings = UserUiSetting::query()
            ->where('user_id', $admin->id)
            ->where('scope', 'browser-storage')
            ->whereIn('key', [
                'prlForm_print_settings:bush-prl',
                'prlForm_print_settings:prl',
            ])
            ->pluck('value', 'key');
        $this->assertSame($bushSettings, $savedPrintSettings->get('prlForm_print_settings:bush-prl'));
        $this->assertSame($prlSettings, $savedPrintSettings->get('prlForm_print_settings:prl'));
    }

    public function test_bushing_prl_prints_all_bushings_crossed_out_when_none_are_ordered(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'BUSH-NONE-100',
            'name' => 'Initial Bushing',
            'ipl_num' => '8-230',
            'bush_ipl_num' => '8-230',
            'units_assy' => 2,
            'is_bush' => true,
        ]);
        Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'BUSH-NONE-110',
            'name' => 'Oversize Bushing',
            'ipl_num' => '8-231',
            'bush_ipl_num' => '8-230',
            'units_assy' => 1,
            'is_bush' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.bushPrlForm', ['id' => $workorder->id]));

        $response->assertOk();
        $response->assertSee('BUSH-NONE-100');
        $response->assertSee('BUSH-NONE-110');
        $this->assertSame(1, substr_count($response->getContent(), 'data-prl-bushing-group="8-230"'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-prl-crossed-out="1"'));
        $this->assertSame(2, substr_count($response->getContent(), 'data-prl-part-number-crossed-out="1"'));

        $showResponse = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));

        $showResponse->assertOk();
        $showResponse->assertSee(route('tdrs.bushPrlForm', ['id' => $workorder->id]), false);
        $showResponse->assertSee('>1</span>', false);
    }

    public function test_bushing_prl_groups_initial_and_oversize_part_numbers_in_one_cell(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $partNumbers = [
            'BUSH-GROUP-INITIAL',
            'BUSH-GROUP-OS-1',
            'BUSH-GROUP-OS-2',
            'BUSH-GROUP-OS-3',
        ];
        $components = collect($partNumbers)->map(function (string $partNumber, int $index) use ($workorder): Component {
            return Component::query()->create([
                'manual_id' => $workorder->unit->manual_id,
                'part_number' => $partNumber,
                'name' => 'Grouped Bushing ' . $index,
                'ipl_num' => '8-' . (230 + $index),
                'bush_ipl_num' => '8-230',
                'units_assy' => 2,
                'is_bush' => true,
            ]);
        });
        Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'BUSH-GROUP-OS-3',
            'name' => 'Grouped Bushing duplicate PN',
            'ipl_num' => '8-234',
            'bush_ipl_num' => '8-230',
            'units_assy' => 2,
            'is_bush' => true,
        ]);
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);
        WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $components->last()->id,
            'qty' => 2,
            'qty_remaining' => 2,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.bushPrlForm', ['id' => $workorder->id]));
        $content = $response->getContent();

        $response->assertOk();
        $this->assertSame(1, substr_count($content, 'data-prl-bushing-group="8-230"'));
        $this->assertSame(3, substr_count($content, 'data-prl-part-number-crossed-out="1"'));
        $this->assertMatchesRegularExpression(
            '/<div class="col-3 prl-col-part[^>]*>.*?BUSH-GROUP-INITIAL.*?BUSH-GROUP-OS-1.*?BUSH-GROUP-OS-2.*?BUSH-GROUP-OS-3.*?<\/div>/s',
            $content
        );
        $this->assertMatchesRegularExpression(
            '/class="prl-part-number-line "[^>]*>BUSH-GROUP-OS-3<\/span>/',
            $content
        );
        $this->assertSame(1, substr_count($content, '>BUSH-GROUP-OS-3</span>'));
        $this->assertSame(1, substr_count($content, 'data-prl-option-qty="2"'));
        $this->assertSame(3, substr_count($content, 'data-prl-option-qty-empty="1"'));
    }

    public function test_kit_form_groups_db_choice_items_without_summing_qty(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'KIT-CHOICE-' . uniqid()]);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        foreach ([
            ['1-320', '170-70165-901'],
            ['1-321', '6620A0001-01RS01'],
            ['1-321A', '6620A0001-01RS02'],
        ] as [$ipl, $partNumber]) {
            Component::query()->create([
                'manual_id' => $manual->id,
                'part_number' => $partNumber,
                'name' => 'BEARING, SPHERICAL',
                'ipl_num' => $ipl,
                'units_assy' => 1,
                'kit' => true,
                'kit_prl_choice_group' => 'bearing_spherical_320_321',
            ]);
        }

        $kitResponse = $this->actingAs($admin)->get(route('tdrs.kitForm', ['id' => $workorder->id]));

        $kitResponse->assertOk();
        $kitResponse->assertSee('320<br />' . "\n" . '321<br />' . "\n" . '321A', false);
        $kitResponse->assertSee('170-70165-901<br />' . "\n" . '6620A0001-01RS01<br />' . "\n" . '6620A0001-01RS02', false);
        $this->assertSame(1, substr_count($kitResponse->getContent(), 'BEARING, SPHERICAL'));
        $kitResponse->assertDontSee('<h6>3</h6>', false);

        $showResponse = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));

        $showResponse->assertOk();
        $showResponse->assertSee(route('tdrs.kitForm', ['id' => $workorder->id]), false);
        $showResponse->assertSee('>1</span>', false);
    }

    public function test_kit_form_auto_ipl_variant_groups_without_summing_qty(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => 'KIT-AUTO-' . uniqid()]);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        foreach ([
            ['1-130', 'MS15001-1'],
            ['1-130A', 'AS15001-1P'],
        ] as [$ipl, $partNumber]) {
            Component::query()->create([
                'manual_id' => $manual->id,
                'part_number' => $partNumber,
                'name' => 'FITTING, LUBRICATION',
                'ipl_num' => $ipl,
                'units_assy' => 3,
                'kit' => true,
            ]);
        }

        $kitResponse = $this->actingAs($admin)->get(route('tdrs.kitForm', ['id' => $workorder->id]));

        $kitResponse->assertOk();
        $kitResponse->assertSee('130<br />' . "\n" . '130A', false);
        $kitResponse->assertSee('MS15001-1<br />' . "\n" . 'AS15001-1P', false);
        $this->assertMatchesRegularExpression(
            '/130<br \/>\\s*130A.*?MS15001-1<br \/>\\s*AS15001-1P.*?<div class="col-1 prl-col-qty[^"]*"[^>]*>\\s*<h6>3<\\/h6>/s',
            $kitResponse->getContent()
        );
        $kitResponse->assertDontSee('<h6>6</h6>', false);
    }

    public function test_manual_parts_can_assign_kit_choice_group_in_bulk(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        $parts = collect([
            ['1-320', '170-70165-901'],
            ['1-321', '6620A0001-01RS01'],
            ['1-321A', '6620A0001-01RS02'],
        ])->map(fn (array $row): Component => Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => $row[0],
            'part_number' => $row[1],
            'name' => 'BEARING, SPHERICAL',
            'units_assy' => 1,
            'kit' => true,
        ]));

        $response = $this->actingAs($admin)->patchJson(route('manuals.components.kit-prl-choice-group', ['manual' => $manual]), [
            'component_ids' => $parts->pluck('id')->all(),
            'action' => 'group',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'updated_count' => 3,
        ]);
        $generatedGroup = $response->json('kit_prl_choice_group');
        $this->assertIsString($generatedGroup);
        $this->assertMatchesRegularExpression('/^bearing_spherical_[a-z]+_\d{4}$/', $generatedGroup);
        foreach ($parts as $part) {
            $this->assertDatabaseHas('components', [
                'id' => $part->id,
                'kit_prl_choice_group' => $generatedGroup,
            ]);
        }

        $csv = implode("\n", [
            'part_number,name,ipl_num,kit',
            '170-70165-901,BEARING SPHERICAL UPDATED,1-320,1',
        ]);

        $csvResponse = $this->actingAs($admin)->postJson(route('components.upload-csv'), [
            'manual_id' => $manual->id,
            'csv_file' => $this->makeUploadedFile('parts.csv', $csv, 'text/csv'),
        ]);

        $csvResponse->assertOk();
        $csvResponse->assertJsonPath('success', true);
        $this->assertDatabaseHas('components', [
            'id' => $parts->first()->id,
            'name' => 'BEARING SPHERICAL UPDATED',
            'kit_prl_choice_group' => $generatedGroup,
        ]);

        $manualResponse = $this->actingAs($admin)->get(route('manuals.show', ['manual' => $manual, 'tab' => 'parts']));

        $manualResponse->assertOk();
        $manualResponse->assertDontSee('manual-kit-choice-group-input', false);
        $manualResponse->assertSee('manual-kit-choice-group-apply', false);
        $manualResponse->assertSee('bi-check2', false);
        $manualResponse->assertDontSee('>Grouped<', false);

        $kitResponse = $this->actingAs($admin)->get(route('tdrs.kitForm', ['id' => $workorder->id]));

        $kitResponse->assertOk();
        $kitResponse->assertSee('320<br />' . "\n" . '321<br />' . "\n" . '321A', false);
        $this->assertSame(1, substr_count($kitResponse->getContent(), 'BEARING, SPHERICAL'));
    }

    public function test_tdr_show_renders_group_process_modal_for_groupable_process_details(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $suffix = uniqid();

        $processName = ProcessName::query()->create([
            'name' => 'Group Process Test ' . $suffix,
            'process_sheet_name' => 'CAD',
            'form_number' => 'GP-' . $suffix,
            'print_form' => true,
            'show_in_process_picker' => true,
        ]);
        $process = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'Grouped operation ' . $suffix,
        ]);
        ManualProcess::query()->create([
            'manual_id' => $manualId,
            'processes_id' => $process->id,
        ]);

        $firstComponent = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'GP-PN-1-' . $suffix,
            'name' => 'Group part one',
            'ipl_num' => '1-10',
        ]);
        $secondComponent = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'GP-PN-2-' . $suffix,
            'name' => 'Group part two',
            'ipl_num' => '1-20',
        ]);

        $firstTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $firstComponent->id,
            'serial_number' => 'GP-SN-1-' . $suffix,
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $secondTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $secondComponent->id,
            'serial_number' => 'GP-SN-2-' . $suffix,
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        foreach ([$firstTdr, $secondTdr] as $index => $tdr) {
            TdrProcess::query()->create([
                'tdrs_id' => $tdr->id,
                'process_names_id' => $processName->id,
                'processes' => [$process->id],
                'sort_order' => $index + 1,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));

        $response->assertOk();
        $response->assertSee('tdrGroupProcessModal', false);
        $response->assertSee('data-tdr-id="' . $firstTdr->id . '"', false);
        $response->assertSee('data-tdr-id="' . $secondTdr->id . '"', false);
        $response->assertSee('GP-PN-1-' . $suffix);
        $response->assertSee('GP-PN-2-' . $suffix);
    }

    public function test_group_process_form_filters_by_selected_tdr_ids(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $suffix = uniqid();

        $processName = ProcessName::query()->create([
            'name' => 'Selected Group Process ' . $suffix,
            'process_sheet_name' => 'CAD',
            'form_number' => 'SGP-' . $suffix,
            'print_form' => true,
            'show_in_process_picker' => true,
        ]);
        $process = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'Selected grouped operation ' . $suffix,
        ]);
        ManualProcess::query()->create([
            'manual_id' => $manualId,
            'processes_id' => $process->id,
        ]);

        $tdrs = collect([1, 2, 3])->map(function (int $number) use ($manualId, $workorder, $processName, $process, $suffix): Tdr {
            $component = Component::query()->create([
                'manual_id' => $manualId,
                'part_number' => 'SGP-PN-' . $number . '-' . $suffix,
                'name' => 'Selected group part ' . $number,
                'ipl_num' => '2-' . $number . '0',
            ]);
            $tdr = Tdr::query()->create([
                'workorder_id' => $workorder->id,
                'component_id' => $component->id,
                'serial_number' => 'SGP-SN-' . $number . '-' . $suffix,
                'qty' => 1,
                'use_tdr' => true,
                'use_process_forms' => true,
            ]);
            TdrProcess::query()->create([
                'tdrs_id' => $tdr->id,
                'process_names_id' => $processName->id,
                'processes' => [$process->id],
                'sort_order' => $number,
            ]);

            return $tdr;
        });

        $response = $this->actingAs($admin)->get(route('tdrs.show_group_forms', [
            'id' => $workorder->id,
            'processNameId' => $processName->id,
            'tdr_ids' => $tdrs->take(2)->pluck('id')->implode(','),
        ]));

        $response->assertOk();
        $response->assertSee('SGP-PN-1-' . $suffix);
        $response->assertSee('SGP-PN-2-' . $suffix);
        $response->assertDontSee('SGP-PN-3-' . $suffix);
    }

    public function test_machining_process_form_reserves_header_space_for_print_qr(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['lib' => '295']);
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => '4259A0000-02',
        ]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
            'number' => 107789,
            'serial_number' => '00762',
        ]);
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => '2821-0067',
            'name' => 'PIN PINTLE',
            'ipl_num' => '9-550',
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'Machining Header QR ' . uniqid(),
            'process_sheet_name' => 'MACHINING',
            'form_number' => '018',
            'print_form' => true,
            'show_in_process_picker' => true,
        ]);
        $process = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'CMM 32-11-15',
        ]);

        ManualProcess::query()->create([
            'manual_id' => $manual->id,
            'processes_id' => $process->id,
        ]);

        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'SN L 1556',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);
        $tdrProcess = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'processes' => [$process->id],
            'requires_fig' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('tdr-processes.show', [
            'tdr_process' => $tdrProcess->id,
            'process_id' => $process->id,
            'omit_form_header_date' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('MACHINING PROCESS SHEET');
        $response->assertSee('Admin User (lib: 295)');
        $response->assertSee('process-sheet-title-row--machining', false);
        $response->assertSee('padding-right: var(--process-header-qr-clearance, calc(var(--print-mark-qr-size, 40px) + 8mm));', false);
        $response->assertSee('--print-mark-qr-size: 40px;', false);
        $response->assertSee('system-print-qr', false);
        $response->assertSee('margin-left: 1ch;', false);
        $response->assertSee('top: .12em;', false);

        $legacyPrintMark = PrintMark::query()->create([
            'token' => 'LEGACYFIG234',
            'workorder_id' => $workorder->id,
            'workorder_number' => 'W' . $workorder->number,
            'form_name' => 'CADMIUM PLATING',
            'requirement_warnings' => [],
            'printed_by_name' => 'Admin User',
            'printed_at' => now(),
        ]);

        $this->get(route('print-mark.show', $legacyPrintMark->token))
            ->assertOk()
            ->assertSee('Missing required FIG')
            ->assertSee('print-mark-warning', false);
    }

    private function htmlBetween(string $html, string $startNeedle, string $endNeedle): string
    {
        $start = strpos($html, $startNeedle);
        $this->assertNotFalse($start, "Start marker [{$startNeedle}] not found.");

        $end = strpos($html, $endNeedle, $start + strlen($startNeedle));
        $this->assertNotFalse($end, "End marker [{$endNeedle}] not found.");

        return substr($html, $start, $end - $start);
    }
}
