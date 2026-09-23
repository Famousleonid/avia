<?php

namespace Tests\Feature;

use App\Models\{Code, Component, Process, ProcessName, WoBushing, WoBushingLine, WoBushingProcess};
use App\Services\{BushingSpecProcessGroups, WoBushingRelationalSync};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class BushingAdditionalQuantityTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_same_part_can_be_added_again_with_shared_capacity_and_immutable_ro_history(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $part = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '8-100',
            'bush_ipl_num' => '8-100', 'part_number' => 'REPEAT-BUSH', 'name' => 'Bushing', 'is_bush' => true, 'units_assy' => 4]);
        $variant = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '8-100A',
            'bush_ipl_num' => '8-100', 'part_number' => 'REPEAT-OVERSIZE', 'name' => 'Bushing', 'is_bush' => true, 'units_assy' => 4]);
        $type = ProcessName::whereIdentityName('NDT-4')->firstOrFail();
        $process = Process::create(['process_names_id' => $type->id, 'process' => 'Inspection']);
        $process->manuals()->attach($wo->unit->manual_id);
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $code = Code::firstOrCreate(['name' => 'Worn'], ['code' => 'K']);
        $sync = app(WoBushingRelationalSync::class);
        $item = ['selected' => 1, 'qty' => 2, 'codes_id' => $code->id, 'need_processes' => 1, 'ndt' => [$process->id]];
        $groups = ['8-100' => ['items' => [$part->id => $item]]];
        $sync->syncFromGroupBushings($bushing, $groups);
        $original = $bushing->lines()->firstOrFail();
        $step = $original->processes()->firstOrFail();
        $step->batch->update(['repair_order' => 'RO-LOCKED']);
        $lineBefore = $original->fresh()->getAttributes();
        $stepBefore = $step->fresh()->getAttributes();
        $batchBefore = $step->batch->fresh()->getAttributes();
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword()])->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
        $edit = $this->get(route('wo_bushings.edit', $bushing))->assertOk();
        $this->assertEmpty($edit->viewData('bushData'));
        $edit->assertSee('History — locked')->assertSee('RO-LOCKED');
        $token = $edit->viewData('draftToken');
        $payload = ['draft_token' => $token, 'group_bushings' => $groups];
        $url = route('wo_bushings.update', $bushing);
        // Old edit pages cannot overwrite or reinterpret historical quantities.
        $this->putJson($url, ['group_bushings' => $groups])->assertUnprocessable();
        $excess = $payload;
        $excess['group_bushings']['8-100']['items'][$part->id]['qty'] = 3;
        $this->putJson($url, $excess)->assertUnprocessable()->assertJsonValidationErrors('group_bushings');
        $excess['group_bushings']['8-100']['items'][$part->id]['do_not_order'] = 1;
        $this->putJson($url, $excess)->assertUnprocessable();
        $excess['group_bushings']['8-100']['items'] = [$variant->id => array_merge($item, ['qty' => 3])];
        $this->putJson($url, $excess)->assertUnprocessable();
        $this->putJson($url, $payload)->assertOk()->assertJsonPath('success', true);
        $this->assertSame([2, 2], $bushing->lines()->orderBy('id')->pluck('qty')->all());
        $new = $bushing->lines()->where('id', '!=', $original->id)->firstOrFail();
        $this->assertSame($part->id, $new->component_id);
        $newBatch = $new->processes()->firstOrFail()->batch;
        $this->assertNotSame($step->batch_id, $newBatch->id);
        $this->assertNull($newBatch->repair_order);
        $this->assertSame([2, 2], array_column(app(BushingSpecProcessGroups::class)->build($wo), 'total_qty'));
        // A retried stale POST cannot add another copy or overwrite intervening changes.
        $this->putJson($url, $payload)->assertUnprocessable();
        $payload['draft_token'] = $sync->editState($bushing)['token'];
        $this->putJson($url, $payload)->assertOk();
        $this->assertSame(4, (int) $bushing->lines()->sum('qty'));
        $this->assertSame($lineBefore, $original->fresh()->getAttributes());
        $this->assertSame($stepBefore, $step->fresh()->getAttributes());
        $this->assertSame($batchBefore, $step->batch->fresh()->getAttributes());
        // Starting the draft after the edit page was opened invalidates that page.
        $payload['draft_token'] = $sync->editState($bushing)['token'];
        $newBatch->update(['repair_order' => 'RO-NEXT']);
        $this->putJson($url, $payload)->assertUnprocessable();
        $edit = $this->get(route('wo_bushings.edit', $bushing))->assertOk();
        $this->assertEmpty($edit->viewData('bushData'));
        $payload['draft_token'] = $edit->viewData('draftToken');
        $this->putJson($url, $payload)->assertUnprocessable();
    }
}
