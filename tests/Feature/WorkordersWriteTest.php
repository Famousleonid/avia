<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Workorder;
use App\Models\Unit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WorkordersWriteTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /**
     * @group smoke
     */
    public function test_create_workorder_customer_select_is_alphabetical(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $this->createCustomer(['name' => 'ZZZ Sort Customer ' . uniqid()]);
        $this->createCustomer(['name' => 'AAA Sort Customer ' . uniqid()]);
        $this->createCustomer(['name' => 'MMM Sort Customer ' . uniqid()]);

        $response = $this->actingAs($admin)->get(route('workorders.create'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'AAA Sort Customer',
            'MMM Sort Customer',
            'ZZZ Sort Customer',
        ]);
    }

    public function test_open_date_placeholder_uses_neutral_mask_on_workorder_forms(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get(route('workorders.create'))
            ->assertOk()
            ->assertSee('placeholder=".... /.... /......"', false)
            ->assertDontSee('placeholder="10.aug.2026"', false)
            ->assertDontSee('placeholder="10/Aug/2026"', false);

        $this->actingAs($admin)
            ->get(route('workorders.edit', $workorder))
            ->assertOk()
            ->assertSee('placeholder=".... /.... /......"', false)
            ->assertDontSee('placeholder="10.aug.2026"', false)
            ->assertDontSee('placeholder="10/Aug/2026"', false);
    }

    public function test_admin_can_open_workorder_edit_when_unit_is_soft_deleted(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $unit = $this->createUnit([
            'part_number' => 'DELETED-UNIT-PN',
            'name' => 'Deleted Unit Name',
        ]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $unit->id,
        ]);
        $unit->delete();

        $this->actingAs($admin)
            ->get(route('workorders.edit', $workorder))
            ->assertOk()
            ->assertSee('DELETED-UNIT-PN')
            ->assertSee('Deleted unit');
    }

    public function test_admin_can_open_workorder_edit_when_customer_is_soft_deleted(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $customer = $this->createCustomer([
            'name' => 'Deleted Customer Name',
        ]);
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'customer_id' => $customer->id,
        ]);
        $customer->delete();

        $this->actingAs($admin)
            ->get(route('workorders.edit', $workorder))
            ->assertOk()
            ->assertSee('Deleted Customer Name');
    }

    public function test_mobile_draft_open_date_placeholder_uses_neutral_mask(): void
    {
        $shipper = $this->createUserWithRole('Shipping');

        $this->actingAs($shipper)
            ->get(route('mobile.draft'))
            ->assertOk()
            ->assertSee('placeholder=".... /.... /......"', false)
            ->assertDontSee('placeholder="10.aug.2026"', false)
            ->assertDontSee('placeholder="10/Aug/2026"', false);
    }

    /**
     * @group smoke
     */
    public function test_admin_can_create_regular_workorder(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $draftInstruction = $this->createDraftInstruction();
        $instruction = $this->createInstruction(['name' => 'Inspection ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit(['name' => 'Created Unit Name']);

        $response = $this->actingAs($admin)->post(route('workorders.store'), [
            'number' => 700001,
            'unit_id' => $unit->id,
            'customer_id' => $customer->id,
            'instruction_id' => $instruction->id,
            'user_id' => $admin->id,
            'description' => 'Created from test',
            'serial_number' => 'SN-REGULAR',
            'open_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('workorders.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('workorders', [
            'number' => 700001,
            'instruction_id' => $instruction->id,
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'description' => 'Created from test',
            'is_draft' => 0,
        ]);
        $this->assertSame('Created from test', $unit->fresh()->name);

        $this->assertNotNull($draftInstruction->id);
    }

    public function test_draft_creation_assigns_generated_number_and_draft_flag(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $draftInstruction = $this->createDraftInstruction();
        $customer = $this->createCustomer();
        $unit = $this->createUnit(['name' => 'Draft Unit Name']);

        $response = $this->actingAs($admin)->post(route('workorders.store'), [
            'unit_id' => $unit->id,
            'customer_id' => $customer->id,
            'instruction_id' => $draftInstruction->id,
            'user_id' => $admin->id,
            'description' => 'Draft from test',
        ]);

        $response->assertRedirect(route('workorders.index'));
        $response->assertSessionHasNoErrors();

        $workorder = Workorder::query()
            ->withoutGlobalScope('exclude_drafts')
            ->where('description', 'Draft from test')
            ->latest('id')
            ->first();

        $this->assertNotNull($workorder);
        $this->assertTrue((bool) $workorder->is_draft);
        $this->assertLessThan(100000, (int) $workorder->number);
        $this->assertSame('Draft from test', $unit->fresh()->name);
    }

    public function test_mobile_can_create_pending_unit_for_draft_without_manual(): void
    {
        $shipper = $this->createUserWithRole('Admin');

        $response = $this->actingAs($shipper)->postJson(route('mobile.draft.units.pending.store'), [
            'part_number' => 'PENDING-' . uniqid(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('manual_id', null);
        $response->assertJsonPath('verified', true);

        $this->assertDatabaseHas('units', [
            'id' => $response->json('id'),
            'manual_id' => null,
            'verified' => 1,
        ]);
    }

    public function test_assign_manual_to_pending_unit_fills_empty_name_from_manual_title(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual(['title' => 'Auto Assigned CMM Title']);
        $pendingUnit = Unit::query()->create([
            'part_number' => 'PENDING-' . uniqid(),
            'manual_id' => null,
            'verified' => false,
            'name' => null,
            'description' => null,
        ]);

        $response = $this->actingAs($admin)
            ->patchJson(route('units.assignManual', $pendingUnit), [
                'manual_id' => $manual->id,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('manual_id', $manual->id);
        $response->assertJsonPath('manual_title', 'Auto Assigned CMM Title');
        $response->assertJsonPath('name', 'Auto Assigned CMM Title');

        $pendingUnit->refresh();
        $this->assertSame($manual->id, (int) $pendingUnit->manual_id);
        $this->assertTrue((bool) $pendingUnit->verified);
        $this->assertSame('Auto Assigned CMM Title', $pendingUnit->name);
    }

    public function test_draft_release_requires_unit_manual(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $draftInstruction = $this->createDraftInstruction();
        $releasedInstruction = $this->createInstruction(['name' => 'Released ' . uniqid()]);
        $customer = $this->createCustomer();
        $pendingUnit = Unit::query()->create([
            'part_number' => 'PENDING-' . uniqid(),
            'manual_id' => null,
            'verified' => false,
        ]);
        $workorder = $this->createWorkorder([
            'number' => 123,
            'instruction_id' => $draftInstruction->id,
            'customer_id' => $customer->id,
            'unit_id' => $pendingUnit->id,
            'user_id' => $admin->id,
            'is_draft' => true,
        ]);

        $response = $this->from(route('workorders.edit', $workorder))
            ->actingAs($admin)
            ->put(route('workorders.update', $workorder), [
                'number' => 700123,
                'unit_id' => $pendingUnit->id,
                'customer_id' => $customer->id,
                'instruction_id' => $releasedInstruction->id,
                'user_id' => $admin->id,
            ]);

        $response->assertRedirect(route('workorders.edit', $workorder));
        $response->assertSessionHasErrors(['unit_id']);

        $workorder->refresh();
        $this->assertTrue((bool) $workorder->is_draft);
        $this->assertSame(123, (int) $workorder->number);
    }

    public function test_draft_release_uses_manual_title_when_description_is_empty(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $draftInstruction = $this->createDraftInstruction();
        $releasedInstruction = $this->createInstruction(['name' => 'Released ' . uniqid()]);
        $customer = $this->createCustomer();
        $manual = $this->createManual(['title' => 'Release Fallback CMM Title']);
        $unit = Unit::query()->create([
            'part_number' => 'PENDING-' . uniqid(),
            'manual_id' => $manual->id,
            'verified' => true,
            'name' => null,
            'description' => null,
        ]);
        $draft = $this->createWorkorder([
            'number' => 124,
            'draft_number' => 124,
            'instruction_id' => $draftInstruction->id,
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'description' => null,
            'is_draft' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('workorders.update', $draft), [
                'number' => 700124,
                'unit_id' => $unit->id,
                'customer_id' => $customer->id,
                'instruction_id' => $releasedInstruction->id,
                'user_id' => $admin->id,
                'description' => '',
            ])
            ->assertRedirect(route('workorders.index'))
            ->assertSessionHasNoErrors();

        $draft->refresh();
        $unit->refresh();

        $this->assertFalse((bool) $draft->is_draft);
        $this->assertSame(700124, (int) $draft->number);
        $this->assertSame('Release Fallback CMM Title', $unit->name);
    }

    public function test_released_draft_keeps_draft_number_and_next_draft_continues_sequence(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $draftInstruction = $this->createDraftInstruction();
        $releasedInstruction = $this->createInstruction(['name' => 'Released ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit(['name' => 'Next Draft Unit Name']);
        $draft = $this->createWorkorder([
            'number' => 7,
            'draft_number' => 7,
            'instruction_id' => $draftInstruction->id,
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'is_draft' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('workorders.update', $draft), [
                'number' => 100900,
                'unit_id' => $unit->id,
                'customer_id' => $customer->id,
                'instruction_id' => $releasedInstruction->id,
                'user_id' => $admin->id,
            ])
            ->assertRedirect(route('workorders.index'));

        $draft->refresh();
        $this->assertFalse((bool) $draft->is_draft);
        $this->assertSame(100900, (int) $draft->number);
        $this->assertSame(7, (int) $draft->draft_number);

        $response = $this->actingAs($admin)->post(route('workorders.store'), [
            'unit_id' => $unit->id,
            'customer_id' => $customer->id,
            'instruction_id' => $draftInstruction->id,
            'user_id' => $admin->id,
            'description' => 'Next draft after release',
        ]);

        $response->assertRedirect(route('workorders.index'));
        $response->assertSessionHasNoErrors();

        $nextDraft = Workorder::query()
            ->withoutGlobalScope('exclude_drafts')
            ->where('description', 'Next draft after release')
            ->firstOrFail();

        $this->assertTrue((bool) $nextDraft->is_draft);
        $this->assertSame(8, (int) $nextDraft->number);
        $this->assertSame(8, (int) $nextDraft->draft_number);
        $this->assertSame('Next draft after release', $unit->fresh()->name);
    }

    public function test_regular_creation_rejects_non_integer_number(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->createDraftInstruction();
        $instruction = $this->createInstruction(['name' => 'Repair ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();

        $response = $this->from(route('workorders.create'))
            ->actingAs($admin)
            ->post(route('workorders.store'), [
                'number' => '190-70500-405',
                'unit_id' => $unit->id,
                'customer_id' => $customer->id,
                'instruction_id' => $instruction->id,
                'user_id' => $admin->id,
            ]);

        $response->assertRedirect(route('workorders.create'));
        $response->assertSessionHasErrors(['number']);
    }

    public function test_regular_creation_rejects_number_larger_than_six_digits(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->createDraftInstruction();
        $instruction = $this->createInstruction(['name' => 'Repair ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();

        $response = $this->from(route('workorders.create'))
            ->actingAs($admin)
            ->post(route('workorders.store'), [
                'number' => '1000000',
                'unit_id' => $unit->id,
                'customer_id' => $customer->id,
                'instruction_id' => $instruction->id,
                'user_id' => $admin->id,
            ]);

        $response->assertRedirect(route('workorders.create'));
        $response->assertSessionHasErrors(['number']);
    }

    public function test_regular_creation_rejects_number_less_than_six_digits(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->createDraftInstruction();
        $instruction = $this->createInstruction(['name' => 'Repair ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();

        $response = $this->from(route('workorders.create'))
            ->actingAs($admin)
            ->post(route('workorders.store'), [
                'number' => '99999',
                'unit_id' => $unit->id,
                'customer_id' => $customer->id,
                'instruction_id' => $instruction->id,
                'user_id' => $admin->id,
            ]);

        $response->assertRedirect(route('workorders.create'));
        $response->assertSessionHasErrors(['number']);
    }

    public function test_regular_creation_accepts_project_open_date_format(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->createDraftInstruction();
        $instruction = $this->createInstruction(['name' => 'Repair ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();

        $response = $this->actingAs($admin)->post(route('workorders.store'), [
            'number' => 700002,
            'unit_id' => $unit->id,
            'customer_id' => $customer->id,
            'instruction_id' => $instruction->id,
            'user_id' => $admin->id,
            'open_at' => '10.aug.2026',
        ]);

        $response->assertRedirect(route('workorders.index'));
        $response->assertSessionHasNoErrors();

        $workorder = Workorder::query()
            ->withoutGlobalScope('exclude_drafts')
            ->where('number', 700002)
            ->firstOrFail();

        $this->assertSame('2026-08-10', $workorder->open_at->format('Y-m-d'));
    }

    public function test_draft_release_rejects_number_larger_than_six_digits(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $draftInstruction = $this->createDraftInstruction();
        $releasedInstruction = $this->createInstruction(['name' => 'Released ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();
        $workorder = $this->createWorkorder([
            'number' => 124,
            'instruction_id' => $draftInstruction->id,
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'is_draft' => true,
        ]);

        $response = $this->from(route('workorders.edit', $workorder))
            ->actingAs($admin)
            ->put(route('workorders.update', $workorder), [
                'number' => '1000000',
                'unit_id' => $unit->id,
                'customer_id' => $customer->id,
                'instruction_id' => $releasedInstruction->id,
                'user_id' => $admin->id,
            ]);

        $response->assertRedirect(route('workorders.edit', $workorder));
        $response->assertSessionHasErrors(['number']);

        $workorder->refresh();
        $this->assertTrue((bool) $workorder->is_draft);
        $this->assertSame(124, (int) $workorder->number);
    }

    public function test_draft_release_rejects_number_less_than_six_digits(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $draftInstruction = $this->createDraftInstruction();
        $releasedInstruction = $this->createInstruction(['name' => 'Released ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();
        $workorder = $this->createWorkorder([
            'number' => 125,
            'instruction_id' => $draftInstruction->id,
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'is_draft' => true,
        ]);

        $response = $this->from(route('workorders.edit', $workorder))
            ->actingAs($admin)
            ->put(route('workorders.update', $workorder), [
                'number' => '99999',
                'unit_id' => $unit->id,
                'customer_id' => $customer->id,
                'instruction_id' => $releasedInstruction->id,
                'user_id' => $admin->id,
            ]);

        $response->assertRedirect(route('workorders.edit', $workorder));
        $response->assertSessionHasErrors(['number']);

        $workorder->refresh();
        $this->assertTrue((bool) $workorder->is_draft);
        $this->assertSame(125, (int) $workorder->number);
    }

    public function test_non_draft_update_keeps_original_number_even_if_new_number_is_posted(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $instruction = $this->createInstruction(['name' => 'Repair ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();
        $workorder = $this->createWorkorder([
            'number' => 800001,
            'instruction_id' => $instruction->id,
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'user_id' => $admin->id,
            'description' => 'Before update',
            'is_draft' => false,
        ]);

        $response = $this->actingAs($admin)->put(route('workorders.update', $workorder), [
            'number' => 900001,
            'unit_id' => $unit->id,
            'customer_id' => $customer->id,
            'instruction_id' => $instruction->id,
            'user_id' => $admin->id,
            'description' => 'After update',
            'serial_number' => 'SN-UPD',
            'open_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('workorders.index'));
        $response->assertSessionHasNoErrors();

        $workorder->refresh();

        $this->assertSame(800001, (int) $workorder->number);
        $this->assertSame('Before update', $workorder->description);
        $this->assertSame('After update', $unit->fresh()->name);
        $this->assertSame('After update', $workorder->fresh(['unit'])->displayDescription());
    }

    public function test_manager_can_change_workorder_technik_on_edit(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $originalTechnik = $this->createUserWithRole('Technician');
        $newTechnik = $this->createUserWithRole('Technician', ['email' => 'new.tech.' . uniqid() . '@example.test']);
        $instruction = $this->createInstruction(['name' => 'Repair ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();
        $workorder = $this->createWorkorder([
            'number' => 800101,
            'instruction_id' => $instruction->id,
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'user_id' => $originalTechnik->id,
            'is_draft' => false,
        ]);

        $response = $this->actingAs($manager)->put(route('workorders.update', $workorder), [
            'unit_id' => $unit->id,
            'customer_id' => $customer->id,
            'instruction_id' => $instruction->id,
            'user_id' => $newTechnik->id,
            'open_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('workorders.index'));
        $response->assertSessionHasNoErrors();

        $this->assertSame($newTechnik->id, $workorder->fresh()->user_id);
    }

    public function test_technician_cannot_open_or_update_workorder_edit_screen(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $originalTechnik = $this->createUserWithRole('Technician', ['email' => 'original.tech.' . uniqid() . '@example.test']);
        $newTechnik = $this->createUserWithRole('Technician', ['email' => 'blocked.tech.' . uniqid() . '@example.test']);
        $instruction = $this->createInstruction(['name' => 'Repair ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();
        $workorder = $this->createWorkorder([
            'number' => 800102,
            'instruction_id' => $instruction->id,
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'user_id' => $originalTechnik->id,
            'is_draft' => false,
        ]);

        $this->actingAs($technician)
            ->get(route('workorders.edit', $workorder))
            ->assertForbidden();

        $response = $this->actingAs($technician)->put(route('workorders.update', $workorder), [
            'unit_id' => $unit->id,
            'customer_id' => $customer->id,
            'instruction_id' => $instruction->id,
            'user_id' => $newTechnik->id,
            'open_at' => now()->toDateString(),
        ]);

        $response->assertForbidden();

        $this->assertSame($originalTechnik->id, $workorder->fresh()->user_id);
    }

    public function test_team_leader_cannot_open_or_update_workorder_edit_screen(): void
    {
        $teamLeader = $this->createUserWithRole('Team Leader');
        $originalTechnik = $this->createUserWithRole('Technician', ['email' => 'original.tl.tech.' . uniqid() . '@example.test']);
        $newTechnik = $this->createUserWithRole('Technician', ['email' => 'blocked.tl.tech.' . uniqid() . '@example.test']);
        $instruction = $this->createInstruction(['name' => 'Repair ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();
        $workorder = $this->createWorkorder([
            'number' => 800103,
            'instruction_id' => $instruction->id,
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'user_id' => $originalTechnik->id,
            'is_draft' => false,
        ]);

        $this->actingAs($teamLeader)
            ->get(route('workorders.edit', $workorder))
            ->assertForbidden();

        $response = $this->actingAs($teamLeader)->put(route('workorders.update', $workorder), [
            'unit_id' => $unit->id,
            'customer_id' => $customer->id,
            'instruction_id' => $instruction->id,
            'user_id' => $newTechnik->id,
            'open_at' => now()->toDateString(),
        ]);

        $response->assertForbidden();

        $this->assertSame($originalTechnik->id, $workorder->fresh()->user_id);
    }

    public function test_mobile_storage_update_is_limited_to_shipping_manager_and_admin(): void
    {
        $workorder = $this->createWorkorder([
            'storage_rack' => null,
            'storage_level' => null,
            'storage_column' => null,
        ]);

        foreach (['Shipping', 'Manager', 'Admin'] as $role) {
            $user = $this->createUserWithRole($role, [
                'email' => strtolower($role) . '.storage.' . uniqid() . '@example.test',
            ]);

            $this->actingAs($user)
                ->patchJson(route('mobile.workorders.storage.update', $workorder), [
                    'storage_rack' => 11,
                    'storage_level' => 2,
                    'storage_column' => 3,
                ])
                ->assertOk()
                ->assertJsonPath('success', true);
        }

        $technician = $this->createUserWithRole('Technician');

        $this->actingAs($technician)
            ->patchJson(route('mobile.workorders.storage.update', $workorder), [
                'storage_rack' => 99,
                'storage_level' => 9,
                'storage_column' => 9,
            ])
            ->assertForbidden();

        $this->assertSame(11, (int) $workorder->fresh()->storage_rack);
    }

    public function test_mobile_arrival_box_update_is_limited_to_shipping_manager_and_admin(): void
    {
        $workorder = $this->createWorkorder([
            'arrival_box_status' => null,
            'arrival_box_notes' => null,
            'arrival_box_recorded_by' => null,
            'arrival_box_recorded_at' => null,
        ]);

        foreach (['Shipping', 'Manager', 'Admin'] as $role) {
            $user = $this->createUserWithRole($role, [
                'email' => strtolower($role) . '.arrival.box.' . uniqid() . '@example.test',
            ]);

            $this->actingAs($user)
                ->patchJson(route('mobile.workorders.arrival-box.update', $workorder), [
                    'arrival_box_status' => 'medium',
                    'arrival_box_notes' => 'Middle rail cracked',
                ])
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('arrival_box.status', 'medium')
                ->assertJsonPath('arrival_box.status_label', 'Medium repair')
                ->assertJsonPath('arrival_box.notes', 'Middle rail cracked');

            $this->assertDatabaseHas('workorders', [
                'id' => $workorder->id,
                'arrival_box_status' => 'medium',
                'arrival_box_notes' => 'Middle rail cracked',
                'arrival_box_recorded_by' => $user->id,
            ]);
            $this->assertNotNull($workorder->fresh()->arrival_box_recorded_at);
        }

        $technician = $this->createUserWithRole('Technician');

        $this->actingAs($technician)
            ->patchJson(route('mobile.workorders.arrival-box.update', $workorder), [
                'arrival_box_status' => 'hard',
                'arrival_box_notes' => 'Blocked',
            ])
            ->assertForbidden();

        $this->assertSame('medium', $workorder->fresh()->arrival_box_status);
    }

    public function test_mobile_arrival_box_block_is_visible_only_to_shipping_manager_and_admin(): void
    {
        $workorder = $this->createWorkorder([
            'arrival_box_status' => 'medium',
            'arrival_box_notes' => 'Middle rail cracked',
        ]);

        foreach (['Shipping', 'Manager', 'Admin'] as $role) {
            $user = $this->createUserWithRole($role, [
                'email' => strtolower($role) . '.arrival.box.visible.' . uniqid() . '@example.test',
            ]);

            $this->actingAs($user)
                ->get(route('mobile.show', $workorder))
                ->assertOk()
                ->assertSee('id="arrivalBoxStatusText_' . $workorder->id . '"', false);
        }

        $technician = $this->createUserWithRole('Technician');

        $this->actingAs($technician)
            ->get(route('mobile.show', $workorder))
            ->assertOk()
            ->assertDontSee('id="arrivalBoxStatusText_' . $workorder->id . '"', false);
    }

    public function test_mobile_arrival_box_can_be_filled_after_draft_creation(): void
    {
        $shipping = $this->createUserWithRole('Shipping');
        $draft = $this->createWorkorder([
            'user_id' => $shipping->id,
            'number' => 109001,
            'draft_number' => 109001,
            'is_draft' => true,
            'arrival_box_status' => null,
            'arrival_box_notes' => null,
        ]);

        $this->actingAs($shipping)
            ->get(route('mobile.show', $draft))
            ->assertOk()
            ->assertSee('Draft:');

        $this->actingAs($shipping)
            ->patchJson(route('mobile.workorders.arrival-box.update', $draft), [
                'arrival_box_status' => 'replace',
                'arrival_box_notes' => 'Use new box before shipping',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('arrival_box.status', 'replace')
            ->assertJsonPath('arrival_box.status_label', 'New box');

        $this->assertDatabaseHas('workorders', [
            'id' => $draft->id,
            'is_draft' => true,
            'arrival_box_status' => 'replace',
            'arrival_box_notes' => 'Use new box before shipping',
            'arrival_box_recorded_by' => $shipping->id,
        ]);
    }

    public function test_desktop_storage_endpoint_uses_same_role_limit(): void
    {
        $workorder = $this->createWorkorder([
            'storage_rack' => null,
            'storage_level' => null,
            'storage_column' => null,
        ]);
        $technician = $this->createUserWithRole('Technician');
        $shipping = $this->createUserWithRole('Shipping');

        $this->actingAs($technician)
            ->patchJson(route('workorders.storage.update', $workorder), [
                'storage_rack' => 99,
                'storage_level' => 9,
                'storage_column' => 9,
            ])
            ->assertForbidden();

        $this->actingAs($shipping)
            ->patchJson(route('workorders.storage.update', $workorder), [
                'storage_rack' => 4,
                'storage_level' => 5,
                'storage_column' => 6,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(4, (int) $workorder->fresh()->storage_rack);
    }
}
