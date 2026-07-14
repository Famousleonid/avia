<?php

namespace Tests;

use App\Models\Customer;
use App\Models\Instruction;
use App\Models\Manual;
use App\Models\Plane;
use App\Models\Role;
use App\Models\Builder;
use App\Models\Scope;
use App\Models\Team;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserFeatureAccess;
use App\Models\Workorder;
use Carbon\Carbon;

trait BuildsDomainData
{
    private int $workorderSequence = 100000;

    protected function createUserWithRole(string $roleName, array $attributes = []): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);
        $team = Team::query()->firstOrCreate(['name' => 'QA Team']);

        $defaults = [
            'name' => $roleName . ' User',
            'selection_name_order' => 'first_last',
            'email' => strtolower($roleName) . '.' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role_id' => $role->id,
            'team_id' => $team->id,
            'is_admin' => $roleName === 'Admin',
            'stamp' => 'QA',
        ];

        $user = User::factory()->create(array_merge($defaults, $attributes));
        $this->grantFeatureAccessForLegacyTestAttributes($user, $attributes, $roleName);

        return $user;
    }

    protected function grantFeatureAccess(User $user, string $featureKey, ?User $grantedBy = null): void
    {
        UserFeatureAccess::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'feature_key' => $featureKey,
            ],
            [
                'granted_by_user_id' => $grantedBy?->id,
            ]
        );

        $user->unsetRelation('featureAccesses');
    }

    private function grantFeatureAccessForLegacyTestAttributes(User $user, array $attributes, string $roleName): void
    {
        if (
            ((bool) $user->is_admin && $roleName === 'Admin')
            || (($attributes['qa_access'] ?? false) === true && in_array($roleName, ['Admin', 'Manager'], true))
        ) {
            $this->grantFeatureAccess($user, 'quality_assurance');
        }

        if ((bool) $user->is_admin || ($attributes['ec_access'] ?? false) === true) {
            $this->grantFeatureAccess($user, 'ec');
        }

        if (($attributes['can_sign_certificates'] ?? false) === true) {
            $this->grantFeatureAccess($user, 'certificates.sign');
        }

        if (($attributes['can_manage_locked_manual_processes'] ?? false) === true) {
            $this->grantFeatureAccess($user, 'manuals.locked_processes');
        }

        if (($attributes['can_manage_locked_manual_parts'] ?? false) === true) {
            $this->grantFeatureAccess($user, 'manuals.locked_parts');
        }

        if ((bool) data_get($attributes, 'notification_prefs.manuals_full_access', false)) {
            $this->grantFeatureAccess($user, 'manuals.full');
        }

        if (in_array($roleName, ['Admin', 'Manager'], true)) {
            $this->grantFeatureAccess($user, 'vendor_tracking');
        }
    }

    protected function createCustomer(array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Customer ' . uniqid(),
        ], $attributes));
    }

    protected function createInstruction(array $attributes = []): Instruction
    {
        return Instruction::query()->create(array_merge([
            'name' => 'Instruction ' . uniqid(),
        ], $attributes));
    }

    protected function createDraftInstruction(): Instruction
    {
        return Instruction::query()->firstOrCreate(['name' => 'Draft']);
    }

    protected function createOverhaulInstruction(): Instruction
    {
        return Instruction::query()->firstOrCreate(['name' => 'Overhaul']);
    }

    protected function createManual(array $attributes = []): Manual
    {
        return Manual::query()->create(array_merge([
            'number' => 'MAN-' . uniqid(),
            'title' => 'QA Manual',
            'lib' => 'LIB-' . random_int(100, 999),
            'revision_date' => Carbon::now()->toDateString(),
            'planes_id' => $attributes['planes_id'] ?? $this->createPlane()->id,
            'builders_id' => $attributes['builders_id'] ?? $this->createBuilder()->id,
            'scopes_id' => $attributes['scopes_id'] ?? $this->createScope()->id,
        ], $attributes));
    }

    protected function createPlane(array $attributes = []): Plane
    {
        return Plane::query()->create(array_merge([
            'type' => 'Plane ' . uniqid(),
        ], $attributes));
    }

    protected function createBuilder(array $attributes = []): Builder
    {
        return Builder::query()->create(array_merge([
            'name' => 'Builder ' . uniqid(),
        ], $attributes));
    }

    protected function createScope(array $attributes = []): Scope
    {
        return Scope::query()->create(array_merge([
            'scope' => 'Scope ' . uniqid(),
        ], $attributes));
    }

    protected function createUnit(array $attributes = []): Unit
    {
        return Unit::query()->create(array_merge([
            'part_number' => 'UNIT-' . uniqid(),
            'verified' => true,
            'manual_id' => $attributes['manual_id'] ?? $this->createManual()->id,
            'name' => 'QA Unit',
            'description' => 'QA Description',
            'eff_code' => 'ALL',
        ], $attributes));
    }

    protected function createWorkorder(array $attributes = []): Workorder
    {
        $userId = $attributes['user_id'] ?? $this->createUserWithRole('Admin')->id;
        $unitId = $attributes['unit_id'] ?? $this->createUnit()->id;
        $customerId = $attributes['customer_id'] ?? $this->createCustomer()->id;
        $instructionId = $attributes['instruction_id'] ?? $this->createInstruction()->id;

        return Workorder::query()->withoutGlobalScope('exclude_drafts')->create(array_merge([
            'number' => $this->workorderSequence++,
            'user_id' => $userId,
            'unit_id' => $unitId,
            'customer_id' => $customerId,
            'instruction_id' => $instructionId,
            'approve_at' => null,
            'approve_name' => null,
            'serial_number' => 'SN-' . uniqid(),
            'description' => 'QA Workorder',
            'customer_po' => 'PO-' . uniqid(),
            'amdt' => '0',
            'place' => 'QA',
            'open_at' => now(),
            'is_draft' => false,
            'external_damage' => false,
            'received_disassembly' => false,
            'nameplate_missing' => false,
            'preliminary_test_false' => false,
            'part_missing' => false,
            'new_parts' => false,
            'extra_parts' => false,
            'disassembly_upon_arrival' => false,
        ], $attributes));
    }

    // ---- Fits & Clearances / Torque / Measurements fixtures ----

    protected function createInspectionComponent(Manual $manual, string $label = 'IC', array $attributes = []): \App\Models\ManualInspectionComponent
    {
        return \App\Models\ManualInspectionComponent::query()->create(array_merge([
            'manual_id' => $manual->id,
            'label' => $label . ' ' . uniqid(),
            'sort_order' => 0,
        ], $attributes));
    }

    protected function createComponent(Manual $manual, array $attributes = []): \App\Models\Component
    {
        return \App\Models\Component::query()->create(array_merge([
            'manual_id' => $manual->id,
            'part_number' => 'PN-' . uniqid(),
            'name' => 'Comp',
            'ipl_num' => 'IPL-' . random_int(1, 99999),
            'is_bush' => false,
        ], $attributes));
    }

    /** Attach a component to an inspection component as its variant. */
    protected function attachComponentToIc(\App\Models\ManualInspectionComponent $ic, \App\Models\Component $component): \App\Models\ManualInspectionComponentVariant
    {
        return \App\Models\ManualInspectionComponentVariant::query()->create([
            'inspection_component_id' => $ic->id,
            'component_id' => $component->id,
        ]);
    }

    protected function createParameter(Manual $manual, ?\App\Models\ManualInspectionComponent $ic = null, array $attributes = []): \App\Models\ManualParameter
    {
        return \App\Models\ManualParameter::query()->create(array_merge([
            'manual_id' => $manual->id,
            'inspection_component_id' => $ic?->id,
            'description' => 'OD',
            'sort_order' => 0,
        ], $attributes));
    }

    /** Creates a figure + one point on it; returns the point. */
    protected function createDimensionPoint(Manual $manual, string $code = 'P', bool $isFc = false, array $attributes = []): \App\Models\ManualDimensionPoint
    {
        $figure = \App\Models\ManualDimensionFigure::query()->create([
            'manual_id' => $manual->id,
            'figure_type' => 'detail',
            'title' => 'Fig ' . uniqid(),
            'image_path' => 'test/fig.png',
            'image_width' => 1000,
            'image_height' => 800,
            'sort_order' => 0,
        ]);

        return \App\Models\ManualDimensionPoint::query()->create(array_merge([
            'manual_dimension_figure_id' => $figure->id,
            'point_type' => 'measurement',
            'code' => $code,
            'is_fits_clearance' => $isFc,
            'x_pct' => 10,
            'y_pct' => 10,
            'sort_order' => 0,
        ], $attributes));
    }

    protected function attachParamToPoint(\App\Models\ManualParameter $param, \App\Models\ManualDimensionPoint $point): void
    {
        $param->points()->syncWithoutDetaching([$point->id]);
    }

    protected function createFit(Manual $manual, \App\Models\ManualParameter $od, \App\Models\ManualParameter $id, array $attributes = []): \App\Models\ManualFit
    {
        return \App\Models\ManualFit::query()->create(array_merge([
            'manual_id' => $manual->id,
            'od_param_id' => $od->id,
            'id_param_id' => $id->id,
            'is_fc' => true,
            'sort_order' => 0,
        ], $attributes));
    }

    protected function createMeasurement(Workorder $workorder, \App\Models\ManualParameter $param, array $attributes = []): \App\Models\WoMeasurement
    {
        return \App\Models\WoMeasurement::query()->create(array_merge([
            'workorder_id' => $workorder->id,
            'manual_parameter_id' => $param->id,
            'stage' => 'final',
        ], $attributes));
    }
}
