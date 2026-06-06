<?php

namespace Tests\Feature;

use App\Models\Component;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    /**
     * @group smoke
     */
    public function test_authenticated_user_can_upload_workorder_media(): void
    {
        File::cleanDirectory(base_path('codex-test-runtime/disks/public'));
        Bus::fake();

        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('workorders.media.upload', $workorder), [
            'group' => 'photos',
            'files' => [
                $this->makeUploadedImage('one.jpg'),
                $this->makeUploadedImage('two.png'),
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertSame(2, Media::query()
            ->where('model_type', $workorder->getMorphClass())
            ->where('model_id', $workorder->id)
            ->where('collection_name', 'photos')
            ->count());
    }

    public function test_media_upload_validation_rejects_non_image_payload(): void
    {
        File::cleanDirectory(base_path('codex-test-runtime/disks/public'));
        Bus::fake();

        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->postJson(route('workorders.media.upload', $workorder), [
            'group' => 'photos',
            'files' => [
                $this->makeUploadedFile('bad.pdf', '%PDF-1.4 test payload', 'application/pdf'),
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['files.0']);
    }

    public function test_authenticated_user_can_delete_uploaded_workorder_photo(): void
    {
        File::cleanDirectory(base_path('codex-test-runtime/disks/public'));
        Bus::fake();

        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('workorders.media.upload', $workorder), [
            'group' => 'photos',
            'files' => [
                $this->makeUploadedImage('delete-me.jpg'),
            ],
        ])->assertOk();

        $media = Media::query()
            ->where('model_type', $workorder->getMorphClass())
            ->where('model_id', $workorder->id)
            ->where('collection_name', 'photos')
            ->firstOrFail();

        $response = $this->actingAs($admin)->delete(route('workorders.photo.delete', $media->id));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_authenticated_user_can_delete_component_image(): void
    {
        File::cleanDirectory(base_path('codex-test-runtime/disks/public'));
        Bus::fake();

        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-10',
            'part_number' => 'PN-DELETE-IMG',
            'name' => 'Delete Image Part',
            'units_assy' => '1',
        ]);
        $media = $component
            ->addMedia(UploadedFile::fake()->image('component.jpg', 10, 10))
            ->toMediaCollection('components');

        $response = $this->actingAs($admin)->deleteJson(route('components.image.destroy', [
            'component' => $component,
            'media' => $media,
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_component_image_delete_requires_matching_component_media(): void
    {
        File::cleanDirectory(base_path('codex-test-runtime/disks/public'));
        Bus::fake();

        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-10',
            'part_number' => 'PN-KEEP-IMG',
            'name' => 'Keep Image Part',
            'units_assy' => '1',
        ]);
        $otherComponent = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '3-20',
            'part_number' => 'PN-OTHER-IMG',
            'name' => 'Other Image Part',
            'units_assy' => '1',
        ]);
        $media = $otherComponent
            ->addMedia(UploadedFile::fake()->image('other-component.jpg', 10, 10))
            ->toMediaCollection('components');

        $response = $this->actingAs($admin)->deleteJson(route('components.image.destroy', [
            'component' => $component,
            'media' => $media,
        ]));

        $response->assertNotFound();
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }
}
