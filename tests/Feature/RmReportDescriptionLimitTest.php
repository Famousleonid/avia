<?php

namespace Tests\Feature;

use App\Models\RmReport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class RmReportDescriptionLimitTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_repair_modification_description_accepts_250_characters_and_rejects_251(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $description250 = str_repeat('A', 250);

        $this->actingAs($admin)
            ->get(route('rm_reports.partial', $workorder->id))
            ->assertOk()
            ->assertSee('name="mod_repair_description" maxlength="250"', false);

        $created = $this->actingAs($admin)->postJson(route('rm_reports.store'), [
            'part_description' => 'Hydraulic Motor',
            'mod_repair' => 'Repair',
            'mod_repair_description' => $description250,
            'ident_method' => 'Nameplate',
            'workorder_id' => $workorder->id,
        ]);

        $created->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.description', $description250);

        $rmReport = RmReport::query()->findOrFail($created->json('data.id'));

        $this->actingAs($admin)->postJson(route('rm_reports.store'), [
            'part_description' => 'Hydraulic Motor',
            'mod_repair' => 'Repair',
            'mod_repair_description' => str_repeat('B', 251),
            'ident_method' => 'Nameplate',
            'workorder_id' => $workorder->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('mod_repair_description');

        $this->actingAs($admin)->putJson(route('rm_reports.updateRecord', $rmReport->id), [
            'part_description' => 'Hydraulic Motor',
            'mod_repair' => 'Repair',
            'mod_repair_description' => str_repeat('C', 251),
            'ident_method' => 'Nameplate',
            'workorder_id' => $workorder->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('mod_repair_description');

        $this->assertSame($description250, $rmReport->fresh()->description);
    }
}
