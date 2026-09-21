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

    /** @dataProvider sleeveBushingQuantities */
    public function test_manufacture_sleeve_order_does_not_get_a_crossed_out_bushing_duplicate(?int $bushingQty): void
    {
        // Production WO107951: Manufacture 14-5/2821-0000RS20, KIT=0,
        // is_bush=1, qty=1; originally no separate WoBushingLine.
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $part = Component::create(['manual_id' => $wo->unit->manual_id,
            'ipl_num' => '14-5', 'part_number' => '2821-0000RS20', 'name' => 'REPAIR SLEEVE',
            'bush_ipl_num' => '14-5', 'is_bush' => true, 'kit' => false, 'units_assy' => 1]);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id,
            'order_component_id' => $part->id, 'qty' => 1, 'tdr_type' => Tdr::TYPE_MANUFACTURE_ORDER,
            'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id,
            'codes_id' => Code::firstOrCreate(['name' => 'Manufacture'])->id]);
        if ($bushingQty !== null) {
            $bushing = WoBushing::create(['workorder_id' => $wo->id]);
            WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id,
                'component_id' => $part->id, 'qty' => $bushingQty, 'qty_remaining' => $bushingQty, 'do_not_order' => false]);
        }
        $response = $this->actingAs($admin)->get(route('tdrs.prlForm', $wo))->assertOk();
        $rows = collect($response->viewData('ordersParts'));
        $this->assertCount($bushingQty === 2 ? 2 : 1, $rows);
        $this->assertSame($tdr->id, $rows->first()->id);
        $this->assertSame(1, (int) $rows->first()->qty);
        $this->assertSame($bushingQty === 2 ? 1 : 0,
            app(\App\Http\Controllers\Admin\TdrPrintFormController::class)->countBushingPrlRows($wo));
        foreach ($rows as $row) {
            $this->assertFalse((bool) data_get($row, 'prl_crossed_out', false));
            $this->assertNotContains(data_get($row, 'codes.code'), ['K', 'KIT']);
        }
        if ($bushingQty === 2) {
            $this->assertSame(1, $rows->last()['qty']);
            $this->assertSame(1, $rows->last()['prl_part_numbers'][0]['qty']);
        } else {
            $this->assertSame(1, substr_count($response->getContent(), '2821-0000RS20'));
        }
        $this->assertFalse((bool) $part->fresh()->kit);
        $this->assertSame(1, (int) $tdr->fresh()->qty);
    }

    public static function sleeveBushingQuantities(): array
    {
        return ['TDR only, WO107951' => [null], 'also selected in Bushings' => [1], 'larger bushing order' => [2]];
    }

    public function test_tdr_bushing_dedup_keeps_other_variants_and_same_pn_in_other_positions(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $parts = collect();
        foreach ([['14-5', '14-5', 'SLEEVE'], ['14-6', '14-5', 'SLEEVE-OS'], ['14-10', '14-10', 'SLEEVE']] as [$ipl, $base, $pn]) {
            $parts->push(Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => $ipl,
                'part_number' => $pn, 'name' => 'Sleeve', 'bush_ipl_num' => $base,
                'is_bush' => true, 'kit' => false, 'units_assy' => 2]));
        }
        // The ordered component, not the inspected source component, is authoritative.
        Tdr::create(['workorder_id' => $wo->id, 'component_id' => $parts[2]->id,
            'order_component_id' => $parts[0]->id, 'qty' => 1,
            'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id,
            'codes_id' => Code::firstOrCreate(['name' => 'Manufacture'])->id]);
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        foreach ([$parts[1], $parts[2]] as $part) {
            WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id,
                'component_id' => $part->id, 'qty' => 1, 'qty_remaining' => 1, 'do_not_order' => false]);
        }
        $response = $this->actingAs($admin)->get(route('tdrs.prlForm', $wo))->assertOk();
        $rows = collect($response->viewData('ordersParts'));
        $this->assertCount(3, $rows);
        $options = $rows->filter(fn ($row) => is_array($row))->flatMap(fn ($row) => $row['prl_part_numbers']);
        $this->assertEqualsCanonicalizing([$parts[1]->id, $parts[2]->id], $options->pluck('component_id')->all());
        foreach ($options as $option) {
            $this->assertFalse($option['crossed_out']);
            $this->assertSame(1, $option['qty']);
        }
    }

    /** @dataProvider familyOrders */
    public function test_kit_pays_for_shared_original_oversize_quantity_without_changing_flags(array $quantities): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $parts = collect();
        foreach ([['14-100', 2, true], ['14-101', 1, false], ['14-102', 1, true]] as [$ipl, $qty, $kit]) {
            $parts->push(Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => $ipl,
                'part_number' => 'FAMILY-'.$ipl, 'name' => 'Shoulder bushing', 'bush_ipl_num' => '14-100',
                'is_bush' => true, 'units_assy' => $qty, 'kit' => $kit]));
        }
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        foreach ($quantities as $index => $qty) {
            WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id,
                'component_id' => $parts[$index]->id, 'qty' => $qty, 'qty_remaining' => $qty, 'do_not_order' => false]);
        }
        $kit = $this->actingAs($admin)->get(route('tdrs.kitForm', $wo))->assertOk();
        $row = collect($kit->viewData('ordersParts'))->firstWhere('prl_bushing_group', '14-100');
        $this->assertSame(2, $row['bushing_kit_capacity']);
        $this->assertCount(3, $row['prl_part_numbers']);
        $options = collect($row['prl_part_numbers'])->keyBy('component_id');
        foreach ($parts as $index => $part) {
            $this->assertSame($quantities[$index] ?? null, $options[$part->id]['qty']);
            $this->assertSame($quantities !== [] && ! isset($quantities[$index]), $options[$part->id]['crossed_out']);
        }
        $extra = $this->get(route('tdrs.prlForm', $wo))->assertOk();
        $this->assertCount(0, $extra->viewData('ordersParts'));
        $this->patchJson(route('tdrs.kit-crossouts.update', [$wo, $parts[1]]), ['crossed_out' => true])->assertOk();
        $this->assertFalse((bool) $parts[1]->fresh()->kit);
        $this->assertSame('1', (string) $parts[1]->fresh()->units_assy);
    }

    public static function familyOrders(): array
    {
        return ['pending' => [[]], 'two original' => [[0 => 2]], 'two oversize' => [[1 => 2]],
            'mixed' => [[0 => 1, 1 => 1]], 'one only' => [[1 => 1]]];
    }

    public function test_identical_pn_in_another_bushing_position_does_not_use_the_kit_family_budget(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        foreach (['14-100' => true, '14-200' => false] as $ipl => $kit) {
            $part = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => $ipl,
                'part_number' => 'SHARED-PN', 'name' => 'Bushing', 'bush_ipl_num' => $ipl,
                'is_bush' => true, 'units_assy' => 2, 'kit' => $kit]);
            WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id,
                'component_id' => $part->id, 'qty' => 2, 'qty_remaining' => 2, 'do_not_order' => false]);
        }
        $response = $this->actingAs($admin)->get(route('tdrs.prlForm', $wo))->assertOk();
        $rows = collect($response->viewData('ordersParts'));
        $this->assertCount(1, $rows);
        $this->assertSame('14-200', $rows->first()['prl_bushing_group']);
        $this->assertSame(2, $rows->first()['prl_part_numbers'][0]['qty']);
    }

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
