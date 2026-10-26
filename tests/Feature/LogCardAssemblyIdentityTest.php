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

    /** @dataProvider springParentCounts */
    public function test_each_spring_instance_keeps_its_own_assy_on_save_reopen_and_print(int $parentCount): void
    {
        [$admin, $wo, $part, $first, $second] = $this->scopedMainFitting();
        $wo->update(['scope_type' => 'full_unit', 'scope_part_group_option_id' => null]);
        $part->update(['name' => 'SPRING', 'ipl_num' => '1001-300C', 'part_number' => '2309-2651-005', 'units_assy' => 2]);
        $first->update(['ipl_num' => '1001-170C', 'part_number' => '2309-2650-005']);
        $second->update(['ipl_num' => '1001-170A', 'part_number' => '2309-2650-003']);
        if ($parentCount === 1) $second->coverages()->delete();
        $parents = [$first, $parentCount === 1 ? $first : $second];
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()]);
        $assertDraft = function (array $selected) use ($wo, $part, $parentCount): void {
            $response = $this->get(route('log_card.partial', $wo).'?edit=1')->assertOk();
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath = new \DOMXPath($dom);
            $base = '//tr[.//input[@data-unit-index and @value="'.$part->id.'"]]';
            $this->assertSame(2, $xpath->query($base)->length);
            foreach ([1, 2] as $index) {
                $select = $base.'[.//input[@data-unit-index="'.$index.'"]]//select[contains(@class,"lc-unit-assy-select")]';
                $this->assertSame(1, $xpath->query($select)->length);
                $this->assertSame($parentCount + 1, $xpath->query($select.'/option')->length);
                $this->assertSame(1, $xpath->query($select.'/option[@selected and @value="'.$selected[$index - 1].'"]')->length);
            }
        };
        $assertDraft($parentCount === 1 ? [$first->id, $first->id] : ['', '']);
        $rows = [];
        foreach ($parents as $i => $parent) {
            $rows[] = ['component_id' => (string) $part->id, 'unit_index' => (string) ($i + 1), 'units_assy' => '2',
                'serial_number' => 'SPRING-SN-'.($i + 1), 'assy_serial_number' => 'CARTRIDGE-SN-'.($i + 1),
                'manual_part_group_choice' => 'component', 'manual_part_group_id' => (string) $parent->manual_part_group_id,
                'assy_selection_explicit' => '1', 'assy_option_id' => (string) $parent->id];
        }
        $this->postJson(route('log_card.store'), ['workorder_id' => $wo->id, 'component_data' => json_encode($rows)])->assertOk();
        $log = LogCard::where('workorder_id', $wo->id)->firstOrFail();
        $saved = collect(json_decode($log->component_data, true))->keyBy('unit_index');
        $this->assertCount(2, $saved);
        foreach ($parents as $i => $parent) {
            $this->assertSame((string) $parent->id, $saved[$i + 1]['assy_option_id']);
            $this->assertSame($parent->part_number, $saved[$i + 1]['assy_part_number']);
            $this->assertSame('CARTRIDGE-SN-'.($i + 1), $saved[$i + 1]['assy_serial_number']);
        }
        $assertDraft(array_map(fn ($p) => $p->id, $parents));
        $print = $this->get(route('log_card.logCardForm', $wo))->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$print->getContent());
        $xpath = new \DOMXPath($dom);
        foreach ($parents as $i => $parent) {
            $printedRow = $xpath->query('//div[contains(concat(" ",normalize-space(@class)," ")," log-card-data-row ") and contains(.,"SPRING-SN-'.($i + 1).'")]')->item(0);
            $this->assertNotNull($printedRow);
            $this->assertStringContainsString($parent->part_number, $printedRow->textContent);
            $this->assertStringContainsString('CARTRIDGE-SN-'.($i + 1), $printedRow->textContent);
        }
        $this->putJson(route('log_card.update', $log), ['workorder_id' => $wo->id, 'component_data' => json_encode([...$rows, $rows[0]])])->assertUnprocessable();
        // Clearing one instance must neither restore its automatic parent nor alter the other instance.
        $rows[0] = app(LogCardAssemblyIdentity::class)->withoutAssembly($rows[0]);
        $this->putJson(route('log_card.update', $log), ['workorder_id' => $wo->id, 'component_data' => json_encode($rows)])->assertOk();
        $saved = collect(json_decode($log->fresh()->component_data, true))->keyBy('unit_index');
        $this->assertSame('', $saved[1]['assy_part_number']);
        $this->assertSame('SPRING-SN-1', $saved[1]['serial_number']);
        $this->assertSame($parents[1]->part_number, $saved[2]['assy_part_number']);
        $this->assertSame('CARTRIDGE-SN-2', $saved[2]['assy_serial_number']);
        $assertDraft(['', $parents[1]->id]);
        $this->get(route('log_card.logCardForm', $wo))->assertOk()->assertDontSee('CARTRIDGE-SN-1')->assertSee('CARTRIDGE-SN-2');
    }

    public static function springParentCounts(): array
    {
        return ['one parent' => [1], 'two parents' => [2]];
    }

    public function test_empty_assy_survives_save_reopen_print_and_mobile_with_one_or_multiple_parents(): void
    {
        [$admin, $wo, $part, $first, $second] = $this->scopedMainFitting();
        $wo->update(['scope_type' => 'full_unit', 'scope_part_group_option_id' => null]);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()]);
        $none = ['component_id' => (string) $part->id, 'manual_id' => (string) $part->manual_id,
            'manual_part_group_choice' => 'component', 'manual_part_group_id' => '',
            'assy_selection_explicit' => '1', 'assy_option_id' => '', 'serial_number' => 'IRON-SN'];
        $this->postJson(route('log_card.store'), ['workorder_id' => $wo->id, 'component_data' => json_encode([$none])])->assertOk();
        LogCard::where('workorder_id', $wo->id)->delete();
        $this->postJson(route('mobile.log-card.store', $wo->id), ['rows' => [$none]])->assertOk();

        $first->coverages()->delete();
        $part->update(['part_number' => '49201-108', 'ipl_num' => '3-2130C']);
        $second->update(['part_number' => '49201-8', 'ipl_num' => '3-1950C']);
        // A sibling letter has a different parent; ordinary BOM expansion must not add it.
        $sibling = Component::create(['manual_id' => $part->manual_id, 'part_number' => '49201-105',
            'ipl_num' => '3-2130', 'name' => 'Cylinder', 'units_assy' => 1, 'log_card' => false]);
        $first->coverages()->create(['component_id' => $sibling->id, 'qty' => 1, 'expand_ipl_family' => true]);
        $second->coverages()->update(['expand_ipl_family' => true]);
        $groups = ManualPartGroup::with('options.coverages')->where('manual_id', $part->manual_id)->get();
        $choices = app(LogCardAssemblyIdentity::class)->assemblyChoicesByComponent($groups, collect([$part]), $wo);
        $this->assertCount(1, $choices[$part->id]);
        $this->assertSame('49201-8', $choices[$part->id][0]['part_number']);
        $log = LogCard::where('workorder_id', $wo->id)->firstOrFail();
        $response = $this->get(route('log_card.partial', $wo))->assertOk();
        $this->assertSame('', collect($response->viewData('componentData'))->firstWhere('component_id', (string) $part->id)['assy_part_number']);
        $this->assertSame('', collect(json_decode($log->fresh()->component_data, true))->firstWhere('component_id', (string) $part->id)['assy_part_number']);
        $draft = $this->get(route('log_card.partial', $wo).'?edit=1')->assertOk()->assertDontSee('No ASSY')->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$draft);
        $xpath = new \DOMXPath($dom);
        $select = '//select[contains(@class,"lc-group-assy-select") and @data-component-id="'.$part->id.'"]';
        $this->assertSame(2, $xpath->query($select.'/option')->length);
        $this->assertSame(1, $xpath->query($select.'/option[@selected and @value=""]')->length);
        $this->putJson(route('log_card.update', $log), ['workorder_id' => $wo->id, 'component_data' => json_encode([$none])])->assertOk();
        $saved = collect(json_decode($log->fresh()->component_data, true))->firstWhere('component_id', (string) $part->id);
        $this->assertSame('', $saved['assy_part_number']);
        $this->assertSame('IRON-SN', $saved['serial_number']);
        $this->get(route('log_card.logCardForm', $wo))->assertOk()->assertDontSee('49201-8');
        $this->putJson(route('mobile.log-card.update', $wo->id), ['rows' => [$none]])->assertOk();
        $this->assertSame('', collect(json_decode($log->fresh()->component_data, true))->firstWhere('component_id', (string) $part->id)['assy_part_number']);
    }

    public function test_shared_parent_does_not_make_harnesses_and_pins_mutually_exclusive(): void
    {
        [$admin, $wo, $original, $first, $second] = $this->scopedMainFitting();
        $wo->update(['scope_type' => 'full_unit', 'scope_part_group_option_id' => null]);
        $original->update(['log_card' => false]);
        $parts = collect();
        foreach (['2-20' => '49103-9', '2-20A' => '49103-13', '2-30' => '49103-11',
            '2-30A' => '49103-15', '2-1290' => '49106-5', '2-1380' => '49131-3', '2-1420' => '49132-1'] as $ipl => $pn) {
            $part = Component::create(['manual_id' => $original->manual_id, 'part_number' => $pn,
                'ipl_num' => $ipl, 'name' => str_starts_with($pn, '49103') ? 'Harness Assembly, Wiring' : 'Pin',
                'units_assy' => 1, 'log_card' => true]);
            foreach ([$first, $second] as $option) {
                $option->coverages()->create(['component_id' => $part->id, 'qty' => 1]);
            }
            $parts->push($part);
        }
        $this->actingAs($admin);
        $template = $this->getJson(route('mobile.log-card.template', $wo->id))->assertOk();
        $groups = collect($template->json('data.assy_groups'));
        $this->assertCount(5, $groups);
        $this->assertCount(5, $groups->pluck('group_key')->unique());
        $this->assertSame([2, 2, 1, 1, 1], $groups->map(fn ($g) => count($g['choices']))->all());
        $this->assertSame(7, $groups->sum(fn ($g) => count($g['choices'])));

        $rows = $parts->whereIn('ipl_num', ['2-20A', '2-30', '2-1290'])->values()->map(fn ($p) => [
            'component_id' => (string) $p->id, 'manual_id' => (string) $p->manual_id,
            'manual_part_group_id' => (string) $first->manual_part_group_id,
            'manual_part_group_choice' => 'component', 'assy_option_id' => (string) $first->id,
            'assy_selection_explicit' => '1', 'ipl_group' => 'assy_group_'.$first->manual_part_group_id,
            'serial_number' => 'SN-'.$p->part_number,
        ])->all();
        $this->postJson(route('log_card.store'), ['workorder_id' => $wo->id, 'component_data' => json_encode($rows)])->assertOk();
        $log = LogCard::where('workorder_id', $wo->id)->firstOrFail();
        $this->assertCount(3, collect(json_decode($log->component_data, true))->whereNotNull('component_id'));
        $response = $this->get(route('log_card.partial', $wo).'?edit=1')->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $rowPath = '//tr[contains(@class,"lc-assy-group-row")]';
        $this->assertSame(5, $xpath->query($rowPath)->length);
        $this->assertSame(3, $xpath->query($rowPath.'//input[@type="checkbox" and @checked]')->length);
        foreach ($xpath->query($rowPath) as $row) {
            $radios = $xpath->query('.//input[@type="radio"]', $row);
            $this->assertLessThanOrEqual(2, $radios->length);
        }
        foreach ($rows as $saved) {
            $this->assertSame(1, $xpath->query($rowPath.'//input[@type="radio" and @checked and @value="'.$saved['component_id'].'"]')->length);
        }
        $this->get(route('log_card.logCardForm', $wo))->assertOk()->assertSee('49103-13')->assertSee('49103-11')->assertSee('49106-5');
        $this->putJson(route('mobile.log-card.update', $wo->id), ['rows' => $rows])->assertOk();
        $savedRows = collect(json_decode($log->fresh()->component_data, true))->whereNotNull('component_id');
        $this->assertCount(3, $savedRows);
        $this->assertSame(3, $savedRows->pluck('serial_number')->unique()->count());
        $duplicate = $rows[0];
        $duplicate['component_id'] = (string) $parts->firstWhere('ipl_num', '2-20')->id;
        $duplicate['manual_part_group_id'] = (string) $second->manual_part_group_id;
        $duplicate['assy_option_id'] = (string) $second->id;
        $this->putJson(route('log_card.update', $log), [
            'workorder_id' => $wo->id, 'component_data' => json_encode([...$rows, $duplicate]),
        ])->assertStatus(422);
        $this->putJson(route('mobile.log-card.update', $wo->id), ['rows' => [...$rows, $duplicate]])->assertStatus(422);
    }

    private function scopedMainFitting(): array
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $part = Component::create([
            'manual_id' => $wo->unit->manual_id, 'part_number' => '2821-0111',
            'ipl_num' => '14-210A', 'name' => 'Main Fitting', 'units_assy' => 1, 'log_card' => true,
        ]);
        $options = [];
        foreach (['02' => '14-1A', '03' => '14-1C'] as $suffix => $ipl) {
            $pn = '2821A0100-'.$suffix;
            $head = Component::create(['manual_id' => $part->manual_id, 'part_number' => $pn, 'name' => 'Main Fitting ASSY', 'ipl_num' => $ipl]);
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
            $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()])->get(route('log_card.partial', $wo))->assertOk()->assertSee($scope->part_number);

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
            ->assertSee('Select ASSY')->assertSee($old->part_number)->assertSee($selected->part_number);
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
        $this->get(route('log_card.partial', $wo).'?edit=1')->assertOk()->assertSee('Select ASSY');
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
        $old->coverages()->where('component_id', $part->id)->delete();
        $row = ['component_id' => (string) $part->id, 'manual_id' => (string) $part->manual_id,
            'manual_part_group_choice' => 'component', 'manual_part_group_id' => (string) $old->manual_part_group_id,
            'assy_selection_explicit' => '1'];
        $this->actingAs($admin)->postJson(route('log_card.store'), ['workorder_id' => $wo->id, 'component_data' => json_encode([$row])])->assertStatus(422);
        $this->assertNull(LogCard::where('workorder_id', $wo->id)->first());
    }

    public function test_technician_selects_specific_option_in_one_assy_group_and_selection_survives(): void
    {
        [, $wo, $part, $first, $second] = $this->scopedMainFitting();
        $second->update(['manual_part_group_id' => $first->manual_part_group_id]);
        $tech = $this->createUserWithRole('Technician');
        $response = $this->actingAs($tech)->withSession(['auth.version' => (int) $tech->auth_version, 'password_hash_web' => $tech->getAuthPassword()])->get(route('log_card.partial', $wo))->assertOk();
        $response->assertSee('Select ASSY')->assertSee('value="'.$second->id.'"', false);
        $row = ['component_id' => (string) $part->id, 'manual_id' => (string) $part->manual_id,
            'manual_part_group_choice' => 'component', 'manual_part_group_id' => (string) $first->manual_part_group_id,
            'assy_selection_explicit' => '1', 'serial_number' => 'BARE-SN', 'assy_serial_number' => 'ASSY-SN'];
        $this->postJson(route('log_card.store'), ['workorder_id' => $wo->id, 'component_data' => json_encode([$row])])->assertStatus(422);
        $row['assy_option_id'] = (string) $second->id;
        $this->postJson(route('log_card.store'), ['workorder_id' => $wo->id, 'component_data' => json_encode([$row])])->assertOk();
        $log = LogCard::where('workorder_id', $wo->id)->firstOrFail();
        $saved = json_decode($log->component_data, true)[0];
        $this->assertSame($part->part_number, $saved['part_number']);
        $this->assertSame($second->part_number, $saved['assy_part_number']);
        $this->assertSame((string) $second->id, $saved['assy_option_id']);
        $this->get(route('log_card.partial', $wo).'?edit=1')->assertOk()->assertSee($second->part_number);
        $this->get(route('log_card.logCardForm', $wo))->assertOk()->assertSee($second->part_number);
        $this->putJson(route('log_card.update', $log), ['workorder_id' => $wo->id, 'component_data' => json_encode([$saved])])->assertOk();
        $this->assertSame($second->part_number, json_decode($log->fresh()->component_data, true)[0]['assy_part_number']);
        $mobileResponse = $this->putJson(route('mobile.log-card.update', $wo->id), ['rows' => [$row]]);
        $this->assertSame(200, $mobileResponse->status(), $mobileResponse->getContent());
        $this->assertSame($second->part_number, collect(json_decode($log->fresh()->component_data,true))->firstWhere('component_id',(string)$part->id)['assy_part_number']);
        $row['assy_option_id'] = 999999;
        $this->putJson(route('log_card.update', $log), ['workorder_id' => $wo->id, 'component_data' => json_encode([$row])])->assertStatus(422);
    }

    public function test_legacy_group_only_choice_resolves_unique_second_option_and_rejects_wrong_first(): void
    {
        [$admin, $wo, $part, $first, $second] = $this->scopedMainFitting();
        $second->update(['manual_part_group_id' => $first->manual_part_group_id]);
        $first->coverages()->where('component_id', $part->id)->delete();
        $row = ['component_id' => (string) $part->id, 'manual_id' => (string) $part->manual_id,
            'manual_part_group_choice' => 'component', 'manual_part_group_id' => (string) $first->manual_part_group_id,
            'assy_selection_explicit' => '1'];
        $this->actingAs($admin)->postJson(route('log_card.store'), [
            'workorder_id' => $wo->id, 'component_data' => json_encode([$row]),
        ])->assertOk();
        $log = LogCard::where('workorder_id', $wo->id)->firstOrFail();
        $saved = json_decode($log->component_data, true)[0];
        $this->assertSame($second->part_number, $saved['assy_part_number']);
        $this->assertSame((string) $second->id, $saved['assy_option_id']);
        $row['assy_option_id'] = (string) $first->id;
        $this->putJson(route('log_card.update', $log), [
            'workorder_id' => $wo->id, 'component_data' => json_encode([$row]),
        ])->assertStatus(422);
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
        $part->update(['assy_part_number' => $old->part_number, 'assy_ipl_num' => $old->ipl_num]);
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

    public function test_kit_membership_is_not_an_assy_identity_and_old_parent_pn_is_not_printed(): void
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
                'type' => 'kit', 'behavior' => 'bundle', 'applies_to' => ['prl'],
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

    public function test_pintle_without_legacy_links_uses_new_group_on_web_mobile_save_and_print(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id, 'scope_type' => 'full_unit']);
        $part = Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => '49224-103',
            'ipl_num' => '3-480A', 'name' => 'Pintle', 'units_assy' => 1, 'log_card' => false]);
        $head = Component::create(['manual_id' => $part->manual_id, 'part_number' => '49224-3',
            'ipl_num' => '3-460A', 'name' => 'Pintle Assembly', 'units_assy' => 1, 'log_card' => false]);
        $group = ManualPartGroup::create(['manual_id' => $part->manual_id, 'code' => uniqid('PINTLE-'),
            'name' => 'Pintle Assembly', 'type' => 'assy', 'behavior' => 'bundle']);
        $option = $group->options()->create(['component_id' => $head->id, 'part_number' => $head->part_number, 'ipl_num' => $head->ipl_num]);
        $option->coverages()->create(['component_id' => $part->id, 'qty' => 1]);
        $this->actingAs($admin)->get(route('log_card.partial', $wo))->assertOk()->assertDontSee('49224-103');
        $part->update(['log_card' => true]);
        $this->get(route('log_card.partial', $wo))->assertOk()->assertSee('49224-103')->assertSee('49224-3');
        $this->getJson(route('mobile.log-card.template', $wo->id))->assertOk()
            ->assertJsonPath('data.assy_groups.0.choices.0.assy_part_number', '49224-3');
        $row = ['component_id' => (string) $part->id, 'manual_id' => (string) $part->manual_id,
            'manual_part_group_id' => (string) $group->id, 'manual_part_group_choice' => 'component',
            'serial_number' => 'PINTLE-SN', 'assy_part_number' => 'STALE-LEGACY'];
        $this->postJson(route('log_card.store'), ['workorder_id' => $wo->id, 'component_data' => json_encode([$row])])->assertOk();
        $log = LogCard::where('workorder_id', $wo->id)->firstOrFail();
        $saved = json_decode($log->component_data, true)[0];
        $this->assertSame('49224-103', $saved['part_number']);
        $this->assertSame('49224-3', $saved['assy_part_number']);
        $this->assertSame((string) $option->id, $saved['assy_option_id']);
        $this->assertSame('1', $saved['assy_selection_explicit']);
        $this->get(route('log_card.partial', $wo))->assertOk()->assertSee('49224-103')->assertSee('49224-3');
        $this->get(route('log_card.logCardForm', $wo))->assertOk()->assertSee('49224-103')->assertSee('49224-3');
        $this->putJson(route('mobile.log-card.update', $wo->id), ['rows' => [$row]])->assertOk();
        $saved = collect(json_decode($log->fresh()->component_data, true))->firstWhere('component_id', (string) $part->id);
        $this->assertSame('49224-3', $saved['assy_part_number']);
        $this->assertSame('PINTLE-SN', $saved['serial_number']);
        $this->assertNull($part->fresh()->assy_part_number);
        $this->assertCount(0, $part->fresh()->assemblies);
    }

    public function test_nested_assy_does_not_offer_ancestor_even_when_legacy_links_claim_it(): void
    {
        [$admin, $wo, $part, $old, $selected] = $this->scopedMainFitting();
        $wo->update(['scope_type' => 'full_unit', 'scope_part_group_option_id' => null]);
        $parent = ManualPartGroup::create(['manual_id' => $part->manual_id, 'code' => uniqid('PARENT-'),
            'name' => 'Top assembly', 'type' => 'assy', 'behavior' => 'bundle']);
        $option = $parent->options()->create(['part_number' => 'TOP-ASSY', 'ipl_num' => '1-1']);
        $option->coverages()->create(['covered_manual_part_group_option_id' => $selected->id, 'qty' => 1]);
        $part->update(['assy_part_number' => 'TOP-ASSY']);
        $part->assemblies()->create(['assy_part_number' => 'TOP-ASSY', 'assy_ipl_num' => '1-1']);
        $groups = ManualPartGroup::where('manual_id', $part->manual_id)->with('options.coverages')->get();
        $choices = app(LogCardAssemblyIdentity::class)->assemblyChoicesByComponent($groups, collect([$part]), $wo)[$part->id];
        $this->assertSame([$old->id, $selected->id], array_column($choices, 'option_id'));
        $row = ['component_id' => (string) $part->id, 'manual_id' => (string) $part->manual_id,
            'manual_part_group_choice' => 'component', 'manual_part_group_id' => (string) $parent->id];
        $this->actingAs($admin)->postJson(route('log_card.store'), ['workorder_id' => $wo->id, 'component_data' => json_encode([$row])])->assertStatus(422);
        $this->postJson(route('mobile.log-card.store', $wo->id), ['rows' => [$row]])->assertStatus(422);
    }

    public function test_new_identity_respects_exact_letters_and_nested_variant_families(): void
    {
        [, $wo, $part, $old, $selected] = $this->scopedMainFitting();
        $variant = Component::create(['manual_id' => $part->manual_id, 'part_number' => 'OTHER-METAL',
            'ipl_num' => '14-210B', 'name' => 'Variant', 'units_assy' => 1, 'log_card' => true]);
        foreach ([$old, $selected] as $option) {
            $option->coverages()->where('component_id', $part->id)->update(['expand_ipl_family' => false]);
        }
        $identity = app(LogCardAssemblyIdentity::class);
        $load = fn () => ManualPartGroup::where('manual_id', $part->manual_id)->with('options.coverages')->get();
        $this->assertArrayNotHasKey($variant->id, $identity->assemblyChoicesByComponent($load(), collect([$variant]), $wo));
        $family = ManualPartGroup::create(['manual_id' => $part->manual_id, 'code' => uniqid('ALT-'),
            'name' => 'Variants', 'type' => 'alternative_pn', 'behavior' => 'choose_one']);
        $first = $family->options()->create(['component_id' => $part->id, 'part_number' => $part->part_number]);
        $family->options()->create(['component_id' => $variant->id, 'part_number' => $variant->part_number]);
        $selected->coverages()->create(['covered_manual_part_group_option_id' => $first->id, 'qty' => 1]);
        $choices = $identity->assemblyChoicesByComponent($load(), collect([$variant]), $wo);
        $this->assertSame([$selected->id], array_column($choices[$variant->id], 'option_id'));
    }

    public function test_unselected_configuration_slot_cannot_become_selected_when_nested_assy_is_excluded(): void
    {
        [, $wo, $part, $old, $selected] = $this->scopedMainFitting();
        $selected->coverages()->where('component_id', $part->id)->update(['choice_slot' => 'variant']);
        $selected->coverages()->create(['covered_manual_part_group_option_id' => $old->id, 'qty' => 1, 'choice_slot' => 'variant']);
        $groups = ManualPartGroup::where('manual_id', $part->manual_id)->with('options.coverages')->get();
        $choices = app(LogCardAssemblyIdentity::class)->assemblyChoicesByComponent($groups, collect([$part]), $wo);
        $this->assertSame([$old->id], array_column($choices[$part->id], 'option_id'));
        $this->assertCount(2, $groups->firstWhere('id', $selected->manual_part_group_id)->options->first()->coverages);
    }
}
