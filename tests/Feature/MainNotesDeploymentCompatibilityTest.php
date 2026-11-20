<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MainNotesDeploymentCompatibilityTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_main_preloads_saved_notes_toolbar_state_for_current_user(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        foreach ([true, false] as $open) {
            $this->actingAs($admin)->postJson('/user-ui-settings', [
                'scope' => 'mains.show', 'key' => 'notesFormattingOpen', 'value' => $open,
            ])->assertOk();
            $this->get('/mains/'.$wo->id)->assertOk()
                ->assertViewHas('mainUiSettings', fn ($settings) =>
                    array_key_exists('notesFormattingOpen', $settings)
                    && $settings['notesFormattingOpen'] === $open);
        }
    }

    public function test_main_keeps_plain_notes_when_appearance_route_is_not_deployed(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $wo->notes = 'Existing notes survive a partial deployment';
        $wo->save();
        $routes = new RouteCollection();
        foreach (Route::getRoutes() as $route) {
            if ($route->getName() !== 'workorders.notes.appearance') $routes->add($route);
        }
        Route::setRoutes($routes);
        $this->actingAs($admin)->get('/mains/'.$wo->id)
            ->assertOk()
            ->assertSee('Existing notes survive a partial deployment')
            ->assertSee('name="notes"', false)
            ->assertDontSee('class="wo-notes-color"', false)
            ->assertDontSee('js/mains/notes-appearance.js');
    }
}
