<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\TravelerNoteTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TravelerNoteTemplatesTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    /** @dataProvider authorizedUsers */
    public function test_authorized_user_crud_search_and_validation(string $role, array $attributes): void
    {
        $admin = $this->createUserWithRole($role, $attributes);
        $manual = $this->createManual();
        $part = Component::create(['manual_id' => $manual->id, 'part_number' => 'NOTE-PART-' . uniqid(), 'name' => 'Test part', 'ipl_num' => '1-10']);
        $process = ProcessName::create(['name' => 'Note process ' . uniqid(), 'process_sheet_name' => 'TEST', 'form_number' => 'TEST']);
        $data = ['manual_id' => $manual->id, 'part_number' => $part->part_number, 'process_names_id' => $process->id, 'notes' => "Test note\nSecond line"];
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()])
            ->get(route('library.traveler-notes.index'))->assertOk()->assertSee('Notes Traveler');
        $this->assertStringContainsString(route('library.traveler-notes.index'), view('components.admin_menu_sidebar', ['themeToggleId' => 'test-theme-toggle'])->render());
        $this->getJson(route('library.traveler-notes.options', ['type' => 'part', 'manual_id' => $manual->id, 'q' => $part->part_number]))
            ->assertOk()->assertJsonPath('results.0.id', $part->part_number);
        $otherManual = $this->createManual();
        $this->getJson(route('library.traveler-notes.options', ['type' => 'part', 'manual_id' => $otherManual->id, 'q' => $part->part_number]))
            ->assertJsonCount(0, 'results');
        $this->post(route('library.traveler-notes.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $note = TravelerNoteTemplate::where('part_number', $part->part_number)->firstOrFail();
        $this->post(route('library.traveler-notes.store'), $data)->assertSessionHasErrors('part_number');
        $this->post(route('library.traveler-notes.store'), array_merge($data, ['manual_id' => $otherManual->id]))->assertSessionHasErrors('part_number');
        $this->post(route('library.traveler-notes.store'), array_merge($data, ['notes' => '   ']))->assertSessionHasErrors('notes');
        $this->get(route('library.traveler-notes.index', ['edit' => $note->id]))->assertOk()->assertSee('Second line');
        $this->put(route('library.traveler-notes.update', $note), array_merge($data, ['notes' => 'Updated note']))->assertSessionHasNoErrors();
        $this->assertSame('Updated note', $note->fresh()->notes);
        $this->get(route('library.traveler-notes.index', ['q' => $part->part_number]))->assertSee('Updated note');
        $this->delete(route('library.traveler-notes.destroy', $note))->assertRedirect();
        $this->assertDatabaseMissing('traveler_note_templates', ['id' => $note->id]);
    }

    public static function authorizedUsers(): array
    {
        return [
            'admin' => ['Admin', []],
            'Slava Y' => ['Manager', ['id' => 31, 'name' => 'Yushkevich Viacheslav']],
        ];
    }

    public function test_other_manager_cannot_access_notes_even_by_direct_url(): void
    {
        $manager = $this->createUserWithRole('Manager', ['id' => 99931, 'name' => 'Slava Y']);
        $this->actingAs($manager)->withSession(['auth.version' => (int) $manager->auth_version, 'password_hash_web' => $manager->getAuthPassword()]);
        $this->get(route('library.traveler-notes.index'))->assertForbidden();
        $this->getJson(route('library.traveler-notes.options', ['type' => 'manual']))->assertForbidden();
        $this->post(route('library.traveler-notes.store'), [])->assertForbidden();
        $manual = $this->createManual();
        $process = ProcessName::firstOrFail();
        $note = TravelerNoteTemplate::create(['manual_id' => $manual->id, 'part_number' => 'ACCESS-TEST', 'process_names_id' => $process->id, 'notes' => 'Protected note']);
        $this->put(route('library.traveler-notes.update', $note), ['notes' => 'Changed'])->assertForbidden();
        $this->delete(route('library.traveler-notes.destroy', $note))->assertForbidden();
        $this->assertSame('Protected note', $note->fresh()->notes);
        $this->assertStringNotContainsString(route('library.traveler-notes.index'), view('components.admin_menu_sidebar', ['themeToggleId' => 'test-theme-toggle'])->render());
    }

    public function test_non_admin_cannot_manage_or_read_templates(): void
    {
        $this->actingAs($this->createUserWithRole('Technician'));
        $this->get(route('library.traveler-notes.index'))->assertForbidden();
        $this->getJson(route('library.traveler-notes.options', ['type' => 'manual']))->assertForbidden();
        $this->post(route('library.traveler-notes.store'), [])->assertForbidden();
    }

    public function test_traveler_matches_all_three_keys_and_preserves_manual_notes(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin);
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        // A supplemental manual is deliberately different from the WO unit manual.
        $manual = $this->createManual();
        $part = Component::create(['manual_id' => $manual->id, 'part_number' => 'TRIPLE-' . uniqid(), 'name' => 'Triple match part', 'ipl_num' => '1-20']);
        $process = ProcessName::create(['name' => 'Traveler note test ' . uniqid(), 'process_sheet_name' => 'TEST', 'form_number' => 'TEST']);
        $otherProcess = ProcessName::create(['name' => 'Other note process ' . uniqid(), 'process_sheet_name' => 'TEST', 'form_number' => 'TEST']);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id, 'qty' => 1, 'use_tdr' => true]);
        $row = TdrProcess::create(['tdrs_id' => $tdr->id, 'process_names_id' => $process->id, 'processes' => [], 'notes' => 'Original row note', 'ignore_row' => false, 'in_traveler' => true, 'traveler_group' => 1]);
        $base = ['manual_id' => $manual->id, 'part_number' => $part->part_number, 'process_names_id' => $process->id];
        $match = TravelerNoteTemplate::create($base + ['notes' => 'Matching template <safe>']);
        TravelerNoteTemplate::create(array_merge($base, ['manual_id' => $wo->unit->manual_id, 'notes' => 'WRONG MANUAL']));
        TravelerNoteTemplate::create(array_merge($base, ['part_number' => 'OTHER-PART', 'notes' => 'WRONG PART']));
        TravelerNoteTemplate::create(array_merge($base, ['process_names_id' => $otherProcess->id, 'notes' => 'WRONG PROCESS']));
        foreach ([[], ['traveler_group' => 1]] as $query) {
            $this->get(route('tdr-processes.travelForm', ['id' => $tdr->id] + $query))->assertOk()
                ->assertSee('Original row note')->assertSee('Matching template &lt;safe&gt;', false)
                ->assertDontSee('WRONG MANUAL')->assertDontSee('WRONG PART')->assertDontSee('WRONG PROCESS');
        }
        $this->assertSame('Original row note', $row->fresh()->notes);
        $match->delete();
        $this->get(route('tdr-processes.travelForm', $tdr->id))->assertOk()->assertSee('Original row note')->assertDontSee('Matching template');
    }
}
