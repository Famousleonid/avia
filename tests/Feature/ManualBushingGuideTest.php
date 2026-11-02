<?php

namespace Tests\Feature;

use App\Models\ManualBushingGuide;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\{BuildsDomainData, TestCase};

class ManualBushingGuideTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function fixture(): array
    {
        $user = $this->createUserWithRole('Technician');
        $a = $this->createManual(); $b = $this->createManual();
        $one = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $a->id])->id]);
        $two = $this->createWorkorder(['unit_id' => $this->createUnit(['manual_id' => $b->id])->id]);
        $document = ['sheets' => collect(['Items', 'Bushing'])->map(fn ($name) => [
            'name' => $name, 'widths' => [150], 'rows' => [['number' => 1, 'cells' => [
                ['address' => 'A1', 'value' => 'Original '.$name, 'colspan' => 1, 'rowspan' => 1, 'style' => ''],
            ]]],
        ])->all()];
        $guide = ManualBushingGuide::create(['key' => uniqid('guide'), 'title' => 'ERJ 190/195 Buching', 'document' => $document, 'revision' => 1]);
        $guide->manuals()->sync([$a->id, $b->id]);
        $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()]);
        return [$user, $guide, $one, $two];
    }

    public function test_technician_can_edit_the_shared_form_and_history_records_exact_cell_changes(): void
    {
        [$user, $guide, $one, $two] = $this->fixture();
        $this->get(route('bushing-guide.show', $one))->assertOk()->assertSee('guideSave')->assertSee('Original Bushing');
        $this->putJson(route('bushing-guide.update', $one), ['revision' => 1, 'changes' => [
            ['sheet' => 1, 'address' => 'A1', 'value' => "  Changed\n<script>not executed</script>  "],
        ]])->assertOk()->assertJson(['revision' => 2, 'changed' => 1]);
        $this->get(route('bushing-guide.show', $two))->assertOk()->assertSee('&lt;script&gt;not executed&lt;/script&gt;', false);
        $entry = DB::table('manual_bushing_guide_changes')->where('guide_id', $guide->id)->first();
        $this->assertEquals($user->id, $entry->user_id);
        $this->assertSame($user->name, $entry->user_name);
        $this->assertNotNull($entry->created_at);
        $this->assertEquals([['sheet' => 'Bushing', 'cell' => 'A1', 'old' => 'Original Bushing', 'new' => "  Changed\n<script>not executed</script>  "]], json_decode($entry->changes, true));
        $this->get(route('bushing-guide.history', $two))->assertForbidden();
        $activity = \Spatie\Activitylog\Models\Activity::where('log_name', 'manual_bushing_guide')->where('subject_id', $guide->id)->firstOrFail();
        $this->assertEquals($user->id, $activity->causer_id);
        $this->assertSame('Original Bushing', $activity->properties['old']['Bushing!A1']);
        $this->assertSame("  Changed\n<script>not executed</script>  ", $activity->properties['attributes']['Bushing!A1']);
        $this->get(route('admin.activity.index', ['log_name' => 'manual_bushing_guide']))->assertForbidden();
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()]);
        $this->get(route('admin.activity.index', ['log_name' => 'manual_bushing_guide']))->assertOk()->assertSee('Original Bushing')->assertSee($user->name);
        $this->get(route('bushing-guide.history', $two))->assertOk()->assertSee('Original Bushing');
    }

    public function test_stale_revision_cannot_overwrite_a_concurrent_edit(): void
    {
        [, $guide, $one, $two] = $this->fixture();
        $payload = ['revision' => 1, 'changes' => [['sheet' => 0, 'address' => 'A1', 'value' => 'First edit']]];
        $this->putJson(route('bushing-guide.update', $one), $payload)->assertOk();
        $payload['changes'][0]['value'] = 'Stale edit';
        $this->putJson(route('bushing-guide.update', $two), $payload)->assertStatus(409);
        $this->assertSame('First edit', $guide->fresh()->document['sheets'][0]['rows'][0]['cells'][0]['value']);
        $this->assertSame(1, DB::table('manual_bushing_guide_changes')->where('guide_id', $guide->id)->count());
    }

    public function test_invalid_cells_are_atomic_and_unchanged_save_does_not_create_history(): void
    {
        [, $guide, $one] = $this->fixture();
        $cell = ['sheet' => 0, 'address' => 'A1', 'value' => 'Original Items'];
        $this->putJson(route('bushing-guide.update', $one), ['revision' => 1, 'changes' => [$cell]])->assertOk()->assertJson(['revision' => 1, 'changed' => 0]);
        $cell['value'] = 'Do not commit';
        foreach ([['sheet' => 1, 'address' => 'Z9', 'value' => 'No'], $cell] as $invalid) {
            $this->putJson(route('bushing-guide.update', $one), ['revision' => 1, 'changes' => [$cell, $invalid]])->assertUnprocessable();
        }
        $this->assertSame('Original Items', $guide->fresh()->document['sheets'][0]['rows'][0]['cells'][0]['value']);
        $this->assertSame(0, DB::table('manual_bushing_guide_changes')->where('guide_id', $guide->id)->count());
        $this->putJson(route('bushing-guide.update', $one), ['revision' => 1, 'changes' => [['sheet' => 0, 'address' => 'A1', 'value' => '']]])->assertOk();
        $this->assertSame('', $guide->fresh()->document['sheets'][0]['rows'][0]['cells'][0]['value']);
    }

    public function test_unlinked_manual_and_guest_cannot_open_or_change_the_form(): void
    {
        [, , $one] = $this->fixture();
        $unrelated = $this->createWorkorder();
        $this->get(route('bushing-guide.show', $unrelated))->assertNotFound();
        $this->get(route('bushing-guide.history', $unrelated))->assertForbidden();
        $this->putJson(route('bushing-guide.update', $unrelated), [])->assertNotFound();
        auth()->logout();
        $this->getJson(route('bushing-guide.show', $one))->assertUnauthorized();
        $this->putJson(route('bushing-guide.update', $one), [])->assertUnauthorized();
    }
}
