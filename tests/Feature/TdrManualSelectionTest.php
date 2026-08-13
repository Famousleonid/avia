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

    public function test_all_roles_can_browse_all_manuals_while_part_edit_access_stays_scoped(): void
    {
        $permissionOwner = $this->createUserWithRole('Technician');
        $primaryManual = $this->createManual(['number' => 'CMM-PRIMARY']);
        $openManual = $this->createManual(['number' => 'CMM-OPEN']);
        $restrictedManual = $this->createManual(['number' => 'CMM-RESTRICTED']);
        $restrictedManual->permittedUsers()->attach($permissionOwner->id);
        $unit = $this->createUnit(['manual_id' => $primaryManual->id]);

        foreach (['Technician', 'Team Leader', 'Manager', 'Admin'] as $role) {
            $user = $this->createUserWithRole($role);
            $workorder = $this->createWorkorder([
                'user_id' => $user->id,
                'unit_id' => $unit->id,
            ]);

            $response = $this->actingAs($user)->get(route('tdrs.show', $workorder->id));

            $response->assertOk();
            $manualIds = $response->viewData('manuals')->pluck('id')->all();
            $allowedManualIds = $response->viewData('allowedManualIds');

            $this->assertContains($primaryManual->id, $manualIds, $role);
            $this->assertContains($openManual->id, $manualIds, $role);
            $this->assertContains($restrictedManual->id, $manualIds, $role);
            $this->assertContains($primaryManual->id, $allowedManualIds, $role);
            $this->assertContains($openManual->id, $allowedManualIds, $role);

            if ($role === 'Admin') {
                $this->assertContains($restrictedManual->id, $allowedManualIds, $role);
            } else {
                $this->assertNotContains($restrictedManual->id, $allowedManualIds, $role);
            }

            $response->assertSee('CMM-OPEN', false);
            $response->assertSee('CMM-RESTRICTED', false);
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
}
