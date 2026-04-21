<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_technician_can_open_techniks_page(): void
    {
        $technician = $this->createUserWithRole('Technician');

        $response = $this->actingAs($technician)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Techniks');
    }
}
