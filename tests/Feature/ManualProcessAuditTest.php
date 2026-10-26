<?php
namespace Tests\Feature;

use App\Models\{ManualProcess, Process, ProcessName, TravelerProcessTemplate};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualProcessAuditTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function fixture(): array
    {
        $user = $this->createUserWithRole('Admin');
        $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()]);
        $manual = $this->createManual(['number' => '32-11-14RM']);
        $name = ProcessName::firstOrCreate(['name' => 'Chrome stripping']);
        $process = Process::create(['process_names_id' => $name->id, 'process' => 'QA SPM 20-00-10P11 Fig. 6101, Fig. 6128.']);
        $link = ManualProcess::create(['manual_id' => $manual->id, 'processes_id' => $process->id]);
        return compact('user', 'manual', 'process', 'link');
    }

    private function template(array $f, bool $active = true): TravelerProcessTemplate
    {
        $template = TravelerProcessTemplate::create(['manual_id' => $f['manual']->id, 'name' => 'Pintle Pin', 'part_number' => '2821-0025', 'revision' => 1, 'active' => $active]);
        $template->steps()->create(['sort_order' => 5, 'process_id' => $f['process']->id, 'description' => '', 'traveler_group' => 1]);
        return $template;
    }

    public function test_audit_preserves_actor_identity_changes_and_deleted_link_in_log_page(): void
    {
        $f = $this->fixture();
        $link = $f['link'];
        $logs = fn () => Activity::where('subject_type', ManualProcess::class)->where('subject_id', $link->id)->orderBy('id')->get();
        $created = $logs()->last();
        $this->assertEquals($f['user']->id, $created->causer_id);
        $this->assertNotNull($created->created_at);
        $this->assertNull($created->properties['manual_process_before']);
        $this->assertSame('32-11-14RM', $created->properties['manual_process_after']['manual_number']);
        $link->save();
        $this->assertCount(1, $logs());
        $link->update(['process_comment' => 'Mask bore', 'requires_fig' => true]);
        $this->assertSame('Mask bore', $logs()->last()->properties['attributes']['process_comment']);
        $link->delete();
        $deleted = $logs()->last();
        $this->assertSame('deleted', $deleted->event);
        $this->assertNull($deleted->properties['manual_process_after']);
        $this->assertSame($f['process']->process, $deleted->properties['manual_process_before']['specification']);
        $f['manual']->update(['number' => 'RENAMED']);
        $f['process']->update(['process' => 'Changed later']);
        $this->get(route('admin.activity.index', ['log_name' => 'manual_process', 'q' => 'QA SPM']))
            ->assertOk()->assertSee('32-11-14RM')->assertSee('Chrome stripping')
            ->assertSee('QA SPM 20-00-10P11')->assertSee('Not linked')->assertSee('Mask bore');
    }

    public function test_deleting_used_link_archives_without_removing_saved_steps(): void
    {
        $f = $this->fixture(); $template = $this->template($f);
        $this->deleteJson(route('manual_processes.destroy', $f['link']))
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('manual_process_id', $f['link']->id);
        $this->assertSoftDeleted('manual_processes', ['id' => $f['link']->id]);
        $this->assertEquals($f['process']->id, $template->steps()->first()->process_id);
        $this->assertTrue(Activity::where('subject_type', ManualProcess::class)->where('subject_id', $f['link']->id)->where('event', 'deleted')->exists());
    }

    public function test_editing_used_link_creates_an_immutable_version(): void
    {
        $f = $this->fixture(); $this->template($f);
        $other = Process::create(['process_names_id' => $f['process']->process_names_id, 'process' => 'Another chrome specification']);
        $count = Process::count();
        $this->putJson(route('manual_processes.update', $f['link']), ['process' => $other->process])
            ->assertOk();
        $this->assertSame($count + 1, Process::count());
        $this->assertNotEquals($f['process']->id, $f['link']->fresh()->processes_id);
        $this->assertSame($f['process']->process, $f['process']->fresh()->process);
    }

    public function test_inactive_template_is_protected_and_direct_model_repoint_is_rejected(): void
    {
        $f = $this->fixture(); $this->template($f, false);
        $this->expectException(ValidationException::class);
        $f['link']->update(['manual_id' => $this->createManual()->id]);
    }

    public function test_both_duplicate_links_can_be_archived_without_physical_deletion(): void
    {
        $f = $this->fixture(); $this->template($f);
        $duplicate = ManualProcess::create(['manual_id' => $f['manual']->id, 'processes_id' => $f['process']->id]);
        $f['link']->delete();
        $this->assertDatabaseHas('manual_processes', ['id' => $duplicate->id]);
        $duplicate->delete();
        $this->assertSoftDeleted('manual_processes', ['id' => $duplicate->id]);
    }

    public function test_unused_link_repoint_logs_both_manuals_and_processes(): void
    {
        $f = $this->fixture(); $otherManual = $this->createManual();
        $otherProcess = Process::create(['process_names_id' => $f['process']->process_names_id, 'process' => 'New specification']);
        $f['link']->update(['manual_id' => $otherManual->id, 'processes_id' => $otherProcess->id]);
        $log = Activity::where('subject_type', ManualProcess::class)->where('subject_id', $f['link']->id)->latest('id')->first();
        $this->assertEquals($f['manual']->id, $log->properties['manual_process_before']['manual_id']);
        $this->assertEquals($f['process']->id, $log->properties['manual_process_before']['process_id']);
        $this->assertEquals($otherManual->id, $log->properties['manual_process_after']['manual_id']);
        $this->assertEquals($otherProcess->id, $log->properties['manual_process_after']['process_id']);
    }

    public function test_deletion_impact_counts_steps_in_current_manual_and_duplicate_links(): void
    {
        $f = $this->fixture();
        $url = route('manual_processes.deletion-impact', $f['link']);
        $this->getJson($url)->assertOk()->assertJsonPath('affected_steps', 0)->assertJsonPath('blocked', false);
        $template = $this->template($f);
        $template->steps()->create(['sort_order' => 6, 'process_id' => $f['process']->id]);
        $this->template($f, false);
        $other = $f;
        $other['manual'] = $this->createManual();
        $this->template($other);
        $this->getJson($url)->assertOk()->assertJsonPath('manual_links_removed', 1)
            ->assertJsonPath('affected_steps', 3)->assertJsonPath('affected_templates', 2)
            ->assertJsonPath('blocked', false)->assertSee('retain their specifications');
        ManualProcess::create(['manual_id' => $f['manual']->id, 'processes_id' => $f['process']->id]);
        $this->getJson($url)->assertOk()->assertJsonPath('affected_steps', 0)->assertJsonPath('blocked', false);
        $this->assertDatabaseHas('manual_processes', ['id' => $f['link']->id]);
    }
}
