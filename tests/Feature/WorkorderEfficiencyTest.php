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
        foreach ([$base.'-02', $base.'- 03', $base.'-04', $base.'-4', $base.'-3-/-5-/-7-8'] as $number) {
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
        $this->assertSame('D22698100', $service->partFamily('D22698100-3-/-5-/-7-8'));
        $this->assertSame('D23197000', $service->partFamily('D23197000'));
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
}
