<?php

namespace Tests\Feature;

use App\Models\Component;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ComponentsEffCodeTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_components_page_create_edit_and_table_include_eff_code(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();

        $createResponse = $this
            ->actingAs($admin)
            ->postJson(route('components.store'), [
                'manual_id' => $manual->id,
                'ipl_num' => '2-10',
                'part_number' => 'CPN-100',
                'name' => 'Component With Eff',
                'units_assy' => '1',
                'eff_code' => 'EFF-COMP-A',
                'redirect' => route('components.index'),
            ]);

        $createResponse->assertOk();
        $this->assertStringContainsString('EFF-COMP-A', $createResponse->json('row_html'));
        $component = Component::query()->where('manual_id', $manual->id)->where('part_number', 'CPN-100')->firstOrFail();
        $this->assertSame('EFF-COMP-A', $component->eff_code);

        $updateResponse = $this
            ->actingAs($admin)
            ->putJson(route('components.update', $component), [
                'manual_id' => $manual->id,
                'ipl_num' => '2-10',
                'part_number' => 'CPN-100',
                'name' => 'Component With Eff',
                'units_assy' => '1',
                'eff_code' => 'EFF-COMP-B',
                'redirect' => route('components.index'),
            ]);

        $updateResponse->assertOk();
        $this->assertStringContainsString('EFF-COMP-B', $updateResponse->json('row_html'));
        $component->refresh();
        $this->assertSame('EFF-COMP-B', $component->eff_code);

        $indexResponse = $this->actingAs($admin)->get(route('components.index'));

        $indexResponse->assertOk();
        $html = $indexResponse->getContent();
        $this->assertStringContainsString('EFF-COMP-B', $html);
        $this->assertMatchesRegularExpression('/<th[^>]*>EFF Code<\/th>\s*<th[^>]*>Image<\/th>/s', $html);

        $ajaxRowsResponse = $this
            ->actingAs($admin)
            ->getJson(route('components.index', ['manual_id' => $manual->id]), ['X-Requested-With' => 'XMLHttpRequest']);

        $ajaxRowsResponse->assertOk();
        $this->assertStringContainsString('EFF-COMP-B', $ajaxRowsResponse->json('rows_html'));
    }
}
