<?php

namespace Tests\Feature;

use App\Models\Workorder;
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
