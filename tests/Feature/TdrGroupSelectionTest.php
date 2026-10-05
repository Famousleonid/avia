<?php

namespace Tests\Feature;

use App\Models\{Code, ManualPartGroup, Necessary, Tdr, Unit};
use App\Services\WorkorderPartScopeResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class TdrGroupSelectionTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_technician_can_select_and_save_nested_variants_without_expanding_bom(): void
    {
        $manual = $this->createManual();
        $tech = $this->createUserWithRole('Technician');
        $make = function ($type, $pns) use ($manual) {
            $group = ManualPartGroup::create(['manual_id' => $manual->id, 'code' => uniqid('QA'),
                'name' => $type, 'type' => $type, 'behavior' => $type === 'assy' ? 'bundle' : 'choose_one',
                'applies_to' => ManualPartGroup::validScopes()]);
            return collect($pns)->map(function ($pn, $i) use ($manual, $group) {
                $c = $this->createComponent($manual, ['part_number' => $pn, 'ipl_num' => '13-'.random_int(10, 999), 'units_assy' => 1]);
                return $group->options()->create(['component_id' => $c->id, 'part_number' => $pn,
                    'option_kind' => 'alternate', 'is_default' => $i === 0]);
            });
        };
        $root = $make('assy', ['ROOT-02', 'ROOT-03']);
        $axle = $make('alternative_pn', ['2821-0202', '2821-0202FS01', '2821-0202RS05']);
        $bush = $make('oversize', ['BUSH-STD', 'BUSH-OS']);
        $nested = $make('assy', ['NESTED-01', 'NESTED-02']);
        $unrelated = $make('assy', ['UNRELATED']);
        $root[1]->coverages()->create(['covered_manual_part_group_option_id' => $axle[0]->id, 'qty' => 1]);
        $root[1]->coverages()->create(['covered_manual_part_group_option_id' => $nested[0]->id, 'qty' => 1]);
        $nested[1]->coverages()->create(['covered_manual_part_group_option_id' => $bush[0]->id, 'qty' => 1]);
        // A cycle must terminate; an incoming parent must not leak into the picker.
        $nested[1]->coverages()->create(['covered_manual_part_group_option_id' => $root[1]->id, 'qty' => 1]);
        $unrelated[0]->coverages()->create(['covered_manual_part_group_option_id' => $axle[0]->id, 'qty' => 1]);
        $unit = $this->createUnit(['manual_id' => $manual->id, 'default_scope_type' => Unit::SCOPE_PART_GROUP_OPTION,
            'default_scope_part_group_option_id' => $root[0]->id]);
        $wo = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $tech->id]);
        $wo->update(['modified_scope_part_group_option_id' => $root[1]->id]);
        $resolver = app(WorkorderPartScopeResolver::class);
        $before = $resolver->componentQuantities($wo);
        $this->assertArrayNotHasKey($axle[1]->component_id, $before);
        $this->actingAs($tech)->withSession(['auth.version' => (int) $tech->auth_version, 'password_hash_web' => $tech->getAuthPassword()]);
        $response = $this->getJson(route('tdrs.get-components-by-manual', ['manual_id' => $manual->id, 'workorder_id' => $wo->id]))->assertOk();
        foreach ($root->concat($axle)->concat($nested)->concat($bush) as $o) {
            $response->assertJsonFragment(['id' => $o->component_id, 'part_number' => $o->part_number]);
        }
        $response->assertJsonMissing(['part_number' => 'UNRELATED']);
        $repair = Necessary::firstOrCreate(['name' => 'Repair']);
        $code = Code::firstOrCreate(['name' => 'Repairable'], ['code' => 'R']);
        $payload = ['workorder_id' => $wo->id, 'component_id' => $axle[1]->component_id,
            'necessaries_id' => $repair->id, 'codes_id' => $code->id, 'qty' => 1, 'use_tdr' => 1, 'use_process_forms' => 1,
            'assy_serial_number' => '', 'description' => 'QA repair', 'order_component_id' => null];
        $this->post(route('tdrs.store'), $payload)->assertSessionHasNoErrors();
        $this->assertTrue(Tdr::where('workorder_id', $wo->id)->where('component_id', $axle[1]->component_id)->exists());
        $payload['component_id'] = $unrelated[0]->component_id;
        $this->postJson(route('tdrs.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('component_id');
        $this->assertSame($before, $resolver->componentQuantities($wo->fresh()));
    }
}
