<?php

namespace Tests\Feature;

use App\Models\Condition;
use App\Models\Tdr;
use App\Models\WorkorderUnitInspection;
use App\Services\TdrInspectionLinesBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ConditionVersionsTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_manage_buttons_call_real_routes_for_edit_and_archive(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $edit = Condition::create(['name' => 'Route edit original', 'unit' => 1]);
        $archive = Condition::create(['name' => 'Route archive original', 'unit' => 1]);
        $page = $this->actingAs($admin)->get(route('tdrs.show', ['id' => $wo->id]))->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML($page->getContent());
        $xpath = new \DOMXPath($dom);
        $urlFor = function (string $class, int $id) use ($xpath): string {
            $button = $xpath->query('//button[contains(@class,"'.$class.'") and @data-condition-id="'.$id.'"]')->item(0);
            $this->assertNotNull($button);
            $url = $button->getAttribute('data-url');
            $this->assertNotEmpty($url);
            return $url;
        };

        // Use the actual URLs rendered into the controls, not independently generated test URLs.
        $this->putJson($urlFor('save-condition-btn', $edit->id), ['name' => 'Route edited version', 'unit' => 1])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertNotNull($edit->fresh()->archived_at);
        $this->assertTrue(Condition::active()->where('name', 'Route edited version')->exists());
        $this->deleteJson($urlFor('delete-condition-btn', $archive->id))
            ->assertOk()->assertJsonPath('redirect', route('tdrs.show', ['id' => $wo->id]));
        $this->assertNotNull($archive->fresh()->archived_at);
    }

    public function test_edit_preserves_both_kinds_of_history_and_offers_only_new_version(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $other = $this->createWorkorder(['user_id' => $admin->id]);
        $old = Condition::create(['name' => 'Original inspection wording', 'unit' => 1]);
        $inspection = WorkorderUnitInspection::create([
            'workorder_id' => $wo->id, 'condition_id' => $old->id,
            'notes' => 'Original note', 'use_tdr' => true,
        ]);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'conditions_id' => $old->id]);
        $before = app(TdrInspectionLinesBuilder::class)->build($wo->fresh());

        $response = $this->actingAs($admin)->putJson(route('conditions.update', $old->id), [
            'name' => 'Replacement inspection wording', 'unit' => 1,
        ])->assertOk();
        $newId = $response->json('condition.id');
        $this->assertNotEquals($old->id, $newId);
        $this->assertNotNull($old->fresh()->archived_at);
        $this->assertSame('Original inspection wording', $inspection->fresh()->condition->name);
        $this->assertSame('Original inspection wording', $tdr->fresh()->conditions->name);
        $this->assertSame($before, app(TdrInspectionLinesBuilder::class)->build($wo->fresh()));
        $this->assertEquals($old->id, Condition::findOrFail($newId)->replaces_id);
        $this->assertFalse(Condition::active()->whereKey($old->id)->exists());
        $this->assertTrue(Condition::availableForInspection($wo->id)->whereKey($old->id)->exists());
        $this->assertFalse(Condition::availableForInspection($other->id)->whereKey($old->id)->exists());

        $this->get(route('tdrs.show', ['id' => $wo->id]))->assertOk()
            ->assertSee('Archived — existing inspection')->assertSee('Replacement inspection wording');
        $this->get(route('tdrs.inspection.unit', ['workorder_id' => $other->id]))->assertOk()
            ->assertDontSee('Original inspection wording')->assertSee('Replacement inspection wording');
        $this->postJson(route('tdrs.store.unit-inspections'), [
            'workorder_id' => $wo->id,
            'conditions' => [$old->id => ['selected' => true, 'notes' => 'Updated note']],
        ])->assertOk();
        $this->assertSame('Updated note', $inspection->fresh()->notes);
        $this->postJson(route('tdrs.store.unit-inspections'), [
            'workorder_id' => $other->id,
            'conditions' => [$old->id => ['selected' => true]],
        ])->assertUnprocessable();
        $this->postJson(route('tdrs.store.unit-inspections'), [
            'workorder_id' => $other->id,
            'conditions' => [$newId => ['selected' => true]],
        ])->assertOk();
        $this->assertDatabaseHas('workorder_unit_inspections', ['workorder_id' => $other->id, 'condition_id' => $newId]);
    }

    public function test_archive_keeps_references_printing_and_rejects_stale_selections_atomically(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $old = Condition::create(['name' => 'Archived used wording', 'unit' => 1]);
        $active = Condition::create(['name' => 'Active wording', 'unit' => 1]);
        $inspection = WorkorderUnitInspection::create(['workorder_id' => $wo->id, 'condition_id' => $old->id, 'use_tdr' => true]);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'conditions_id' => $old->id]);
        $before = app(TdrInspectionLinesBuilder::class)->build($wo->fresh());
        $this->actingAs($admin)->deleteJson(route('conditions.destroy', $old->id))->assertOk();
        $this->assertNotNull($old->fresh()->archived_at);
        $this->assertEquals($old->id, $inspection->fresh()->condition_id);
        $this->assertSame($old->name, $tdr->fresh()->conditions->name);
        $this->assertSame($before, app(TdrInspectionLinesBuilder::class)->build($wo->fresh()));

        $other = $this->createWorkorder(['user_id' => $admin->id]);
        $this->postJson(route('tdrs.store.unit-inspections'), [
            'workorder_id' => $other->id,
            'conditions' => [$active->id => ['selected' => true], $old->id => ['selected' => true]],
        ])->assertUnprocessable();
        $this->assertSame(0, WorkorderUnitInspection::where('workorder_id', $other->id)->count());
        $this->postJson(route('tdrs.store'), [
            'workorder_id' => $other->id, 'conditions_id' => $old->id, 'qty' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('conditions_id');
        $this->putJson(route('conditions.update', $old->id), ['name' => 'Stale edit'])->assertNotFound();
        $this->postJson(route('tdrs.store.unit-inspections'), [
            'workorder_id' => $wo->id, 'conditions' => [],
        ])->assertOk();
        $this->assertNull($inspection->fresh());
    }

    public function test_unchanged_edit_does_not_make_version_and_system_missing_remains_protected(): void
    {
        $this->actingAs($this->createUserWithRole('Admin'));
        $condition = Condition::create(['name' => 'note 123456', 'unit' => 1]);
        $this->putJson(route('conditions.update', $condition->id), ['name' => '', 'unit' => 1])
            ->assertOk()->assertJsonPath('condition.id', $condition->id);
        $this->assertNull($condition->fresh()->archived_at);
        $missing = Condition::firstOrCreate(['name' => Condition::NAME_PARTS_MISSING], ['unit' => 1]);
        $this->putJson(route('conditions.update', $missing->id), ['name' => 'Changed'])->assertForbidden();
        $this->deleteJson(route('conditions.destroy', $missing->id))->assertForbidden();
        $this->assertNull($missing->fresh()->archived_at);
    }
}
