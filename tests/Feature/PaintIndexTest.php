<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class PaintIndexTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_paint_index_defaults_to_date_start_filter_and_marks_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $withDateStart = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 880001,
            'approve_at' => '2026-06-01',
            'open_at' => null,
        ]);
        $withoutDateStart = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 880002,
            'approve_at' => '2026-06-01',
            'open_at' => '2026-05-28',
        ]);

        $this->attachPaintDetail($withDateStart, 'PAINT-DATE-START', '2026-06-03');
        $this->attachPaintDetail($withoutDateStart, 'PAINT-NO-DATE-START');

        $response = $this->actingAs($admin)->get(route('paint.index'));

        $response->assertOk();
        $response->assertSee('id="paintTableState" class="paint-table-state is-loading"', false);
        $response->assertSee('Loading', false);
        $response->assertSee('paint-loading-dots', false);
        $response->assertSee('id="paintOnlyDateStartRows" checked', false);
        $response->assertSee('Date start only', false);
        $response->assertSee('data-paint-has-date-start="1"', false);
        $response->assertSee('data-paint-has-date-start="0"', false);
        $response->assertDontSee('paintOnlyArrivalRows', false);
        $response->assertDontSee('data-paint-has-arrival', false);
        $response->assertSee('PAINT-DATE-START', false);
        $response->assertSee('PAINT-NO-DATE-START', false);
    }

    private function attachPaintDetail(Workorder $workorder, string $partNumber, ?string $dateStart = null): void
    {
        $processName = ProcessName::query()->firstOrCreate(
            ['name' => 'Paint'],
            [
                'process_sheet_name' => 'PAINT',
                'form_number' => 'PAINT',
                'print_form' => true,
                'show_in_process_picker' => true,
            ]
        );

        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => $partNumber,
            'name' => 'Paint detail',
            'ipl_num' => $partNumber,
            'eff_code' => 'ALL',
        ]);

        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'SN-' . $partNumber,
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'date_start' => $dateStart,
        ]);
    }
}
