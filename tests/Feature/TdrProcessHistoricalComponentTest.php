<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TdrProcessHistoricalComponentTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_existing_process_forms_show_a_soft_deleted_part_without_restoring_it(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $part = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'ipl_num' => '10-320',
            'part_number' => '2821-0310',
            'name' => 'Historical fitting',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $part->id,
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        $this->actingAs($admin)->withSession([
            'auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword(),
        ]);

        $before = $this->get(route('tdrs.show', $workorder->id))->assertOk();
        $this->assertStringContainsString('data-tdr-id="'.$tdr->id.'"', $this->tdrTableHtml($before->getContent()));

        $part->delete();
        $this->assertNull(Component::query()->find($part->id));
        $after = $this->get(route('tdrs.show', $workorder->id))->assertOk();
        $this->assertStringNotContainsString('data-tdr-id="'.$tdr->id.'"', $this->tdrTableHtml($after->getContent()));

        foreach (['STRESS RELIEF', 'PAINT', 'NDT'] as $sheet) {
            $name = ProcessName::query()->create([
                'name' => 'Historical '.$sheet,
                'process_sheet_name' => $sheet,
                'form_number' => 'QA',
                'print_form' => true,
                'show_in_process_picker' => true,
            ]);
            $process = Process::query()->create([
                'process_names_id' => $name->id,
                'process' => 'Process archived part',
            ]);
            ManualProcess::query()->create([
                'manual_id' => $workorder->unit->manual_id,
                'processes_id' => $process->id,
            ]);
            $row = TdrProcess::query()->create([
                'tdrs_id' => $tdr->id,
                'process_names_id' => $name->id,
                'processes' => [$process->id],
                'sort_order' => 1,
            ]);

            $this->get(route('tdr-processes.show', [
                'tdr_process' => $row->id,
                'process_id' => $process->id,
                'omit_form_header_date' => 1,
            ]))->assertOk()->assertSee('10-320')->assertSee('2821-0310');
        }

        $this->assertTrue(Component::withTrashed()->findOrFail($part->id)->trashed());
    }

    private function tdrTableHtml(string $html): string
    {
        $start = strpos($html, 'id="tdr_process_Table"');
        $this->assertNotFalse($start);
        $end = strpos($html, '</table>', $start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }
}
