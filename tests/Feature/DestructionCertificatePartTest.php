<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\LogCard;
use App\Models\Unit;
use App\Support\LogCardDestructionCertificate as Certificate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class DestructionCertificatePartTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    private function receivedPart(): array
    {
        $wo = $this->createWorkorder(['serial_number' => 'RECEIVED-SN']);
        $part = Component::create([
            'manual_id' => $wo->unit->manual_id,
            'part_number' => 'RECEIVED-PN', 'name' => 'Received pin', 'ipl_num' => '8-100',
        ]);
        $wo->update(['scope_type' => Unit::SCOPE_COMPONENT, 'scope_component_id' => $part->id]);

        return [$wo, $part];
    }

    public function test_single_received_part_is_rendered_without_creating_log_card(): void
    {
        [$wo] = $this->receivedPart();
        $admin = $this->createUserWithRole('Admin');
        $rows = Certificate::rowsForWorkorder($wo);
        $this->assertCount(1, $rows);
        $this->assertSame('RECEIVED-PN', $rows[0]['part_number']);
        $this->assertSame('RECEIVED-SN', $rows[0]['serial_number']);
        $this->assertSame('Received pin', $rows[0]['description']);
        $this->assertFalse($rows[0]['selected']);
        $this->assertTrue(Certificate::availableFor($wo));
        $this->actingAs($admin)->get(route('log_card.sertDistrForm', $wo->id))
            ->assertOk()->assertSee('RECEIVED-PN')->assertSee('RECEIVED-SN');
        $this->assertDatabaseMissing('log_cards', ['workorder_id' => $wo->id]);
    }

    public function test_empty_or_manual_header_only_log_card_uses_received_part(): void
    {
        [$wo] = $this->receivedPart();
        $card = LogCard::create(['workorder_id' => $wo->id]);
        foreach ([null, '[]', 'null', json_encode([['row_type' => 'manual', 'manual_label' => 'CMM'], []])] as $value) {
            $card->update(['component_data' => $value]);
            $rows = Certificate::rowsForWorkorder($wo);
            $this->assertCount(1, $rows);
            $this->assertSame('RECEIVED-SN', $rows[0]['serial_number']);
        }
        $wo->update(['serial_number' => null]);
        $this->assertSame('', Certificate::rowsForWorkorder($wo)[0]['serial_number']);
    }

    public function test_log_card_rows_and_manual_certificate_entries_have_priority(): void
    {
        [$wo, $part] = $this->receivedPart();
        $card = LogCard::create(['workorder_id' => $wo->id, 'component_data' => json_encode([
            ['row_type' => 'manual', 'manual_label' => 'CMM'],
            ['component_id' => $part->id, 'serial_number' => 'LOG-SN'],
            ['part_number' => 'SECOND-PN', 'serial_number' => 'SECOND-SN'],
        ])]);
        $rows = Certificate::rowsForWorkorder($wo);
        $this->assertCount(2, $rows);
        $this->assertSame('LOG-SN', $rows[0]['serial_number']);
        $this->assertSame('SECOND-PN', $rows[1]['part_number']);
        $card->update(['component_data' => '[]', 'destruction_certificate_data' => [
            'manual_row' => ['part_number' => 'QA-PN', 'serial_number' => 'QA-SN'],
        ]]);
        $this->assertSame([], Certificate::rowsForWorkorder($wo));
        $this->assertSame('QA-SN', Certificate::manualRowFor($card)['serial_number']);
    }

    public function test_complete_units_and_assemblies_are_not_guessed_as_single_parts(): void
    {
        [$wo] = $this->receivedPart();
        foreach ([Unit::SCOPE_FULL_UNIT, Unit::SCOPE_PART_GROUP_OPTION] as $scope) {
            $wo->update(['scope_type' => $scope]);
            $this->assertSame([], Certificate::rowsForWorkorder($wo));
        }
    }

    public function test_qa_can_save_and_clear_selection_of_received_part(): void
    {
        [$wo] = $this->receivedPart();
        $manager = $this->createUserWithRole('Manager', ['qa_access' => true]);
        $key = Certificate::rowsForWorkorder($wo)[0]['key'];
        foreach ([[$key], []] as $keys) {
            $this->actingAs($manager)->postJson(route('log_card.destruction_certificate.update', $wo->id), [
                'selected_keys' => $keys, 'certificate_date' => '07/Nov/2026',
            ])->assertOk();
            $rows = Certificate::rowsForWorkorder($wo);
            $this->assertSame($key, $rows[0]['key']);
            $this->assertSame($keys !== [], $rows[0]['selected']);
            $this->assertSame('RECEIVED-SN', $rows[0]['serial_number']);
        }
        $this->assertNull(LogCard::where('workorder_id', $wo->id)->first()->component_data);
    }

    public function test_legacy_scope_requires_exactly_one_resolved_part(): void
    {
        [$wo, $part] = $this->receivedPart();
        $wo->unit->update(['part_number' => $part->part_number]);
        $wo->update([
            'scope_type' => null, 'scope_component_id' => null,
            'instruction_id' => $this->createInstruction(['name' => 'Overhaul'])->id,
        ]);
        $this->assertSame('RECEIVED-PN', Certificate::rowsForWorkorder($wo->fresh())[0]['part_number']);
        Component::create([
            'manual_id' => $part->manual_id, 'part_number' => $part->part_number,
            'name' => 'Other IPL', 'ipl_num' => '9-100',
        ]);
        $this->assertSame([], Certificate::rowsForWorkorder($wo->fresh()));
    }
}
