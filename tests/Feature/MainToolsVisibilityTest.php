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
}
