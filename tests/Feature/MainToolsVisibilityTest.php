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

    public function test_pdf_library_button_is_rendered_after_the_photos_button_in_main(): void
    {
        $workorder = $this->createWorkorder(['number' => random_int(700000, 999999)]);
        $admin = $this->createUserWithRole('Admin');

        $response = $this->actingAs($admin)
            ->get(route('mains.show', $workorder))
            ->assertOk()
            ->assertSee('dir-top-square-btn open-pdf-modal', false)
            ->assertSee('bi bi-file-earmark-pdf', false)
            ->assertSee('aria-label="PDF Library"', false)
            ->assertSee('id="pdfCountBadge"', false)
            ->assertSee('dir-top-count-badge bg-warning d-none', false)
            ->assertSee('id="pdfModal"', false)
            ->assertSee('js/tdrs/show/pdf-library-handler.js', false);

        $html = $response->getContent();

        $this->assertLessThan(
            strpos($html, 'dir-top-square-btn open-pdf-modal'),
            strpos($html, 'bi bi-images text-decoration-none')
        );
    }

    public function test_draft_main_shows_only_photo_action_and_no_work_panels_for_every_role(): void
    {
        $draft = $this->createWorkorder([
            'number' => random_int(700000, 999999),
            'draft_number' => random_int(1000, 9999),
            'is_draft' => true,
        ]);
        $admin = $this->createUserWithRole('Admin');
        $technician = $this->createUserWithRole('Technician');

        foreach ([$admin, $technician] as $user) {
            $this->flushSession();

            $this->actingAs($user)
                ->get(route('mains.show', $draft))
                ->assertOk()
                ->assertSee('data-draft-main="1"', false)
                ->assertSee('data-main-draft-badge', false)
                ->assertSee('data-main-action="photos"', false)
                ->assertDontSee('data-main-action="tdr"', false)
                ->assertDontSee('data-main-action="pdf"', false)
                ->assertDontSee('data-main-action="tools"', false)
                ->assertDontSee('data-main-action="logs"', false)
                ->assertDontSee('data-main-action="parts"', false)
                ->assertDontSee('data-main-tabs', false)
                ->assertDontSee('id="pdfModal"', false)
                ->assertDontSee('js/tdrs/show/pdf-library-handler.js', false);
        }
    }
}
