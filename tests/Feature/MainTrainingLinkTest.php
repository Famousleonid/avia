<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MainTrainingLinkTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_technician_plus_links_to_trainings_without_local_dialog(): void
    {
        $this->actingAs($this->createUserWithRole('Technician'));
        $html = Blade::render('<x-training-status :manual-id="78" />');
        $this->assertStringContainsString(route('trainings.index', ['manual_id' => 78]), $html);
        $this->assertStringNotContainsString('mains-add-trainings-btn', $html);
        $this->assertStringContainsString('training-link', $html);
    }

    public function test_admin_still_has_no_plus(): void
    {
        $this->actingAs($this->createUserWithRole('Admin'));
        $html = Blade::render('<x-training-status :manual-id="78" />');
        $this->assertStringNotContainsString('href=', $html);
    }
}
