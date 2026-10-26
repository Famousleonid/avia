<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\TdrPrintFormController;
use App\Models\{Code, Component, ManualPartGroup, Necessary, Tdr, Workorder};
use App\Services\WorkorderPartsList;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class KitEffectivityTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function workorder(string $eff): Workorder
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);
        $wo = $this->createWorkorder(['user_id' => $admin->id,
            'instruction_id' => $this->createOverhaulInstruction()->id]);
        $wo->unit->update(['eff_code' => $eff]);
        return $wo;
    }

    /** @dataProvider effectivities */
    public function test_kit_keeps_inapplicable_options_crossed_out_and_preserves_universal_parts(string $unitEff, array $expected): void
    {
        $wo = $this->workorder($unitEff);
        $group = ManualPartGroup::create(['manual_id' => $wo->unit->manual_id,
            'code' => 'SIDE-OPTIONS', 'name' => 'Nut alternatives', 'type' => 'alternative_pn',
            'behavior' => 'choose_one', 'applies_to' => ['prl']]);
        $parts = [];
        foreach (['L', 'R', null, 'L / R', '1A 1B;1C'] as $i => $eff) {
            $parts[$i] = Component::create(['manual_id' => $wo->unit->manual_id,
                'ipl_num' => '5-'.(11 + $i), 'part_number' => $i === 0 ? 'AN924-4J' : 'KIT-EFF-'.$i,
                'name' => 'NUT, HEXAGONAL', 'eff_code' => $eff, 'kit' => true,
                'units_assy' => $i === 0 ? 9 : 1]);
            if ($i < 2) {
                $group->options()->create(['component_id' => $parts[$i]->id,
                    'part_number' => $parts[$i]->part_number, 'ipl_num' => $parts[$i]->ipl_num]);
            }
        }
        $response = $this->get(route('tdrs.kitForm', $wo))->assertOk();
        $rows = collect($response->viewData('ordersParts'));
        $options = $rows->flatMap(fn ($row) => $row['kit_component_options'])->keyBy('component_id');
        $this->assertEqualsCanonicalizing(array_map(fn ($p) => $p->id, $parts), $options->keys()->all());
        $this->assertEqualsCanonicalizing(array_map(fn ($i) => $parts[$i]->id, $expected),
            $options->where('crossed_out', false)->keys()->all());
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        foreach (array_diff(array_keys($parts), $expected) as $i) {
            $option = $options[$parts[$i]->id];
            $this->assertTrue($option['controller_crossed_out']);
            $label = $i === 0 ? 'Left' : ($i === 1 ? 'Right' : 'EFF '.$parts[$i]->eff_code);
            $this->assertSame($label, $option['effectivity_label']);
            $node = $xpath->query('//*[contains(@class,"prl-col-part")]//*[@data-kit-prl-component-id="'.$parts[$i]->id.'"]')->item(0);
            $this->assertNotNull($node);
            $this->assertStringContainsString(' — '.$label, $node->textContent);
            $this->assertStringContainsString('kit-prl-option-crossed-out', $node->getAttribute('class'));
            $this->assertFalse($node->hasAttribute('role'));
            $this->patchJson(route('tdrs.kit-crossouts.update', [$wo, $parts[$i]]), ['crossed_out' => false])
                ->assertUnprocessable();
        }
        if ($unitEff === 'R') {
            $response->assertSee('AN924-4J');
            $this->assertSame(1, $rows->firstWhere('component.id', $parts[0]->id)['qty']);
        }
        $receiptIds = app(WorkorderPartsList::class)->rows($wo)->pluck('component.id')->all();
        foreach (array_diff(array_keys($parts), $expected) as $i) {
            $this->assertNotContains($parts[$i]->id, $receiptIds);
        }
    }

    public static function effectivities(): array
    {
        return ['right' => ['R', [1, 2, 3]], 'left' => ['L', [0, 2, 3]],
            'case insensitive' => ['r', [1, 2, 3]], 'unset' => ['', [0, 1, 2, 3, 4]],
            'multiple codes' => ['1B', [2, 4]]];
    }

    public function test_extra_parts_and_bushing_alternatives_follow_unit_effectivity(): void
    {
        $wo = $this->workorder('R');
        $expected = [];
        foreach (['L', 'R'] as $side) {
            foreach (['kit_bush', 'extra_bush', 'extra_part'] as $i => $kind) {
                $part = Component::create(['manual_id' => $wo->unit->manual_id,
                    'ipl_num' => '6-'.($i * 10 + ($side === 'L' ? 1 : 2)),
                    'bush_ipl_num' => $kind !== 'extra_part' ? '6-'.($i * 10 + 1) : null,
                    'part_number' => $kind.'-'.$side, 'name' => $kind, 'eff_code' => $side,
                    'is_bush' => $kind !== 'extra_part', 'kit' => $kind === 'kit_bush',
                    'kit_e' => $kind === 'extra_part', 'units_assy' => 1]);
                if ($side === 'R') $expected[] = $part->id;
            }
        }
        $rows = app(TdrPrintFormController::class)->kitRows($wo, true);
        $options = $rows->flatMap(fn ($row) => $row['kit_component_options'] ?? $row['prl_part_numbers']);
        $this->assertCount(6, $options);
        $excluded = $options->whereNotIn('component_id', $expected);
        $this->assertCount(3, $excluded);
        foreach ($excluded as $option) {
            $this->assertTrue($option['crossed_out']);
            $this->assertTrue($option['controller_crossed_out']);
            $this->assertSame('Left', $option['effectivity_label']);
        }
    }

    public function test_same_pn_bushing_sides_stay_separate_and_shared_selection_cannot_restore_left(): void
    {
        $wo = $this->workorder('R');
        $bushing = \App\Models\WoBushing::create(['workorder_id' => $wo->id]);
        foreach (['L', 'R'] as $i => $side) {
            $part = Component::create(['manual_id' => $wo->unit->manual_id,
                'ipl_num' => '6-'.(10 + $i), 'bush_ipl_num' => '6-10', 'part_number' => 'SAME-BUSH',
                'name' => 'BUSHING', 'eff_code' => $side, 'is_bush' => true, 'kit' => true,
                'units_assy' => $side === 'L' ? 9 : 1]);
            \App\Models\WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id,
                'component_id' => $part->id, 'qty' => 1, 'qty_remaining' => 1, 'do_not_order' => false]);
        }
        $row = app(TdrPrintFormController::class)->kitRows($wo, true)->sole();
        $this->assertCount(2, $row['prl_part_numbers']);
        $this->assertFalse($row['prl_crossed_out']);
        $this->assertSame(1, $row['qty']);
        $left = collect($row['prl_part_numbers'])->firstWhere('effectivity_label', 'Left');
        $this->assertTrue($left['crossed_out']);
        $this->assertFalse($left['bushing_selection']);
        $this->assertFalse(collect($row['prl_part_numbers'])->firstWhere('component_id', $part->id)['crossed_out']);
    }

    /** @dataProvider supplyFlags */
    public function test_opposite_side_kit_supply_does_not_reduce_explicit_prl_order(string $flag): void
    {
        $wo = $this->workorder('R');
        Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '5-11',
            'part_number' => 'AN924-4J', 'name' => 'Nut', 'eff_code' => 'L', $flag => true, 'units_assy' => 1]);
        $right = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '5-12',
            'part_number' => 'AN924-4J', 'name' => 'Nut', 'eff_code' => 'R', 'units_assy' => 1]);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $right->id,
            'order_component_id' => $right->id, 'qty' => 2,
            'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id,
            'codes_id' => Code::firstOrCreate(['name' => 'Damaged'], ['code' => 'D'])->id]);
        $rows = app(TdrPrintFormController::class)->prlRows($wo);
        $this->assertSame(2, (int) data_get($rows->sole(), 'qty'));
        $this->assertSame(2, (int) $tdr->fresh()->qty);
    }

    public static function supplyFlags(): array
    {
        return [['kit'], ['kit_e']];
    }
}
