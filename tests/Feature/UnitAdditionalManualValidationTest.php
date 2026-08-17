<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class UnitAdditionalManualValidationTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_manual_endpoint_assigns_additional_manual_once_for_multiple_units(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $primaryManual = $this->createManual(['number' => 'UNIT-BULK-UPDATE-MAIN']);
        $additionalManual = $this->createManual(['number' => 'UNIT-BULK-UPDATE-EXTRA']);
        $firstUnit = $this->createUnit(['manual_id' => $primaryManual->id, 'part_number' => 'UNIT-PN-100']);
        $secondUnit = $this->createUnit(['manual_id' => $primaryManual->id, 'part_number' => 'UNIT-PN-200']);

        $this->actingAs($admin)
            ->patchJson(route('manuals.additional-manuals.update', $primaryManual), [
                'additional_manual_ids' => [$additionalManual->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame([$additionalManual->id], $primaryManual->fresh()->additionalManualIds());
        $this->assertSame([$additionalManual->id], $firstUnit->fresh()->additionalManualIds());
        $this->assertSame([$additionalManual->id], $secondUnit->fresh()->additionalManualIds());
    }

    public function test_bulk_create_does_not_store_additional_manuals_on_units(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $primaryManual = $this->createManual(['number' => 'UNIT-BULK-CREATE-MAIN']);
        $additionalManual = $this->createManual(['number' => 'UNIT-BULK-CREATE-EXTRA']);
        $primaryManual->update(['additional_manual_ids' => [$additionalManual->id]]);

        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson(route('units.store'), [
                'cmm_id' => $primaryManual->id,
                'units' => [
                    [
                        'part_number' => 'UNIT-CREATE-100',
                        'additional_manual_ids' => [$additionalManual->id],
                    ],
                    [
                        'part_number' => 'UNIT-CREATE-200',
                        'additional_manual_ids' => [$additionalManual->id],
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $units = \App\Models\Unit::query()
            ->where('manual_id', $primaryManual->id)
            ->whereIn('part_number', ['UNIT-CREATE-100', 'UNIT-CREATE-200'])
            ->get();

        $this->assertCount(2, $units);
        $this->assertSame([$additionalManual->id], $primaryManual->fresh()->additionalManualIds());
        foreach ($units as $unit) {
            $this->assertSame([$additionalManual->id], $unit->additionalManualIds());
        }
    }
}
