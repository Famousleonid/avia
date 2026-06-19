<?php

namespace Tests\Feature;

use App\Models\Component;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualPartsEffCodeTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_manual_parts_create_edit_and_table_include_eff_code(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();

        $createResponse = $this
            ->actingAs($admin)
            ->postJson(route('components.store'), [
                'manual_id' => $manual->id,
                'ipl_num' => '1-10',
                'part_number' => 'PN-100',
                'name' => 'Manual Part With Eff',
                'units_assy' => '2',
                'eff_code' => 'EFF-A',
                'np' => true,
                'redirect' => route('manuals.show', ['manual' => $manual, 'tab' => 'parts']),
            ]);

        $createResponse->assertOk();
        $component = Component::query()->where('manual_id', $manual->id)->where('part_number', 'PN-100')->firstOrFail();
        $this->assertSame('EFF-A', $component->eff_code);
        $this->assertTrue((bool) $component->np);

        $updateResponse = $this
            ->actingAs($admin)
            ->putJson(route('components.update', $component), [
                'manual_id' => $manual->id,
                'ipl_num' => '1-10',
                'part_number' => 'PN-100',
                'name' => 'Manual Part With Eff',
                'units_assy' => '2',
                'eff_code' => 'EFF-B',
                'np' => true,
                'redirect' => route('manuals.show', ['manual' => $manual, 'tab' => 'parts']),
            ]);

        $updateResponse->assertOk();
        $component->refresh();
        $this->assertSame('EFF-B', $component->eff_code);
        $this->assertTrue((bool) $component->np);

        $showResponse = $this->actingAs($admin)->get(route('manuals.show', ['manual' => $manual, 'tab' => 'parts']));

        $showResponse->assertOk();
        $html = $showResponse->getContent();
        $this->assertStringContainsString('EFF Code', $html);
        $this->assertStringContainsString('EFF-B', $html);
        $this->assertMatchesRegularExpression('/<th[^>]*title="Kit"[^>]*>Kit<\/th>\s*<th[^>]*title="NP"[^>]*>NP<\/th>\s*<th[^>]*title="Kit E"[^>]*>Kit_E<\/th>/s', $html);
        $this->assertStringContainsString('data-field="np"', $html);

        $jsonResponse = $this->actingAs($admin)->getJson(route('components.showJson', $component));
        $jsonResponse->assertOk();
        $jsonResponse->assertJsonPath('component.np', true);

        $flagResponse = $this->actingAs($admin)->patchJson(route('components.updateFlags', $component), [
            'field' => 'np',
            'value' => false,
        ]);
        $flagResponse->assertOk();
        $flagResponse->assertJsonPath('field', 'np');
        $flagResponse->assertJsonPath('value', false);
        $this->assertFalse((bool) $component->fresh()->np);
    }
}
