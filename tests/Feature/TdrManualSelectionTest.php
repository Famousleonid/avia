<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualIplBranchRule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TdrManualSelectionTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_all_roles_can_browse_only_workorder_manuals_while_part_edit_access_stays_scoped(): void
    {
        $permissionOwner = $this->createUserWithRole('Technician');
        $primaryManual = $this->createManual(['number' => 'CMM-PRIMARY']);
        $openManual = $this->createManual(['number' => 'CMM-ADDITIONAL-OPEN']);
        $restrictedManual = $this->createManual(['number' => 'CMM-ADDITIONAL-RESTRICTED']);
        $notUsedManual = $this->createManual(['number' => 'CMM-ADDITIONAL-NOT-USED']);
        $unassignedManual = $this->createManual(['number' => 'CMM-UNASSIGNED']);
        $restrictedManual->permittedUsers()->attach($permissionOwner->id);
        $unit = $this->createUnit([
            'manual_id' => $primaryManual->id,
            'additional_manual_ids' => [
                $openManual->id,
                $restrictedManual->id,
                $notUsedManual->id,
            ],
        ]);

        foreach (['Technician', 'Team Leader', 'Manager', 'Admin'] as $role) {
            $user = $this->createUserWithRole($role);
            $workorder = $this->createWorkorder([
                'user_id' => $user->id,
                'unit_id' => $unit->id,
            ]);
            $workorder->update(['not_used_manual_ids' => [$notUsedManual->id]]);

            $response = $this->actingAs($user)->get(route('tdrs.show', $workorder->id));

            $response->assertOk();
            $manualIds = $response->viewData('manuals')->pluck('id')->all();
            $allowedManualIds = $response->viewData('allowedManualIds');

            $this->assertContains($primaryManual->id, $manualIds, $role);
            $this->assertContains($openManual->id, $manualIds, $role);
            $this->assertContains($restrictedManual->id, $manualIds, $role);
            $this->assertNotContains($notUsedManual->id, $manualIds, $role);
            $this->assertNotContains($unassignedManual->id, $manualIds, $role);
            $this->assertContains($primaryManual->id, $allowedManualIds, $role);
            $this->assertContains($openManual->id, $allowedManualIds, $role);

            if ($role === 'Admin') {
                $this->assertContains($restrictedManual->id, $allowedManualIds, $role);
            } else {
                $this->assertNotContains($restrictedManual->id, $allowedManualIds, $role);
            }

            $response->assertSee('CMM-ADDITIONAL-OPEN', false);
            $response->assertSee('CMM-ADDITIONAL-RESTRICTED', false);
            $response->assertDontSee('CMM-ADDITIONAL-NOT-USED', false);
            $response->assertDontSee('CMM-UNASSIGNED', false);
        }
    }

    public function test_component_picker_applies_branch_rules_from_selected_manual(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $primaryManual = $this->createManual(['number' => 'CMM-BRANCH-PRIMARY']);
        $selectedManual = $this->createManual(['number' => 'CMM-BRANCH-SELECTED']);
        $unit = $this->createUnit([
            'manual_id' => $primaryManual->id,
            'part_number' => 'BRANCH-UNIT',
            'additional_manual_ids' => [$selectedManual->id],
        ]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);

        ManualIplBranchRule::query()->create([
            'manual_id' => $primaryManual->id,
            'is_default' => true,
            'unit_match_value' => null,
            'include_prefix' => '9A-',
            'exclude_prefix' => '9-',
        ]);
        $selectedComponent = Component::query()->create([
            'manual_id' => $selectedManual->id,
            'part_number' => 'SECOND-MANUAL-PART',
            'name' => 'Second manual part',
            'ipl_num' => '9-10',
        ]);
        $primaryComponent = Component::query()->create([
            'manual_id' => $primaryManual->id,
            'part_number' => 'PRIMARY-MANUAL-PART',
            'name' => 'Primary manual part',
            'ipl_num' => '9A-20',
        ]);

        $response = $this->actingAs($admin)->getJson(route('api.get-components-by-manual', [
            'manual_id' => $selectedManual->id,
            'workorder_id' => $workorder->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('manual_id', $selectedManual->id);
        $components = collect($response->json('components'));
        $componentIds = $components->pluck('id')->all();

        $this->assertContains($selectedComponent->id, $componentIds);
        $this->assertNotContains($primaryComponent->id, $componentIds);
        $this->assertTrue($components->every(
            fn (array $component): bool => (int) $component['manual_id'] === (int) $selectedManual->id
        ));
    }

    public function test_component_picker_and_tdr_store_reject_unassigned_manual_components(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $primaryManual = $this->createManual(['number' => 'CMM-PRIMARY-SECURE']);
        $unassignedManual = $this->createManual(['number' => 'CMM-UNASSIGNED-SECURE']);
        $unit = $this->createUnit(['manual_id' => $primaryManual->id]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);
        $unassignedComponent = Component::query()->create([
            'manual_id' => $unassignedManual->id,
            'part_number' => 'UNASSIGNED-PART',
            'name' => 'Unassigned manual part',
            'ipl_num' => '9-10',
        ]);

        $this->actingAs($admin)->getJson(route('api.get-components-by-manual', [
            'manual_id' => $unassignedManual->id,
            'workorder_id' => $workorder->id,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('manual_id');

        $this->actingAs($admin)->postJson(route('tdrs.store'), [
            'workorder_id' => $workorder->id,
            'component_id' => $unassignedComponent->id,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('component_id');

        $this->assertDatabaseMissing('tdrs', [
            'workorder_id' => $workorder->id,
            'component_id' => $unassignedComponent->id,
        ]);
    }
}
