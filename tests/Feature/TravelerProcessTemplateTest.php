<?php

namespace Tests\Feature;

use App\Models\{Component, ManualProcess, Process, ProcessName, Tdr, TdrProcess, TravelerProcessTemplate};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TravelerProcessTemplateTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function fixture(): array
    {
        $user = $this->createUserWithRole('Technician');
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        $manual = $wo->unit->manuals;
        $part = Component::create(['manual_id' => $manual->id, 'part_number' => 'TPL-52378-1', 'ipl_num' => '2-400', 'name' => 'Pin']);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id]);
        $name = ProcessName::firstOrCreate(['name' => 'NDT-4']);
        $process = Process::create(['process_names_id' => $name->id, 'process' => 'Template test specification']);
        ManualProcess::create(['manual_id' => $manual->id, 'processes_id' => $process->id, 'requires_fig' => true]);
        $payload = ['part_number' => $part->part_number, 'name' => 'Repair sequence', 'active' => true, 'steps' => [
            ['process_id' => $process->id, 'traveler_group' => null, 'description' => 'Standalone Fig 6031'],
            ['process_id' => $process->id, 'traveler_group' => 1, 'description' => "Fig 6031\nPage 6128"],
            ['process_id' => $process->id, 'traveler_group' => 2, 'description' => 'Repeat NDT Fig 6031'],
        ]];
        $this->login($user);
        return compact('user', 'wo', 'manual', 'part', 'tdr', 'process', 'payload');
    }

    public function test_specification_is_not_stored_in_description_and_satisfies_reference_requirements(): void
    {
        $f = $this->fixture();
        $f['process']->update(['process' => "Requirement line\nFig 6031"]);
        $f['payload']['steps'][0]['description'] = "NDT-4\nRequirement line Fig 6031\nMask bore.\nKeep this note.";
        $f['payload']['steps'][1]['description'] = "Requirement line\nFig 6031";
        $template = $this->createTemplate($f);
        $this->assertSame("Mask bore.\nKeep this note.", $template['steps'][0]['description']);
        $this->assertSame('', $template['steps'][1]['description']);
        $saved = $this->postJson(route('manuals.traveler-templates.step', [$f['manual'], $template['id']]), [
            'revision' => 1, 'action' => 'save', 'id' => $template['steps'][0]['id'],
            'process_id' => $f['process']->id, 'description' => "Requirement line\nFig 6031\nNew note.",
        ])->assertOk()->json('template');
        $this->assertSame('New note.', $saved['steps'][0]['description']);
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 2])->assertOk();
        $this->assertSame('New note.', TdrProcess::where('tdrs_id', $f['tdr']->id)->orderBy('sort_order')->first()->description);
    }

    private function login($user): void
    {
        $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()]);
    }

    public function test_semicolon_part_numbers_are_normalized_matched_exactly_and_validated(): void
    {
        $f = $this->fixture();
        $variant = Component::create(['manual_id' => $f['manual']->id, 'part_number' => '2821-0222R', 'ipl_num' => '13-180', 'name' => 'Tube']);
        $f['payload']['part_number'] = ' '.$f['part']->part_number.' ; 2821-0222R; ;2821-0222R;';
        $template = $this->createTemplate($f);
        $this->assertSame($f['part']->part_number.';2821-0222R', $template['part_number']);
        $otherTdr = Tdr::create(['workorder_id' => $f['wo']->id, 'component_id' => $variant->id]);
        foreach ([$f['tdr'], $otherTdr] as $tdr) {
            $this->assertTrue(\App\Services\TravelerProcessTemplates::forTdr($tdr)->whereKey($template['id'])->exists());
            $this->postJson(route('tdr.traveler-template.apply', $tdr), ['template_id' => $template['id'], 'revision' => 1])->assertOk();
        }
        $variant->update(['part_number' => '2821-0222']);
        $this->assertFalse(\App\Services\TravelerProcessTemplates::forTdr($otherTdr->fresh())->exists());
        $url = route('manuals.traveler-templates.metadata', [$f['manual'], $template['id']]);
        $header = ['revision' => 1, 'name' => 'Repair sequence', 'active' => true];
        $this->patchJson($url, $header + ['part_number' => $f['part']->part_number.';UNKNOWN___;'])->assertUnprocessable()->assertJsonValidationErrors('part_number');
        $this->patchJson($url, $header + ['part_number' => ' ; ; '])->assertUnprocessable();
        $this->patchJson($url, $header + ['part_number' => '2821-0222; '.$f['part']->part_number.';'])->assertOk()
            ->assertJsonPath('template.part_number', '2821-0222;'.$f['part']->part_number);
    }

    private function createTemplate(array $f): array
    {
        $f['user']->permittedManuals()->attach($f['manual']);
        return $this->postJson(route('manuals.traveler-templates.store', $f['manual']), $f['payload'])->assertOk()->json('template');
    }

    public function test_explicit_10104_family_can_be_saved_and_applied_only_in_approved_manual(): void
    {
        $f = $this->fixture();
        $f['manual']->update(['number' => '32-10-07']);
        $f['part']->update(['part_number' => '10104-505']);
        $f['payload']['part_number'] = ' 10104-50___ ;10104-50___; ';
        $template = $this->createTemplate($f);
        $this->assertSame('10104-50___', $template['part_number']);
        foreach (['10104-*', '10104-50%', '2821-___'] as $unsupported) {
            $this->patchJson(route('manuals.traveler-templates.metadata', [$f['manual'], $template['id']]), [
                'revision' => 1, 'name' => 'Family', 'active' => true, 'part_number' => $unsupported,
            ])->assertUnprocessable()->assertJsonValidationErrors('part_number');
        }
        $this->patchJson(route('manuals.traveler-templates.metadata', [$f['manual'], $template['id']]), [
            'revision' => 1, 'name' => 'Family', 'active' => true, 'part_number' => '10104-505',
        ])->assertOk();
        $f['part']->update(['part_number' => '10104-506']);
        $this->assertFalse(\App\Services\TravelerProcessTemplates::forTdr($f['tdr']->fresh())->whereKey($template['id'])->exists());
        $f['part']->update(['part_number' => '10104-505']);
        $this->patchJson(route('manuals.traveler-templates.metadata', [$f['manual'], $template['id']]), [
            'revision' => 2, 'name' => 'Family', 'active' => true, 'part_number' => '10104-50___',
        ])->assertOk();
        $template['revision'] = 3;
        foreach (['10104-505', '10104-5099'] as $pn) {
            $f['part']->update(['part_number' => $pn]);
            $this->assertTrue(\App\Services\TravelerProcessTemplates::forTdr($f['tdr']->fresh())->whereKey($template['id'])->exists());
        }
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => $template['revision']])->assertOk();
        foreach (['10104-501', '10104-51', '10104-50', 'X10104-505', '2821-0013'] as $pn) {
            $f['part']->update(['part_number' => $pn]);
            $matches = \App\Services\TravelerProcessTemplates::forTdr($f['tdr']->fresh())->whereKey($template['id'])->exists();
            $this->assertSame($pn === '10104-501', $matches, $pn);
            if (!$matches) {
                $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])->assertNotFound();
            }
        }
        $f['part']->update(['part_number' => '10104-505']);
        $f['manual']->update(['number' => '32-10-08']);
        $this->assertFalse(\App\Services\TravelerProcessTemplates::forTdr($f['tdr']->fresh())->whereKey($template['id'])->exists());
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])->assertNotFound();
        $this->patchJson(route('manuals.traveler-templates.metadata', [$f['manual'], $template['id']]), [
            'revision' => 1, 'name' => 'Family', 'active' => true, 'part_number' => '10104-50___',
        ])->assertUnprocessable()->assertJsonValidationErrors('part_number');
    }

    public function test_explicit_D60945_family_is_scoped_and_can_be_applied(): void
    {
        $f = $this->fixture();
        $f['manual']->update(['number' => '32-18-36']);
        $f['part']->update(['part_number' => 'D60945-1']);
        $f['payload']['part_number'] = 'D60945-';
        $template = $this->createTemplate($f);
        foreach (['D60945-1', 'D60945-999'] as $pn) {
            $f['part']->update(['part_number' => $pn]);
            $this->assertTrue(\App\Services\TravelerProcessTemplates::forTdr($f['tdr']->fresh())->whereKey($template['id'])->exists());
        }
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])->assertOk();
        foreach (['D60946-1', 'D60945-', 'XD60945-1', '10104-505'] as $pn) {
            $f['part']->update(['part_number' => $pn]);
            $this->assertFalse(\App\Services\TravelerProcessTemplates::forTdr($f['tdr']->fresh())->whereKey($template['id'])->exists());
            $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])->assertNotFound();
        }
        $f['part']->update(['part_number' => 'D60945-1']);
        $f['manual']->update(['number' => '32-21-09RM']);
        $this->assertFalse(\App\Services\TravelerProcessTemplates::forTdr($f['tdr']->fresh())->whereKey($template['id'])->exists());
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])->assertNotFound();
        $this->patchJson(route('manuals.traveler-templates.metadata', [$f['manual'], $template['id']]), [
            'revision' => 1, 'name' => 'Family', 'active' => true, 'part_number' => 'D60945-',
        ])->assertUnprocessable()->assertJsonValidationErrors('part_number');
    }

    public static function approvedDashFamilyCases(): array
    {
        return [
            'bolt' => ['32-11-06', '190-70164-', '190-70165-', '32-11-06 Liebherr', '____'],
            'wheel axle' => ['32-21-02', '190-70460-', '190-70461-', '32-21-01', '____'],
            '2309 axle' => ['32-10-01', '2309-3032-', '2309-3033-', '32-10-02', '____'],
            'welded assembly' => ['32-11-03', '46301-', '46302-', '32-11-04', ''],
        ];
    }

    public function test_future_part_and_unique_active_template_guards(): void
    {
        $f = $this->fixture();
        $f['payload']['part_number'] = 'FUTURE-123';
        $template = $this->createTemplate($f);
        $this->assertFalse(\App\Services\TravelerProcessTemplates::forTdr($f['tdr'])->exists());
        $f['part']->update(['part_number' => 'FUTURE-123']);
        $this->assertSame(1, \App\Services\TravelerProcessTemplates::forTdr($f['tdr']->fresh())->count());
        $this->postJson(route('manuals.traveler-templates.store', $f['manual']), $f['payload'])
            ->assertUnprocessable()->assertJsonValidationErrors('part_number');
        $duplicate = TravelerProcessTemplate::findOrFail($template['id'])->replicate();
        $duplicate->save();
        $duplicate->steps()->create(['process_id' => $f['process']->id, 'sort_order' => 1, 'description' => 'Fig 1']);
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])->assertStatus(409);
        $this->assertSame(0, TdrProcess::where('tdrs_id', $f['tdr']->id)->count());
    }

    public function test_family_excludes_selected_exact_part_and_rejects_other_overlap(): void
    {
        $f = $this->fixture();
        $f['manual']->update(['number' => '32-11-06']);
        $f['payload']['part_number'] = '49203-10__';
        $family = $this->createTemplate($f);
        $f['payload']['part_number'] = '49203-107';
        $exact = $this->postJson(route('manuals.traveler-templates.store', $f['manual']), $f['payload'])->assertOk()->json('template');
        foreach (['49203-107' => $exact['id'], '49203-109' => $family['id']] as $pn => $id) {
            $f['part']->update(['part_number' => $pn]);
            $this->assertSame([$id], \App\Services\TravelerProcessTemplates::forTdr($f['tdr']->fresh())->pluck('id')->all());
        }
        $f['payload']['part_number'] = '49203-109';
        $this->postJson(route('manuals.traveler-templates.store', $f['manual']), $f['payload'])->assertUnprocessable();
        foreach (['32-21-10RM' => '1170-14___', '32-21-09RM' => '1170-140__'] as $manual => $token) {
            $f['manual']->update(['number' => $manual]);
            $f['payload']['part_number'] = $token;
            $this->postJson(route('manuals.traveler-templates.store', $f['manual']), $f['payload'])->assertUnprocessable();
        }
    }

    /** @dataProvider approvedDashFamilyCases */
    public function test_explicit_dash_family_is_scoped_and_can_be_applied(string $manual, string $prefix, string $otherPrefix, string $otherManual, string $placeholder): void
    {
        $f = $this->fixture();
        $f['manual']->update(['number' => $manual]);
        $f['part']->update(['part_number' => $prefix.'001']);
        $f['payload']['part_number'] = $prefix.$placeholder;
        $template = $this->createTemplate($f);
        $f['part']->update(['part_number' => $prefix.'005']);
        $this->assertTrue(\App\Services\TravelerProcessTemplates::forTdr($f['tdr']->fresh())->whereKey($template['id'])->exists());
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])->assertOk();
        foreach ([$prefix, rtrim($prefix, '-').'0-001', $otherPrefix.'001'] as $pn) {
            $f['part']->update(['part_number' => $pn]);
            $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])->assertNotFound();
        }
        $f['part']->update(['part_number' => $prefix.'001']);
        $f['manual']->update(['number' => $otherManual]);
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])->assertNotFound();
        $this->patchJson(route('manuals.traveler-templates.metadata', [$f['manual'], $template['id']]), [
            'revision' => 1, 'name' => 'Family', 'active' => true, 'part_number' => $prefix.$placeholder,
        ])->assertUnprocessable()->assertJsonValidationErrors('part_number');
    }

    public function test_existing_manual_access_is_required_for_all_management_endpoints(): void
    {
        $f = $this->fixture();
        $url = route('manuals.traveler-templates.index', $f['manual']);
        $this->getJson($url)->assertForbidden();
        $this->postJson($url, $f['payload'])->assertForbidden();
        $template = $this->createTemplate($f);
        ManualProcess::where('manual_id', $f['manual']->id)->where('processes_id', $f['process']->id)
            ->update(['process_comment' => 'Only outer diameter <reference>']);
        $response = $this->getJson($url)->assertOk()->assertJsonPath('templates.0.name', 'Repair sequence');
        $process = collect($response->json('processes'))->firstWhere('id', $f['process']->id);
        $this->assertSame('Only outer diameter <reference>', $process['comment']);
        $this->assertSame('Template test specification', $process['specification']);
        $f['user']->permittedManuals()->detach();
        $this->putJson($url.'/'.$template['id'], $f['payload'] + ['revision' => 1])->assertForbidden();
    }

    public function test_technician_without_manual_access_can_apply_but_not_edit_template(): void
    {
        $f = $this->fixture();
        $template = $this->createTemplate($f);
        $f['user']->permittedManuals()->detach();
        $existing = TdrProcess::create(['tdrs_id' => $f['tdr']->id, 'process_names_id' => $f['process']->process_names_id,
            'processes' => [$f['process']->id], 'sort_order' => 5, 'in_traveler' => true, 'traveler_group' => 3,
            'repair_order' => 'R1234', 'description' => 'Do not change', 'date_start' => '2026-09-01']);
        $before = $existing->fresh()->getAttributes();
        $url = route('tdr.traveler-template.apply', $f['tdr']);
        $this->postJson($url, ['template_id' => $template['id'], 'revision' => 1])->assertOk()->assertJsonCount(3, 'process_ids');
        $this->assertSame($before, $existing->fresh()->getAttributes());
        $rows = TdrProcess::where('tdrs_id', $f['tdr']->id)->where('id', '!=', $existing->id)->orderBy('sort_order')->get();
        $this->assertEquals([6,7,8], $rows->pluck('sort_order')->all());
        $this->assertEquals([null,4,4], $rows->pluck('traveler_group')->all());
        $this->assertEquals([false,true,true], $rows->pluck('in_traveler')->map(fn ($v) => (bool) $v)->all());
        $this->assertSame("Fig 6031\nPage 6128", $rows[1]->description);
        foreach ($rows as $row) {
            $this->assertNull($row->repair_order);
            $this->assertNull($row->date_start);
            $this->assertNull($row->date_finish);
            $this->assertNull($row->vendor_id);
            $this->assertTrue($row->requires_fig);
        }
        $this->postJson($url, ['template_id' => $template['id'], 'revision' => 1])->assertStatus(409);
        $this->assertSame(4, TdrProcess::where('tdrs_id', $f['tdr']->id)->count());
    }

    public function test_step_saves_are_isolated_and_revision_guarded(): void
    {
        $f = $this->fixture();
        $template = $this->createTemplate($f);
        $url = route('manuals.traveler-templates.step', [$f['manual'], $template['id']]);
        $other = $template['steps'][1];
        $payload = ['action' => 'save', 'revision' => 1, 'id' => $template['steps'][0]['id'],
            'process_id' => $f['process']->id, 'description' => 'Changed Fig 123'];
        $saved = $this->postJson($url, $payload)->assertOk()->assertJsonPath('template.revision', 2)->json('template');
        $this->assertSame($other, $saved['steps'][1]);
        $this->assertSame('Changed Fig 123', $saved['steps'][0]['description']);
        $this->postJson($url, $payload)->assertStatus(409);
        $this->postJson($url, array_merge($payload, ['revision' => 2, 'description' => 'Missing reference']))->assertUnprocessable();
        $moved = $this->postJson($url, ['action' => 'down', 'revision' => 2, 'id' => $template['steps'][0]['id']])->assertOk()->json('template');
        $this->assertSame($other['id'], $moved['steps'][0]['id']);
        $this->postJson($url, ['action' => 'delete', 'revision' => 3, 'id' => $other['id']])->assertOk()->assertJsonCount(2, 'template.steps');
        $this->postJson($url, ['action' => 'save', 'revision' => 4, 'id' => $template['steps'][2]['id'],
            'process_id' => $f['process']->id, 'description' => 'Updated Fig 6031'])
            ->assertOk()->assertJsonPath('template.steps.1.traveler_group', 2);
        $f['user']->permittedManuals()->detach();
        $this->postJson($url, array_merge($payload, ['revision' => 4]))->assertForbidden();
    }

    public function test_step_traveler_checkbox_controls_applied_membership(): void
    {
        $f = $this->fixture();
        $template = $this->createTemplate($f);
        $url = route('manuals.traveler-templates.step', [$f['manual'], $template['id']]);
        foreach ([0 => true, 1 => false] as $index => $included) {
            $this->postJson($url, ['action' => 'save', 'revision' => $index + 1,
                'id' => $template['steps'][$index]['id'], 'process_id' => $f['process']->id,
                'description' => 'Fig 6031', 'in_traveler' => $included])->assertOk()
                ->assertJsonPath('template.steps.'.$index.'.traveler_group', $included ? 1 : null);
        }
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']),
            ['template_id' => $template['id'], 'revision' => 3])->assertOk();
        $rows = TdrProcess::where('tdrs_id', $f['tdr']->id)->orderBy('sort_order')->get();
        $this->assertEquals([true, false, true], $rows->pluck('in_traveler')->map(fn ($v) => (bool) $v)->all());
        $this->assertEquals([1, null, 1], $rows->pluck('traveler_group')->all());
    }

    public function test_template_details_and_first_step_can_be_saved_separately(): void
    {
        $f = $this->fixture();
        $f['user']->permittedManuals()->attach($f['manual']);
        $header = collect($f['payload'])->except('steps')->all();
        $template = $this->postJson(route('manuals.traveler-templates.metadata.create', $f['manual']), $header)
            ->assertOk()->assertJsonCount(0, 'template.steps')->json('template');
        $this->assertFalse(\App\Services\TravelerProcessTemplates::forTdr($f['tdr'])->whereKey($template['id'])->exists());
        $url = route('manuals.traveler-templates.step', [$f['manual'], $template['id']]);
        $this->postJson($url, ['action' => 'save', 'revision' => 1, 'process_id' => $f['process']->id,
            'description' => 'Fig 10'])->assertOk()->assertJsonCount(1, 'template.steps');
        $this->assertTrue(\App\Services\TravelerProcessTemplates::forTdr($f['tdr'])->whereKey($template['id'])->exists());
        $this->patchJson(route('manuals.traveler-templates.metadata', [$f['manual'], $template['id']]),
            array_merge($header, ['revision' => 2, 'name' => 'Renamed']))->assertOk()->assertJsonCount(1, 'template.steps');
        $foreign = $this->createManual();
        $f['user']->permittedManuals()->attach($foreign);
        $this->postJson(route('manuals.traveler-templates.step', [$foreign, $template['id']]), ['action' => 'delete', 'revision' => 3, 'id' => 1])->assertNotFound();
    }

    public function test_reapply_is_allowed_only_after_all_previous_rows_are_removed(): void
    {
        $f = $this->fixture();
        $template = $this->createTemplate($f);
        $url = route('tdr.traveler-template.apply', $f['tdr']);
        $payload = ['template_id' => $template['id'], 'revision' => 1];
        $ids = $this->postJson($url, $payload)->assertOk()->json('process_ids');
        $blockedPicker = view('admin.tdr-processes.partials.traveler-template-picker', ['current_tdr' => $f['tdr']])->render();
        $this->assertStringContainsString('data-blocked-reason="Processes from the previous template application still remain.', $blockedPicker);
        $this->assertStringNotContainsString('data-template-apply disabled', $blockedPicker);
        $this->assertStringContainsString('aria-disabled="true"', $blockedPicker);
        TdrProcess::whereIn('id', array_slice($ids, 1))->delete();
        $this->postJson($url, $payload)->assertStatus(409);
        TdrProcess::where('id', $ids[0])->delete();
        $picker = view('admin.tdr-processes.partials.traveler-template-picker', ['current_tdr' => $f['tdr']])->render();
        $this->assertStringNotContainsString('disabled', $picker);
        $newIds = $this->postJson($url, $payload)->assertOk()->assertJsonCount(3, 'process_ids')->json('process_ids');
        $this->assertEmpty(array_intersect($ids, $newIds));
        $this->assertSame(3, TdrProcess::where('tdrs_id', $f['tdr']->id)->count());
        $this->assertDatabaseHas('activity_log', ['event' => 'template_reapplied', 'subject_id' => $f['tdr']->id]);
        $this->postJson($url, $payload)->assertStatus(409);
    }

    public function test_exact_full_part_number_and_manual_are_required(): void
    {
        $f = $this->fixture();
        $template = $this->createTemplate($f);
        $part = Component::create(['manual_id' => $f['manual']->id, 'part_number' => 'TPL-52378-2', 'name' => 'Other']);
        $f['tdr']->update(['component_id' => $part->id]);
        $url = route('tdr.traveler-template.apply', $f['tdr']);
        $this->postJson($url, ['template_id' => $template['id'], 'revision' => 1])->assertNotFound();
        $part->update(['manual_id' => $this->createManual()->id, 'part_number' => $f['part']->part_number]);
        $this->postJson($url, ['template_id' => $template['id'], 'revision' => 1])->assertNotFound();
        $this->assertDatabaseMissing('tdr_processes', ['tdrs_id' => $f['tdr']->id]);
    }

    public function test_edit_is_versioned_and_does_not_change_applied_rows(): void
    {
        $f = $this->fixture();
        $template = $this->createTemplate($f);
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])->assertOk();
        $before = TdrProcess::where('tdrs_id', $f['tdr']->id)->get()->toArray();
        $url = route('manuals.traveler-templates.update', [$f['manual'], $template['id']]);
        $data = $f['payload'] + ['revision' => 1];
        $data['steps'][0]['description'] = 'Changed template only Fig 6031';
        $data['active'] = false;
        $this->putJson($url, $data)->assertOk()->assertJsonPath('template.revision', 2);
        $this->putJson($url, $data)->assertStatus(409);
        $this->assertSame($before, TdrProcess::where('tdrs_id', $f['tdr']->id)->get()->toArray());
        $this->assertDatabaseHas('tdr_traveler_template_applications', ['tdr_id' => $f['tdr']->id, 'revision' => 1]);
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 2])->assertNotFound();
    }

    public function test_invalid_catalog_and_cross_manual_update_are_rejected(): void
    {
        $f = $this->fixture();
        $template = $this->createTemplate($f);
        $data = $f['payload'];
        $data['steps'][0]['process_id'] = 999999999;
        $this->postJson(route('manuals.traveler-templates.store', $f['manual']), $data)->assertUnprocessable();
        $other = $this->createManual();
        $f['user']->permittedManuals()->attach($other);
        $this->putJson(route('manuals.traveler-templates.update', [$other, $template['id']]), $f['payload'] + ['revision' => 1])->assertNotFound();
        ManualProcess::where('manual_id', $f['manual']->id)->where('processes_id', $f['process']->id)->delete();
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])->assertUnprocessable();
        $this->assertDatabaseMissing('tdr_processes', ['tdrs_id' => $f['tdr']->id]);
    }

    public function test_stale_preview_is_rejected_and_picker_renders_preview_without_edit_controls(): void
    {
        $f = $this->fixture();
        $template = $this->createTemplate($f);
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 2])->assertStatus(409);
        $this->get(route('tdr-processes.processesBody', $f['tdr']->id))->assertOk()
            ->assertSee('data-traveler-picker', false)->assertSee('Repair sequence')->assertDontSee('data-traveler-editor', false)
            ->assertSee('Apply template (1)')->assertDontSee('data-template-select', false);
        $this->get(route('manuals.show', ['manual' => $f['manual'], 'tab' => 'traveler']))->assertOk()->assertSee('data-traveler-editor', false);
    }

    public function test_machining_ec_keeps_existing_companion_untouched_and_creates_its_own(): void
    {
        $f = $this->fixture();
        $machining = ProcessName::firstOrCreate(['name' => 'Machining (EC)'], ['process_sheet_name' => 'Machining']);
        $ec = ProcessName::firstOrCreate(['name' => 'EC'], ['process_sheet_name' => 'EC']);
        $f['process']->update(['process_names_id' => $machining->id]);
        $template = $this->createTemplate($f);
        $existing = TdrProcess::create(['tdrs_id' => $f['tdr']->id, 'process_names_id' => $ec->id,
            'processes' => [], 'repair_order' => 'KEEP-RO', 'sort_order' => 1, 'standalone_ec_only' => false]);
        $before = $existing->fresh()->getAttributes();
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])
            ->assertOk()->assertJsonCount(4, 'process_ids');
        $this->assertSame($before, $existing->fresh()->getAttributes());
        $rows = TdrProcess::where('tdrs_id', $f['tdr']->id)->where('process_names_id', $machining->id)->get();
        $this->assertCount(3, $rows);
        $this->assertTrue($rows->every(fn ($row) => (bool) $row->ec));
        $companion = TdrProcess::where('tdrs_id', $f['tdr']->id)->where('id', '!=', $existing->id)->where('process_names_id', $ec->id)->sole();
        $this->assertNull($companion->repair_order);
        $this->assertSame([$f['process']->id], $companion->processes);
    }

    public function test_long_template_description_is_applied_without_truncation(): void
    {
        $f = $this->fixture();
        $description = trim('FIG 6114. '.str_repeat('Bake 190±5°C for ≥4 hours; preserve full requirements. ', 25));
        $f['payload']['steps'][1]['description'] = $description;
        $template = $this->createTemplate($f);
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])
            ->assertOk()->assertJsonCount(3, 'process_ids');
        $row = TdrProcess::where('tdrs_id', $f['tdr']->id)->where('sort_order', 2)->sole();
        $this->assertSame($description, $row->description);
        $this->assertDatabaseHas('tdr_traveler_template_applications', ['tdr_id' => $f['tdr']->id, 'template_id' => $template['id']]);
    }

    public function test_templates_do_not_bypass_manual_fig_zone_requirements(): void
    {
        $f = $this->fixture();
        $template = $this->createTemplate($f);
        $payload = $f['payload'];
        $payload['steps'][0]['description'] = 'Missing reference';
        $this->postJson(route('manuals.traveler-templates.store', $f['manual']), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('steps.0.description');
        ManualProcess::where('manual_id', $f['manual']->id)->where('processes_id', $f['process']->id)->update(['requires_zone' => true]);
        $this->postJson(route('tdr.traveler-template.apply', $f['tdr']), ['template_id' => $template['id'], 'revision' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('steps.0.description');
        $this->assertDatabaseMissing('tdr_processes', ['tdrs_id' => $f['tdr']->id]);
    }
}
