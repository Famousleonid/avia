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
