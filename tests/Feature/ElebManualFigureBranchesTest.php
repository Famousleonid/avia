<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Unit;
use App\Services\ManualIplBranchRuleResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ElebManualFigureBranchesTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_kit_and_extra_parts_follow_head_part_number_for_either_unit_side(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);
        $manual = $this->createManual(['number' => '32-10-01 ELEB']);
        foreach ([1001, 1011, 1012, 1021] as $figure) {
            foreach (['kit', 'is_bush', 'kit_e'] as $index => $flag) {
                foreach (['L', 'R'] as $side) {
                    Component::create(['manual_id' => $manual->id,
                        'ipl_num' => $figure.'-'.(10 + $index * 10 + ($side === 'R' ? 1 : 0)),
                        'part_number' => $figure.'-'.$flag.'-'.$side, 'name' => 'Branch part',
                        'units_assy' => 2, 'eff_code' => $side, $flag => true]);
                }
            }
        }
        foreach (['2309-2002-619' => [1001, 1011], '2309-3002-615' => [1012, 1021],
            '2309-3100-601' => [1012, 1021], '2309-4002-401' => [1012, 1021]] as $pn => $figures) {
            foreach (['L', 'R'] as $side) {
                $sidePn = $side === 'L' ? $pn : substr($pn, 0, -1).((int) substr($pn, -1) + 1);
                $unit = $this->createUnit(['manual_id' => $manual->id, 'part_number' => $sidePn, 'eff_code' => $side]);
                $wo = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id,
                    'scope_type' => Unit::SCOPE_FULL_UNIT,
                    'instruction_id' => $this->createOverhaulInstruction()->id]);
                $response = $this->get(route('tdrs.kitForm', $wo))->assertOk();
                $rows = collect($response->viewData('ordersParts'));
                $this->assertCount(12, $rows);
                $this->assertEqualsCanonicalizing($figures, $rows->map(fn ($row) => (int) explode('-', $row['component']['ipl_num'])[0])->unique()->all());
                $this->assertCount(4, $rows->where('kit_extra_bushing', true));
                $this->assertCount(4, $rows->where('kit_extra_part', true));
            }
        }
    }

    public function test_ranges_do_not_leak_into_other_manuals_or_unknown_head_numbers(): void
    {
        $manual = $this->createManual(['number' => '32-10-01 ELEB']);
        $other = $this->createManual(['number' => '32-10-01 Safran']);
        $local = $this->createManual(['number' => '32-10-01']);
        $unit = $this->createUnit(['manual_id' => $manual->id, 'part_number' => '2309-3002-615']);
        $resolver = app(ManualIplBranchRuleResolver::class);
        $this->assertFalse($resolver->allowsComponentForUnit($unit, '1011-150RS'));
        $this->assertTrue($resolver->allowsComponentForUnit($unit, '1012-30RS'));
        $this->assertTrue($resolver->allowsComponentForUnit($unit, '1001-10', $other->id));
        $this->assertFalse($resolver->allowsComponentForUnit($unit, '1001-10', $local->id));
        $this->assertTrue($resolver->allowsComponentForUnit($unit, '1021-10', $local->id));
        $unit->part_number = '2309-30020-001';
        $this->assertTrue($resolver->allowsComponentForUnit($unit, '1001-10'));
    }

    public function test_additional_manual_is_retained_in_std_and_kit_forms_unless_disabled(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);
        $additional = $this->createManual(['number' => '32-21-02']);
        $manual = $this->createManual(['number' => '32-10-01 ELEB',
            'additional_manual_ids' => [$additional->id]]);
        $unit = $this->createUnit(['manual_id' => $manual->id, 'part_number' => '2309-3002-615']);
        $wo = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id,
            'scope_type' => Unit::SCOPE_FULL_UNIT,
            'instruction_id' => $this->createOverhaulInstruction()->id]);
        $parts = [];
        foreach ([[$manual, '1001-10'], [$manual, '1012-10'], [$additional, '1-10']] as [$source, $ipl]) {
            $part = Component::create(['manual_id' => $source->id, 'ipl_num' => $ipl,
                'part_number' => 'PACKAGE-'.$source->id.'-'.$ipl, 'name' => 'Package part',
                'units_assy' => 1, 'kit' => true, 'ndt_list' => true, 'cad_list' => true,
                'paint_list' => true, 'stress_relief_list' => true]);
            foreach (\App\Models\StdProcess::validStdValues() as $std) {
                \App\Models\StdProcess::updateOrCreate(['manual_id' => $source->id,
                    'component_id' => $part->id, 'std' => $std], ['process' => '1', 'qty' => 1]);
            }
            $parts[] = $part;
        }
        foreach ([false, true] as $disabled) {
            $wo->update(['not_used_manual_ids' => $disabled ? [$additional->id] : []]);
            $expected = $disabled ? [$parts[1]->part_number] : [$parts[1]->part_number, $parts[2]->part_number];
            foreach (['ndt', 'cad'] as $std) {
                $response = $this->get(route('tdrs.'.$std.'Std', ['workorder_id' => $wo->id]))->assertOk();
                $this->assertEqualsCanonicalizing($expected,
                    collect($response->viewData($std.'_components'))->pluck('part_number')->all());
            }
            foreach (\App\Models\StdProcess::validStdValues() as $std) {
                $this->assertEqualsCanonicalizing($expected, \App\Models\WorkorderStdProcessItem::query()
                    ->where('workorder_id', $wo->id)->where('std_type', $std)->pluck('part_number')->all());
            }
            $response = $this->get(route('tdrs.kitForm', $wo))->assertOk();
            $this->assertEqualsCanonicalizing($expected,
                collect($response->viewData('ordersParts'))->pluck('component.part_number')->all());
        }
    }
}
