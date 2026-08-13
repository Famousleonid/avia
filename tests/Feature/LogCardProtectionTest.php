<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\GeneralTask;
use App\Models\LogCard;
use App\Models\Main;
use App\Models\MobileApiToken;
use App\Models\Task;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class LogCardProtectionTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_only_qa_user_can_change_log_card_after_post_disassembly_inspection(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $manager = $this->createUserWithRole('Manager');
        $qa = $this->createUserWithRole('Manager', ['qa_access' => true]);
        $superAdmin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        [$workorder, $component, $logCard] = $this->makeLogCard();
        $this->finishPostDisassemblyInspection($workorder);

        $payload = [
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([[
                'component_id' => (string) $component->id,
                'manual_id' => (string) $component->manual_id,
                'ipl_group' => '1-100',
                'serial_number' => 'QA-ONLY-SN',
            ]]),
        ];

        $this->actingAs($technician)
            ->putJson(route('log_card.update', $logCard), $payload)
            ->assertStatus(423);
        $this->actingAs($manager)
            ->putJson(route('log_card.update', $logCard), $payload)
            ->assertStatus(423);

        $this->assertSame('OLD-SN', $this->rows($logCard)[0]['serial_number']);

        $this->actingAs($qa)
            ->putJson(route('log_card.update', $logCard), $payload)
            ->assertOk();

        $this->assertSame('QA-ONLY-SN', $this->rows($logCard)[0]['serial_number']);

        $payload['component_data'] = json_encode([[
            'component_id' => (string) $component->id,
            'manual_id' => (string) $component->manual_id,
            'ipl_group' => '1-100',
            'serial_number' => 'SUPER-ADMIN-SN',
        ]]);
        $this->actingAs($superAdmin)
            ->putJson(route('log_card.update', $logCard), $payload)
            ->assertOk();
        $this->assertSame('SUPER-ADMIN-SN', $this->rows($logCard)[0]['serial_number']);
    }

    public function test_ordinary_update_preserves_qa_owned_fields_and_ignores_tampering(): void
    {
        $technician = $this->createUserWithRole('Technician');
        [$workorder, $component, $logCard] = $this->makeLogCard([
            'part_number' => 'QA-PN',
            'name' => 'QA description',
            'reason' => 'QA reason',
            'qa_fit_date' => '05/aug/2026',
            'qa_cell_colors' => ['description' => '#d3f9d8'],
        ]);

        $response = $this->actingAs($technician)->putJson(route('log_card.update', $logCard), [
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([[
                'component_id' => (string) $component->id,
                'manual_id' => (string) $component->manual_id,
                'ipl_group' => '1-100',
                'serial_number' => 'NEW-SN',
                'part_number' => 'HACKED-PN',
                'name' => 'Hacked description',
                'reason' => 'Hacked reason',
                'qa_fit_date' => '',
                'qa_cell_colors' => [],
            ]]),
        ]);

        $response->assertOk();
        $row = $this->rows($logCard)[0];
        $this->assertSame('NEW-SN', $row['serial_number']);
        $this->assertSame('QA-PN', $row['part_number']);
        $this->assertSame('QA description', $row['name']);
        $this->assertSame('Hacked reason', $row['reason']);
        $this->assertSame('05/aug/2026', $row['qa_fit_date']);
        $this->assertSame('#d3f9d8', $row['qa_cell_colors']['description']);
    }

    public function test_ordinary_log_card_can_update_reason_before_qa_lock(): void
    {
        $technician = $this->createUserWithRole('Technician');
        [, , $logCard] = $this->makeLogCard();

        $this->actingAs($technician)->patchJson(route('log_card.inline_field.update', $logCard), [
            'row' => 0,
            'field' => 'reason',
            'value' => 'Technician reason',
        ])->assertOk();

        $this->assertSame('Technician reason', $this->rows($logCard)[0]['reason']);
    }

    public function test_ordinary_create_strips_qa_and_qa_owned_fields_from_payload(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder();
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'name' => 'Canonical component',
            'part_number' => 'CANONICAL-PN',
            'ipl_num' => '1-300',
            'log_card' => true,
        ]);

        $this->actingAs($technician)->postJson(route('log_card.store'), [
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([[
                'component_id' => (string) $component->id,
                'manual_id' => (string) $component->manual_id,
                'ipl_group' => '1-300',
                'serial_number' => 'TECH-SN',
                'name' => 'Injected QA description',
                'part_number' => 'INJECTED-PN',
                'reason' => 'Injected QA reason',
                'qa_fit_date' => '06/aug/2026',
            ]]),
        ])->assertOk();

        $row = json_decode((string) LogCard::where('workorder_id', $workorder->id)->firstOrFail()->component_data, true)[0];
        $this->assertSame('TECH-SN', $row['serial_number']);
        $this->assertSame('Injected QA reason', $row['reason']);
        $this->assertArrayNotHasKey('name', $row);
        $this->assertArrayNotHasKey('part_number', $row);
        $this->assertArrayNotHasKey('qa_fit_date', $row);
    }

    public function test_ordinary_update_cannot_remove_row_with_qa_data(): void
    {
        $technician = $this->createUserWithRole('Technician');
        [$workorder, $component, $logCard] = $this->makeLogCard([
            'qa_fit_date' => '05/aug/2026',
        ]);
        $other = Component::query()->create([
            'manual_id' => $component->manual_id,
            'name' => 'Other component',
            'part_number' => 'OTHER-PN',
            'ipl_num' => '1-200',
            'log_card' => true,
        ]);

        $this->actingAs($technician)->putJson(route('log_card.update', $logCard), [
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([[
                'component_id' => (string) $other->id,
                'manual_id' => (string) $other->manual_id,
                'ipl_group' => '1-200',
                'serial_number' => 'OTHER-SN',
            ]]),
        ])->assertUnprocessable();

        $row = $this->rows($logCard)[0];
        $this->assertSame((string) $component->id, (string) $row['component_id']);
        $this->assertSame('05/aug/2026', $row['qa_fit_date']);
    }

    public function test_ordinary_reset_cannot_delete_qa_data(): void
    {
        $technician = $this->createUserWithRole('Technician');
        [, , $logCard] = $this->makeLogCard(['qa_fit_date' => '05/aug/2026']);

        $this->actingAs($technician)
            ->deleteJson(route('log_card.destroy', $logCard))
            ->assertUnprocessable();

        $this->assertDatabaseHas('log_cards', ['id' => $logCard->id]);
    }

    public function test_destruction_certificate_write_requires_qa_access(): void
    {
        $manager = $this->createUserWithRole('Manager');
        [$workorder] = $this->makeLogCard();

        $this->actingAs($manager)
            ->postJson(route('log_card.destruction_certificate.update', $workorder), [])
            ->assertForbidden();
    }

    public function test_mobile_technician_is_locked_after_post_disassembly_inspection(): void
    {
        $technician = $this->createUserWithRole('Technician');
        [$workorder, $component, $logCard] = $this->makeLogCard();
        $this->finishPostDisassemblyInspection($workorder);
        $plainToken = 'log-card-protection-'.uniqid('', true);
        MobileApiToken::query()->create([
            'user_id' => $technician->id,
            'name' => 'Log Card protection test',
            'token_hash' => MobileApiToken::hashPlainTextToken($plainToken),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->putJson(route('api.mobile.workorders.log-card.update', $workorder), [
                'rows' => [[
                    'component_id' => $component->id,
                    'serial_number' => 'MOBILE-HACK',
                ]],
            ])
            ->assertStatus(423);

        $this->assertSame('OLD-SN', $this->rows($logCard)[0]['serial_number']);
    }

    private function makeLogCard(array $extraRow = []): array
    {
        $workorder = $this->createWorkorder();
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'name' => 'Protected component',
            'part_number' => 'PROTECTED-PN',
            'ipl_num' => '1-100',
            'log_card' => true,
        ]);
        $logCard = LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                array_merge([
                    'component_id' => (string) $component->id,
                    'manual_id' => (string) $component->manual_id,
                    'ipl_group' => '1-100',
                    'serial_number' => 'OLD-SN',
                    'assy_serial_number' => '',
                    'new_serial_number' => '',
                    'reason' => '',
                ], $extraRow),
            ], JSON_UNESCAPED_UNICODE),
        ]);

        return [$workorder, $component, $logCard];
    }

    private function finishPostDisassemblyInspection($workorder): void
    {
        $generalTask = GeneralTask::query()->create([
            'name' => 'QA Log Card lock',
            'sort_order' => 1,
        ]);
        $task = Task::query()->create([
            'name' => 'Post Disassembly inspection',
            'general_task_id' => $generalTask->id,
            'task_has_start_date' => 0,
        ]);
        Main::query()->create([
            'workorder_id' => $workorder->id,
            'general_task_id' => $generalTask->id,
            'task_id' => $task->id,
            'date_finish' => '2026-08-06',
        ]);
    }

    private function rows(LogCard $logCard): array
    {
        return json_decode((string) $logCard->fresh()->component_data, true);
    }
}
