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
        $response->assertSee('2.7 Tasks and notes');
        $response->assertSee('2.8 Processes and parts');
        $response->assertSee('2.9 TDR, pictures and PDF Library');
        $response->assertSee('2.10 TDR: parts and processes');
        $response->assertSee('2.13 TDR: paper workorder');
        $response->assertSee('3. Training');
        $response->assertSee('3.1 My training');
        $response->assertSee('4. Technician');
        $response->assertSee('4.1 Technician directory');
        $response->assertSee('5. Materials');
        $response->assertSee('5.1 Materials list');
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
        $response->assertSee('img/user-guide/technician-tdr-filled-parts.svg', false);
        $response->assertSee('img/user-guide/technician-tdr-code.png', false);
        $response->assertSee('img/user-guide/technician-tdr-processes-button.png', false);
        $response->assertSee('img/user-guide/technician-tdr-processes-add.png', false);
        $response->assertSee('img/user-guide/technician-tdr-traveler-selection.svg', false);
        $response->assertSee('img/user-guide/technician-tdr-process-form.svg', false);
        $response->assertSee('Traveler checkboxes, Vendor and Form / Form traveler buttons.', false);
        $response->assertDontSee('Form checkboxes', false);
        $response->assertSee('The generated form uses current workorder and process data.', false);
        $response->assertDontSee('img/user-guide/technician-tdr.png', false);
        $response->assertSee('img/user-guide/technician-tdr-paper-forms.png', false);
        $response->assertSee('img/user-guide/technician-training.png', false);
        $response->assertSee('img/user-guide/technician-directory.png', false);
        $response->assertSee('img/user-guide/technician-materials.png', false);
        $response->assertSee('data-guide-order="30"', false);
        $response->assertSee('data-guide-order="31"', false);
        $response->assertSee('data-guide-order="32"', false);
        $response->assertSee('data-guide-order="33"', false);
        $response->assertSee('data-guide-order="40"', false);
        $response->assertSee('data-guide-order="50"', false);
        $response->assertSee('data-guide-order="60"', false);
        $response->assertSee('const technicianGuideTranslations', false);
        $response->assertSee('const tdrGuideTranslations', false);
        $response->assertSee('const tdrPartsAddTranslations', false);
        $response->assertSee('The blue image icon opens all photos for the selected workorder.', false);
        $response->assertSee('Filters change only the rows shown in the table.', false);
        $response->assertSee('filter-highlight--approved', false);
        $response->assertSee('Your manager assigns a workorder to you.', false);
        $response->assertSee('assignment-highlight--technician', false);
        $response->assertSee('Click its blue number.', false);
        $response->assertSee('The paper icons do not create a new workorder', false);
        $response->assertSee('Saved parts in TDR; Add creates the next part row.', false);
        $response->assertSee('On the saved part row, press the train icon in Action.', false);
        $response->assertSee('Add Process appears only when you are allowed to create a new process definition.', false);
        $response->assertSee('guide-page__split', false);
        $response->assertSee('grid-template-columns: minmax(0, 3.6fr) minmax(0, 1.4fr);', false);
        $response->assertSee('guide-page__title-row', false);
        $response->assertSee('data-guide-order="22"', false);
        $response->assertSee("const scope = 'user-guide-book'", false);
        $response->assertSee("document.documentElement.dir = 'ltr'", false);
        $response->assertSee('const tdrPartsDetailedTranslations', false);
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

    public function test_sidebar_shows_guide_link_to_every_role_branch(): void
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

        foreach (['Technician', 'Paint'] as $role) {
            $this->actingAs($this->createUserWithRole($role));

            $this->assertStringContainsString(
                'href="' . route('admin.user-guide', ['center' => 1]) . '"',
                view('components.admin_menu_sidebar')->render(),
                "User Guide must be available to {$role}."
            );
        }
    }
}
