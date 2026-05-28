<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\Component;
use App\Models\ComponentAssembly;
use App\Models\Condition;
use App\Models\LogCard;
use App\Models\ManualIplBranchRule;
use App\Models\Necessary;
use App\Models\Tdr;
use App\Models\TdrProcess;
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

    public function test_only_system_admin_can_replace_tdr_component_and_serial_number_from_edit(): void
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
        $this->assertSame('SN-OLD', $tdr->serial_number);
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

    public function test_get_components_by_manual_prefers_unit_eff_detail_but_keeps_no_eff_letter_variants_and_assemblies(): void
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
        $this->assertNotContains($universalSameBase->id, $componentIds);
        $this->assertContains($noEffA->id, $componentIds);
        $this->assertContains($noEffB->id, $componentIds);

        $noEffAResponse = $components->firstWhere('id', $noEffA->id);
        $this->assertSame($assembly->id, $noEffAResponse['assemblies'][0]['id'] ?? null);
        $this->assertSame('ASSY-240A', $noEffAResponse['assemblies'][0]['assy_part_number'] ?? null);
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
            'deleted_at' => null,
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
        $this->assertSoftDeleted('tdrs', ['id' => $missingTdr->id]);
        $this->assertDatabaseHas('tdrs', [
            'id' => $stdCarrier->id,
            'tdr_type' => Tdr::TYPE_STD_LIST_CARRIER,
            'deleted_at' => null,
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
            'is_bush' => true,
        ]);
        $bushingB = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'BUSH-PN-110',
            'name' => 'Bushing Second',
            'ipl_num' => '1-110',
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
        $bushingResponse->assertSee('BUSH-PN-100');
        $bushingResponse->assertSee('BUSH-PN-110');
        $bushingResponse->assertSee('<h6>K</h6>', false);

        $bushResponse = $this->actingAs($admin)->get(route('tdrs.bushPrlForm', ['id' => $workorder->id]));
        $bushResponse->assertOk();
        $bushResponse->assertSee('PARTS REPLACEMENT LIST');
        $bushResponse->assertSee('BUSH-PN-100');
        $bushResponse->assertSee('BUSH-PN-110');
        $bushResponse->assertSee('<h6>K</h6>', false);

        $kitResponse = $this->actingAs($admin)->get(route('tdrs.kitForm', ['id' => $workorder->id]));
        $kitResponse->assertOk();
        $kitResponse->assertSee('PARTS REPLACEMENT LIST - KIT');
        $kitResponse->assertDontSee('KIT PARTS REPLACEMENT LIST');
        $kitResponse->assertDontSee('2<br />' . "\n" . '2', false);
        $kitResponse->assertSee('50A');
        $kitResponse->assertSee('50B');
        $kitResponse->assertSee('KIT-PN-50A');
        $kitResponse->assertSee('KIT-PN-50B');
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
        $showResponse->assertSeeInOrder([
            'TDR Form',
            'R&amp;M Form',
            'SP Form',
            'Bushing Form',
            'Log Card',
            'SB Form',
            'NDT STD',
            'CAD STD',
            'Stress STD',
            'Paint STD',
            'KIT',
            'PRL',
            'Bush PRL',
        ], false);
        $showResponse->assertSee('>2</span>', false);
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
