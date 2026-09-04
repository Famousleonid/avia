<?php

namespace Tests\Feature;

use App\Models\Component;
use Carbon\Carbon;
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

    public function test_workorder_photo_names_use_short_date_and_persistent_folder_sequence(): void
    {
        File::cleanDirectory(base_path('codex-test-runtime/disks/public'));
        Bus::fake();
        $this->travelTo(Carbon::parse('2026-09-01 08:19:11'));

        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 107884,
        ]);

        // A legacy file makes the first new folder sequence start at 002.
        $workorder
            ->addMedia($this->makeUploadedImage('legacy.jpg'))
            ->usingFileName('wo_107884_20260901_0819_old.jpg')
            ->toMediaCollection('repair');

        $this->actingAs($admin)->post(route('workorders.media.upload', $workorder), [
            'group' => 'repair',
            'files' => [
                $this->makeUploadedImage('one.jpg'),
                $this->makeUploadedImage('two.png'),
            ],
        ])->assertOk();

        $repairMedia = Media::query()
            ->where('model_type', $workorder->getMorphClass())
            ->where('model_id', $workorder->id)
            ->where('collection_name', 'repair')
            ->orderBy('id')
            ->get();

        $this->assertSame([
            'wo_107884_20260901_0819_old.jpg',
            'wo_107884_260901_002.jpg',
            'wo_107884_260901_003.png',
        ], $repairMedia->pluck('file_name')->all());

        // Deleting a photo must not make its sequence number reusable.
        $repairMedia->last()->delete();

        $this->actingAs($admin)->post(route('workorders.media.upload', $workorder), [
            'group' => 'repair',
            'files' => [$this->makeUploadedImage('replacement.jpg')],
        ])->assertOk();

        $this->assertDatabaseHas('media', [
            'model_id' => $workorder->id,
            'collection_name' => 'repair',
            'file_name' => 'wo_107884_260901_004.jpg',
        ]);

        // Each archive folder has its own persistent sequence.
        $this->actingAs($admin)->post(route('workorders.media.upload', $workorder), [
            'group' => 'photos',
            'files' => [$this->makeUploadedImage('unit.jpg')],
        ])->assertOk();

        $this->assertDatabaseHas('media', [
            'model_id' => $workorder->id,
            'collection_name' => 'photos',
            'file_name' => 'wo_107884_260901_001.jpg',
        ]);

        $photoToMove = Media::query()
            ->where('model_id', $workorder->id)
            ->where('collection_name', 'photos')
            ->firstOrFail();

        $this->actingAs($admin)->patch(route('workorders.media.move', $photoToMove), [
            'workorder_id' => $workorder->id,
            'to' => 'repair',
        ])->assertOk();

        $this->assertDatabaseHas('media', [
            'model_id' => $workorder->id,
            'collection_name' => 'repair',
            'file_name' => 'wo_107884_260901_005.jpg',
        ]);
        $this->assertDatabaseMissing('media', ['id' => $photoToMove->id]);
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
