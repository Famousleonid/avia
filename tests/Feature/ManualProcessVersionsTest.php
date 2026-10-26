<?php
namespace Tests\Feature;

use App\Models\{ManualProcess, Process, ProcessName, TravelerProcessTemplate};
use App\Services\ManualProcessVersions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class ManualProcessVersionsTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function fixture(): array
    {
        $user = $this->createUserWithRole('Admin');
        $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()]);
        $manual = $this->createManual();
        $name = ProcessName::firstOrCreate(['name' => 'Chrome stripping']);
        $process = Process::create(['process_names_id' => $name->id, 'process' => "Original\nSpecification"]);
        $link = ManualProcess::create(['manual_id' => $manual->id, 'processes_id' => $process->id]);
        $template = TravelerProcessTemplate::create(['manual_id' => $manual->id, 'name' => 'Version QA', 'part_number' => 'QA-001', 'revision' => 1, 'active' => true]);
        $step = $template->steps()->create(['sort_order' => 1, 'process_id' => $process->id, 'description' => 'Fig. 10', 'traveler_group' => 1]);
        return compact('user', 'manual', 'process', 'link', 'template', 'step');
    }

    public function test_edit_preserves_old_specification_and_manual_membership(): void
    {
        $f = $this->fixture();
        $other = ManualProcess::create(['manual_id' => $this->createManual()->id, 'processes_id' => $f['process']->id]);
        $this->putJson(route('manual_processes.update', $f['link']), ['process' => "New\nSpecification", 'process_comment' => 'Changed'])->assertOk();
        $this->assertSame("Original\nSpecification", $f['process']->fresh()->process);
        $this->assertEquals($f['process']->id, $other->fresh()->processes_id);
        $this->assertEquals($f['process']->id, $f['step']->fresh()->process_id);
        $this->assertCount(2, $f['link']->versions()->get());
        $this->assertTrue(ManualProcessVersions::catalog($f['manual']->id, [$f['process']->id])->has($f['process']->id));
        $this->assertSame("New\nSpecification", $f['link']->fresh()->process->process);
    }

    public function test_archive_does_not_break_saved_catalog_and_admin_only_restore(): void
    {
        $f = $this->fixture();
        $this->delete(route('manual_processes.destroy', $f['link']))->assertRedirect();
        $this->assertNull(ManualProcess::find($f['link']->id));
        $this->assertNotNull(ManualProcess::withTrashed()->find($f['link']->id));
        $this->assertTrue(ManualProcessVersions::catalog($f['manual']->id, [$f['process']->id])->has($f['process']->id));
        $this->get(route('manual_processes.archive', $f['manual']))->assertOk()->assertSee('Original');
        $f['user']->update(['is_admin' => false]);
        $this->actingAs($f['user']->fresh());
        $this->get(route('manual_processes.archive', $f['manual']))->assertForbidden();
        $this->post(route('manual_processes.restore', $f['link']->id))->assertForbidden();
        $f['user']->update(['is_admin' => true]);
        $this->actingAs($f['user']->fresh());
        $this->post(route('manual_processes.restore', $f['link']->id))->assertRedirect();
        $this->assertNotNull(ManualProcess::find($f['link']->id));
    }

    public function test_historical_specification_can_be_retained_but_not_added_as_new(): void
    {
        $f = $this->fixture();
        ManualProcessVersions::edit($f['link'], ['process' => 'New'], $f['user']->id);
        ManualProcessVersions::assertSelectable($f['manual']->id, $f['process']->id, [$f['process']->id]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        ManualProcessVersions::assertSelectable($f['manual']->id, $f['process']->id);
    }

    public function test_history_page_and_template_catalog_show_saved_version(): void
    {
        $f = $this->fixture();
        ManualProcessVersions::edit($f['link'], ['process' => 'New'], $f['user']->id);
        $this->get(route('manual_processes.history', $f['link']->id))->assertOk()->assertSee('Original')->assertSee('New');
        $response = $this->getJson(action([\App\Http\Controllers\Admin\TravelerProcessTemplateController::class, 'index'], $f['manual']));
        $response->assertOk();
        $old = collect($response->json('processes'))->firstWhere('id', $f['process']->id);
        $this->assertTrue($old['historical']);
    }

    public function test_new_zone_requirement_does_not_invalidate_old_template(): void
    {
        $f = $this->fixture();
        $updated = ManualProcessVersions::edit($f['link'], ['requires_zone' => true], $f['user']->id);
        $this->assertNotEquals($f['process']->id, $updated->processes_id);
        $old = ManualProcessVersions::catalog($f['manual']->id, [$f['process']->id])->get($f['process']->id);
        $this->assertFalse($old->requires_zone);
        \App\Services\TravelerProcessTemplates::validateDescriptions($f['manual']->id, [['process_id'=>$f['process']->id, 'description'=>'Fig 10']]);
        $this->assertTrue($updated->requires_zone);
    }
}
