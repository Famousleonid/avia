<?php

namespace Tests\Feature;

use App\Models\Instruction;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MobileShippingDraftRecognitionTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_shipping_draft_page_has_as_received_nameplate_recognition(): void
    {
        $shipper = $this->createUserWithRole('Shipping');

        $this->actingAs($shipper)
            ->get(route('mobile.draft'))
            ->assertOk()
            ->assertSee('As Received nameplate')
            ->assertSee('name="as_received_photo"', false)
            ->assertSee('id="mobileLandscapeWarning"', false)
            ->assertSee('TURN YOUR PHONE HORIZONTALLY!')
            ->assertSee(route('mobile.draft.nameplate.recognize'), false)
            ->assertSee('mobile-landscape-photo.js', false)
            ->assertSee('mobile-shipping-draft.js', false);
    }

    public function test_shipping_can_read_nameplate_before_creating_draft(): void
    {
        config()->set('services.openai.api_key', 'test-key-not-a-real-secret');
        config()->set('services.openai.retry_attempts', 1);
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output' => [[
                    'type' => 'function_call',
                    'name' => 'extract_nameplate_identifiers',
                    'arguments' => json_encode([
                        'part_numbers' => ['SHIP-PN-100'],
                        'serial_numbers' => ['SHIP-SN-200'],
                        'other_identifiers' => [],
                        'confidence' => 'high',
                        'notes' => 'Clear nameplate.',
                    ]),
                ]],
            ]),
        ]);

        $shipper = $this->createUserWithRole('Shipping');

        $this->actingAs($shipper)
            ->post(route('mobile.draft.nameplate.recognize'), [
                'photo' => UploadedFile::fake()->image('shipping-nameplate.png', 120, 80),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('recognition.part_numbers.0', 'SHIP-PN-100')
            ->assertJsonPath('recognition.serial_numbers.0', 'SHIP-SN-200');
    }

    public function test_draft_creation_saves_staged_nameplate_photo_in_as_received(): void
    {
        $shipper = $this->createUserWithRole('Shipping');
        $customer = $this->createCustomer();
        $unit = $this->createUnit(['part_number' => 'SHIP-PN-SAVED']);
        Instruction::query()->firstOrCreate(['id' => 6], ['name' => 'Mobile Draft']);

        $this->actingAs($shipper)
            ->post(route('mobile.draft.store'), [
                'unit_id' => $unit->id,
                'customer_id' => $customer->id,
                'serial_number' => 'SHIP-SN-SAVED',
                'description' => 'Shipping recognized unit',
                'open_at' => format_project_date(now()),
                'as_received_photo' => UploadedFile::fake()->image('as-received-nameplate.png', 120, 80),
            ])
            ->assertSessionHasNoErrors();

        $draft = Workorder::query()
            ->withoutGlobalScope('exclude_drafts')
            ->where('user_id', $shipper->id)
            ->where('is_draft', true)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('SHIP-SN-SAVED', $draft->serial_number);
        $this->assertSame($unit->id, $draft->unit_id);
        $this->assertCount(1, $draft->getMedia('received'));
        $this->assertSame(
            'mobile_shipping_draft',
            $draft->getFirstMedia('received')?->getCustomProperty('source')
        );
    }

    public function test_portrait_nameplate_is_rejected_before_draft_is_created(): void
    {
        $shipper = $this->createUserWithRole('Shipping');
        $customer = $this->createCustomer();
        $unit = $this->createUnit(['part_number' => 'SHIP-PORTRAIT']);
        Instruction::query()->firstOrCreate(['id' => 6], ['name' => 'Mobile Draft']);
        $draftCount = Workorder::query()->withoutGlobalScope('exclude_drafts')->where('is_draft', true)->count();

        $this->actingAs($shipper)
            ->post(route('mobile.draft.store'), [
                'unit_id' => $unit->id,
                'customer_id' => $customer->id,
                'open_at' => format_project_date(now()),
                'as_received_photo' => UploadedFile::fake()->image('portrait.png', 80, 120),
            ])
            ->assertSessionHasErrors(['as_received_photo']);

        $this->assertSame(
            $draftCount,
            Workorder::query()->withoutGlobalScope('exclude_drafts')->where('is_draft', true)->count()
        );
    }

    public function test_non_shipping_role_cannot_use_shipping_draft_recognition(): void
    {
        config()->set('services.openai.api_key', 'test-key-not-a-real-secret');
        Http::fake();
        $technician = $this->createUserWithRole('Technician');

        $this->actingAs($technician)
            ->post(route('mobile.draft.nameplate.recognize'), [
                'photo' => UploadedFile::fake()->image('forbidden.png', 120, 80),
            ], ['Accept' => 'application/json'])
            ->assertForbidden();

        Http::assertNothingSent();
    }
}
