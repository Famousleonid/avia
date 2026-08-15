<?php

namespace Tests\Feature;

use Tests\BuildsDomainData;
use Tests\TestCase;

class UserGuideTest extends TestCase
{
    use BuildsDomainData;

    public function test_admin_can_open_the_user_guide(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->trainingWorkorderFor($this->createUserWithRole('Technician'));

        $response = $this->actingAs($admin)->get(route('admin.user-guide'));

        $response->assertOk();
        $response->assertSee('data-language="en"', false);
        $response->assertSee('data-language="ru"', false);
        $response->assertSee('data-language="uk"', false);
        $response->assertSee('data-language="he"', false);
        $response->assertSee('data-language="de"', false);
        $response->assertSee('data-language="kk"', false);
        $response->assertSee('data-language="be"', false);
        $response->assertSee('data-toc-toggle', false);
        $response->assertSee('data-open-all', false);
        $response->assertSee('data-guide-previous', false);
        $response->assertSee('data-guide-next', false);
        $response->assertSee('window.requestAnimationFrame', false);
        $response->assertSee('const duration = 260', false);
        $response->assertSee("window.UserUiSettings.get(scope, 'open-sections'", false);
        $response->assertDontSee('guide-help', false);
        $response->assertSee('1.1 Sign in');
        $response->assertDontSee('class="guide-intro"', false);
        $response->assertSee('sectionObserver', false);
        $response->assertSee("'toc-open-all'", false);
        $response->assertSee('img/user-guide/technician-login.png', false);
        $response->assertSee('img/user-guide/technician-cabinet.png', false);
        $response->assertSee('guide-workorder-cursor', false);
        $response->assertSee('guideCursorToWorkorder 4s infinite', false);
        $response->assertSee('guide-workorders-demo-html', false);
        $response->assertSee('guide-html-row is-active', false);
        $response->assertSee("'workorder-interactive-state'", false);
        $response->assertSee('107870', false);
        $response->assertSee('filterMineActiveFilter', false);
        $response->assertSee('data-workorders-stage', false);
        $response->assertDontSee('data-animation-toggle', false);
        $response->assertDontSee('startInteractiveWorkorders', false);
        $response->assertSee('Open workorder ${number.textContent.trim()}', false);
        $response->assertSee("target.closest('.guide-html-number')", false);
        $response->assertSee("scrollToGuideSection(mainSection)", false);
        $response->assertSee('outline:1px solid #ffd54a', false);
        $response->assertSee("'last-section'", false);
        $response->assertSee("'scroll-top'", false);
        $response->assertSee('workorder-main', false);
        $response->assertSee('data-main-demo', false);
        $response->assertSee('guide-main-demo-tab', false);
        $response->assertSee('data-main-demo-open="photos"', false);
        $response->assertSee("'main-demo-draft'", false);
        $response->assertSee('Parts &amp; Repair Processes', false);
        $response->assertSee('img/user-guide/technician-training.png', false);
        $response->assertSee('img/user-guide/technician-technicians.png', false);
        $response->assertSee('img/user-guide/technician-materials.png', false);
        $response->assertSee('img/user-guide/technician-mobile-workorders.png', false);
        $response->assertSee('img/user-guide/technician-mobile-workorder.png', false);
        $response->assertSee('img/user-guide/technician-mobile-photos.png', false);

        $this->actingAs($admin)
            ->get(route('admin.user-guide.workorder-main'))
            ->assertOk()
            ->assertSee('100000')
            ->assertDontSee('id="sidebarColumn"', false)
            ->assertDontSee('id="notifSettingsModal"', false);
    }

    public function test_every_technician_can_open_the_live_training_workorder_regardless_of_assignment(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $assignedTechnician = $this->createUserWithRole('Technician');
        $this->trainingWorkorderFor($assignedTechnician);

        $this->actingAs($technician)
            ->get(route('admin.user-guide'))
            ->assertOk();

        $this->actingAs($technician)
            ->get(route('admin.user-guide.workorder-main'))
            ->assertOk()
            ->assertSee('100000');

        $this->actingAs($assignedTechnician)
            ->get(route('admin.user-guide.workorder-main'))
            ->assertOk()
            ->assertSee('100000');
    }

    public function test_manager_can_open_the_user_guide_and_training_main(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $this->trainingWorkorderFor($this->createUserWithRole('Technician'));

        $this->actingAs($manager)
            ->get(route('admin.user-guide'))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('admin.user-guide.workorder-main'))
            ->assertOk()
            ->assertSee('100000');
    }

    public function test_team_leader_can_open_the_user_guide(): void
    {
        $teamLeader = $this->createUserWithRole('Team Leader');
        $this->trainingWorkorderFor($this->createUserWithRole('Technician'));

        $this->actingAs($teamLeader)
            ->get(route('admin.user-guide'))
            ->assertOk();

        $this->actingAs($teamLeader)
            ->get(route('admin.user-guide.workorder-main'))
            ->assertOk()
            ->assertSee('100000');

        $this->assertStringContainsString(
            'href="' . route('admin.user-guide', ['center' => 1]) . '"',
            view('components.admin_menu_sidebar')->render()
        );
    }

    public function test_minimal_shop_role_sees_user_guide_in_sidebar(): void
    {
        $paintUser = $this->createUserWithRole('Paint');

        $this->actingAs($paintUser);

        $this->assertStringContainsString(
            'href="' . route('admin.user-guide', ['center' => 1]) . '"',
            view('components.admin_menu_sidebar')->render()
        );
    }

    private function trainingWorkorderFor(\App\Models\User $technician): \App\Models\Workorder
    {
        return \App\Models\Workorder::query()
            ->where('number', 100000)
            ->first()
            ?? $this->createWorkorder([
                'number' => 100000,
                'user_id' => $technician->id,
            ]);
    }
}
