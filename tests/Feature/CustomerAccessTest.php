<?php

namespace Tests\Feature;

use App\Models\Customer;
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
}
