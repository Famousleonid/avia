<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TdrComponentReplacementAccessTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    /** @dataProvider actors */
    public function test_part_picker_and_update_share_the_same_permission(string $role, array $attributes, bool $allowed): void
    {
        $actor = $this->createUserWithRole($role, $attributes + ['is_admin' => false]);
        $wo = $this->createWorkorder(['user_id' => $actor->id]);
        $old = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '1-10', 'part_number' => 'BEFORE', 'name' => 'Old part']);
        $new = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '1-20', 'part_number' => 'AFTER', 'name' => 'New part']);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $old->id, 'serial_number' => 'SN', 'qty' => 1]);
        $process = ProcessName::create(['name' => 'Replacement history', 'process_sheet_name' => 'TEST', 'form_number' => 'TEST']);
        $history = TdrProcess::create(['tdrs_id' => $tdr->id, 'process_names_id' => $process->id, 'processes' => [], 'notes' => 'Keep history']);
        $this->actingAs($actor)->withSession(['auth.version' => (int) $actor->auth_version, 'password_hash_web' => $actor->getAuthPassword()]);
        $form = $this->get(route('tdrs.editForm', $tdr->id))->assertOk();
        if ($allowed) {
            $form->assertSee('id="edit_component_id"', false)->assertSee('AFTER');
        } else {
            $form->assertDontSee('id="edit_component_id"', false);
        }
        $this->putJson(route('tdrs.update', $tdr->id), [
            'workorder_id' => $wo->id, 'component_id' => $new->id, 'description' => 'Updated inspection',
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertSame($allowed ? $new->id : $old->id, $tdr->fresh()->component_id);
        $this->assertSame('Updated inspection', $tdr->fresh()->description);
        $this->assertSame($tdr->id, $history->fresh()->tdrs_id);
        $this->assertSame('Keep history', $history->fresh()->notes);
        if ((int) $actor->id === 31) {
            $this->assertFalse($actor->roleIs('Admin'));
            $this->assertFalse($actor->can('users.update'));
        }
    }

    public static function actors(): array
    {
        return [
            'Admin unchanged' => ['Admin', [], true],
            'Slava Y account' => ['Manager', ['id' => 31, 'name' => 'Yushkevich Viacheslav'], true],
            'Another manager with same display name' => ['Manager', ['id' => 99931, 'name' => 'Yushkevich Viacheslav'], false],
            'Technician' => ['Technician', [], false],
        ];
    }

    public function test_slava_cannot_replace_part_with_one_from_another_manual(): void
    {
        $actor = $this->createUserWithRole('Manager', ['id' => 31]);
        $wo = $this->createWorkorder(['user_id' => $actor->id]);
        $part = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '1-10', 'part_number' => 'ORIGINAL', 'name' => 'Original']);
        $foreign = Component::create(['manual_id' => $this->createManual()->id, 'ipl_num' => '1-20', 'part_number' => 'FOREIGN', 'name' => 'Foreign']);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id, 'qty' => 1]);
        $this->actingAs($actor)->withSession(['auth.version' => (int) $actor->auth_version, 'password_hash_web' => $actor->getAuthPassword()]);
        $this->putJson(route('tdrs.update', $tdr->id), ['workorder_id' => $wo->id, 'component_id' => $foreign->id])
            ->assertUnprocessable()->assertJsonValidationErrors('component_id');
        $this->assertSame($part->id, $tdr->fresh()->component_id);
    }
}
