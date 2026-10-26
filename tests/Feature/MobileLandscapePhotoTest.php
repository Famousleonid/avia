<?php

namespace Tests\Feature;

use App\Models\Component;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MobileLandscapePhotoTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_mobile_workorder_upload_rejects_portrait_and_accepts_landscape_photos(): void
    {
        File::cleanDirectory(base_path('codex-test-runtime/disks/public'));
        Bus::fake();

        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $url = route('mobile.workorders.media.store', [
            'workorder' => $workorder,
            'category' => 'repair',
        ]);

        $this->actingAs($admin)
            ->postJson($url, [
                'photos' => [UploadedFile::fake()->image('portrait.jpg', 80, 120)],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photos.0']);

        $this->assertCount(0, $workorder->fresh()->getMedia('repair'));

        $this->actingAs($admin)
            ->postJson($url, [
                'photos' => [UploadedFile::fake()->image('landscape.jpg', 120, 80)],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('photo_count', 1);
    }

    public function test_portrait_replacement_does_not_delete_existing_component_photo(): void
    {
        File::cleanDirectory(base_path('codex-test-runtime/disks/public'));

        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '10-20',
            'part_number' => 'LANDSCAPE-TEST',
            'name' => 'Landscape Test Part',
        ]);
        $existingMedia = $component
            ->addMedia(UploadedFile::fake()->image('existing.jpg', 120, 80))
            ->toMediaCollection('components');

        $this->actingAs($admin)
            ->postJson(route('mobile.components.updatePhoto', $component), [
                'photo' => UploadedFile::fake()->image('portrait.jpg', 80, 120),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo']);

        $this->assertDatabaseHas('media', ['id' => $existingMedia->id]);
        $this->assertSame($existingMedia->id, $component->fresh()->getFirstMedia('components')?->id);
    }

    public function test_mobile_workorder_landscape_view_hides_headers_and_gives_the_photo_groups_full_height(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)
            ->get(route('mobile.show', $workorder));

        $response
            ->assertOk()
            ->assertSee('@media (orientation: landscape) and (max-height: 600px)', false)
            ->assertSee('.app-header,', false)
            ->assertSee('#block-info,', false)
            ->assertSee('.mobile-workorder-intake,', false)
            ->assertSee('.table-body-scrollable {', false)
            ->assertSee('padding-bottom: env(safe-area-inset-bottom);', false)
            ->assertSee('class="mobile-workorder-intake border-secondary', false)
            ->assertSee('class="mobile-workorder-intake rounded-3', false)
            ->assertSee('class="text-center col-camera camera-cell-shared"', false)
            ->assertSee('class="landscape-camera-panel"', false)
            ->assertDontSee('camera-cell-group', false)
            ->assertDontSee('data-photo-category="repair"', false)
            ->assertSee('currentPhotoCategory = requestedCategory;', false)
            ->assertSee('id="mobilePhotos"', false);

        $this->assertSame(
            2,
            substr_count($response->getContent(), 'class="text-info js-camera-btn"'),
        );
    }

    public function test_camera_pilot_can_be_disabled_without_removing_the_regular_camera(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $this->actingAs($admin);

        config()->set('mobile_camera.pilot_enabled', true);
        $enabled = $this->get(route('mobile.show', $workorder))->assertOk()
            ->assertSee('class="btn btn-sm btn-outline-info js-pilot-camera-btn"', false)
            ->assertSee('id="mobileCameraPilot"', false)
            ->assertSee('js/mobile-camera-pilot.js', false);

        // Parse like a browser: a body element inside head silently moves later
        // styles out of head and defeats loading the full-screen CSS up front.
        $document = new \DOMDocument();
        @$document->loadHTML($enabled->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//head/link[contains(@href, "css/mobile-camera-pilot.css")]')->length);

        config()->set('mobile_camera.pilot_enabled', false);
        $disabled = $this->get(route('mobile.show', $workorder))->assertOk()
            ->assertDontSee('class="btn btn-sm btn-outline-info js-pilot-camera-btn"', false)
            ->assertDontSee('id="mobileCameraPilot"', false)
            ->assertDontSee('js/mobile-camera-pilot.js', false)
            ->assertSee('id="photo-upload-form"', false)
            ->assertSee('id="mobilePhotoRejected"', false);

        $this->assertSame(
            substr_count($enabled->getContent(), 'class="text-info js-camera-btn"'),
            substr_count($disabled->getContent(), 'class="text-info js-camera-btn"'),
        );
    }
}
