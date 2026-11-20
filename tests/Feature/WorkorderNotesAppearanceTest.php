<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WorkorderNotesAppearanceTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_appearance_is_shared_for_the_wo_and_does_not_replace_notes(): void
    {
        $user = $this->createUserWithRole('Technician')->fresh();
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        $wo->notes = 'Keep  these notes';
        $wo->save();
        $this->actingAs($user)->patchJson(route('workorders.notes.appearance', $wo), ['color' => '#ffcc00', 'size' => 18])->assertOk();
        $this->assertSame('Keep  these notes', $wo->fresh()->notes);
        $this->assertEquals(['color' => '#ffcc00', 'size' => 18], $wo->fresh()->notes_appearance);
        $manager = $this->createUserWithRole('Manager')->fresh();
        $this->flushSession();
        $this->actingAs($manager)->withSession(['auth.version' => (int) $manager->auth_version]);
        $this->patchJson(route('workorders.notes.appearance', $wo), ['color' => '#ffcc00', 'size' => 18])
            ->assertOk()->assertJsonPath('appearance.color', '#ffcc00');
        $this->patchJson(route('workorders.notes.update', $wo), ['notes' => 'Updated'])->assertOk();
        $this->assertSame(18, $wo->fresh()->notes_appearance['size']);
        $this->patchJson(route('workorders.notes.appearance', $wo), ['color' => null, 'size' => null])->assertOk();
        $this->assertNull($wo->fresh()->notes_appearance['color']);
    }

    public function test_invalid_styles_are_rejected(): void
    {
        $user = $this->createUserWithRole('Technician')->fresh();
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        $this->actingAs($user)->patchJson(route('workorders.notes.appearance', $wo), ['color' => 'red;display:none', 'size' => 100])
            ->assertUnprocessable()->assertJsonValidationErrors(['color', 'size']);
        $this->assertNull($wo->fresh()->notes_appearance);
    }

    public function test_selected_text_styles_are_saved_with_plain_notes(): void
    {
        $user = $this->createUserWithRole('Technician')->fresh();
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        $runs = [['text' => 'Red  ', 'color' => '#ff0000', 'size' => 18, 'bold' => true], ['text' => 'normal', 'color' => null, 'size' => null, 'bold' => false]];
        $this->actingAs($user)->patchJson(route('workorders.notes.update', $wo), ['notes' => 'Red  normal', 'runs' => $runs])->assertOk();
        $this->assertEquals($runs, $wo->fresh()->notes_appearance['runs']);
        $this->assertSame('Red  normal', $wo->fresh()->notes);
        $this->patchJson(route('workorders.notes.update', $wo), ['notes' => 'different', 'runs' => $runs])->assertStatus(422);
    }
}

