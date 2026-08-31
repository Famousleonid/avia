<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WorkorderPhotosPrintTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_workorder_photos_fancybox_has_a_print_button_for_the_current_photo(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->get(route('mains.photos', $workorder))
            ->assertOk()
            ->assertSee('data-workorder-number="' . $workorder->number . '"', false)
            ->assertSee('data-fancybox-print', false)
            ->assertSee("buttons: ['zoom', 'slideShow', 'thumbs', 'print', 'close']", false)
            ->assertSee('openPhotoPrintDialog(current.src)', false);
    }
}
