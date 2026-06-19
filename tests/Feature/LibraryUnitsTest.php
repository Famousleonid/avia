<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class LibraryUnitsTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_admin_can_manage_units_from_library(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual([
            'number' => 'CMM-UNIT-CRUD',
            'title' => 'Unit CRUD Manual',
        ]);

        $indexResponse = $this->actingAs($admin)->get(route('library.units.index'));
        $indexResponse
            ->assertOk()
            ->assertSee('Units')
            ->assertSee('Manual pending');

        $createResponse = $this->actingAs($admin)->post(route('library.units.store'), [
            'part_number' => 'CRUD-UNIT-001',
            'manual_id' => $manual->id,
            'name' => 'Created Unit',
            'description' => 'Created Description',
            'eff_code' => 'ALL',
            'verified' => '1',
        ]);

        $createResponse
            ->assertRedirect(route('library.units.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('units', [
            'part_number' => 'CRUD-UNIT-001',
            'manual_id' => $manual->id,
            'name' => 'Created Unit',
            'description' => 'Created Description',
            'eff_code' => 'ALL',
            'verified' => 1,
        ]);

        $unitId = \App\Models\Unit::query()->where('part_number', 'CRUD-UNIT-001')->value('id');

        $updateResponse = $this->actingAs($admin)->put(route('library.units.update', ['unit' => $unitId]), [
            'part_number' => 'CRUD-UNIT-002',
            'manual_id' => '',
            'name' => 'Updated Unit',
            'description' => '',
            'eff_code' => '',
        ]);

        $updateResponse
            ->assertRedirect(route('library.units.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('units', [
            'id' => $unitId,
            'part_number' => 'CRUD-UNIT-002',
            'manual_id' => null,
            'name' => 'Updated Unit',
            'verified' => 0,
        ]);

        $deleteResponse = $this->actingAs($admin)->delete(route('library.units.destroy', ['unit' => $unitId]));
        $deleteResponse
            ->assertRedirect(route('library.units.index'))
            ->assertSessionMissing('success');

        $this->assertSoftDeleted('units', ['id' => $unitId]);
    }

    public function test_library_unit_rows_open_edit_without_edit_icon(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->createUnit(['part_number' => 'ROW-EDIT-UNIT']);

        $response = $this->actingAs($admin)->get(route('library.units.index', [
            'q' => 'ROW-EDIT-UNIT',
        ]));

        $response
            ->assertOk()
            ->assertSee('library-unit-row', false)
            ->assertSee('data-unit', false)
            ->assertDontSee('Scroll to load more units')
            ->assertDontSee('bi-pencil-square');
    }

    public function test_library_units_are_admin_only(): void
    {
        $manager = $this->createUserWithRole('Manager');

        $this->actingAs($manager)
            ->get(route('library.units.index'))
            ->assertForbidden();
    }

    public function test_library_unit_delete_is_blocked_when_workorders_are_linked(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $unit = $this->createUnit(['part_number' => 'LINKED-UNIT-' . uniqid()]);
        $this->createWorkorder([
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('library.units.destroy', ['unit' => $unit]));

        $response
            ->assertRedirect(route('library.units.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'deleted_at' => null,
        ]);
    }

    public function test_library_units_prevents_duplicate_pending_part_numbers(): void
    {
        $admin = $this->createUserWithRole('Admin');

        \App\Models\Unit::query()->create([
            'part_number' => 'PENDING-DUPLICATE',
            'manual_id' => null,
            'verified' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('library.units.store'), [
            'part_number' => 'PENDING-DUPLICATE',
            'manual_id' => '',
            'verified' => '1',
        ]);

        $response->assertSessionHasErrors(['part_number']);
    }

    public function test_library_units_infinite_scroll_returns_next_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');

        for ($i = 1; $i <= 55; $i++) {
            \App\Models\Unit::query()->create([
                'part_number' => sprintf('SCROLL-UNIT-%03d', $i),
                'manual_id' => null,
                'verified' => true,
            ]);
        }

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('library.units.index', [
                'q' => 'SCROLL-UNIT',
                'page' => 2,
            ]));

        $response
            ->assertOk()
            ->assertJsonPath('has_more', false);

        $this->assertStringContainsString('SCROLL-UNIT-051', $response->json('html'));
        $this->assertStringNotContainsString('SCROLL-UNIT-050', $response->json('html'));
    }
}
