<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualPartGroup;
use App\Models\Tdr;
use App\Services\PartGroupCoverageResolver;
use App\Services\PartVariantGrouping;
use App\Support\KitPrlGrouping;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Tests\BuildsDomainData;
use Tests\TestCase;

class LegacyPartGroupRetirementTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions {
        beginDatabaseTransaction as private beginLegacyTransaction;
    }

    public function beginDatabaseTransaction()
    {
        // These compatibility tests deliberately emulate a pre-retirement DB.
        // DDL must run outside the transaction (MySQL implicitly commits DDL).
        $migration = require database_path('migrations/2026_09_17_100000_drop_retired_kit_prl_choice_group.php');
        $restoreSchema = ! Schema::hasColumn('components', 'kit_prl_choice_group');
        if ($restoreSchema) {
            $migration->down();
        }
        $this->beginLegacyTransaction();
        if ($restoreSchema) {
            $this->beforeApplicationDestroyed(fn () => $migration->up());
        }
    }

    private function part($manual, string $ipl, string $pn, array $extra = []): Component
    {
        return Component::forceCreate(array_merge(['manual_id' => $manual->id, 'ipl_num' => $ipl,
            'part_number' => $pn, 'name' => 'Bearing', 'units_assy' => 1, 'is_bush' => false], $extra));
    }

    private function alternative($manual, array $parts): ManualPartGroup
    {
        $g = ManualPartGroup::create(['manual_id' => $manual->id, 'name' => 'Variants', 'code' => uniqid('TEST-'),
            'type' => 'alternative_pn', 'behavior' => 'choose_one', 'applies_to' => ManualPartGroup::validScopes()]);
        foreach ($parts as $i => $p) {
            $g->options()->create(['component_id' => $p->id, 'ipl_num' => $p->ipl_num,
                'part_number' => $p->part_number, 'is_default' => $i === 0, 'sort_order' => $i]);
        }
        return $g;
    }

    public function test_new_group_replaces_legacy_keys_but_pn_alone_never_groups_positions(): void
    {
        $m = $this->createManual();
        $a = $this->part($m, '1-230', 'SAME', ['kit_prl_choice_group' => 'old-A']);
        $b = $this->part($m, '1-231', 'B', ['kit_prl_choice_group' => 'old-B']);
        $other = $this->part($m, '1-500', 'SAME', ['kit_prl_choice_group' => 'old-A']);
        $letter = $this->part($m, '1-230A', 'A-LETTER');
        $this->alternative($m, [$a, $b]);
        $keys = app(PartVariantGrouping::class)->componentKeys([$a, $b, $other, $letter]);
        $this->assertSame($keys[$a->id], $keys[$b->id]);
        $this->assertSame($keys[$a->id], $keys[$letter->id]);
        $this->assertNotSame($keys[$a->id], $keys[$other->id]);
        $this->assertNotSame(KitPrlGrouping::numericIplGroupKey('1A-230'), KitPrlGrouping::numericIplGroupKey('1-230'));
        $this->assertSame(KitPrlGrouping::numericIplGroupKey('1-230A'), KitPrlGrouping::numericIplGroupKey('1-230B'));
    }

    public function test_automatic_ipl_crossout_only_covers_unordered_letter_variants(): void
    {
        $m = $this->createManual(); $unit = $this->createUnit(['manual_id' => $m->id]);
        $wo = $this->createWorkorder(['unit_id' => $unit->id]);
        $a = $this->part($m, '1-230', 'A'); $b = $this->part($m, '1-230A', 'B');
        $other = $this->part($m, '1-500', 'A');
        Tdr::create(['workorder_id' => $wo->id, 'component_id' => $a->id, 'order_component_id' => $b->id, 'qty' => 1]);
        $coverage = app(PartGroupCoverageResolver::class)->coverageForWorkorder($wo, 'prl');
        $this->assertArrayHasKey($a->id, $coverage);
        $this->assertArrayNotHasKey($b->id, $coverage);
        $this->assertArrayNotHasKey($other->id, $coverage);
    }

    public function test_retired_endpoint_does_not_write_and_manual_has_only_part_groups(): void
    {
        $admin = $this->createUserWithRole('Admin'); $m = $this->createManual();
        $p = $this->part($m, '1-230', 'A', ['kit_prl_choice_group' => 'archive']);
        $this->actingAs($admin)->patchJson(route('manuals.components.kit-prl-choice-group', $m), [
            'component_ids' => [$p->id], 'action' => 'clear',
        ])->assertStatus(410);
        $this->assertSame('archive', $p->fresh()->kit_prl_choice_group);
        $this->get(route('manuals.show', $m))->assertOk()->assertSee('manual-part-groups-open', false)
            ->assertDontSee('manual-kit-choice-group-apply', false)->assertDontSee('initManualKitChoiceGrouping', false);
    }

    public function test_migration_reuses_exact_group_and_automatic_family_needs_no_group(): void
    {
        Storage::fake('local'); $m = $this->createManual();
        $a = $this->part($m, '1-230', 'A', ['kit_prl_choice_group' => 'old']);
        $b = $this->part($m, '1-231', 'B', ['kit_prl_choice_group' => 'old']);
        $g = $this->alternative($m, [$a, $b]);
        $c = $this->part($m, '1-400', 'C', ['kit_prl_choice_group' => 'letters']);
        $d = $this->part($m, '1-400A', 'D', ['kit_prl_choice_group' => 'letters']);
        $this->assertSame(0, Artisan::call('part-groups:retire-legacy'));
        $this->assertSame('old', $a->fresh()->kit_prl_choice_group);
        $this->assertSame(0, Artisan::call('part-groups:retire-legacy', ['--apply' => true]));
        $this->assertNull($a->fresh()->kit_prl_choice_group);
        $this->assertNull($c->fresh()->kit_prl_choice_group);
        $this->assertSame([$g->id], ManualPartGroup::where('manual_id', $m->id)->pluck('id')->all());
        $this->assertCount(1, Storage::disk('local')->files('part-groups'));
        $this->assertSame(0, Artisan::call('part-groups:retire-legacy', ['--apply' => true]));
    }

    public function test_mixed_legacy_group_splits_fitting_from_complete_bushing_family(): void
    {
        Storage::fake('local'); $m = $this->createManual();
        $f = $this->part($m, '1-590', 'F', ['name' => 'Fitting', 'kit_prl_choice_group' => 'mixed']);
        $this->part($m, '1-590A', 'FA', ['name' => 'Fitting', 'kit_prl_choice_group' => 'mixed']);
        $b = $this->part($m, '1-610', 'B', ['name' => 'Bushing', 'is_bush' => true, 'bush_ipl_num' => '1-610', 'kit_prl_choice_group' => 'mixed']);
        $repair = $this->part($m, '1-611', 'BA', ['name' => 'Bushing', 'is_bush' => true, 'bush_ipl_num' => '1-610']);
        $this->assertSame(0, Artisan::call('part-groups:retire-legacy', ['--apply' => true]));
        $g = ManualPartGroup::where('manual_id', $m->id)->sole();
        $this->assertSame('oversize', $g->type);
        $this->assertSame([$b->id, $repair->id], $g->options->pluck('component_id')->all());
        $this->assertNull($f->fresh()->kit_prl_choice_group);
    }

    public function test_std_rows_use_new_group_key_and_keep_crossed_rows_separate(): void
    {
        $m = $this->createManual(); $a = $this->part($m, '1-230', 'A'); $b = $this->part($m, '1-231', 'B');
        $this->alternative($m, [$a, $b]);
        $rows = app(PartVariantGrouping::class)->annotateStdRows([
            ['component_id' => $a->id, 'manual_id' => $m->id, 'ipl_num' => $a->ipl_num, 'qty' => 2],
            ['component_id' => $b->id, 'manual_id' => $m->id, 'ipl_num' => $b->ipl_num, 'qty' => 3],
        ], 'ndt');
        $this->assertSame(PartVariantGrouping::stdKey($rows[0]), PartVariantGrouping::stdKey($rows[1]));
        $rows[1]['group_crossed_out'] = true;
        $this->assertNotSame(PartVariantGrouping::stdKey($rows[0]), PartVariantGrouping::stdKey($rows[1]));
    }

    public function test_archived_legacy_links_are_backed_up_and_cleared_without_new_groups(): void
    {
        Storage::fake('local');
        $manual = $this->createManual();
        $part = $this->part($manual, '1-411', 'ARCHIVED', ['kit_prl_choice_group' => 'old-archived']);
        $part->delete();
        $this->assertSame(0, Artisan::call('part-groups:retire-legacy'));
        $this->assertSame('old-archived', Component::withTrashed()->findOrFail($part->id)->kit_prl_choice_group);
        $this->assertSame(0, Artisan::call('part-groups:retire-legacy', ['--apply' => true]));
        $archived = Component::withTrashed()->findOrFail($part->id);
        $this->assertTrue($archived->trashed());
        $this->assertNull($archived->kit_prl_choice_group);
        $this->assertSame(0, ManualPartGroup::where('manual_id', $manual->id)->count());
        $files = Storage::disk('local')->files('part-groups');
        $this->assertCount(1, $files);
        $audit = json_decode(Storage::disk('local')->get($files[0]), true);
        $this->assertSame('old-archived', $audit['parts'][0]['kit_prl_choice_group']);
        $this->assertNotNull($audit['parts'][0]['deleted_at']);
    }
}
