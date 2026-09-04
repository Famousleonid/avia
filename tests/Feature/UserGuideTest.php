<?php

namespace Tests\Feature;

use Tests\BuildsDomainData;
use Tests\TestCase;

class UserGuideTest extends TestCase
{
    use BuildsDomainData;

    public function test_authenticated_user_can_open_static_book_without_training_workorder(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $response = $this->actingAs($admin)->get(route('admin.user-guide'));

        $response->assertOk();
        $response->assertSee('class="guide-reading"', false);
        $response->assertSee('data-guide-page', false);
        $response->assertSee('class="guide-steps"', false);
        $response->assertSee('<h2 class="page-title"', false);
        $response->assertDontSee('<h1 class="page-title"', false);
        $response->assertSee('font-size: clamp(1.05rem, 2vw, 1.35rem);', false);
        $response->assertSee('1. Getting started');
        $response->assertSee('2. Workorder');
        $response->assertSee('2.1 Workorders page');
        $response->assertSee('2.2 Getting started with a workorder');
        $response->assertSee('2.3 Filters');
        $response->assertSee('2.4 Open a workorder');
        $response->assertSee('2.5 Main');
        $response->assertSee('2.6 Main: header and work area');
        $response->assertSee('img/user-guide/technician-login.png', false);
        $response->assertSee('img/user-guide/technician-cabinet.png', false);
        $response->assertSee('img/user-guide/technician-workorders.png', false);
        $response->assertSee('img/user-guide/technician-workorders-assignment.png', false);
        $response->assertSee('img/user-guide/technician-workorders-open.png', false);
        $response->assertSee('img/user-guide/technician-workorders-filters-split.png', false);
        $response->assertSee('img/user-guide/technician-workorder-main-technician.png', false);
        $response->assertSee('img/user-guide/technician-workorder-main-header-only.png', false);
        $response->assertDontSee('img/user-guide/technician-workorder-main-header.png', false);
        $response->assertSee('guide-figure--main-header', false);
        $response->assertSee('guide-actions', false);
        $response->assertSee('bi bi-mortarboard', false);
        $response->assertSee('Opens the form to add or update your training record for this manual.', false);
        $response->assertDontSee('data-i18n="workorderMainDetailsLead"', false);
        $response->assertDontSee('data-i18n="workorderMainHeaderWhat"', false);
        $response->assertSee('img/user-guide/technician-workorder-main-workarea.png', false);
        $response->assertSee('The blue image icon opens all photos for the selected workorder.', false);
        $response->assertSee('Filters change only the rows shown in the table.', false);
        $response->assertSee('filter-highlight--approved', false);
        $response->assertSee('Your manager assigns a workorder to you.', false);
        $response->assertSee('assignment-highlight--technician', false);
        $response->assertSee('Click its blue number.', false);
        $response->assertSee('guide-page__split', false);
        $response->assertSee('guide-page__title-row', false);
        $response->assertSee('data-guide-order="22"', false);
        $response->assertSee("const scope = 'user-guide-book'", false);
        $response->assertSee("document.documentElement.dir = 'ltr'", false);
        $response->assertSee('href="' . route('workorders.index') . '"', false);
        $response->assertSee('width: min(100%, 1196px)', false);

        foreach (['en', 'ru', 'uk', 'he', 'de', 'kk', 'be'] as $language) {
            $response->assertSee('data-language="' . $language . '"', false);
        }

        $response->assertDontSee('<iframe', false);
        $response->assertDontSee('userGuideEmbed', false);
        $response->assertDontSee('guide-live-', false);
        $response->assertDontSee('data-workorders-stage', false);
    }

    public function test_technician_can_open_static_book_without_assignment(): void
    {
        $technician = $this->createUserWithRole('Technician');

        $this->actingAs($technician)
            ->get(route('admin.user-guide'))
            ->assertOk()
            ->assertSee('Workorders page');
    }

    public function test_old_interactive_guide_routes_are_removed(): void
    {
        $admin = $this->createUserWithRole('Admin');

        foreach ([
            'workorder-main',
            'tdr-report',
            'workorder-pictures',
            'training',
            'technicians',
            'materials',
            'mobile-workorders',
            'mobile-workorder',
            'mobile-workorder-pictures',
        ] as $page) {
            $this->actingAs($admin)
                ->get('/admin/user-guide/' . $page)
                ->assertNotFound();
        }
    }

    public function test_sidebar_keeps_guide_link_for_admin_only(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);

        $sidebar = view('components.admin_menu_sidebar', [
            'themeToggleId' => 'test-theme-toggle',
        ])->render();

        $userGuidePosition = strpos($sidebar, '>User Guide</span>');
        $themePosition = strpos($sidebar, '>Thema</span>');
        $this->assertNotFalse($userGuidePosition);
        $this->assertNotFalse($themePosition);
        $this->assertLessThan($themePosition, $userGuidePosition);

        $paintUser = $this->createUserWithRole('Paint');
        $this->actingAs($paintUser);

        $this->assertStringNotContainsString(
            'href="' . route('admin.user-guide', ['center' => 1]) . '"',
            view('components.admin_menu_sidebar')->render()
        );
    }
}
