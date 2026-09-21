<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\LogCard;
use App\Models\ManualPartGroup;
use App\Services\LogCardAssemblyIdentity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class LogCardAssemblyIdentityTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function scopedMainFitting(): array
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $part = Component::create([
            'manual_id' => $wo->unit->manual_id, 'part_number' => '2821-0111',
            'ipl_num' => '14-210A', 'name' => 'Main Fitting', 'units_assy' => 1, 'log_card' => true,
            'assy_part_number' => '2821A0100-02', 'assy_ipl_num' => '14-1A',
        ]);
        $options = [];
        foreach (['02' => '14-1A', '03' => '14-1C'] as $suffix => $ipl) {
            $pn = '2821A0100-'.$suffix;
            $head = Component::create(['manual_id' => $part->manual_id, 'part_number' => $pn, 'name' => 'Main Fitting ASSY', 'ipl_num' => $ipl]);
            $part->assemblies()->create(['assy_part_number' => $pn, 'assy_ipl_num' => $ipl, 'units_assy' => 1]);
            $group = ManualPartGroup::create([
                'manual_id' => $part->manual_id, 'code' => uniqid('LC-SCOPE-'), 'name' => $pn,
                'type' => 'assy', 'behavior' => 'bundle', 'applies_to' => ['prl'],
            ]);
            $option = $group->options()->create(['component_id' => $head->id, 'part_number' => $pn, 'ipl_num' => $ipl, 'option_kind' => 'assy']);
            $option->coverages()->create(['component_id' => $part->id, 'qty' => 1]);
            $options[] = $option;
        }
        $wo->update(['scope_type' => 'part_group_option', 'scope_part_group_option_id' => $options[1]->id]);
        return [$admin, $wo, $part, ...$options];
    }

    public function test_draft_uses_selected_wo_assy_not_first_shared_group_on_web_and_mobile(): void
    {
        [$admin, $wo, $part, $old, $selected] = $this->scopedMainFitting();
        $controller = app(\App\Http\Controllers\Admin\LogCardController::class);
        $method = new \ReflectionMethod($controller, 'buildLogCardAssyChoiceGroups');
        $method->setAccessible(true);
        foreach ([$selected, $old] as $scope) {
            $wo->update(['scope_part_group_option_id' => $scope->id]);
            [$groups] = $method->invoke($controller, $part->manual_id, collect([$part]), $wo);
            $this->assertCount(1, $groups);
            $this->assertSame($scope->part_number, $groups[0]['choices'][0]['assy_part_number']);
            $this->actingAs($admin)->get(route('log_card.partial', $wo))->assertOk()->assertSee($scope->part_number);

            $mobile = app(\App\Http\Controllers\Api\Mobile\MobileApiController::class);
            $mobileMethod = new \ReflectionMethod($mobile, 'mobileLogCardAssyGroups');
            $mobileMethod->setAccessible(true);
            [$mobileGroups] = $mobileMethod->invoke($mobile, $part->manual_id, collect([$part]), fn ($c) => ['component_id' => $c->id], $wo);
            $this->assertCount(1, $mobileGroups);
            $this->assertSame($scope->part_number, $mobileGroups[0]['choices'][0]['assy_part_number']);
        }
    }

    public function test_technician_can_explicitly_select_other_own_assy_and_it_survives_reopen_and_save(): void
    {
        [, $wo, $part, $old, $selected] = $this->scopedMainFitting();
        $tech = $this->createUserWithRole('Technician');
        $this->actingAs($tech)->get(route('log_card.partial', $wo))->assertOk()
            ->assertSee('Select your ASSY')->assertSee($old->part_number)->assertSee($selected->part_number);
        $row = ['component_id' => (string) $part->id, 'manual_id' => (string) $part->manual_id,
            'manual_part_group_choice' => 'component', 'manual_part_group_id' => (string) $old->manual_part_group_id,
            'assy_selection_explicit' => '1', 'serial_number' => 'IRON-123', 'assy_serial_number' => 'ASSY-456'];
        $this->postJson(route('log_card.store'), ['workorder_id' => $wo->id, 'component_data' => json_encode([$row])])->assertOk();
        $log = LogCard::where('workorder_id', $wo->id)->firstOrFail();
        $saved = json_decode($log->component_data, true)[0];
        $this->assertSame($old->part_number, $saved['assy_part_number']);
        $this->assertSame('1', $saved['assy_selection_explicit']);
        $response = $this->get(route('log_card.partial', $wo))->assertOk();
        $this->assertSame($old->part_number, $response->viewData('componentData')[0]['assy_part_number']);
        $this->get(route('log_card.partial', $wo).'?edit=1')->assertOk()->assertSee('Select your ASSY');
        $this->get(route('log_card.logCardForm', $wo))->assertOk()->assertSee($old->part_number);
        $this->putJson(route('log_card.update', $log), ['workorder_id' => $wo->id, 'component_data' => json_encode([$saved])])->assertOk();
        $saved = json_decode($log->fresh()->component_data, true)[0];
        $this->assertSame($old->part_number, $saved['assy_part_number']);
        $this->assertSame('IRON-123', $saved['serial_number']);
        $this->assertSame('ASSY-456', $saved['assy_serial_number']);

        $mobile = app(\App\Http\Controllers\Api\Mobile\MobileApiController::class);
        $method = new \ReflectionMethod($mobile, 'buildMobileLogCardRows');
        $method->setAccessible(true);
        $mobileRows = $method->invoke($mobile, [$row], $wo, $log);
        $mobilePart = collect($mobileRows)->firstWhere('component_id', (string) $part->id);
        $this->assertSame($old->part_number, $mobilePart['assy_part_number']);
        $this->assertSame('1', $mobilePart['assy_selection_explicit']);
    }

    public function test_explicit_selection_cannot_use_unrelated_or_bom_only_assembly(): void
    {
        [$admin, $wo, $part, $old, $selected] = $this->scopedMainFitting();
        $part->assemblies()->where('assy_part_number', $old->part_number)->delete();
        $part->update(['assy_part_number' => null]);
        $row = ['component_id' => (string) $part->id, 'manual_id' => (string) $part->manual_id,
            'manual_part_group_choice' => 'component', 'manual_part_group_id' => (string) $old->manual_part_group_id,
            'assy_selection_explicit' => '1'];
        $this->actingAs($admin)->postJson(route('log_card.store'), ['workorder_id' => $wo->id, 'component_data' => json_encode([$row])])->assertStatus(422);
        $this->assertNull(LogCard::where('workorder_id', $wo->id)->first());
    }

    public function test_mobile_template_and_store_accept_explicit_own_assembly(): void
    {
        [, $wo, $part, $old] = $this->scopedMainFitting();
        $tech = $this->createUserWithRole('Technician');
        $this->actingAs($tech)->getJson(route('mobile.log-card.template', $wo->id))->assertOk()
            ->assertJsonPath('data.assy_groups.0.choices.0.assembly_choices.0.part_number', $old->part_number);
        $this->postJson(route('mobile.log-card.store', $wo->id), ['rows' => [[
            'component_id' => $part->id, 'manual_id' => $part->manual_id,
            'manual_part_group_id' => $old->manual_part_group_id, 'manual_part_group_choice' => 'component',
            'assy_selection_explicit' => '1', 'serial_number' => 'MOBILE-SN',
        ]]])->assertOk();
        $log = LogCard::where('workorder_id', $wo->id)->firstOrFail();
        $row = collect(json_decode($log->component_data, true))->firstWhere('component_id', (string) $part->id);
        $this->assertSame($old->part_number, $row['assy_part_number']);
        $this->assertSame('1', $row['assy_selection_explicit']);
    }

    public function test_active_saved_wrong_assy_is_projected_without_read_writes_and_corrected_on_explicit_save(): void
    {
        [$admin, $wo, $part, $old, $selected] = $this->scopedMainFitting();
        $row = [
            'component_id' => (string) $part->id, 'manual_id' => (string) $part->manual_id,
            'manual_part_group_id' => (string) $old->manual_part_group_id, 'manual_part_group_choice' => 'component',
            'part_number' => $part->part_number, 'assy_part_number' => $old->part_number, 'assy_ipl_num' => $old->ipl_num,
            'serial_number' => 'METAL-SN', 'assy_serial_number' => 'ASSY-SN', 'included' => '1',
        ];
        $log = LogCard::create(['workorder_id' => $wo->id, 'component_data' => json_encode([$row])]);
        $response = $this->actingAs($admin)->get(route('log_card.partial', $wo))->assertOk();
        $projected = $response->viewData('componentData')[0];
        $this->assertSame($selected->part_number, $projected['assy_part_number']);
        $this->assertSame($selected->ipl_num, $projected['assy_ipl_num']);
        $this->assertSame('METAL-SN', $projected['serial_number']);
        $this->assertSame('ASSY-SN', $projected['assy_serial_number']);
        $this->get(route('log_card.logCardForm', $wo))->assertOk()->assertSee($selected->part_number);
        $this->assertSame($old->part_number, json_decode($log->fresh()->component_data, true)[0]['assy_part_number']);
        $this->putJson(route('log_card.update', $log), ['workorder_id' => $wo->id, 'component_data' => json_encode([$row])])->assertStatus(422);
        $this->putJson(route('log_card.update', $log), ['workorder_id' => $wo->id, 'component_data' => json_encode([$projected])])->assertOk();
        $saved = json_decode($log->fresh()->component_data, true)[0];
        $this->assertSame($selected->part_number, $saved['assy_part_number']);
        $this->assertSame((string) $selected->manual_part_group_id, (string) $saved['manual_part_group_id']);
        $this->assertSame($part->part_number, $saved['part_number']);
        $this->assertSame('ASSY-SN', $saved['assy_serial_number']);
    }

    public function test_completed_card_identity_and_received_scope_are_not_replaced_by_modified_scope(): void
    {
        [, $wo, $part, $old, $selected] = $this->scopedMainFitting();
        $row = ['component_id' => $part->id, 'manual_part_group_choice' => 'component', 'assy_part_number' => $old->part_number];
        $identity = app(LogCardAssemblyIdentity::class);
        $wo->forceFill(['modified_scope_part_group_option_id' => $old->id])->save();
        $this->assertSame($selected->part_number, $identity->cleanRows([$row], $wo)[0]['assy_part_number']);
        $wo->forceFill(['done_at' => now()])->save();
        $this->assertSame([$row], $identity->cleanRows([$row], $wo));
        $this->assertSame([$row], $identity->cleanRows([$row]));
    }

    public function test_qa_incoming_projection_does_not_change_explicit_outgoing_identity(): void
    {
        [$admin, $wo, $part, $old, $selected] = $this->scopedMainFitting();
        $row = ['component_id' => $part->id, 'manual_id' => $part->manual_id,
            'manual_part_group_choice' => 'component', 'manual_part_group_id' => $old->manual_part_group_id,
            'assy_part_number' => $old->part_number, 'assy_ipl_num' => $old->ipl_num];
        $log = LogCard::create(['workorder_id' => $wo->id,
            'component_data' => json_encode([$row]), 'component_data_out' => json_encode([$row])]);
        $controller = app(\App\Http\Controllers\Admin\QualityAssuranceController::class);
        $data = $controller->logCardForm(\Illuminate\Http\Request::create('/'), $wo)->getData();
        $this->assertSame($selected->part_number, $data['componentData'][0]['assy_part_number']);
        $this->assertSame($old->part_number, $data['componentDataOut'][0]['assy_part_number']);
        $this->assertSame($old->part_number, json_decode($log->fresh()->component_data, true)[0]['assy_part_number']);
    }

    public function test_scoped_groups_keep_nested_assy_and_exclude_other_configuration(): void
    {
        [, $wo, $part, $old, $selected] = $this->scopedMainFitting();
        $nested = ManualPartGroup::create(['manual_id' => $part->manual_id, 'code' => uniqid('NEST-'), 'name' => 'Nested', 'type' => 'assy', 'behavior' => 'bundle']);
        $option = $nested->options()->create(['part_number' => 'NESTED', 'option_kind' => 'assy']);
        $selected->coverages()->create(['covered_manual_part_group_option_id' => $option->id, 'qty' => 1]);
        $groups = ManualPartGroup::where('manual_id', $part->manual_id)->with('options.coverages')->get();
        $allowed = app(LogCardAssemblyIdentity::class)->groupsForWorkorder($groups, $wo)->pluck('id')->all();
        $this->assertContains($nested->id, $allowed);
        $this->assertContains($selected->manual_part_group_id, $allowed);
        $this->assertNotContains($old->manual_part_group_id, $allowed);
    }

    public function test_axle_in_two_boms_is_a_separate_log_card_part_and_old_parent_pn_is_not_printed(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $axle = Component::create([
            'manual_id' => $wo->unit->manual_id, 'part_number' => '2821-0202',
            'ipl_num' => '13-70', 'name' => 'AXLE, WHEEL', 'units_assy' => 1, 'log_card' => true,
        ]);
        foreach (['2821A0200-02', '2821A0200-03'] as $pn) {
            $head = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => $pn, 'name' => 'SLIDING TUBE ASSY', 'ipl_num' => $pn]);
            $group = ManualPartGroup::create([
                'manual_id' => $wo->unit->manual_id, 'code' => uniqid('LC-'), 'name' => $pn,
                'type' => 'assy', 'behavior' => 'bundle', 'applies_to' => ['prl'],
            ]);
            $option = $group->options()->create(['component_id' => $head->id, 'part_number' => $pn, 'ipl_num' => '13-1', 'option_kind' => 'assy']);
            $option->coverages()->create(['component_id' => $axle->id, 'qty' => 1]);
        }
        $this->actingAs($admin)->get(route('log_card.partial', $wo))->assertOk()
            ->assertSee('2821-0202')->assertDontSee('class="lc-assy-group-row"', false);
        $row = [
            'component_id' => (string) $axle->id, 'included' => '1', 'serial_number' => 'L1708',
            'manual_part_group_choice' => 'component', 'manual_part_group_id' => (string) $group->id,
            'assy_part_number' => '2821A0200-02', 'assy_ipl_num' => '13-1A',
        ];
        $log = LogCard::create(['workorder_id' => $wo->id, 'component_data' => json_encode([$row])]);
        $this->get(route('log_card.logCardForm', $wo))->assertOk()->assertSee('2821-0202')
            ->assertSee('L1708')->assertDontSee('(2821A0200-02)');
        $partial = $this->get(route('log_card.partial', $wo))->assertOk();
        $this->assertSame('', $partial->viewData('componentData')[0]['assy_part_number']);
        // Rendering does not mutate saved history.
        $this->assertSame('2821A0200-02', json_decode($log->fresh()->component_data, true)[0]['assy_part_number']);
        $controller = app(\App\Http\Controllers\Admin\LogCardController::class);
        $normalize = new \ReflectionMethod($controller, 'normalizeLogCardComponentData');
        $normalize->setAccessible(true);
        $normalized = json_decode($normalize->invoke($controller, json_encode([$row]), $wo), true);
        $this->assertSame('', $normalized[0]['assy_part_number']);
        $this->assertSame('L1708', $normalized[0]['serial_number']);
    }

    public function test_explicit_component_assembly_identity_is_preserved(): void
    {
        $part = Component::create([
            'manual_id' => $this->createManual()->id, 'part_number' => 'PART', 'name' => 'Part',
            'ipl_num' => '1-1', 'assy_part_number' => 'REAL-ASSY', 'assy_ipl_num' => '1-2',
        ]);
        $identity = app(LogCardAssemblyIdentity::class);
        $this->assertTrue($identity->hasOwnAssembly($part, 'REAL-ASSY'));
        $row = ['component_id' => $part->id, 'manual_part_group_choice' => 'component', 'assy_part_number' => 'REAL-ASSY', 'assy_serial_number' => 'AS-123'];
        $this->assertSame([$row], $identity->cleanRows([$row]));
    }
}
