<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\Component;
use App\Models\Necessary;
use App\Models\Tdr;
use App\Models\WoBushing;
use App\Models\WoBushingLine;
use App\Models\WorkorderKitPrlCrossout;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class CombinedPrlTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_non_overhaul_prl_preserves_kit_parts_and_quantities_and_has_no_kit_actions(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $part = Component::create([
            'manual_id' => $workorder->unit->manual_id, 'part_number' => 'REPAIR-KIT-PART',
            'name' => 'Kit flagged part', 'ipl_num' => '1-100', 'kit' => true, 'units_assy' => 2,
        ]);
        $bush = Component::create([
            'manual_id' => $workorder->unit->manual_id, 'part_number' => 'REPAIR-KIT-BUSH',
            'name' => 'Kit flagged bushing', 'ipl_num' => '1-200', 'bush_ipl_num' => '1-200',
            'kit' => true, 'is_bush' => true, 'units_assy' => 2,
        ]);
        Tdr::create([
            'workorder_id' => $workorder->id, 'component_id' => $part->id,
            'order_component_id' => $part->id, 'qty' => 3,
            'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id,
            'codes_id' => Code::firstOrCreate(['name' => 'Damaged'], ['code' => 'DMG'])->id,
        ]);
        $order = WoBushing::create(['workorder_id' => $workorder->id]);
        Component::create([
            'manual_id' => $workorder->unit->manual_id, 'part_number' => 'UNORDERED-REPAIR-BUSH',
            'name' => 'Unused alternative', 'ipl_num' => '1-201', 'bush_ipl_num' => '1-200', 'is_bush' => true,
        ]);
        WoBushingLine::create([
            'wo_bushing_id' => $order->id, 'workorder_id' => $workorder->id,
            'component_id' => $bush->id, 'qty' => 3, 'qty_remaining' => 3, 'do_not_order' => false,
        ]);
        WorkorderKitPrlCrossout::create([
            'workorder_id' => $workorder->id, 'component_id' => $part->id, 'created_by_user_id' => $admin->id,
        ]);
        foreach (['Repair', 'Test & inspect', '60M', '96M'] as $name) {
            $workorder->update(['instruction_id' => $this->createInstruction(['name' => $name])->id]);
            $admin->refresh();
            $response = $this->actingAs($admin)->withSession([
                'auth.version' => (int) $admin->auth_version,
                'password_hash_web' => $admin->getAuthPassword(),
            ])->get(route('tdrs.prlForm', $workorder));
            $response->assertOk()->assertSee('REPAIR-KIT-PART')->assertSee('REPAIR-KIT-BUSH');
            $response->assertDontSee('UNORDERED-REPAIR-BUSH');
            $this->assertFalse($response->viewData('kitInteractive'));
            foreach ($response->viewData('ordersParts') as $row) {
                $this->assertFalse((bool) data_get($row, 'prl_crossed_out', false));
                $this->assertSame(3, (int) data_get($row, 'qty'));
                foreach (data_get($row, 'prl_part_numbers', []) as $option) {
                    $this->assertFalse((bool) ($option['crossed_out'] ?? false));
                }
            }
            $this->get(route('tdrs.kitForm', $workorder))->assertRedirect(route('tdrs.prlForm', $workorder));
            $this->patchJson(route('tdrs.kit-crossouts.update', [$workorder, $part]), ['crossed_out' => true])
                ->assertUnprocessable();
        }
        $this->get(route('tdrs.show', $workorder))->assertOk()
            ->assertViewHas('kitPrlCount', 0)
            ->assertDontSee(route('tdrs.kitForm', $workorder), false);
    }

    public function test_prl_combines_extra_parts_and_bushings_and_removes_parts_already_supplied_by_kit(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $manualId = (int) $workorder->unit->manual_id;
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);
        $damaged = Code::query()->firstOrCreate(['name' => 'Damaged'], ['code' => 'DMG']);

        $extraPart = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'EXTRA-PART-100',
            'name' => 'Extra ordered part',
            'ipl_num' => '1-100',
        ]);
        $kitPart = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'KIT-PART-200',
            'name' => 'Already paid KIT part',
            'ipl_num' => '1-200',
            'units_assy' => 1,
            'kit' => true,
        ]);
        $inspectedPart = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'INSPECTED-SOURCE-100',
            'name' => 'Inspected source',
            'ipl_num' => '1-90',
        ]);
        foreach ([[$extraPart, 2], [$kitPart, 1]] as [$component, $qty]) {
            Tdr::query()->create([
                'workorder_id' => $workorder->id,
                'component_id' => $inspectedPart->id,
                'order_component_id' => $component->id,
                'codes_id' => $damaged->id,
                'necessaries_id' => $orderNew->id,
                'qty' => $qty,
            ]);
        }

        $kitBushing = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'BUSH-IN-KIT',
            'name' => 'Bushing supplied by KIT',
            'ipl_num' => '8-230',
            'bush_ipl_num' => '8-230',
            'units_assy' => 1,
            'is_bush' => true,
            'kit' => true,
        ]);
        $extraBushing = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'BUSH-EXTRA',
            'name' => 'Bushing ordered separately',
            'ipl_num' => '8-240',
            'bush_ipl_num' => '8-240',
            'units_assy' => 1,
            'is_bush' => true,
        ]);
        Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'UNUSED-OVERSIZE-FOR-KIT-BUSH',
            'name' => 'Unselected alternative',
            'ipl_num' => '8-231',
            'bush_ipl_num' => '8-230',
            'is_bush' => true,
        ]);
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);
        foreach ([$kitBushing, $extraBushing] as $index => $component) {
            WoBushingLine::query()->create([
                'wo_bushing_id' => $woBushing->id,
                'workorder_id' => $workorder->id,
                'component_id' => $component->id,
                'qty' => 1,
                'qty_remaining' => 1,
                'do_not_order' => false,
                'sort_order' => $index,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('tdrs.prlForm', $workorder));
        $content = $response->getContent();

        $response->assertOk();
        $response->assertSee('EXTRA-PART-100');
        $response->assertDontSee('KIT-PART-200');
        $response->assertDontSee('BUSH-IN-KIT');
        $response->assertDontSee('UNUSED-OVERSIZE-FOR-KIT-BUSH');
        $response->assertSee('BUSH-EXTRA');
        $response->assertDontSee('Included in KIT');
        $this->assertMatchesRegularExpression(
            '/data-prl-component-id="'.$extraBushing->id.'"(?![^>]*data-prl-part-number-crossed-out)[^>]*>BUSH-EXTRA/s',
            $content
        );

        $legacyResponse = $this->actingAs($admin)->get(route('tdrs.bushPrlForm', $workorder));
        $legacyResponse->assertOk()->assertSee('EXTRA-PART-100');
        $legacyResponse->assertSee('const PRINT_SETTINGS_PROFILE = "prl";', false);

        $showResponse = $this->actingAs($admin)->get(route('tdrs.show', $workorder));
        $showResponse->assertOk();
        $showResponse->assertSee(route('tdrs.prlForm', $workorder), false);
        $showResponse->assertDontSee(route('tdrs.bushPrlForm', $workorder), false);
        $this->assertSame(3, (int) $showResponse->viewData('prlPartsCount'));

        $this->actingAs($admin)->get(route('tdrs.kitForm', $workorder))
            ->assertOk()->assertSee('BUSH-IN-KIT')->assertSee('KIT-PART-200');
    }

    public function test_kit_quantities_are_deducted_from_extra_part_and_bushing_order_quantities(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $manualId = (int) $workorder->unit->manual_id;
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);
        $damaged = Code::query()->firstOrCreate(['name' => 'Damaged'], ['code' => 'DMG']);

        $kitPart = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'PARTIAL-KIT-PART',
            'name' => 'Partially supplied part',
            'ipl_num' => '2-100',
            'units_assy' => 1,
            'kit' => true,
        ]);
        $inspectedPart = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'PARTIAL-INSPECTED-SOURCE',
            'name' => 'Inspected source',
            'ipl_num' => '2-90',
        ]);
        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $inspectedPart->id,
            'order_component_id' => $kitPart->id,
            'codes_id' => $damaged->id,
            'necessaries_id' => $orderNew->id,
            'qty' => 3,
        ]);

        $kitBushing = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'PARTIAL-KIT-BUSH',
            'name' => 'Partially supplied bushing',
            'ipl_num' => '9-100',
            'bush_ipl_num' => '9-100',
            'units_assy' => 1,
            'is_bush' => true,
            'kit' => true,
        ]);
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);
        WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $kitBushing->id,
            'qty' => 3,
            'qty_remaining' => 3,
            'do_not_order' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.prlForm', $workorder));

        $response->assertOk()->assertViewHas('ordersParts', function ($rows) use ($kitPart, $kitBushing): bool {
            $rows = collect($rows);
            $partRow = $rows->first(function ($row) use ($kitPart): bool {
                return $row instanceof Tdr && (int) ($row->order_component_id ?? 0) === $kitPart->id;
            });
            $bushingRow = $rows->firstWhere('prl_bushing_group', '9-100');
            $bushingOption = collect($bushingRow['prl_part_numbers'] ?? [])->firstWhere('component_id', $kitBushing->id);

            return (int) ($partRow?->qty ?? 0) === 2
                && (int) ($bushingOption['qty'] ?? 0) === 2
                && empty($bushingOption['crossed_out']);
        });
    }

    public function test_prl_omits_kit_options_but_preserves_selected_non_kit_variant_in_same_group(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $bushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);
        $definitions = [
            ['MIXED-KIT-BASE', true, true],
            ['MIXED-KIT-UNSELECTED', true, false],
            ['MIXED-EXTRA-OVERSIZE', false, true],
        ];
        foreach ($definitions as $index => [$pn, $inKit, $selected]) {
            $component = Component::query()->create([
                'manual_id' => $workorder->unit->manual_id,
                'part_number' => $pn,
                'name' => 'Mixed group bushing',
                'ipl_num' => '8-'.(230 + $index),
                'bush_ipl_num' => '8-230',
                'is_bush' => true,
                'kit' => $inKit,
                'units_assy' => 1,
            ]);
            if ($selected) {
                WoBushingLine::query()->create([
                    'wo_bushing_id' => $bushing->id,
                    'workorder_id' => $workorder->id,
                    'component_id' => $component->id,
                    'qty' => 1,
                    'qty_remaining' => 1,
                    'do_not_order' => false,
                    'sort_order' => $index,
                ]);
            }
        }

        $response = $this->actingAs($admin)->get(route('tdrs.prlForm', $workorder));
        $response->assertOk()
            ->assertDontSee('MIXED-KIT-BASE')
            ->assertDontSee('MIXED-KIT-UNSELECTED')
            ->assertSee('MIXED-EXTRA-OVERSIZE')
            ->assertDontSee('Included in KIT');
        $rows = collect($response->viewData('ordersParts'));
        $this->assertCount(1, $rows);
        $this->assertCount(1, $rows->first()['prl_part_numbers']);
        $this->assertFalse($rows->first()['prl_part_numbers'][0]['crossed_out']);
        $this->actingAs($admin)->get(route('tdrs.kitForm', $workorder))
            ->assertOk()->assertSee('MIXED-KIT-BASE')->assertSee('MIXED-KIT-UNSELECTED');
    }

    public function test_kit_form_only_uses_manual_crossouts_for_choice_options(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $manualId = (int) $workorder->unit->manual_id;
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);
        $damaged = Code::query()->firstOrCreate(['name' => 'Damaged'], ['code' => 'DMG']);

        $kitPart = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'KIT-MANUAL-ONLY',
            'name' => 'KIT part also selected for order',
            'ipl_num' => '3-100',
            'kit' => true,
        ]);
        $inspectedPart = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'KIT-INSPECTED-SOURCE',
            'name' => 'Inspected source',
            'ipl_num' => '3-90',
        ]);
        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $inspectedPart->id,
            'order_component_id' => $kitPart->id,
            'codes_id' => $damaged->id,
            'necessaries_id' => $orderNew->id,
            'qty' => 1,
        ]);
        $variantA = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'KIT-BUSH-230A',
            'name' => 'KIT bushing A',
            'ipl_num' => '8-230A',
            'bush_ipl_num' => '8-230',
            'is_bush' => true,
            'kit' => true,
        ]);
        $variantB = Component::query()->create([
            'manual_id' => $manualId,
            'part_number' => 'KIT-BUSH-230B',
            'name' => 'KIT bushing B',
            'ipl_num' => '8-230B',
            'bush_ipl_num' => '8-230',
            'is_bush' => true,
            'kit' => true,
        ]);
        WorkorderKitPrlCrossout::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $variantB->id,
            'created_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.kitForm', $workorder));

        $response->assertOk()->assertViewHas('ordersParts', function ($rows) use ($kitPart, $variantA, $variantB): bool {
            $rows = collect($rows);
            $partRow = $rows->first(fn (array $row): bool => (int) ($row['component']['id'] ?? 0) === $kitPart->id);
            $bushingRow = $rows->firstWhere('prl_bushing_group', '8-230');
            $options = collect($bushingRow['prl_part_numbers'] ?? [])->keyBy('component_id');

            return empty($partRow['prl_crossed_out'])
                && empty($options->get($variantA->id)['crossed_out'])
                && ! empty($options->get($variantB->id)['manual_crossed_out'])
                && ! empty($options->get($variantB->id)['crossed_out']);
        });
    }
}
