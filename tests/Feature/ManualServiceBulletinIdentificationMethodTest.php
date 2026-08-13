<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualServiceBulletinIdentificationMethodTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_identification_method_accepts_600_characters_and_rejects_601(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createWorkorder()->unit->manual;
        $acceptedValue = str_repeat('A', 600);

        $accepted = $this->actingAs($admin)->post(
            route('manuals.service-bulletins.store', ['manual' => $manual]),
            ['identification_method' => $acceptedValue]
        );

        $accepted->assertSessionHasNoErrors();
        $this->assertDatabaseHas('manual_service_bulletins', [
            'manual_id' => $manual->id,
            'identification_method' => $acceptedValue,
        ]);

        $rejected = $this->actingAs($admin)->from(
            route('manuals.show', ['manual' => $manual, 'tab' => 'sb'])
        )->post(
            route('manuals.service-bulletins.store', ['manual' => $manual]),
            ['identification_method' => str_repeat('B', 601)]
        );

        $rejected->assertSessionHasErrors('identification_method');
        $this->assertDatabaseCount('manual_service_bulletins', 1);
    }

    public function test_identification_method_inputs_expose_the_600_character_limit(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createWorkorder()->unit->manual;
        $manual->serviceBulletins()->create([
            'identification_method' => 'Visual inspection',
        ]);

        $response = $this->actingAs($admin)->get(
            route('manuals.show', ['manual' => $manual, 'tab' => 'sb'])
        );

        $response->assertOk();
        $this->assertSame(2, substr_count(
            $response->getContent(),
            'name="identification_method" class="form-control form-control-sm" maxlength="600"'
        ));
    }
}
