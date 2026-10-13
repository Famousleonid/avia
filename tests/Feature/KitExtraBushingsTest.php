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
            'code' => 'EXTRA-'.$position, 'name' => 'Bushing', 'type' => 'oversize',
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
        $this->assertFalse($rows->last()['prl_crossed_out']);
        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, '>Extra parts</strong>'));
        $this->assertSame(2, substr_count($html, 'data-kit-original-qty="2"'));
        $this->assertLessThan(strpos($html, '>Extra parts</strong>'), strpos($html, '>BUSH-200-original</span>'));
        $this->assertGreaterThan(strpos($html, '>Extra parts</strong>'), strpos($html, '>BUSH-100-original</span>'));
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
            $response->assertSee('data-kit-original-qty="2"', false);
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
        $response->assertSee('data-kit-original-qty="2"', false);
    }

    public function test_extra_heading_is_absent_without_extra_bushings(): void
    {
        $wo = $this->setupWorkorder();
        $this->family($wo, 100, true);
        $this->get(route('tdrs.kitForm', $wo))->assertOk()->assertDontSee('Extra parts');
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
        $kitResponse = $this->get(route('tdrs.kitForm', $wo))->assertOk()->assertSee('Extra parts');
        foreach ([$extra, $direct, $kit] as $part) $kitResponse->assertSee($part->part_number);
        $controller = app(TdrPrintFormController::class);
        $this->assertSame(0, $controller->countBushingPrlRows($wo));
        $this->assertCount(3, $controller->prlRows($wo)); // Orders/receipts are preserved.
    }
}
