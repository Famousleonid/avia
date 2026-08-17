<?php

namespace Tests\Feature;

use Tests\BuildsDomainData;
use Tests\TestCase;

class MainToolsVisibilityTest extends TestCase
{
    use BuildsDomainData;

    public function test_tools_button_is_visible_only_to_admin_in_main(): void
    {
        $workorder = $this->createWorkorder();
        $admin = $this->createUserWithRole('Admin');
        $technician = $this->createUserWithRole('Technician');

        $this->actingAs($admin)
            ->get(route('mains.show', $workorder))
            ->assertOk()
            ->assertSee('data-tippy-content="Tools"', false);

        $this->actingAs($technician)
            ->get(route('mains.show', $workorder))
            ->assertOk()
            ->assertDontSee('data-tippy-content="Tools"', false);
    }
}
