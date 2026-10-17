<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\TdrPrintFormController;
use App\Models\{Component, ManualPartGroup, Necessary, Tdr, WoBushing, WoBushingLine, Workorder};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class KitExtraBushingsTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function setupWorkorder(): Workorder
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);
        return $this->createWorkorder(['user_id' => $admin->id,
            'instruction_id' => $this->createOverhaulInstruction()->id]);
    }

    private function family(Workorder $wo, int $position, bool $kit): array
    {
        $group = ManualPartGroup::create(['manual_id' => $wo->unit->manual_id,
            'code' => 'EXTRA-'.$wo->id.'-'.$position, 'name' => 'Bushing', 'type' => 'oversize',
            'behavior' => 'choose_one', 'applies_to' => ['prl']]);
        $parts = [];
        foreach (['original', 'oversize'] as $index => $kind) {
            $part = Component::create(['manual_id' => $wo->unit->manual_id,
                'ipl_num' => '8-'.($position + $index), 'bush_ipl_num' => '8-'.$position,
                'part_number' => 'BUSH-'.$position.'-'.$kind, 'name' => 'BUSHING',
                'is_bush' => true, 'kit' => $kit && $index === 0,
                'units_assy' => $index === 0 ? 2 : 9]);
            $group->options()->create(['component_id' => $part->id, 'part_number' => $part->part_number,
                'ipl_num' => $part->ipl_num, 'option_kind' => $kind, 'sort_order' => $index]);
            $parts[] = $part;
        }
        return $parts;
    }

    private function printedQuantity(string $html, int $componentId): string
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $node = (new \DOMXPath($dom))->query('//*[@data-bushing-qty-component-id="'.$componentId.'"]')->item(0);
        $this->assertNotNull($node);
        return trim(str_replace("\u{00A0}", '', $node->textContent));
    }

    public function test_kit_e_parts_follow_extra_bushings_and_can_be_crossed_out(): void
    {
        $wo = $this->setupWorkorder();
        [$bushing] = $this->family($wo, 100, false);
        $bushing->update(['kit_e' => true]);
        $extra = Component::create(['manual_id' => $wo->unit->manual_id,
            'ipl_num' => '1-10', 'part_number' => 'EXTRA-PIN', 'name' => 'Pin', 'units_assy' => 2, 'kit_e' => true]);
        $response = $this->get(route('tdrs.kitForm', $wo))->assertOk()
            ->assertSee('EXTRA PARTS (ENTER CODE OR CROSS OUT)')->assertSee('EXTRA-PIN');
        $rows = collect($response->viewData('ordersParts'));
        $this->assertCount(2, $rows);
        $this->assertTrue($rows->first()['kit_extra_bushing']);
        $this->assertTrue($rows->last()['kit_extra_part']);
        $this->assertSame(2, $rows->last()['qty']);
        $this->assertCount(0, app(TdrPrintFormController::class)->kitRows($wo));
        $this->patchJson(route('tdrs.kit-crossouts.update', [$wo, $extra]), ['crossed_out' => true])->assertOk();
        $response = $this->get(route('tdrs.kitForm', $wo))->assertOk();
        $this->assertTrue(collect($response->viewData('ordersParts'))->last()['prl_crossed_out']);
        $this->patchJson(route('tdrs.kit-crossouts.update', [$wo, $extra]), ['crossed_out' => false])->assertOk();
    }

    public function test_kit_e_only_still_has_extra_heading(): void
    {
        $wo = $this->setupWorkorder();
        Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '1-10',
            'part_number' => 'ONLY-EXTRA', 'name' => 'Pin', 'units_assy' => 1, 'kit_e' => true]);
        $this->get(route('tdrs.kitForm', $wo))->assertOk()
            ->assertSee('EXTRA PARTS (ENTER CODE OR CROSS OUT)')->assertSee('ONLY-EXTRA');
    }

    public static function quantitySections(): array
    {
        return ['KIT' => [true], 'EXTRA PARTS (ENTER CODE OR CROSS OUT)' => [false]];
    }

    /** @dataProvider quantitySections */
    public function test_quantities_follow_selected_original_and_oversize_in_kit_and_extra_parts(bool $kit): void
    {
        $wo = $this->setupWorkorder();
        [$original, $oversize] = $this->family($wo, 360, $kit);
        $bush = WoBushing::create(['workorder_id' => $wo->id]);
        $lines = collect([$original, $oversize])->map(fn ($part) => WoBushingLine::create([
            'workorder_id' => $wo->id, 'wo_bushing_id' => $bush->id, 'component_id' => $part->id,
            'qty' => 1, 'qty_remaining' => 1, 'selection_active' => true, 'do_not_order' => false,
        ]));
        $html = $this->get(route('tdrs.kitForm', $wo))->assertOk()->getContent();
        $this->assertSame('1', $this->printedQuantity($html, $original->id));
        $this->assertSame('1', $this->printedQuantity($html, $oversize->id));

        $lines[0]->update(['selection_active' => false]);
        $lines[1]->update(['qty' => 2, 'qty_remaining' => 2]);
        $html = $this->get(route('tdrs.kitForm', $wo))->assertOk()->getContent();
        $this->assertSame('', $this->printedQuantity($html, $original->id));
        $this->assertSame('2', $this->printedQuantity($html, $oversize->id));

        $lines[1]->update(['do_not_order' => true]);
        $html = $this->get(route('tdrs.kitForm', $wo))->assertOk()->getContent();
        $this->assertSame('2', $this->printedQuantity($html, $original->id));
        $this->assertSame('', $this->printedQuantity($html, $oversize->id));
    }

    public function test_kit_print_includes_all_families_with_original_quantity_and_extra_section_last(): void
    {
        $wo = $this->setupWorkorder();
        [$extra, $extraOs] = $this->family($wo, 100, false);
        [$kit, $kitOs] = $this->family($wo, 200, true);
        $response = $this->get(route('tdrs.kitForm', $wo))->assertOk();
        $rows = collect($response->viewData('ordersParts'));
        $this->assertCount(2, $rows);
        $this->assertSame(['KIT', 'K'], $rows->pluck('codes.code')->all());
        $this->assertSame([2, 2], $rows->pluck('bushing_kit_capacity')->all());
        $this->assertSame([$kit->id, $kitOs->id, $extra->id, $extraOs->id],
            $rows->flatMap(fn ($row) => $row['prl_part_numbers'])->pluck('component_id')->all());
        $this->assertTrue($rows->every(fn ($row) => $row['prl_crossed_out']));
        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, '>EXTRA PARTS (ENTER CODE OR CROSS OUT)</strong>'));
        $this->assertSame('2', $this->printedQuantity($html, $kit->id));
        $this->assertSame('2', $this->printedQuantity($html, $extra->id));
        $this->assertLessThan(strpos($html, '>EXTRA PARTS (ENTER CODE OR CROSS OUT)</strong>'), strpos($html, '>BUSH-200-original</span>'));
        $this->assertGreaterThan(strpos($html, '>EXTRA PARTS (ENTER CODE OR CROSS OUT)</strong>'), strpos($html, '>BUSH-100-original</span>'));
        // Showing an extra bushing in the printed KIT must not turn it into KIT supply.
        $this->assertCount(1, app(TdrPrintFormController::class)->kitRows($wo));
    }

    public function test_extra_bushing_selection_manual_crossout_and_restore_keep_original_quantity(): void
    {
        $wo = $this->setupWorkorder();
        [$original, $oversize] = $this->family($wo, 100, false);
        $bush = WoBushing::create(['workorder_id' => $wo->id]);
        WoBushingLine::create(['workorder_id' => $wo->id, 'wo_bushing_id' => $bush->id,
            'component_id' => $oversize->id, 'qty' => 1, 'qty_remaining' => 1, 'do_not_order' => false]);
        foreach ([true, false] as $crossed) {
            $this->patchJson(route('tdrs.kit-crossouts.update', [$wo, $oversize]), ['crossed_out' => $crossed])->assertOk();
            $response = $this->get(route('tdrs.kitForm', $wo))->assertOk();
            $row = $response->viewData('ordersParts')->first();
            $options = collect($row['prl_part_numbers'])->keyBy('component_id');
            $this->assertTrue($options[$original->id]['crossed_out']);
            $this->assertFalse($options[$original->id]['controller_crossed_out']);
            $this->assertSame($crossed, $options[$oversize->id]['crossed_out']);
            $this->assertSame($crossed, $row['prl_crossed_out']);
            $this->assertSame($crossed ? '2' : '', $this->printedQuantity($response->getContent(), $original->id));
            $this->assertSame($crossed ? '' : '1', $this->printedQuantity($response->getContent(), $oversize->id));
        }
        $other = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '9-10',
            'part_number' => 'OTHER', 'name' => 'BOLT', 'units_assy' => 1]);
        $this->patchJson(route('tdrs.kit-crossouts.update', [$wo, $other]), ['crossed_out' => true])->assertUnprocessable();
        $this->assertCount(1, app(TdrPrintFormController::class)->prlRows($wo));
    }

    public function test_assembly_covered_extra_bushings_remain_visible_and_crossed_out(): void
    {
        $wo = $this->setupWorkorder();
        [$original] = $this->family($wo, 100, false);
        $head = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '8-90',
            'part_number' => 'ASSY', 'name' => 'ASSY', 'kit' => true, 'units_assy' => 1]);
        $group = ManualPartGroup::create(['manual_id' => $head->manual_id, 'code' => 'ASSY',
            'name' => 'ASSY', 'type' => 'assy', 'behavior' => 'bundle', 'applies_to' => ['prl']]);
        $option = $group->options()->create(['component_id' => $head->id, 'part_number' => 'ASSY', 'ipl_num' => '8-90']);
        $option->coverages()->create(['component_id' => $original->id, 'qty' => 2, 'applies_to' => ['prl']]);
        Tdr::create(['workorder_id' => $wo->id, 'component_id' => $head->id, 'order_component_id' => $head->id,
            'qty' => 1, 'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id]);
        $response = $this->get(route('tdrs.kitForm', $wo))->assertOk();
        $extra = $response->viewData('ordersParts')->last();
        $this->assertTrue($extra['kit_extra_bushing']);
        $this->assertTrue(collect($extra['prl_part_numbers'])->firstWhere('component_id', $original->id)['controller_crossed_out']);
        $this->assertSame('2', $this->printedQuantity($response->getContent(), $original->id));
    }

    public function test_extra_heading_is_absent_without_extra_bushings(): void
    {
        $wo = $this->setupWorkorder();
        $this->family($wo, 100, true);
        $this->get(route('tdrs.kitForm', $wo))->assertOk()->assertDontSee('EXTRA PARTS (ENTER CODE OR CROSS OUT)');
    }

    public function test_extra_parts_print_saved_replacement_codes_without_changing_kit_or_quantity(): void
    {
        $wo = $this->setupWorkorder();
        [$original, $oversize] = $this->family($wo, 100, false);
        [$kit] = $this->family($wo, 200, true);
        $missing = \App\Models\Code::firstOrCreate(['code' => 'P'], ['name' => 'Missing']);
        $worn = \App\Models\Code::firstOrCreate(['code' => 'K'], ['name' => 'Worn']);
        $bush = WoBushing::create(['workorder_id' => $wo->id]);
        foreach ([$original, $kit] as $part) {
            WoBushingLine::create(['wo_bushing_id' => $bush->id, 'workorder_id' => $wo->id,
                'component_id' => $part->id, 'qty' => 1, 'qty_remaining' => 1, 'codes_id' => $missing->id]);
        }
        $response = $this->get(route('tdrs.kitForm', $wo))->assertOk();
        $this->assertSame('PARTS REPLACEMENT LIST', $response->viewData('formTitle'));
        $response->assertDontSee('PARTS REPLACEMENT LIST - KIT');
        $rows = collect($response->viewData('ordersParts'));
        $this->assertSame(['KIT', 'P'], $rows->pluck('codes.code')->all());
        $this->assertSame(2, $rows->last()['bushing_kit_capacity']);
        $this->assertFalse($rows->last()['prl_crossed_out']);
        WoBushingLine::create(['wo_bushing_id' => $bush->id, 'workorder_id' => $wo->id,
            'component_id' => $oversize->id, 'qty' => 1, 'qty_remaining' => 1, 'codes_id' => $worn->id]);
        $response = $this->get(route('tdrs.kitForm', $wo))->assertOk();
        $rows = collect($response->viewData('ordersParts'));
        $this->assertCount(2, $rows);
        $this->assertSame(['P', 'K'], array_column($rows->last()['bushing_option_codes'], 'code'));
        $response->assertSee('data-prl-code-component-id="'.$original->id.'">P</span>', false);
        $response->assertSee('data-prl-code-component-id="'.$oversize->id.'">K</span>', false);
        WoBushingLine::where('workorder_id', $wo->id)->update(['do_not_order' => true]);
        $rows = collect($this->get(route('tdrs.kitForm', $wo))->assertOk()->viewData('ordersParts'));
        $this->assertTrue($rows->last()['prl_crossed_out']);
        $this->assertSame(['P', 'K'], array_column($rows->last()['bushing_option_codes'], 'code'));
    }

    public function test_overhaul_prl_routes_bushing_lines_and_direct_orders_only_to_kit(): void
    {
        $wo = $this->setupWorkorder();
        [$extra] = $this->family($wo, 100, false);
        [$direct] = $this->family($wo, 200, false);
        [$kit] = $this->family($wo, 300, true);
        $bush = WoBushing::create(['workorder_id' => $wo->id]);
        foreach ([$extra, $kit] as $part) {
            WoBushingLine::create(['workorder_id' => $wo->id, 'wo_bushing_id' => $bush->id,
                'component_id' => $part->id, 'qty' => 1, 'qty_remaining' => 1, 'do_not_order' => false]);
        }
        $ordinary = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '6-50',
            'part_number' => 'KEEP-BOLT', 'name' => 'BOLT', 'units_assy' => 1]);
        foreach ([$direct, $ordinary] as $part) {
            Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id,
                'order_component_id' => $part->id, 'qty' => 1,
                'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id]);
        }
        foreach (['tdrs.prlForm', 'tdrs.bushPrlForm', 'tdrs.bushingPrlForm'] as $route) {
            $response = $this->get(route($route, $wo))->assertOk()->assertSee('KEEP-BOLT');
            foreach ([$extra, $direct, $kit] as $part) $response->assertDontSee($part->part_number);
            $this->assertCount(1, $response->viewData('ordersParts'));
        }
        $kitResponse = $this->get(route('tdrs.kitForm', $wo))->assertOk()->assertSee('EXTRA PARTS (ENTER CODE OR CROSS OUT)');
        foreach ([$extra, $direct, $kit] as $part) $kitResponse->assertSee($part->part_number);
        $controller = app(TdrPrintFormController::class);
        $this->assertSame(0, $controller->countBushingPrlRows($wo));
        $this->assertCount(3, $controller->prlRows($wo)); // Orders/receipts are preserved.
    }
}
