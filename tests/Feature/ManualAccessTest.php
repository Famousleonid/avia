<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualAccessTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_technician_cannot_open_manual_show_page_without_permission(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $manual = $this->createManual([
            'number' => 'FORBIDDEN-MANUAL',
            'title' => 'Forbidden Manual',
        ]);

        $response = $this->actingAs($technician)->get(route('manuals.show', $manual));

        $response->assertForbidden();
    }

    public function test_technician_can_open_manual_show_page_when_permitted(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $manual = $this->createManual([
            'number' => 'ALLOWED-MANUAL',
            'title' => 'Allowed Manual',
        ]);
        $manual->permittedUsers()->attach($technician->id);

        $response = $this->actingAs($technician)->get(route('manuals.show', $manual));

        $response->assertOk();
        $response->assertSee('Allowed Manual');
    }

    public function test_technician_cannot_open_manual_edit_page_without_permission(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $manual = $this->createManual([
            'number' => 'EDIT-FORBIDDEN',
            'title' => 'Edit Forbidden Manual',
        ]);

        $response = $this->actingAs($technician)->get(route('manuals.edit', $manual));

        $response->assertForbidden();
    }
}
