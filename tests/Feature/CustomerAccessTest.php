<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plane;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class CustomerAccessTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_manager_can_open_customers_directory_from_library_menu(): void
    {
        $manager = $this->createUserWithRole('Manager');
        Customer::query()->create(['name' => 'Manager Visible Customer']);

        $response = $this->actingAs($manager)->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Customers');
        $response->assertSee('Manager Visible Customer');
        $response->assertSee(route('customers.index'), false);
        $response->assertDontSee('data-bs-target="#createModal"', false);
    }

    public function test_only_admin_can_create_customers(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $admin = $this->createUserWithRole('Admin');

        $this->actingAs($manager)
            ->postJson(route('customers.store'), ['name' => 'Manager New Customer'])
            ->assertForbidden();

        $this->assertDatabaseMissing('customers', ['name' => 'Manager New Customer']);

        $this->actingAs($admin)
            ->postJson(route('customers.store'), ['name' => 'Admin New Customer'])
            ->assertCreated()
            ->assertJsonPath('name', 'Admin New Customer');

        $this->assertDatabaseHas('customers', ['name' => 'Admin New Customer']);
    }

    public function test_customer_delete_action_is_visible_only_for_system_admin(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $systemAdmin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $customer = Customer::query()->create(['name' => 'Delete Visibility Customer']);

        $this->actingAs($roleOnlyAdmin)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Delete Visibility Customer')
            ->assertDontSee('data-bs-target="#deleteModal"', false)
            ->assertDontSee('id="deleteModal"', false);

        $this->actingAs($systemAdmin)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Delete Visibility Customer')
            ->assertSee('data-bs-target="#deleteModal"', false)
            ->assertSee('id="deleteModal"', false);
    }

    public function test_only_system_admin_can_delete_customers(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $systemAdmin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $blockedCustomer = Customer::query()->create(['name' => 'Blocked Delete Customer']);
        $deletedCustomer = Customer::query()->create(['name' => 'Allowed Delete Customer']);

        $this->actingAs($roleOnlyAdmin)
            ->delete(route('customers.destroy', $blockedCustomer))
            ->assertForbidden();

        $this->assertDatabaseHas('customers', [
            'id' => $blockedCustomer->id,
            'deleted_at' => null,
        ]);

        $this->actingAs($systemAdmin)
            ->delete(route('customers.destroy', $deletedCustomer))
            ->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted('customers', ['id' => $deletedCustomer->id]);
    }

    public function test_workorder_create_customer_plus_is_admin_only(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $admin = $this->createUserWithRole('Admin');

        $this->actingAs($manager)
            ->get(route('workorders.create'))
            ->assertOk()
            ->assertDontSee('id="new_customer_create"', false)
            ->assertDontSee('id="addCustomerModal"', false);

        $this->actingAs($admin)
            ->get(route('workorders.create'))
            ->assertOk()
            ->assertSee('id="new_customer_create"', false)
            ->assertSee('id="addCustomerModal"', false);
    }

    public function test_manager_can_delete_planes_directory_items(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $plane = Plane::query()->create(['type' => 'Manager Delete Plane ' . uniqid()]);

        $response = $this->actingAs($manager)->get(route('planes.index'));

        $response->assertOk();
        $response->assertSee('Manager Delete Plane');
        $response->assertSee('data-bs-target="#dirDeleteModal"', false);

        $deleteResponse = $this->actingAs($manager)->delete(route('planes.destroy', $plane->id));

        $deleteResponse->assertRedirect(route('planes.index'));
        $this->assertDatabaseMissing('planes', ['id' => $plane->id]);
    }

    public function test_manager_still_cannot_delete_other_directory_items(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $builder = $this->createBuilder();

        $response = $this->actingAs($manager)->delete(route('builders.destroy', $builder->id));

        $response->assertForbidden();
        $this->assertDatabaseHas('builders', ['id' => $builder->id]);
    }
}
