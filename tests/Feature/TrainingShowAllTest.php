<?php

namespace Tests\Feature;

use App\Models\Training;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TrainingShowAllTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_show_all_excludes_system_admin_users_from_technician_columns(): void
    {
        $viewer = $this->createUserWithRole('Admin', [
            'name' => 'Training Viewer ' . uniqid(),
            'stamp' => 'TV',
        ]);
        $technician = $this->createUserWithRole('Technician', [
            'name' => 'Visible Technician ' . uniqid(),
            'stamp' => 'T01',
            'is_admin' => false,
        ]);
        $systemAdmin = $this->createUserWithRole('Admin', [
            'name' => 'Hidden Training Admin ' . uniqid(),
            'stamp' => 'A01',
            'is_admin' => true,
        ]);
        $manual = $this->createManual([
            'title' => 'Training Show All Manual ' . uniqid(),
            'unit_name_training' => 'TRAIN-PN-' . uniqid(),
        ]);

        Training::query()->create([
            'user_id' => $technician->id,
            'manuals_id' => $manual->id,
            'date_training' => '2026-05-15',
            'form_type' => '112',
        ]);
        Training::query()->create([
            'user_id' => $systemAdmin->id,
            'manuals_id' => $manual->id,
            'date_training' => '2024-01-01',
            'form_type' => '112',
        ]);

        $response = $this->actingAs($viewer)->get(route('trainings.showAll'));

        $response->assertOk();
        $response->assertSee($technician->name);
        $response->assertSee('May-15-2026');
        $response->assertDontSee($systemAdmin->name);
        $response->assertDontSee('Jan-01-2024');
    }
}
