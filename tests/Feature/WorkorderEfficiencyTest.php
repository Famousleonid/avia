<?php

namespace Tests\Feature;

use App\Models\{GeneralTask, Main, Task};
use App\Services\WorkorderEfficiency;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class WorkorderEfficiencyTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;

    public function test_modifications_share_one_option_and_report_without_merging_different_bases(): void
    {
        $base = '2821A0000';
        $ids = [];
        foreach ([$base.'-02', $base.'- 03', $base.'-04', $base.'-4'] as $number) {
            $unit = $this->createUnit(['part_number' => $number]);
            $ids[] = $this->createWorkorder(['unit_id' => $unit->id])->id;
        }
        $other = $this->createUnit(['part_number' => '2821A0100-03']);
        $this->createWorkorder(['unit_id' => $other->id]);
        $service = app(WorkorderEfficiency::class);
        $report = $service->report($base.'-02');
        $this->assertSame($base, $report['partNumber']);
        $this->assertContains($base, $report['partNumbers']);
        $this->assertNotContains($base.'-04', $report['partNumbers']);
        $this->assertEqualsCanonicalizing($ids, $report['workorders']->modelKeys());
        $this->assertSame('2821', $service->partFamily('2821-0202'));
        $this->assertSame('2821A0100', $service->partFamily('2821A0100-03'));
        $this->assertSame('D23188000', $service->partFamily('D23188000-4'));
        $this->assertSame('D23188000', $service->partFamily('D23188000-7'));
        $this->assertSame('D22698100-3-/-5-/-7', $service->partFamily('D22698100-3-/-5-/-7-8'));
        $this->assertSame('D23197000', $service->partFamily('D23197000'));
    }

    public function test_selecting_a_multi_dash_family_keeps_its_full_base_and_matching_workorders(): void
    {
        $ids = [];
        foreach (['190-70150-003', '190-70150-004'] as $number) {
            $unit = $this->createUnit(['part_number' => $number]);
            $ids[] = $this->createWorkorder(['unit_id' => $unit->id])->id;
        }
        $other = $this->createUnit(['part_number' => '190-70250-003']);
        $this->createWorkorder(['unit_id' => $other->id]);
        $service = app(WorkorderEfficiency::class);
        $this->assertSame('190-70150', $service->partFamily('190-70150-003'));
        foreach (['190-70150', '190-70150-003'] as $selection) {
            $report = $service->report($selection);
            $this->assertSame('190-70150', $report['partNumber']);
            $this->assertContains('190-70150', $report['partNumbers']);
            $this->assertContains('190-70250', $report['partNumbers']);
            $this->assertEqualsCanonicalizing($ids, $report['workorders']->modelKeys());
        }
        $this->actingAs($this->createUserWithRole('Admin'))
            ->get(route('workorders.status.efficiency', ['part_number' => '190-70150']))
            ->assertOk()->assertViewHas('partNumber', '190-70150')
            ->assertViewHas('workorders', fn ($workorders) => $workorders->modelKeys() === $ids);
    }

    public function test_disassembly_uses_header_number_and_working_days_excluding_unfinished_and_skipped(): void
    {
        $unit = $this->createUnit(['part_number' => 'EFF-'.uniqid()]);
        $unused = $this->createUnit(['part_number' => 'UNUSED-'.uniqid()]);
        $tech = $this->createUserWithRole('Technician');
        $other = $this->createUserWithRole('Technician');
        $general = GeneralTask::firstOrCreate(['name' => 'Disassembly']);
        $task = Task::firstOrCreate(['name' => '2. Disassembly'], ['general_task_id' => $general->id, 'task_has_start_date' => true]);
        foreach ([[0, false, $tech], [4, false, $tech], [20, true, $tech], [null, false, $tech], [8, false, $other]] as [$days, $skip, $user]) {
            $wo = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $user->id]);
            Main::create(['workorder_id' => $wo->id, 'task_id' => $task->id, 'general_task_id' => $general->id,
                'user_id' => $other->id, 'date_start' => '2026-01-01',
                'date_finish' => $days === null ? null : now()->parse('2026-01-01')->addDays($days)->toDateString(), 'ignore_row' => $skip]);
        }
        $unit->delete(); // Retired catalog entries still belong to historical workorders.
        $report = app(WorkorderEfficiency::class)->report($unit->part_number);
        $this->assertCount(5, $report['workorders']);
        $this->assertContains(app(WorkorderEfficiency::class)->partFamily($unit->part_number), $report['partNumbers']);
        $this->assertNotContains(app(WorkorderEfficiency::class)->partFamily($unused->part_number), $report['partNumbers']);
        $this->assertEquals(8 / 3, $report['average']); // (0 + 2 + 6) / 3, not average of technician averages.
        $this->assertEquals(1, $report['rows']->firstWhere('name', $tech->selection_name)['average']);
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin)->get(route('workorders.status.efficiency', ['part_number' => $unit->part_number]))
            ->assertOk()->assertSee('Average, days')->assertSee('efficiency-scroll');
    }

    public function test_missing_disassembly_dates_use_open_date_and_submission_then_inspection(): void
    {
        $unit = $this->createUnit(['part_number' => 'EFF-FALLBACK-001']);
        $general = GeneralTask::firstOrCreate(['name' => 'Disassembly']);
        $tasks = [];
        foreach (['2. Disassembly', '3. WO Submitted to inspection', 'Post Disassembly inspection'] as $name) {
            $tasks[$name] = Task::firstOrCreate(['name' => $name], ['general_task_id' => $general->id, 'task_has_start_date' => $name === '2. Disassembly']);
        }
        // Disassembly start/finish, submission, inspection, ignored inspection, expected days, source.
        $cases = [
            [null, null, '2026-01-06', '2026-01-08', false, 2, 'WO Submitted to inspection'],
            [null, null, null, '2026-01-08', false, 4, 'Post Disassembly inspection'],
            ['2026-01-05', null, '2026-01-06', null, false, 2, 'WO Open date'],
            [null, '2026-01-07', '2026-01-06', null, false, 2, 'WO Open date'],
            ['2026-01-05', '2026-01-06', '2026-01-08', null, false, 1, 'Disassembly:'],
            [null, null, null, null, false, null, 'Dates not completed'],
            [null, null, null, '2026-01-08', true, null, 'Dates not completed'],
            [null, null, '2026-01-01', null, false, null, 'invalid date range'],
        ];
        $expected = [];
        foreach ($cases as [$start, $finish, $submitted, $inspected, $ignoreInspection, $days, $source]) {
            $wo = $this->createWorkorder(['unit_id' => $unit->id, 'open_at' => '2026-01-02 15:00:00']);
            foreach ([['2. Disassembly', $start, $finish, false], ['3. WO Submitted to inspection', null, $submitted, false], ['Post Disassembly inspection', null, $inspected, $ignoreInspection]] as [$name, $from, $to, $ignored]) {
                Main::create(['workorder_id' => $wo->id, 'task_id' => $tasks[$name]->id,
                    'general_task_id' => $general->id, 'date_start' => $from, 'date_finish' => $to, 'ignore_row' => $ignored]);
            }
            $expected[$wo->id] = [$days, $source];
        }
        // A historical WO with no task rows stays incomplete until an inspection date exists.
        $missing = $this->createWorkorder(['unit_id' => $unit->id, 'open_at' => '2024-02-02']);
        $expected[$missing->id] = [null, 'Dates not completed'];
        $report = app(WorkorderEfficiency::class)->report('EFF-FALLBACK');
        $cells = [];
        foreach ($report['rows'] as $row) {
            $cells += $row['cells'];
        }
        foreach ($expected as $id => [$days, $source]) {
            $this->assertSame($days, $cells[$id]['days']);
            $this->assertStringContainsString($source, $cells[$id]['note']);
        }
        $this->assertEquals(2.2, $report['average']);
        $this->actingAs($this->createUserWithRole('Admin'))
            ->get(route('workorders.status.efficiency', ['part_number' => 'EFF-FALLBACK']))
            ->assertOk()->assertSee('WO Open date')->assertSee('02/Jan/2026')->assertSee('Post Disassembly inspection');
    }
}
