<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MainWorkorderJumpTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_main_header_renders_inline_workorder_jump_controls_for_technician(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder([
            'user_id' => $technician->id,
            'number' => 107884,
        ]);

        $response = $this->actingAs($technician)->get(route('mains.show', $workorder));

        $response->assertOk();
        $response->assertSee('data-main-workorder-jump', false);
        $response->assertSee('data-main-workorder-jump-trigger', false);
        $response->assertSee('data-main-workorder-jump-input', false);
        $response->assertSee(
            'data-resolve-url="' . route('mains.resolve-workorder-number') . '"',
            false
        );
        $response->assertSee('>w 107884</button>', false);
    }

    public function test_technician_can_resolve_an_exact_workorder_number_for_mains(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder([
            'user_id' => $technician->id,
            'number' => 107891,
        ]);

        $response = $this->actingAs($technician)->getJson(route('mains.resolve-workorder-number', [
            'number' => 'WO 107891',
        ]));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('url', route('mains.show', $workorder))
            ->assertJsonPath('workorder.number', '107891');
    }

    public function test_workorder_jump_requires_the_complete_number(): void
    {
        $user = $this->createUserWithRole('Manager');
        $this->createWorkorder([
            'user_id' => $user->id,
            'number' => 107891,
        ]);

        $response = $this->actingAs($user)->getJson(route('mains.resolve-workorder-number', [
            'number' => '7891',
        ]));

        $response->assertNotFound()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'Workorder not found.');
    }

    public function test_workorder_jump_rejects_an_invalid_number(): void
    {
        $user = $this->createUserWithRole('Technician');

        $response = $this->actingAs($user)->getJson(route('mains.resolve-workorder-number', [
            'number' => 'not-a-wo',
        ]));

        $response->assertUnprocessable()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'Enter a valid workorder number.');
    }
}
