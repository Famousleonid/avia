<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Tdr;
use App\Models\User;
use App\Models\Workorder;
use App\Services\Ai\AiAgentService;
use App\Services\Ai\Tools\SearchActivityLogsTool;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class AiActivityLogSearchToolTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_system_admin_can_combine_workorder_part_actor_event_and_date_filters(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin');
        $actor = $this->createUserWithRole('Technician', [
            'name' => 'Audit Technician',
            'email' => 'audit.technician@example.test',
        ]);
        $workorder = $this->createWorkorder(['number' => 812345]);
        $otherWorkorder = $this->createWorkorder(['number' => 812346]);
        $component = $this->createComponentForWorkorder($workorder, '1840-0302', '8-230');
        $otherComponent = $this->createComponentForWorkorder($workorder, '1840-0999', '8-231');

        $matchingTdr = $this->createTdrWithoutAudit($workorder, $component);
        $otherPartTdr = $this->createTdrWithoutAudit($workorder, $otherComponent);
        $otherWorkorderTdr = $this->createTdrWithoutAudit($otherWorkorder, $component);

        $this->createTdrActivity($matchingTdr, $actor, now()->subHour());
        $this->createTdrActivity($otherPartTdr, $actor, now()->subHour());
        $this->createTdrActivity($otherWorkorderTdr, $actor, now()->subHour());

        $result = app(SearchActivityLogsTool::class)->run($systemAdmin, [
            'workorder_number' => 'WO 812345',
            'part_number' => '1840-0302',
            'exact_part_number' => true,
            'actor' => 'Audit Technician',
            'event' => 'created',
            'area' => 'parts',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['count']);
        $this->assertSame('Audit Technician', $result['matches'][0]['actor']);
        $this->assertSame('created', $result['matches'][0]['event']);
        $this->assertSame([812345], $result['matches'][0]['workorder_numbers']);
        $this->assertContains('1840-0302', $result['matches'][0]['part_numbers']);
        $this->assertContains('part', $result['matches'][0]['changed_fields']);
        $this->assertStringContainsString('/mains/', (string) $result['matches'][0]['open_url']);

        $prefixedResult = app(SearchActivityLogsTool::class)->run($systemAdmin, [
            'workorder_number' => 'W812345',
            'part_number' => '1840-0302',
            'event' => 'created',
        ]);
        $this->assertSame(1, $prefixedResult['count']);
    }

    public function test_tool_finds_bushing_part_number_inside_before_after_snapshot(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin');
        $actor = $this->createUserWithRole('Technician', ['name' => 'Bushing Auditor']);
        $workorder = $this->createWorkorder(['number' => 823456]);

        Activity::query()->create([
            'log_name' => 'workorder',
            'description' => 'Bushing data updated',
            'event' => 'updated',
            'subject_type' => Workorder::class,
            'subject_id' => $workorder->id,
            'causer_type' => User::class,
            'causer_id' => $actor->id,
            'properties' => [
                'source' => 'wo_bushings',
                'snapshot_before' => ['rows' => []],
                'snapshot_after' => [
                    'rows' => [
                        ['component_id' => 999999, 'part_number' => 'BUSH-OS-300', 'qty' => 2],
                    ],
                ],
                'attributes' => ['bushing_save' => 'Updated: 1 row(s), qty 2'],
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = app(SearchActivityLogsTool::class)->run($systemAdmin, [
            'workorder_number' => '823456',
            'part_number' => 'BUSH-OS-300',
            'area' => 'bushings',
            'event' => 'updated',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['count']);
        $this->assertSame('Bushings', $result['matches'][0]['area']);
        $this->assertSame('Bushing Auditor', $result['matches'][0]['actor']);
        $this->assertContains('BUSH-OS-300', $result['matches'][0]['part_numbers']);
    }

    public function test_tool_finds_who_added_parts_to_a_specific_manual(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin');
        $actor = $this->createUserWithRole('Technician', ['name' => 'Manual Parts Editor']);
        $manual = $this->createManual(['number' => '32-11-01RM']);
        $otherManual = $this->createManual(['number' => '32-11-02']);

        $component = Component::withoutEvents(fn (): Component => Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'MANUAL-PN-100',
            'name' => 'Manual Audit Part',
            'ipl_num' => '8-100',
            'eff_code' => 'ALL',
        ]));
        $otherComponent = Component::withoutEvents(fn (): Component => Component::query()->create([
            'manual_id' => $otherManual->id,
            'part_number' => 'MANUAL-PN-100',
            'name' => 'Other Manual Audit Part',
            'ipl_num' => '8-100',
            'eff_code' => 'ALL',
        ]));

        foreach ([$component, $otherComponent] as $loggedComponent) {
            Activity::query()->create([
                'log_name' => 'component',
                'description' => 'created',
                'event' => 'created',
                'subject_type' => Component::class,
                'subject_id' => $loggedComponent->id,
                'causer_type' => User::class,
                'causer_id' => $actor->id,
                'properties' => [
                    'attributes' => [
                        'manual_id' => $loggedComponent->manual_id,
                        'part_number' => $loggedComponent->part_number,
                        'name' => $loggedComponent->name,
                        'ipl_num' => $loggedComponent->ipl_num,
                    ],
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $result = app(SearchActivityLogsTool::class)->run($systemAdmin, [
            'manual_number' => 'CMM 32-11-01',
            'area' => 'components',
            'event' => 'created',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['count']);
        $this->assertSame('Manual Parts Editor', $result['matches'][0]['actor']);
        $this->assertSame(['32-11-01RM'], $result['matches'][0]['manual_numbers']);
        $this->assertContains('MANUAL-PN-100', $result['matches'][0]['part_numbers']);
        $this->assertStringContainsString('/manuals/', (string) $result['matches'][0]['manual_url']);
    }

    public function test_tdr_add_part_endpoint_logs_source_workorder_and_actor_for_ai_search(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin', ['name' => 'TDR Parts Editor']);
        $workorder = $this->createWorkorder([
            'number' => 834567,
            'user_id' => $systemAdmin->id,
        ]);

        $response = $this->actingAs($systemAdmin)->postJson(route('components.storeFromInspection'), [
            'current_wo' => $workorder->id,
            'manual_id' => $workorder->unit->manual_id,
            'name' => 'Part Entered From TDR',
            'part_number' => 'TDR-ADD-PN-500',
            'ipl_num' => '8-500',
            'redirect' => route('tdrs.show', $workorder->id),
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $component = Component::query()
            ->where('manual_id', $workorder->unit->manual_id)
            ->where('part_number', 'TDR-ADD-PN-500')
            ->firstOrFail();
        $activity = Activity::query()
            ->where('log_name', 'component')
            ->where('event', 'created')
            ->where('subject_type', Component::class)
            ->where('subject_id', $component->id)
            ->latest('id')
            ->firstOrFail();
        $properties = $activity->properties->toArray();

        $this->assertSame($systemAdmin->id, (int) $activity->causer_id);
        $this->assertSame('tdr_add_part', $properties['source'] ?? null);
        $this->assertSame($workorder->id, (int) ($properties['workorder_id'] ?? 0));

        $result = app(SearchActivityLogsTool::class)->run($systemAdmin, [
            'workorder_number' => 'W834567',
            'part_number' => 'TDR-ADD-PN-500',
            'area' => 'components',
            'event' => 'created',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['count']);
        $this->assertSame('TDR Add Part', $result['matches'][0]['area']);
        $this->assertSame('tdr_add_part', $result['matches'][0]['source']);
        $this->assertSame('TDR Parts Editor', $result['matches'][0]['actor']);
        $this->assertSame([834567], $result['matches'][0]['workorder_numbers']);
    }

    public function test_activity_log_tool_rejects_non_system_admin(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => 0]);

        $result = app(SearchActivityLogsTool::class)->run($roleOnlyAdmin, [
            'workorder_number' => '812345',
        ]);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('System Admin', $result['message']);
    }

    public function test_ai_agent_registers_and_executes_activity_log_search_tool(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin');

        Http::fakeSequence()
            ->push([
                'id' => 'activity-response-1',
                'output' => [[
                    'type' => 'function_call',
                    'call_id' => 'activity-call-1',
                    'name' => 'searchActivityLogs',
                    'arguments' => json_encode([
                        'event' => 'created',
                        'area' => 'parts',
                        'limit' => 5,
                    ]),
                ]],
            ], 200)
            ->push([
                'id' => 'activity-response-2',
                'output_text' => 'Audit search completed.',
            ], 200);

        $result = app(AiAgentService::class)->handle(
            user: $systemAdmin,
            sessionKey: 'activity-log-tool-registration',
            userMessage: 'Who added parts today?',
            pageContext: [],
            confirmAction: []
        );

        $this->assertTrue($result['ok']);
        $this->assertSame('Audit search completed.', $result['reply']);
        Http::assertSent(function ($request): bool {
            $tools = collect($request->data()['tools'] ?? []);

            return $tools->contains(fn (array $tool): bool => ($tool['name'] ?? null) === 'searchActivityLogs');
        });
    }

    private function createComponentForWorkorder(Workorder $workorder, string $partNumber, string $iplNumber): Component
    {
        return Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => $partNumber,
            'name' => 'Audit Part '.$partNumber,
            'ipl_num' => $iplNumber,
            'eff_code' => 'ALL',
        ]);
    }

    private function createTdrWithoutAudit(Workorder $workorder, Component $component): Tdr
    {
        return Tdr::withoutEvents(fn (): Tdr => Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'NSN',
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]));
    }

    private function createTdrActivity(Tdr $tdr, User $actor, $createdAt): Activity
    {
        return Activity::query()->create([
            'log_name' => 'tdr',
            'description' => 'created',
            'event' => 'created',
            'subject_type' => Tdr::class,
            'subject_id' => $tdr->id,
            'causer_type' => User::class,
            'causer_id' => $actor->id,
            'properties' => [
                'attributes' => [
                    'workorder_id' => $tdr->workorder_id,
                    'component_id' => $tdr->component_id,
                    'qty' => $tdr->qty,
                ],
            ],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
