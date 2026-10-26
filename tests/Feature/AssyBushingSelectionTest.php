<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\TdrPrintFormController;
use App\Models\{Code, ManualPartGroup, Unit, WoBushing, WoBushingLine};
use App\Services\WorkorderPartScopeResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class AssyBushingSelectionTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    /** @dataProvider instructions */
    public function test_received_assy_bushings_can_be_selected_saved_and_printed_without_expanding_work_scope(string $instruction): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['number' => '32-21-06']);
        $head = $this->createComponent($manual, ['ipl_num' => '2-405', 'part_number' => '52203-1']);
        $original = $this->createComponent($manual, ['ipl_num' => '2-455', 'part_number' => '52167-27',
            'is_bush' => true, 'units_assy' => 4]);
        $oversize = $this->createComponent($manual, ['ipl_num' => '2-455A', 'part_number' => '52167-27SR',
            'is_bush' => true, 'units_assy' => 4]);
        $outside = $this->createComponent($manual, ['ipl_num' => '3-585', 'part_number' => '52167-27',
            'is_bush' => true, 'units_assy' => 4]);
        $makeGroup = function ($component, string $type) use ($manual) {
            $group = ManualPartGroup::create(['manual_id' => $manual->id, 'code' => 'ASSY-BUSH-'.$component->id,
                'name' => $component->part_number, 'type' => $type,
                'behavior' => $type === 'assy' ? 'bundle' : 'choose_one',
                'applies_to' => ManualPartGroup::validScopes()]);
            return $group->options()->create(['component_id' => $component->id,
                'part_number' => $component->part_number, 'ipl_num' => $component->ipl_num,
                'option_kind' => $type === 'assy' ? 'assy' : 'original']);
        };
        $size = $makeGroup($original, ManualPartGroup::TYPE_OVERSIZE);
        $size->group->options()->create(['component_id' => $oversize->id, 'part_number' => $oversize->part_number,
            'ipl_num' => $oversize->ipl_num, 'option_kind' => 'oversize']);
        $assy = $makeGroup($head, ManualPartGroup::TYPE_ASSY);
        $assy->coverages()->create(['covered_manual_part_group_option_id' => $size->id,
            'qty' => 1, 'applies_to' => ManualPartGroup::validScopes()]);
        // A containing parent must not bring its other bushings into this received ASSY.
        $parent = $makeGroup($this->createComponent($manual, ['ipl_num' => '2-1']), ManualPartGroup::TYPE_ASSY);
        $parent->coverages()->createMany([
            ['covered_manual_part_group_option_id' => $assy->id, 'qty' => 1, 'applies_to' => ManualPartGroup::validScopes()],
            ['component_id' => $outside->id, 'qty' => 4, 'applies_to' => ManualPartGroup::validScopes()],
        ]);
        $wo = $this->createWorkorder(['user_id' => $admin->id,
            'unit_id' => $this->createUnit(['manual_id' => $manual->id, 'part_number' => '52203-1'])->id,
            'instruction_id' => $this->createInstruction(['name' => $instruction])->id,
            'scope_type' => Unit::SCOPE_COMPONENT, 'scope_component_id' => $head->id]);
        $code = Code::firstOrCreate(['name' => 'Worn'], ['code' => 'K']);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword()]);

        $create = $this->get(route('wo_bushings.create', $wo))->assertOk();
        $this->assertSame([$original->id, $oversize->id], $create->viewData('bushings')->flatten(1)->pluck('id')->all());
        $state = fn () => $this->getJson(route('workorders.bushing-selection.show', $wo))->assertOk()->json();
        $options = collect($state()['groups'])->flatMap(fn ($g) => $g['options'])->keyBy('id');
        $this->assertSame([$original->id, $oversize->id], $options->keys()->all());
        $this->assertTrue($options->every(fn ($o) => !$o['selected'] && $o['crossed_out']));

        $key = $create->viewData('bushings')->keys()->first();
        $item = fn ($qty) => ['selected' => 1, 'qty' => $qty, 'codes_id' => $code->id, 'do_not_order' => false];
        $payload = ['workorder_id' => $wo->id, 'group_bushings' => [$key => ['items' => [
            $original->id => $item(2), $oversize->id => $item(2),
        ]]]];
        $invalid = $payload;
        $invalid['group_bushings'][$key]['items'][$oversize->id]['qty'] = 3;
        $this->postJson(route('wo_bushings.store'), $invalid)->assertUnprocessable();
        $invalid = $payload;
        $invalid['group_bushings'][$key]['items'] = [$outside->id => $item(1)];
        $this->postJson(route('wo_bushings.store'), $invalid)->assertUnprocessable();
        $this->patchJson(route('workorders.bushing-selection.update', [$wo, $outside]),
            ['revision' => $state()['revision'], 'items' => [$outside->id => $item(1)]])->assertUnprocessable();

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('wo_bushings.store'), $payload)->assertOk()->assertJsonPath('success', true);
        $bushing = WoBushing::where('workorder_id', $wo->id)->firstOrFail();
        $edit = $this->get(route('wo_bushings.edit', $bushing))->assertOk();
        $this->assertSame([$original->id, $oversize->id], $edit->viewData('bushings')->flatten(1)->pluck('id')->all());
        if ($instruction === 'Overhaul') {
            file_put_contents(base_path('codex-test-runtime/assy-bushing-edit.html'), $edit->getContent());
            file_put_contents(base_path('codex-test-runtime/assy-bushing-state.json'), json_encode($state()));
        }
        $this->assertSame(4, (int) WoBushingLine::where('workorder_id', $wo->id)->sum('qty'));
        $print = fn () => collect($this->get(route('tdrs.prlForm', $wo))->assertOk()->viewData('ordersParts'));
        $this->assertSame(4, (int) $print()->sum('qty'));
        $this->assertSame([2, 2], $print()->flatMap(fn ($r) => $r['prl_part_numbers'])->where('crossed_out', false)->pluck('qty')->all());

        // Do Not Order keeps the chosen size, but removes its purchasing quantity.
        $payload['group_bushings'][$key]['items'][$original->id]['do_not_order'] = true;
        $this->putJson(route('wo_bushings.update', $bushing), $payload)->assertOk();
        $options = collect($state()['groups'])->flatMap(fn ($g) => $g['options'])->keyBy('id');
        $this->assertTrue($options[$original->id]['selected']);
        $this->assertTrue($options[$original->id]['crossed_out']);
        $this->assertSame(2, (int) $print()->sum('qty'));
        foreach ([$original, $oversize] as $part) {
            $this->patchJson(route('workorders.bushing-selection.update', [$wo, $part]), [
                'revision' => $state()['revision'], 'items' => [$part->id => ['selected' => false, 'qty' => 2]],
            ])->assertOk();
        }
        $this->assertCount(0, $print());
        $this->assertTrue(collect($state()['groups'])->flatMap(fn ($g) => $g['options'])->every(fn ($o) => $o['crossed_out']));
        $this->assertSame([$head->id => 1], app(WorkorderPartScopeResolver::class)->componentQuantities($wo->fresh()));
        $this->assertCount(0, app(TdrPrintFormController::class)->kitRows($wo));
        $wo->update(['scope_component_id' => $outside->id]);
        $single = $this->get(route('wo_bushings.create', $wo))->assertOk();
        $this->assertSame([$outside->id], $single->viewData('bushings')->flatten(1)->pluck('id')->all());
        $wo->update(['scope_type' => Unit::SCOPE_FULL_UNIT, 'scope_component_id' => null]);
        $complete = $this->get(route('wo_bushings.create', $wo))->assertOk();
        $this->assertSame([$original->id, $oversize->id, $outside->id],
            $complete->viewData('bushings')->flatten(1)->pluck('id')->all());
    }

    public static function instructions(): array
    {
        return [['Repair'], ['Overhaul']];
    }
}
