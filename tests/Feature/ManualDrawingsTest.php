<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ManualDrawingController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualDrawingsTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function signInDrawingUser(\App\Models\User $user): self
    {
        return $this->actingAs($user)->withSession([
            'auth.version' => (int) $user->auth_version,
            'password_hash_web' => $user->getAuthPassword(),
        ]);
    }

    public function test_owner_can_rename_but_other_users_cannot_modify_and_admin_can_delete(): void
    {
        $owner = $this->createUserWithRole('Technician');
        $other = $this->createUserWithRole('Technician');
        $manager = $this->createUserWithRole('Manager');
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $uploaded = $this->signInDrawingUser($owner)->postJson(route('manuals.drawings.store', $manual), [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('original.png'),
        ])->assertCreated()->assertJsonPath('can_manage', true)->json();
        $media = Media::findOrFail($uploaded['id']);
        $path = $media->getPath(); $hash = hash_file('sha256', $path);
        try {
            $this->patchJson($uploaded['update_url'], ['name' => 'New drawing name'])
                ->assertOk()->assertJsonPath('name', 'New drawing name');
            $this->assertSame($hash, hash_file('sha256', $path));
            $this->assertSame('original.png', $media->fresh()->file_name);
            $this->getJson(route('manuals.drawings.index', $manual))->assertJsonPath('items.0.name', 'New drawing name');
            foreach ([$other, $manager] as $user) {
                $this->signInDrawingUser($user)->getJson(route('manuals.drawings.index', $manual))->assertJsonPath('items.0.can_manage', false);
                $this->patchJson($uploaded['update_url'], ['name' => 'Forbidden', 'uploaded_by' => $user->id])->assertForbidden();
                $this->deleteJson($uploaded['delete_url'])->assertForbidden();
                $this->get($uploaded['url'])->assertOk();
            }
            $this->assertSame('New drawing name', $media->fresh()->getCustomProperty('display_name'));
            $this->assertEquals($owner->id, $media->fresh()->getCustomProperty('uploaded_by'));
            $this->signInDrawingUser($admin)->getJson(route('manuals.drawings.index', $manual))->assertJsonPath('items.0.can_manage', true);
            $this->patchJson($uploaded['update_url'], ['name' => 'Admin correction'])->assertOk();
            $this->deleteJson($uploaded['delete_url'])->assertOk()->assertJsonPath('success', true);
            $this->assertDatabaseMissing('media', ['id' => $media->id]);
            $this->assertFileDoesNotExist($path);
            $this->get($uploaded['url'])->assertNotFound();
            $this->getJson(route('manuals.drawings.index', $manual))->assertJsonCount(0, 'items');
            $this->assertDatabaseHas('activity_log', ['description' => 'Manual drawing renamed', 'causer_id' => $owner->id, 'subject_id' => $manual->id]);
            $this->assertDatabaseHas('activity_log', ['description' => 'Manual drawing deleted', 'causer_id' => $admin->id, 'subject_id' => $manual->id]);
        } finally { $media->fresh()?->delete(); }
    }

    public function test_owner_can_delete_and_invalid_names_or_foreign_manual_cannot_modify_file(): void
    {
        $manual = $this->createManual(); $otherManual = $this->createManual();
        $this->signInDrawingUser($this->createUserWithRole('Technician'));
        $uploaded = $this->postJson(route('manuals.drawings.store', $manual), [
            'file' => \Illuminate\Http\UploadedFile::fake()->image('owner.png'),
        ])->assertCreated()->json();
        $media = Media::findOrFail($uploaded['id']);
        try {
            foreach (['', '   ', str_repeat('a', 201), "invalid\nname"] as $name) {
                $this->patchJson($uploaded['update_url'], ['name' => $name])->assertUnprocessable();
            }
            $this->patchJson(route('manuals.drawings.update', [$otherManual, $media]), ['name' => 'Wrong manual'])->assertNotFound();
            $this->deleteJson(route('manuals.drawings.destroy', [$otherManual, $media]))->assertNotFound();
            $this->deleteJson($uploaded['delete_url'])->assertOk();
            $this->assertNull($media->fresh());
        } finally { $media->fresh()?->delete(); }
    }

    public function test_legacy_drawings_without_owner_are_manageable_only_by_admin_and_other_collections_are_protected(): void
    {
        $manual = $this->createManual();
        $legacy = $manual->addMedia(\Illuminate\Http\UploadedFile::fake()->image('legacy.png'))->toMediaCollection('manual_drawings', 'private');
        $unrelated = $manual->addMedia($this->makeUploadedFile('other.csv', "ipl,part\n1-1,123", 'text/csv'))->toMediaCollection('component_csv_files', 'private');
        try {
            $this->signInDrawingUser($this->createUserWithRole('Technician'));
            $this->getJson(route('manuals.drawings.index', $manual))->assertJsonPath('items.0.can_manage', false);
            $this->deleteJson(route('manuals.drawings.destroy', [$manual, $legacy]))->assertForbidden();
            $this->signInDrawingUser($this->createUserWithRole('Admin'));
            $this->patchJson(route('manuals.drawings.update', [$manual, $unrelated]), ['name' => 'Invalid collection'])->assertNotFound();
            $this->deleteJson(route('manuals.drawings.destroy', [$manual, $unrelated]))->assertNotFound();
            $this->deleteJson(route('manuals.drawings.destroy', [$manual, $legacy]))->assertOk();
        } finally { $legacy->fresh()?->delete(); $unrelated->fresh()?->delete(); }
    }

    public function test_technician_uploads_multiple_drawings_shared_by_manual_and_can_view_them(): void
    {
        $tech = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $other = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $wo1 = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $tech->id]);
        $wo2 = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $tech->id]);
        $this->actingAs($tech);
        $ids = [];
        try {
            foreach ([\Illuminate\Http\UploadedFile::fake()->image('Drawing A.png', 80, 80), $this->makeUploadedFile('Drawing B.pdf', "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF", 'application/pdf')] as $file) {
                $response = $this->postJson(route('manuals.drawings.store', $manual), ['file' => $file])->assertCreated();
                $ids[] = $response->json('id');
                $media = Media::findOrFail($response->json('id'));
                $this->assertEquals($manual->id, $media->model_id);
                $this->assertSame('private', $media->disk);
                $this->assertSame(ManualDrawingController::COLLECTION, $media->collection_name);
                $this->assertEquals($tech->id, $media->getCustomProperty('uploaded_by'));
                $this->get($response->json('url'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
                $this->get(route('manuals.drawings.show', [$other, $media]))->assertNotFound();
            }
            $this->getJson(route('manuals.drawings.index', $manual))->assertOk()->assertJsonCount(2, 'items');
            $this->getJson(route('manuals.drawings.index', $other))->assertOk()->assertJsonCount(0, 'items');
            foreach ([$wo1, $wo2] as $wo) {
                $this->get(route('mains.show', $wo))->assertOk()
                    ->assertViewHas('manualDrawingCount', 2)
                    ->assertSee('data-main-action="manual-drawings"', false)
                    ->assertSee('data-list-url="'.route('manuals.drawings.index', $manual).'"', false);
            }
            $this->assertDatabaseHas('activity_log', ['description' => 'Manual drawing uploaded', 'subject_id' => $manual->id, 'causer_id' => $tech->id]);
        } finally {
            foreach (Media::whereIn('id', $ids)->get() as $media) $media->delete();
        }
    }

    public function test_upload_rejects_unsupported_files_and_cannot_read_other_collections(): void
    {
        $manual = $this->createManual();
        $this->actingAs($this->createUserWithRole('Technician'));
        $this->postJson(route('manuals.drawings.store', $manual), [
            'file' => $this->makeUploadedFile('bad.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>', 'image/svg+xml'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->postJson(route('manuals.drawings.store', $manual), [
            'file' => $this->makeUploadedFile('large.pdf', "%PDF-1.4\n".str_repeat('a', 10240 * 1024), 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $media = $manual->addMedia($this->makeUploadedFile('other.csv', "ipl,part\n1-1,123", 'text/csv'))->toMediaCollection('component_csv_files', 'private');
        try {
            $this->get(route('manuals.drawings.show', [$manual, $media]))->assertNotFound();
            $this->getJson(route('manuals.drawings.index', $manual))->assertJsonCount(0, 'items');
        } finally { $media->delete(); }
    }

    public function test_guests_cannot_list_upload_or_read_drawings(): void
    {
        $manual = $this->createManual();
        $this->getJson(route('manuals.drawings.index', $manual))->assertUnauthorized();
        $this->postJson(route('manuals.drawings.store', $manual))->assertUnauthorized();
        $this->getJson(route('manuals.drawings.show', [$manual, 999999]))->assertUnauthorized();
        $this->patchJson(route('manuals.drawings.update', [$manual, 999999]), ['name' => 'Guest'])->assertUnauthorized();
        $this->deleteJson(route('manuals.drawings.destroy', [$manual, 999999]))->assertUnauthorized();
    }
}
