<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\LogCard;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MobileLogCardWebTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_mobile_workorder_menu_replaces_process_with_log_card_for_allowed_roles(): void
    {
        $workorder = $this->createWorkorder();

        foreach (['Technician', 'Manager', 'Admin', 'Team Leader'] as $role) {
            $this->flushSession();
            $user = $this->createUserWithRole($role, [
                'email' => strtolower($role).'.mobile-log-card.'.uniqid().'@example.test',
            ]);

            $response = $this->actingAs($user)->get(route('mobile.show', $workorder));

            $response->assertOk()
                ->assertSee(route('mobile.log-card', $workorder), false)
                ->assertSee('bi-card-checklist', false)
                ->assertSee('Log Card', false)
                ->assertDontSee('<span class="menu-label">Process</span>', false);

            $this->get(route('mobile.log-card', $workorder))
                ->assertOk()
                ->assertSee('id="mobileLogCardApp"', false);
        }
    }

    public function test_shipping_paint_and_machining_cannot_see_or_open_mobile_log_card(): void
    {
        $workorder = $this->createWorkorder();

        foreach (['Shipping', 'Paint', 'Machining'] as $role) {
            $this->flushSession();
            $user = $this->createUserWithRole($role, [
                'email' => strtolower($role).'.no-mobile-log-card.'.uniqid().'@example.test',
            ]);

            $this->actingAs($user)
                ->get(route('mobile.show', $workorder))
                ->assertOk()
                ->assertDontSee(route('mobile.log-card', $workorder), false)
                ->assertDontSee('bi-card-checklist', false)
                ->assertDontSee('Log Card', false);

            $this->get(route('mobile.log-card', $workorder))->assertForbidden();
            $this->getJson(route('mobile.log-card.data', $workorder->id))->assertForbidden();
        }
    }

    public function test_mobile_log_card_page_and_shared_data_routes_work_with_web_session_auth(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $technician->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'name' => 'Web Mobile Log Card Part',
            'part_number' => 'WEB-LC-100',
            'ipl_num' => '3-100',
            'log_card' => true,
        ]);

        $this->actingAs($technician)
            ->get(route('mobile.log-card', $workorder))
            ->assertOk()
            ->assertSee('id="mobileLogCardApp"', false)
            ->assertSee('id="mobileLogCardRecognition"', false)
            ->assertSee('id="mobileLogCardRecognitionPhoto"', false)
            ->assertSee('id="mobileLogCardPartNumberWarning"', false)
            ->assertSee('data-retake-recognition', false)
            ->assertSee('data-swap-recognized-numbers', false)
            ->assertSee('Check photographed numbers')
            ->assertSee(route('mobile.log-card.data', $workorder->id), false)
            ->assertSee('Log Card Photos');

        $this->actingAs($technician)
            ->getJson(route('mobile.log-card.template', $workorder->id))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.groups.0.variants.0.component_id', $component->id);

        $this->actingAs($technician)
            ->postJson(route('mobile.log-card.store', $workorder->id), [
                'rows' => [[
                    'component_id' => $component->id,
                    'serial_number' => 'WEB-SN-001',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $logCard = LogCard::query()->where('workorder_id', $workorder->id)->firstOrFail();

        $this->actingAs($technician)
            ->patchJson(route('mobile.log-card.rows.update', [$logCard->id, 1]), [
                'field' => 'serial_number',
                'value' => 'WEB-SN-002',
            ])
            ->assertOk()
            ->assertJsonPath('data.value', 'WEB-SN-002');
    }

    public function test_mobile_log_card_photo_is_saved_to_logs_only_after_recognition_is_confirmed(): void
    {
        config()->set('services.openai.api_key', 'test-key-not-a-real-secret');
        config()->set('services.openai.model', 'gpt-5.4');
        config()->set('services.openai.retry_attempts', 1);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output' => [[
                    'type' => 'function_call',
                    'name' => 'extract_nameplate_identifiers',
                    'arguments' => json_encode([
                        'part_numbers' => ['PN-32/100'],
                        'serial_numbers' => ['SN-00042'],
                        'other_identifiers' => [],
                        'confidence' => 'high',
                        'notes' => 'Both labels are clear.',
                    ]),
                ]],
            ]),
        ]);

        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $technician->id]);

        $response = $this->actingAs($technician)
            ->post(route('mobile.log-card.photo.store', $workorder), [
                'photo' => UploadedFile::fake()->image('nameplate.png', 120, 80),
                'row_index' => 1,
                'expected_part_number' => 'PN-32/100',
            ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('recognition.part_numbers.0', 'PN-32/100')
            ->assertJsonPath('recognition.serial_numbers.0', 'SN-00042')
            ->assertJsonPath('recognition.confidence', 'high')
            ->assertJsonPath('photo', null)
            ->assertJsonPath('pending_confirmation', true)
            ->assertJsonPath('photo_count', 0);

        $this->assertCount(0, $workorder->fresh()->getMedia('logs'));

        $this->actingAs($technician)
            ->post(route('mobile.log-card.photo.store', $workorder), [
                'photo' => UploadedFile::fake()->image('confirmed-nameplate.png', 120, 80),
                'row_index' => 1,
                'recognize' => 0,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pending_confirmation', false)
            ->assertJsonPath('photo.id', fn ($id): bool => is_int($id) && $id > 0)
            ->assertJsonPath('photo_count', 1);

        $media = $workorder->fresh()->getMedia('logs')->first();
        $this->assertNotNull($media);
        $this->assertSame('mobile_log_card', $media->getCustomProperty('source'));
        $this->assertSame(1, $media->getCustomProperty('log_card_row_index'));

        Http::assertSent(function (HttpRequest $request): bool {
            $payload = $request->data();
            $image = $payload['input'][0]['content'][1] ?? [];
            $tool = $payload['tools'][0] ?? [];

            return $request->url() === 'https://api.openai.com/v1/responses'
                && ($image['type'] ?? null) === 'input_image'
                && str_starts_with((string) ($image['image_url'] ?? ''), 'data:image/png;base64,')
                && ($image['detail'] ?? null) === 'high'
                && ($tool['name'] ?? null) === 'extract_nameplate_identifiers'
                && ($tool['strict'] ?? false) === true
                && ($payload['store'] ?? null) === false;
        });
    }

    public function test_unlabelled_identifier_is_offered_as_serial_candidate_for_selected_part_number(): void
    {
        config()->set('services.openai.api_key', 'test-key-not-a-real-secret');
        config()->set('services.openai.model', 'gpt-5.4');
        config()->set('services.openai.retry_attempts', 1);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output' => [[
                    'type' => 'function_call',
                    'name' => 'extract_nameplate_identifiers',
                    'arguments' => json_encode([
                        'part_numbers' => ['52103-1101'],
                        'serial_numbers' => [],
                        'other_identifiers' => ['SPP511051'],
                        'confidence' => 'high',
                        'notes' => 'Both engraved identifiers are visible.',
                    ]),
                ]],
            ]),
        ]);

        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $technician->id]);

        $this->actingAs($technician)
            ->post(route('mobile.log-card.photo.store', $workorder), [
                'photo' => UploadedFile::fake()->image('piston-axle.png', 120, 80),
                'row_index' => 1,
                'expected_part_number' => '52103-1101',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('recognition.part_numbers.0', '52103-1101')
            ->assertJsonPath('recognition.serial_numbers.0', 'SPP511051');

        Http::assertSent(function (HttpRequest $request): bool {
            $payload = $request->data();
            $prompt = (string) ($payload['input'][0]['content'][0]['text'] ?? '');
            $required = $payload['tools'][0]['parameters']['required'] ?? [];

            return str_contains($prompt, 'Expected row P/N: 52103-1101')
                && str_contains($prompt, 'does not need to have a label')
                && str_contains($prompt, 'serial number may be above the part number or below it')
                && str_contains($prompt, 'separated by a visible gap around the circumference')
                && in_array('other_identifiers', $required, true);
        });
    }

    public function test_mobile_log_card_photo_is_not_saved_when_recognition_is_unavailable_until_confirmed(): void
    {
        config()->set('services.openai.api_key', 'test-key-not-a-real-secret');
        config()->set('services.openai.retry_attempts', 1);
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response(['error' => ['type' => 'server_error']], 500),
        ]);

        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $technician->id]);

        $this->actingAs($technician)
            ->post(route('mobile.log-card.photo.store', $workorder), [
                'photo' => UploadedFile::fake()->image('unreadable-nameplate.png', 120, 80),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('recognition', null)
            ->assertJsonPath('pending_confirmation', true)
            ->assertJsonPath('photo_count', 0);

        $this->assertCount(0, $workorder->fresh()->getMedia('logs'));
    }

    public function test_mobile_log_card_photo_only_skips_nameplate_recognition(): void
    {
        config()->set('services.openai.api_key', 'test-key-not-a-real-secret');
        Http::fake();

        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $technician->id]);

        $this->actingAs($technician)
            ->post(route('mobile.log-card.photo.store', $workorder), [
                'photo' => UploadedFile::fake()->image('detail-only.png', 120, 80),
                'recognize' => 0,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('recognition', null)
            ->assertJsonPath('message', 'Log Card photo saved.')
            ->assertJsonPath('pending_confirmation', false)
            ->assertJsonPath('photo_count', 1);

        Http::assertNothingSent();
    }

    public function test_mobile_log_card_rejects_portrait_before_nameplate_recognition(): void
    {
        config()->set('services.openai.api_key', 'test-key-not-a-real-secret');
        Http::fake();

        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $technician->id]);

        $this->actingAs($technician)
            ->post(route('mobile.log-card.photo.store', $workorder), [
                'photo' => UploadedFile::fake()->image('portrait-nameplate.png', 80, 120),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo']);

        Http::assertNothingSent();
        $this->assertCount(0, $workorder->fresh()->getMedia('logs'));
    }
}
