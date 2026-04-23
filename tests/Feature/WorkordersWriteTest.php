<?php

namespace Tests\Feature;

use App\Models\Workorder;
use App\Models\Unit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WorkordersWriteTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    /**
     * @group smoke
     */
    public function test_admin_can_create_regular_workorder(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $draftInstruction = $this->createDraftInstruction();
        $instruction = $this->createInstruction(['name' => 'Inspection ' . uniqid()]);
        $customer = $this->createCustomer();
        $unit = $this->createUnit();

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
            'is_draft' => 0,
        ]);

        $this->assertNotNull($draftInstruction->id);
    }

    public function test_draft_creation_assigns_generated_number_and_draft_flag(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $draftInstruction = $this->createDraftInstruction();
        $customer = $this->createCustomer();
        $unit = $this->createUnit();

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
        $this->assertSame('After update', $workorder->description);
    }
}
