<?php

namespace Tests\Feature;

use App\Models\{Code, Process, ProcessName, WoBushing};
use App\Services\WoBushingRelationalSync;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class BushingManualChangeTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function fixture(): array
    {
        $admin = $this->createUserWithRole('Admin');
        $oldManual = $this->createManual(['number' => 'QA-OLD-CMM']);
        $newManual = $this->createManual(['number' => 'QA-NEW-CMM']);
        $oldUnit = $this->createUnit(['manual_id' => $oldManual->id]);
        $newUnit = $this->createUnit(['manual_id' => $newManual->id]);
        $wo = $this->createWorkorder(['unit_id' => $oldUnit->id, 'user_id' => $admin->id]);
        $oldPart = $this->createComponent($oldManual, ['ipl_num' => '14-5', 'part_number' => 'QA-RETAINED-PART',
            'bush_ipl_num' => '14-5', 'is_bush' => true, 'units_assy' => 2]);
        $newPart = $this->createComponent($newManual, ['ipl_num' => '14-5', 'part_number' => 'QA-RETAINED-PART',
            'bush_ipl_num' => '14-5', 'is_bush' => true, 'units_assy' => 2]);
        $type = ProcessName::whereIdentityName('NDT-4')->firstOrFail();
        $oldProcess = Process::create(['process_names_id' => $type->id, 'process' => 'QA prior specification only']);
        $oldProcess->manuals()->attach($oldManual->id);
        $newProcess = Process::create(['process_names_id' => $type->id, 'process' => 'QA current specification only']);
        $newProcess->manuals()->attach($newManual->id);
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $sync = app(WoBushingRelationalSync::class);
        $code = Code::firstOrCreate(['name' => 'Worn'], ['code' => 'K']);
        $groups = fn ($part, $process) => ['14-5' => ['items' => [$part->id => [
            'selected' => 1, 'qty' => 2, 'codes_id' => $code->id, 'need_processes' => 1, 'ndt' => [$process->id],
        ]]]];
        $sync->syncFromGroupBushings($bushing, $groups($oldPart, $oldProcess));
        $line = $bushing->lines()->firstOrFail();
        $step = $line->processes()->firstOrFail();
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword()])->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);

        return compact('wo', 'oldManual', 'newUnit', 'oldPart', 'newPart', 'oldProcess', 'newProcess', 'bushing', 'sync', 'groups', 'line', 'step');
    }

    public function test_previous_manual_assignments_are_visible_and_unchanged_after_save_and_clear(): void
    {
        extract($this->fixture());
        $beforeLine = $line->getAttributes();
        $beforeStep = $step->getAttributes();
        $beforeBatch = $step->batch->getAttributes();
        $wo->update(['unit_id' => $newUnit->id]);
        $this->get(route('wo_bushings.partial', $wo))->assertOk()
            ->assertSee('QA-RETAINED-PART')->assertSee('Saved under')->assertSee('QA-OLD-CMM');
        $edit = $this->get(route('wo_bushings.edit', $bushing))->assertOk()
            ->assertSee('Saved assignments from previous manuals')->assertSee('QA prior specification only');
        $this->assertCount(1, $edit->viewData('retainedManualLines'));
        $this->assertEmpty($edit->viewData('bushData'));
        $this->assertFalse($edit->viewData('ndtProcesses')->contains('id', $oldProcess->id));

        $url = route('wo_bushings.update', $bushing);
        $payload = ['draft_token' => $edit->viewData('draftToken'), 'group_bushings' => $groups($newPart, $newProcess)];
        $this->putJson($url, $payload)->assertOk()->assertJsonPath('success', true);
        $this->assertSame(2, $bushing->lines()->count());
        $this->assertSame($beforeLine, $line->fresh()->getAttributes());
        $this->assertSame($beforeStep, $step->fresh()->getAttributes());
        $this->assertSame($beforeBatch, $step->batch->fresh()->getAttributes());

        // Clear only the current catalogue. The old assignments remain selected.
        $this->putJson($url, ['draft_token' => $sync->editState($bushing)['token'],
            'group_bushings' => ['14-5' => ['items' => [$newPart->id => ['selected' => 0]]]],
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertSame([$line->id], $bushing->lines()->pluck('id')->all());
        $this->assertSame($beforeLine, $line->fresh()->getAttributes());
        $this->assertSame($beforeStep, $step->fresh()->getAttributes());
        $this->assertSame($beforeBatch, $step->batch->fresh()->getAttributes());
        $this->get(route('wo_bushings.partial', $wo))->assertOk()->assertSee('QA-OLD-CMM');
    }

    public function test_manual_change_invalidates_an_open_edit_draft(): void
    {
        extract($this->fixture());
        $token = $sync->editState($bushing)['token'];
        $wo->update(['unit_id' => $newUnit->id]);
        $this->putJson(route('wo_bushings.update', $bushing), [
            'draft_token' => $token, 'group_bushings' => $groups($newPart, $newProcess),
        ])->assertUnprocessable()->assertJsonValidationErrors('group_bushings')
            ->assertJsonPath('errors.group_bushings.0', 'Bushing data changed. Reopen the list before saving.');
        $this->assertSame([$line->id], $bushing->lines()->pluck('id')->all());
        $this->assertTrue($step->fresh()->exists);
    }

    public function test_saved_view_uses_assigned_specifications_even_if_removed_from_catalogue(): void
    {
        extract($this->fixture());
        $oldProcess->manuals()->detach($oldManual->id);
        $response = $this->get(route('wo_bushings.partial', $wo))->assertOk();
        $response->assertSee('QA-RETAINED-PART')->assertSee('NDT-4')->assertSee('QA prior specification only');
        $this->assertStringContainsString('data-wo-process-id="'.$step->id.'"', $response->getContent());
        $this->assertSame(1, $bushing->lines()->count());
    }
}
