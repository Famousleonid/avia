<?php

namespace Tests\Feature;

use App\Models\PageVisit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class PageVisitStatTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_is_admin_can_view_page_visit_stats_with_filters(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $technician = $this->createUserWithRole('Technician');

        PageVisit::query()->create([
            'user_id' => $technician->id,
            'visited_at' => now()->setDate(2026, 5, 8)->setTime(9, 15),
            'method' => 'GET',
            'path' => '/tdrs/show/2',
            'url' => 'http://avia.loc/tdrs/show/2',
            'route_name' => 'tdrs.show',
        ]);

        PageVisit::query()->create([
            'user_id' => $admin->id,
            'visited_at' => now()->setDate(2026, 5, 7)->setTime(10, 0),
            'method' => 'GET',
            'path' => '/filtered-out-stat-test-page',
            'url' => 'http://avia.loc/filtered-out-stat-test-page',
            'route_name' => 'users.index',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.page-visits.index', [
            'user_id' => $technician->id,
            'from' => '2026-05-08',
            'to' => '2026-05-08',
        ]));

        $response->assertOk();
        $response->assertSee('Page visit stats');
        $response->assertSee('/tdrs/show/2');
        $response->assertDontSee('/filtered-out-stat-test-page');
    }

    public function test_page_visit_stats_hide_is_admin_users_in_all_users_view(): void
    {
        $admin = $this->createUserWithRole('Admin', [
            'is_admin' => true,
            'name' => 'Hidden Admin',
            'email' => 'hidden-stat-admin@example.test',
        ]);
        $technician = $this->createUserWithRole('Technician', ['name' => 'Visible Technician']);

        PageVisit::query()->create([
            'user_id' => $technician->id,
            'visited_at' => now()->setDate(2026, 5, 8)->setTime(9, 15),
            'method' => 'GET',
            'path' => '/visible-user-page',
            'url' => 'http://avia.loc/visible-user-page',
            'route_name' => 'tdrs.show',
        ]);

        PageVisit::query()->create([
            'user_id' => $admin->id,
            'visited_at' => now()->setDate(2026, 5, 8)->setTime(10, 0),
            'method' => 'GET',
            'path' => '/hidden-admin-page',
            'url' => 'http://avia.loc/hidden-admin-page',
            'route_name' => 'users.index',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.page-visits.index'));

        $response->assertOk();
        $response->assertSee('Hidden Admin');
        $response->assertDontSee('hidden-stat-admin@example.test');
        $response->assertSee('Visible Technician');
        $response->assertSee('/visible-user-page');
        $response->assertDontSee('/hidden-admin-page');
    }

    public function test_page_visit_stats_can_filter_to_one_is_admin_user(): void
    {
        $admin = $this->createUserWithRole('Admin', [
            'is_admin' => true,
            'name' => 'Selected Stat Admin',
            'email' => 'selected-stat-admin@example.test',
        ]);
        $technician = $this->createUserWithRole('Technician', ['name' => 'Hidden Technician']);

        PageVisit::query()->create([
            'user_id' => $technician->id,
            'visited_at' => now()->setDate(2026, 5, 8)->setTime(9, 15),
            'method' => 'GET',
            'path' => '/hidden-technician-page',
            'url' => 'http://avia.loc/hidden-technician-page',
            'route_name' => 'tdrs.show',
        ]);

        PageVisit::query()->create([
            'user_id' => $admin->id,
            'visited_at' => now()->setDate(2026, 5, 8)->setTime(10, 0),
            'method' => 'GET',
            'path' => '/selected-admin-page',
            'url' => 'http://avia.loc/selected-admin-page',
            'route_name' => 'users.index',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.page-visits.index', [
            'user_id' => $admin->id,
        ]));

        $response->assertOk();
        $response->assertSee('Selected Stat Admin');
        $response->assertSee('/selected-admin-page');
        $response->assertDontSee('/hidden-technician-page');
    }

    public function test_admin_role_without_is_admin_cannot_view_page_visit_stats(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => false]);

        $this->actingAs($roleOnlyAdmin)
            ->get(route('admin.page-visits.index'))
            ->assertForbidden();
    }

    public function test_authenticated_page_get_is_logged(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);

        $this->actingAs($admin)->get('/');

        $this->assertDatabaseHas('page_visits', [
            'user_id' => $admin->id,
            'method' => 'GET',
            'path' => '/',
            'route_name' => 'home',
        ]);
    }
}
