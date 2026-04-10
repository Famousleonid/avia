<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WorkordersIndexTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    /**
     * @group smoke
     */
    public function test_admin_can_open_workorders_index_page(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder([
            'user_id' => $admin->id,
            'description' => 'Visible workorder',
        ]);

        $response = $this->actingAs($admin)->get(route('workorders.index'));

        $response->assertOk();
        $response->assertSee((string) $workorder->number);
        $response->assertSee('Visible workorder');
    }

    /**
     * @group smoke
     */
    public function test_guest_is_redirected_from_workorders_index(): void
    {
        $response = $this->get(route('workorders.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_only_my_filter_returns_only_current_user_workorders(): void
    {
        $currentUser = $this->createUserWithRole('Admin');
        $otherUser = $this->createUserWithRole('Manager');

        $ownWorkorder = $this->createWorkorder([
            'user_id' => $currentUser->id,
            'description' => 'Own WO',
        ]);
        $otherWorkorder = $this->createWorkorder([
            'user_id' => $otherUser->id,
            'description' => 'Other WO',
        ]);

        $response = $this->actingAs($currentUser)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_my' => 1,
        ]));

        $response->assertOk();
        $response->assertJsonPath('total_count', 1);
        $response->assertJsonPath('loaded_count', 1);
        $response->assertSee((string) $ownWorkorder->number);
        $response->assertDontSee((string) $otherWorkorder->number);
    }

    public function test_approved_filter_returns_only_approved_workorders(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $approved = $this->createWorkorder([
            'user_id' => $admin->id,
            'approve_at' => now(),
            'approve_name' => $admin->name,
            'description' => 'Approved WO',
        ]);
        $notApproved = $this->createWorkorder([
            'user_id' => $admin->id,
            'description' => 'Pending WO',
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_my' => 0,
            'only_approved' => 1,
        ]));

        $response->assertOk();
        $response->assertJsonPath('total_count', 1);
        $response->assertSee((string) $approved->number);
        $response->assertDontSee((string) $notApproved->number);
    }

    public function test_draft_filter_returns_only_drafts(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $draft = $this->createWorkorder([
            'user_id' => $admin->id,
            'is_draft' => true,
            'description' => 'Draft WO',
        ]);
        $regular = $this->createWorkorder([
            'user_id' => $admin->id,
            'is_draft' => false,
            'description' => 'Regular WO',
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_my' => 0,
            'show_drafts' => 1,
        ]));

        $response->assertOk();
        $response->assertJsonPath('total_count', 1);
        $response->assertSee((string) $draft->number);
        $response->assertDontSee((string) $regular->number);
    }

    /**
     * @group smoke
     */
    public function test_fragment_endpoint_returns_expected_json_shape(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->createWorkorder(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_my' => 0,
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'html',
            'next_cursor',
            'has_more',
            'loaded_count',
            'total_count',
            'overall_total',
        ]);
    }

    public function test_search_by_number_works(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $matching = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 543210,
            'description' => 'Find me',
        ]);
        $other = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 654321,
            'description' => 'Do not find me',
        ]);

        $response = $this->actingAs($admin)->getJson(route('workorders.index', [
            'fragment' => 1,
            'per_page' => 50,
            'only_my' => 0,
            'q' => '543210',
        ]));

        $response->assertOk();
        $response->assertJsonPath('total_count', 1);
        $response->assertSee((string) $matching->number);
        $response->assertDontSee((string) $other->number);
    }
}
