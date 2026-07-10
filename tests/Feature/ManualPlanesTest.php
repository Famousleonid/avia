<?php

namespace Tests\Feature;

use App\Models\Manual;
use App\Models\Plane;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

/**
 * Multi-plane CMM: a manual may apply to several planes of one builder.
 * manual_plane pivot is the source of truth; manuals.planes_id mirrors the
 * first entry (denormalized "primary" for legacy readers).
 */
class ManualPlanesTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_manual_update_syncs_multiple_planes_and_mirrors_primary(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $p1 = Plane::query()->create(['type' => 'E170-' . uniqid()]);
        $p2 = Plane::query()->create(['type' => 'E175-' . uniqid()]);

        $this->actingAs($admin)->put(route('manuals.update', $manual->id), [
            'number' => $manual->number,
            'title' => $manual->title,
            'revision_date' => $manual->revision_date,
            'builders_id' => $manual->builders_id,
            'scopes_id' => $manual->scopes_id,
            'lib' => $manual->lib,
            'planes' => [$p1->id, $p2->id],
        ])->assertSessionHasNoErrors();

        $manual->refresh();
        $this->assertEqualsCanonicalizing(
            [$p1->id, $p2->id],
            $manual->planes()->pluck('planes.id')->all()
        );
        $this->assertSame($p1->id, (int) $manual->planes_id); // primary = first
        $this->assertStringContainsString(', ', $manual->planeTypesLabel());
    }

    public function test_manual_update_accepts_legacy_single_planes_id(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $plane = Plane::query()->create(['type' => 'A320-' . uniqid()]);

        $this->actingAs($admin)->put(route('manuals.update', $manual->id), [
            'number' => $manual->number,
            'title' => $manual->title,
            'revision_date' => $manual->revision_date,
            'builders_id' => $manual->builders_id,
            'scopes_id' => $manual->scopes_id,
            'lib' => $manual->lib,
            'planes_id' => $plane->id, // legacy payload — no planes[]
        ])->assertSessionHasNoErrors();

        $manual->refresh();
        $this->assertSame([$plane->id], $manual->planes()->pluck('planes.id')->all());
        $this->assertSame($plane->id, (int) $manual->planes_id);
    }

    public function test_std_kinship_uses_plane_set_intersection(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $shared = Plane::query()->create(['type' => 'E175-' . uniqid()]);
        $otherPlane = Plane::query()->create(['type' => 'B737-' . uniqid()]);

        // page manual covers [own, shared]; kin source covers only [shared]
        $page = $this->createManual();
        $page->planes()->sync([$page->planes_id, $shared->id]);

        $kin = $this->createManual(['planes_id' => $shared->id, 'builders_id' => $page->builders_id]);
        $kin->planes()->sync([$shared->id]);

        $alien = $this->createManual(['planes_id' => $otherPlane->id, 'builders_id' => $page->builders_id]);
        $alien->planes()->sync([$otherPlane->id]);

        // intersection non-empty → parts list served
        $this->actingAs($admin)
            ->getJson(route('manuals.std-processes.components-for-add', $page->id) . '?source_manual_id=' . $kin->id)
            ->assertOk();

        // no shared plane → 403 even with the same builder
        $this->actingAs($admin)
            ->getJson(route('manuals.std-processes.components-for-add', $page->id) . '?source_manual_id=' . $alien->id)
            ->assertStatus(403);
    }
}
