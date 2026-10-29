<?php

namespace Tests\Feature;

use App\Models\{Component, StdProcess, UserUiSetting, WorkorderStdProcessItem};
use App\Services\{WorkorderFormCalculation, WorkorderStdProcessItemsService};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\{BuildsDomainData, TestCase};

class TdrLoadingTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_empty_std_is_prepared_once_and_a_new_calculation_reads_new_parts(): void
    {
        $wo = $this->createWorkorder(['scope_type' => 'full_unit']);
        WorkorderStdProcessItem::where('workorder_id', $wo->id)->delete();
        $calculation = new WorkorderFormCalculation($wo);
        DB::enableQueryLog(); DB::flushQueryLog();
        try {
            foreach (StdProcess::validStdValues() as $scope) $this->assertSame([], $calculation->stdRows($scope));
            $deletes = collect(DB::getQueryLog())->filter(fn ($q) => str_starts_with($q['query'], 'delete from `workorder_std_process_items`'));
            $this->assertCount(1, $deletes);
        } finally { DB::disableQueryLog(); }
        $part = $this->createComponent($wo->unit->manual, ['ndt_list' => true, 'ipl_num' => '1-10']);
        $rows = (new WorkorderFormCalculation($wo))->stdRows('ndt');
        $this->assertContains($part->id, array_column($rows, 'component_id'));
    }

    public function test_shared_calculation_matches_each_live_std_scope_and_refreshes_kit_exclusions(): void
    {
        $wo = $this->createWorkorder(['scope_type' => 'full_unit', 'instruction_id' => $this->createOverhaulInstruction()->id]);
        $part = $this->createComponent($wo->unit->manual, ['ipl_num' => '1-10', 'kit' => true,
            'ndt_list' => true, 'cad_list' => true, 'paint_list' => true, 'stress_relief_list' => true]);
        app(WorkorderStdProcessItemsService::class)->rebuild($wo);
        $calculation = new WorkorderFormCalculation($wo);
        foreach (StdProcess::validStdValues() as $scope) {
            $this->assertSame(StdProcess::snapshotComponentsForWorkorder($wo, $scope), $calculation->stdRows($scope));
        }
        $this->assertSame([], $calculation->stdRows('ndt'));
        $part->update(['kit' => false]);
        $this->assertContains($part->id, array_column((new WorkorderFormCalculation($wo))->stdRows('ndt'), 'component_id'));
    }

    public function test_page_embeds_user_tab_and_fragments_keep_counts_without_scripts_or_catalogues(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $this->createComponent($wo->unit->manual, ['ipl_num' => '1-10', 'part_number' => 'CATALOGUE-ONLY']);
        UserUiSetting::create(['user_id' => $admin->id, 'scope' => 'tdrs.show', 'key' => 'activeTab:'.$wo->id, 'value' => 'tab-log-card']);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()]);
        $page = $this->get(route('tdrs.show', $wo))->assertOk();
        $this->assertSame('tab-log-card', $page->viewData('initialTdrTab'));
        $page->assertSee('class="nav-link active" id="tab-log-card"', false)->assertDontSee('visibility:hidden;', false);
        $fragment = $this->get(route('tdrs.show.fragments', $wo))->assertOk();
        $this->assertSame($page->viewData('prlPartsCount'), $fragment->viewData('prlPartsCount'));
        $fragment->assertSee('id="tdr-prl-paper-group"', false)->assertDontSee('<script', false)
            ->assertDontSee('<html', false)->assertDontSee('CATALOGUE-ONLY');
        $this->assertStringContainsString('no-store', $fragment->headers->get('Cache-Control'));
        UserUiSetting::where('user_id', $admin->id)->where('scope', 'tdrs.show')->update(['value' => json_encode('tab-transfers')]);
        $this->assertSame('tab-tdr', $this->get(route('tdrs.show', $wo))->assertOk()->viewData('initialTdrTab'));
    }
}
