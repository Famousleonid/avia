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
            'email' => strtolower($roleName) . '.' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role_id' => $role->id,
            'team_id' => $team->id,
            'is_admin' => $roleName === 'Admin',
            'stamp' => 'QA',
        ];

        return User::factory()->create(array_merge($defaults, $attributes));
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
}
