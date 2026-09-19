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
