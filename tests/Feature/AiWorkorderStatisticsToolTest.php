<?php

namespace Tests\Feature;

use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\Task;
use App\Models\Workorder;
use App\Services\Ai\AiAgentService;
use App\Services\Ai\Tools\WorkorderStatisticsTool;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\BuildsDomainData;
use Tests\TestCase;

class AiWorkorderStatisticsToolTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_summary_counts_technician_customer_intersection_by_instruction(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $technician = $this->createUserWithRole('Technician', [
            'name' => 'Alpha Statistics Technician',
            'selection_name_order' => 'last_first',
        ]);
        $otherTechnician = $this->createUserWithRole('Technician', [
            'name' => 'Beta Statistics Technician',
            'selection_name_order' => 'last_first',
        ]);
        $customer = $this->createCustomer(['name' => 'Statistics Customer Alpha']);
        $otherCustomer = $this->createCustomer(['name' => 'Statistics Customer Beta']);
        $overhaul = $this->createInstruction(['name' => 'Overhaul Statistics']);
        $repair = $this->createInstruction(['name' => 'Repair Statistics']);

        $this->createStatisticsWorkorder($technician->id, $customer->id, $overhaul->id);
        $this->createStatisticsWorkorder($technician->id, $customer->id, $overhaul->id);
        $this->createStatisticsWorkorder($technician->id, $customer->id, $repair->id);
        $this->createStatisticsWorkorder($technician->id, $otherCustomer->id, $repair->id);
        $this->createStatisticsWorkorder($otherTechnician->id, $customer->id, $repair->id);

        $result = app(WorkorderStatisticsTool::class)->run($admin, [
            'mode' => 'summary',
            'technician_query' => 'Technician Alpha',
            'customer_query' => 'Customer Alpha',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame(3, $result['total_workorders']);
        $this->assertSame([
            ['name' => 'Overhaul Statistics', 'workorders' => 2],
            ['name' => 'Repair Statistics', 'workorders' => 1],
        ], $result['instruction_counts']);
        $this->assertSame([
            ['name' => 'Statistics Technician Alpha', 'workorders' => 3],
        ], $result['technician_counts']);
        $this->assertSame([
            ['name' => 'Statistics Customer Alpha', 'workorders' => 3],
        ], $result['customer_counts']);
    }

    public function test_turnaround_returns_requested_longest_top_and_omitted_counts(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $technician = $this->createUserWithRole('Technician', [
            'name' => 'Turnaround Technician',
            'selection_name_order' => 'first_last',
        ]);
        $customer = $this->createCustomer(['name' => 'Turnaround Customer']);
        $instruction = $this->createInstruction(['name' => 'Repair Turnaround']);
        [$submissionTask, $generalTask] = $this->createFinalSubmissionTask();

        $longest = $this->createStatisticsWorkorder($technician->id, $customer->id, $instruction->id, '2026-01-01');
        $middle = $this->createStatisticsWorkorder($technician->id, $customer->id, $instruction->id, '2026-01-01');
        $shortest = $this->createStatisticsWorkorder($technician->id, $customer->id, $instruction->id, '2026-01-01');
        $this->createStatisticsWorkorder($technician->id, $customer->id, $instruction->id, '2026-01-01');
        $withoutOpenDate = $this->createStatisticsWorkorder($technician->id, $customer->id, $instruction->id, null);
        $submissionBeforeOpen = $this->createStatisticsWorkorder($technician->id, $customer->id, $instruction->id, '2026-01-10');

        $this->addSubmission($longest, $submissionTask, $generalTask, '2026-01-21');
        $this->addSubmission($middle, $submissionTask, $generalTask, '2026-01-11');
        $this->addSubmission($middle, $submissionTask, $generalTask, '2026-03-01', true);
        $this->addSubmission($shortest, $submissionTask, $generalTask, '2026-01-05');
        $this->addSubmission($withoutOpenDate, $submissionTask, $generalTask, '2026-01-15');
        $this->addSubmission($submissionBeforeOpen, $submissionTask, $generalTask, '2026-01-08');

        $result = app(WorkorderStatisticsTool::class)->run($admin, [
            'mode' => 'turnaround',
            'technician_query' => 'Turnaround Technician',
            'customer_query' => 'Turnaround Customer',
            'ranking' => 'longest',
            'limit' => 2,
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame(6, $result['matching_workorders']);
        $this->assertSame(3, $result['eligible_workorders']);
        $this->assertSame(11.3, $result['average_days']);
        $this->assertSame(10.0, $result['median_days']);
        $this->assertSame([
            'missing_open_date' => 1,
            'missing_final_submission_date' => 1,
            'submission_before_open_date' => 1,
        ], $result['omitted']);
        $this->assertSame([$longest->number, $middle->number], array_column($result['workorders'], 'workorder_number'));
        $this->assertSame([20, 10], array_column($result['workorders'], 'duration_days'));
        $this->assertSame('01/Jan/2026', $result['workorders'][0]['open_date']);
        $this->assertSame('21/Jan/2026', $result['workorders'][0]['submitted_for_final_inspection_date']);
        $this->assertStringContainsString('/mains/' . $longest->id, $result['workorders'][0]['open_url']);
    }

    public function test_active_statistics_include_team_leader_assignees_and_exclude_completed_workorders(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $teamLeader = $this->createUserWithRole('Team Leader', [
            'name' => 'Lipikhin Test Andrey',
            'selection_name_order' => 'last_first',
        ]);
        $customer = $this->createCustomer(['name' => 'Active Statistics Customer']);
        $instruction = $this->createInstruction(['name' => 'Active Statistics Repair']);
        $this->createStatisticsWorkorder($teamLeader->id, $customer->id, $instruction->id);
        $this->createStatisticsWorkorder($teamLeader->id, $customer->id, $instruction->id);
        $completed = $this->createStatisticsWorkorder($teamLeader->id, $customer->id, $instruction->id);

        $generalTask = GeneralTask::query()->create([
            'name' => 'Statistics Complete Stage ' . uniqid(),
            'sort_order' => 999,
        ]);
        $completedTask = Task::query()->firstOrCreate(['name' => 'Completed'], [
            'general_task_id' => $generalTask->id,
            'task_has_start_date' => false,
        ]);
        Main::query()->create([
            'workorder_id' => $completed->id,
            'task_id' => $completedTask->id,
            'general_task_id' => $completedTask->general_task_id,
            'date_finish' => '2026-02-01',
            'ignore_row' => false,
        ]);

        $result = app(WorkorderStatisticsTool::class)->run($admin, [
            'mode' => 'summary',
            'technician_query' => 'Test Andrey Lipikhin',
            'status' => 'active',
            'shop_roles_only' => true,
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame(2, $result['total_workorders']);
        $this->assertSame(['Technician', 'Team Leader'], $result['filters']['assignee_roles']);
        $this->assertSame([
            ['name' => 'Test Andrey Lipikhin', 'workorders' => 2],
        ], $result['technician_counts']);
    }

    public function test_statistics_tool_rejects_non_system_admin(): void
    {
        $technician = $this->createUserWithRole('Technician');

        $result = app(WorkorderStatisticsTool::class)->run($technician, [
            'mode' => 'summary',
        ]);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('System Admin', $result['message']);
    }

    public function test_ai_agent_registers_and_executes_statistics_for_system_admin(): void
    {
        $admin = $this->createUserWithRole('Admin');

        Http::fakeSequence()
            ->push([
                'id' => 'statistics-response-1',
                'output' => [[
                    'type' => 'function_call',
                    'call_id' => 'statistics-call-1',
                    'name' => 'getWorkorderStatistics',
                    'arguments' => json_encode([
                        'mode' => 'summary',
                        'technician_query' => 'Statistics Technician',
                        'customer_query' => 'Statistics Customer',
                    ]),
                ]],
            ], 200)
            ->push([
                'id' => 'statistics-response-2',
                'output_text' => 'Workorder statistics completed.',
            ], 200);

        $result = app(AiAgentService::class)->handle(
            user: $admin,
            sessionKey: 'workorder-statistics-registration',
            userMessage: 'How many overhauls and repairs does this technician have for this customer?',
            pageContext: [],
            confirmAction: []
        );

        $this->assertTrue($result['ok']);
        $this->assertSame('Workorder statistics completed.', $result['reply']);
        Http::assertSent(function ($request): bool {
            $tools = collect($request->data()['tools'] ?? []);
            $systemPrompt = (string) data_get($request->data(), 'input.0.content', '');

            return $tools->contains(fn (array $tool): bool => ($tool['name'] ?? null) === 'getWorkorderStatistics')
                && str_contains($systemPrompt, 'time from Open Date until submission for Final Inspection');
        });
    }

    public function test_ai_agent_does_not_expose_statistics_to_technician(): void
    {
        $technician = $this->createUserWithRole('Technician');
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'id' => 'statistics-technician-response',
                'output_text' => 'I can help with your visible workorders.',
            ], 200),
        ]);

        app(AiAgentService::class)->handle(
            user: $technician,
            sessionKey: 'workorder-statistics-technician',
            userMessage: 'Show statistics for every technician.',
            pageContext: [],
            confirmAction: []
        );

        Http::assertSent(function ($request): bool {
            $toolNames = collect($request->data()['tools'] ?? [])->pluck('name');
            $systemPrompt = (string) data_get($request->data(), 'input.0.content', '');

            return ! $toolNames->contains('getWorkorderStatistics')
                && ! str_contains($systemPrompt, 'getWorkorderStatistics');
        });
    }

    private function createStatisticsWorkorder(
        int $technicianId,
        int $customerId,
        int $instructionId,
        ?string $openAt = '2026-01-01'
    ): Workorder {
        return $this->createWorkorder([
            'number' => random_int(800000, 899999),
            'user_id' => $technicianId,
            'customer_id' => $customerId,
            'instruction_id' => $instructionId,
            'open_at' => $openAt,
        ]);
    }

    /** @return array{Task, GeneralTask} */
    private function createFinalSubmissionTask(): array
    {
        $generalTask = GeneralTask::query()->create([
            'name' => 'Statistics Final Inspection ' . uniqid(),
            'sort_order' => 950,
        ]);
        $task = Task::query()->create([
            'name' => 'WO Submitted for Final Inspection ' . uniqid(),
            'general_task_id' => $generalTask->id,
            'task_has_start_date' => false,
        ]);

        return [$task, $generalTask];
    }

    private function addSubmission(
        Workorder $workorder,
        Task $task,
        GeneralTask $generalTask,
        string $date,
        bool $ignored = false
    ): void {
        Main::query()->create([
            'workorder_id' => $workorder->id,
            'task_id' => $task->id,
            'general_task_id' => $generalTask->id,
            'date_finish' => $date,
            'ignore_row' => $ignored,
        ]);
    }
}
