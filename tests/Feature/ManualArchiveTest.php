<?php

namespace Tests\Feature;

use App\Models\{Manual, Component, ManualProcess, Process, ProcessName, TravelerProcessTemplate};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\{BuildsDomainData, TestCase};

class ManualArchiveTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_archive_and_restore_preserve_files_and_links(): void
    {
        Storage::fake('local');
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $manual->permittedUsers()->attach($admin->id);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $part = Component::create(['manual_id' => $manual->id, 'part_number' => 'ARCHIVE-QA', 'name' => 'Part']);
        $name = ProcessName::firstOrCreate(['name' => 'Archive QA'], ['process_sheet_name' => 'QA', 'form_number' => 'QA']);
        $process = Process::create(['process_names_id' => $name->id, 'process' => 'Keep specification']);
        $link = ManualProcess::create(['manual_id' => $manual->id, 'processes_id' => $process->id]);
        $template = TravelerProcessTemplate::create(['manual_id' => $manual->id, 'name' => 'Archive QA', 'part_number' => 'ARCHIVE-QA', 'revision' => 1, 'active' => true]);
        $step = $template->steps()->create(['sort_order' => 1, 'process_id' => $process->id, 'description' => 'Keep description']);
        $media = $manual->addMedia(UploadedFile::fake()->create('manual.pdf', 1, 'application/pdf'))->toMediaCollection('manuals', 'local');
        $path = $media->getPathRelativeToRoot();

        $this->actingAs($admin)->delete(route('manuals.destroy', $manual))->assertRedirect();
        $this->assertSoftDeleted('manuals', ['id' => $manual->id]);
        $this->assertNotNull($media->fresh());
        Storage::disk('local')->assertExists($path);
        $this->assertNotNull($part->fresh());
        $this->assertEquals($manual->id, $part->fresh()->manual->id);
        $this->assertEquals($manual->id, $unit->fresh()->manual->id);
        $this->assertNotNull($link->fresh());
        $this->assertNotNull($step->fresh());
        $this->assertTrue(Manual::withTrashed()->find($manual->id)->permittedUsers()->whereKey($admin->id)->exists());
        $this->get(route('manuals.index'))->assertDontSee($manual->number);
        $this->get(route('manuals.index', ['with_deleted' => 1]))->assertOk()->assertSee(route('manuals.restore', $manual->id), false);
        $this->post(route('manuals.restore', $manual->id))->assertRedirect();
        $this->assertNotNull(Manual::find($manual->id));
        Storage::disk('local')->assertExists($path);
        $this->assertNotNull($step->fresh());
    }

    public function test_admin_role_without_flag_cannot_view_archive_or_restore(): void
    {
        $user = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $manual = $this->createManual();
        $this->actingAs($user)->delete(route('manuals.destroy', $manual))->assertRedirect();
        $this->get(route('manuals.index'))->assertOk()->assertDontSee('id="showDeletedManualsCheckbox"', false);
        $this->get(route('manuals.index', ['with_deleted' => 1]))->assertForbidden();
        $this->post(route('manuals.restore', $manual->id))->assertForbidden();
        $this->assertSoftDeleted('manuals', ['id' => $manual->id]);
    }
}
