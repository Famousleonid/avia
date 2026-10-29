<?php

namespace Tests\Feature;

use App\Models\QuantumRoLine;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MainQuantumRoTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_ro_details_are_scoped_to_the_exact_wo_and_keep_their_own_dates(): void
    {
        $wo = $this->createWorkorder(['number' => 887321]);
        foreach ([
            ['W887321', 'applied', 'PART-A', '2026-09-20'],
            ['  w887321 ', 'unresolved', 'PART-B', null],
            ['887321', 'dismissed', 'PART-C', '2026-09-22'],
            ['W8873210', 'applied', 'OTHER-WO', '2026-09-25'],
        ] as [$number, $status, $pn, $returned]) {
            QuantumRoLine::create([
                'source_uid' => 'main-ro-test:' . $pn, 'source_hash' => str_repeat('a', 64),
                'wo_number' => $number, 'ro_number' => 'R-TEST', 'pn' => $pn,
                'vendor_name' => '<script>vendor</script>', 'apply_status' => $status,
                'out_date' => '2026-09-10', 'returned_date' => $returned,
                'last_seen_at' => '2026-09-28 09:30:00',
            ]);
        }
        $user = $this->createUserWithRole('Manager');
        $response = $this->actingAs($user)
            ->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()])
            ->getJson(route('mains.quantum-ro-lines', $wo))->assertOk();
        $html = $response->json('html');
        foreach (['PART-A', 'PART-B', 'PART-C', '10/Sep/2026', '20/Sep/2026', '22/Sep/2026', 'Part No.', '&lt;script&gt;'] as $value) {
            $this->assertStringContainsString($value, $html);
        }
        $this->assertStringNotContainsString('OTHER-WO', $html);
        $this->assertStringNotContainsString('<script>', $html);
        preg_match('/<td data-ro-pn="PART-B"[^>]*>.*?<\/td>/s', $html, $row);
        $this->assertNotEmpty($row);
        $this->assertStringNotContainsString('20/Sep/2026', $row[0]);
        $this->assertStringContainsString('Returned: <span>-</span>', $row[0]);
        $this->assertSame(3, substr_count($html, 'class="main-ro-entry"'));
        $this->assertStringContainsString('main-ro-pending', $row[0]);
        $this->assertStringNotContainsString('main-ro-returned', $row[0]);
        $this->assertSame(2, substr_count($html, 'main-ro-returned'));
        $this->assertSame(1, substr_count($html, 'main-ro-pending'));
    }

    public function test_matrix_keeps_serials_and_multiple_ro_details_separate(): void
    {
        \App\Models\ProcessName::create(['name' => 'Matrix process', 'process_sheet_name' => 'Matrix process', 'code' => 'QATEST', 'sp_sort_order' => 1]);
        $lines = collect([
            new QuantumRoLine(['pn' => 'PART-1', 'serial_number' => 'SN1', 'bom_ref' => 'QATEST', 'ro_number' => 'R1']),
            new QuantumRoLine(['pn' => 'PART-1', 'serial_number' => 'SN2', 'bom_ref' => 'QATEST', 'ro_number' => 'R2']),
            new QuantumRoLine(['pn' => 'PART-1', 'serial_number' => 'SN1', 'bom_ref' => 'QATEST', 'ro_number' => 'R3']),
            new QuantumRoLine(['pn' => 'PART-1', 'serial_number' => 'SN1', 'bom_ref' => 'T2', 'ro_number' => 'R4']),
        ]);
        $matrix = app(\App\Services\QuantumRoMatrix::class)->build($lines);
        $this->assertCount(2, $matrix['columns']);
        $this->assertCount(2, $matrix['rows']);
        $this->assertSame('Matrix process', $matrix['rows'][0]['label']);
        $cells = array_values($matrix['rows'][0]['cells']);
        $this->assertSame(['R1', 'R3'], array_map(fn ($line) => $line->ro_number, $cells[0]));
        $this->assertSame('R2', $cells[1][0]->ro_number);
        $this->assertSame('Traveler 2', $matrix['rows'][1]['label']);
    }

    public function test_planned_steps_are_numbered_per_detail_and_completion_includes_unsent_work(): void
    {
        $first = \App\Models\ProcessName::create(['name' => 'Plan First', 'process_sheet_name' => 'QA', 'code' => 'PLAN1', 'sp_sort_order' => 1, 'show_in_process_picker' => true]);
        $second = \App\Models\ProcessName::create(['name' => 'Plan Second', 'process_sheet_name' => 'QA', 'code' => 'PLAN2', 'sp_sort_order' => 2, 'show_in_process_picker' => true]);
        $part = new \App\Models\Component(['part_number' => 'PART-PLAN', 'name' => 'Planned part']);
        $tdr = new \App\Models\Tdr(['serial_number' => 'SN1']);
        $make = function ($id, $name, $attrs = []) {
            $process = new \App\Models\TdrProcess(array_merge(['sort_order' => $id, 'ignore_row' => false], $attrs));
            $process->id = $id;
            return $process->setRelation('processName', $name);
        };
        $tdr->setRelation('component', $part)->setRelation('tdrProcesses', collect([
            $make(1, $first, ['date_start' => '2026-10-01', 'date_finish' => '2026-10-02']),
            $make(2, $second, ['ignore_row' => true]),
            $make(3, $second, ['in_traveler' => true, 'traveler_group' => 1]),
            $make(4, $first, ['in_traveler' => true, 'traveler_group' => 1]),
            $make(5, $second),
        ]));
        $lines = collect([
            new QuantumRoLine(['pn' => 'PART-PLAN', 'serial_number' => 'SN1', 'bom_ref' => 'PLAN1', 'ro_number' => 'R1', 'returned_date' => '2026-10-02']),
            new QuantumRoLine(['pn' => 'PART-PLAN', 'serial_number' => 'SN2', 'bom_ref' => 'PLAN1', 'ro_number' => 'R2', 'returned_date' => '2026-10-02']),
        ]);
        $matrix = app(\App\Services\QuantumRoMatrix::class)->build($lines, collect([$tdr]));
        $key = json_encode(['PART-PLAN', 'SN1']);
        $this->assertFalse($matrix['columns'][$key]['complete']);
        $this->assertTrue($matrix['columns'][json_encode(['PART-PLAN', 'SN2'])]['complete']);
        $rows = collect($matrix['rows'])->keyBy('label');
        $this->assertSame([2], $rows['Traveler 1']['pending'][$key]);
        $this->assertSame([3], $rows['Plan Second']['pending'][$key]);
        foreach ($tdr->tdrProcesses as $process) $process->date_finish = '2026-10-03';
        $finished = app(\App\Services\QuantumRoMatrix::class)->build($lines, collect([$tdr]));
        $this->assertTrue($finished['columns'][$key]['complete']);
        $lines->last()->returned_date = null;
        $unfinishedRo = app(\App\Services\QuantumRoMatrix::class)->build($lines, collect([$tdr]));
        $this->assertFalse($unfinishedRo['columns'][json_encode(['PART-PLAN', 'SN2'])]['complete']);
    }

    public function test_ro_endpoint_shows_unsent_detail_without_any_quantum_lines(): void
    {
        $wo = $this->createWorkorder();
        $part = \App\Models\Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'PLAN-ONLY', 'name' => 'No RO yet', 'ipl_num' => '1-1']);
        $tdr = \App\Models\Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id, 'serial_number' => 'PLAN-SN', 'qty' => 1]);
        $name = \App\Models\ProcessName::create(['name' => 'Planned operation', 'process_sheet_name' => 'QA', 'show_in_process_picker' => true]);
        \App\Models\TdrProcess::create(['tdrs_id' => $tdr->id, 'process_names_id' => $name->id, 'sort_order' => 1]);
        $user = $this->createUserWithRole('Manager');
        $html = $this->actingAs($user)->getJson(route('mains.quantum-ro-lines', $wo))->assertOk()->json('html');
        $this->assertStringContainsString('PLAN-ONLY', $html);
        $this->assertStringContainsString('Planned operation', $html);
        $this->assertStringContainsString('Process 1: not sent, RO not assigned', $html);
        $this->assertStringNotContainsString('main-ro-part-complete', $html);
    }

    public function test_internal_operations_show_at_with_their_own_dates_and_status(): void
    {
        $wo = $this->createWorkorder();
        $part = \App\Models\Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => 'LOCAL-AT', 'name' => 'Repair Sleeve', 'ipl_num' => '1-1']);
        $tdr = \App\Models\Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id, 'serial_number' => 'NSN', 'qty' => 1]);
        $name = \App\Models\ProcessName::create(['name' => 'QA Internal', 'process_sheet_name' => 'QA', 'show_in_process_picker' => true]);
        $process = \App\Models\TdrProcess::create(['tdrs_id' => $tdr->id, 'process_names_id' => $name->id, 'repair_order' => 'AT',
            'date_start' => '2026-07-28', 'date_finish' => '2026-08-01', 'sort_order' => 1]);
        $user = $this->createUserWithRole('Manager');
        $read = fn () => $this->actingAs($user)->getJson(route('mains.quantum-ro-lines', $wo))->assertOk()->json('html');
        $html = $read();
        $this->assertStringContainsString('>AT</button>', $html);
        $this->assertStringContainsString('28/Jul/2026', $html);
        $this->assertStringContainsString('01/Aug/2026', $html);
        $this->assertSame(3, substr_count($html, 'class="main-ro-part-complete"'));
        $this->assertStringContainsString('main-ro-returned', $html);
        $process->update(['repair_order' => null, 'date_finish' => null]);
        $html = $read();
        $this->assertStringContainsString('>AT</button>', $html);
        $this->assertStringContainsString('main-ro-pending', $html);
        $this->assertStringNotContainsString('main-ro-part-complete', $html);
        $this->assertStringContainsString('Returned: <span>-</span>', $html);
        $process->update(['date_start' => null]);
        $html = $read();
        $this->assertStringNotContainsString('>AT</button>', $html);
        $this->assertStringContainsString('Process 1: not sent, RO not assigned', $html);
    }

    public function test_external_or_source_backed_operations_are_not_invented_as_at(): void
    {
        $name = \App\Models\ProcessName::create(['name' => 'QA External', 'process_sheet_name' => 'QA', 'code' => 'QAEXT', 'show_in_process_picker' => true]);
        $part = new \App\Models\Component(['part_number' => 'PART-EXT', 'name' => 'External part']);
        $tdr = new \App\Models\Tdr(['serial_number' => 'SN1']);
        $process = new \App\Models\TdrProcess(['date_start' => '2026-07-28', 'sort_order' => 1]);
        $process->id = 123;
        $process->setRelation('processName', $name);
        $tdr->setRelation('component', $part)->setRelation('tdrProcesses', collect([$process]));
        $line = new QuantumRoLine(['pn' => 'PART-EXT', 'serial_number' => 'SN1', 'bom_ref' => 'QAEXT', 'ro_number' => 'R123']);
        $builder = app(\App\Services\QuantumRoMatrix::class);
        $matrix = $builder->build(collect([$line]), collect([$tdr]));
        $this->assertEmpty($matrix['rows'][0]['internal'] ?? []);
        $process->vendor_id = 123;
        $this->assertEmpty($builder->build(collect(), collect([$tdr]))['rows']);
        $process->vendor_id = null;
        $process->repair_order = 'R124';
        $this->assertEmpty($builder->build(collect(), collect([$tdr]))['rows']);
        $process->repair_order = 'AT';
        $matrix = $builder->build(collect([$line]), collect([$tdr]));
        $key = json_encode(['PART-EXT', 'SN1']);
        $this->assertCount(1, $matrix['rows'][0]['cells'][$key]);
        $this->assertCount(1, $matrix['rows'][0]['internal'][$key]);
    }

    public function test_applied_ro_with_empty_serial_joins_the_actual_tdr_part_column(): void
    {
        $wo = $this->createWorkorder();
        $part = \App\Models\Component::create(['manual_id' => $wo->unit->manual_id, 'part_number' => '300-QA-SLEEVE', 'name' => 'Repair Sleeve', 'ipl_num' => '1-1']);
        $tdr = \App\Models\Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id, 'serial_number' => 'NSN', 'qty' => 1]);
        foreach (['AT', 'R-QA-NDT', 'R-QA-CAD'] as $index => $ro) {
            $name = \App\Models\ProcessName::create(['name' => 'QA Sleeve '.$index, 'code' => 'SLEEVE'.$index, 'process_sheet_name' => 'QA', 'show_in_process_picker' => true]);
            $process = \App\Models\TdrProcess::create(['tdrs_id' => $tdr->id, 'process_names_id' => $name->id, 'sort_order' => $index,
                'repair_order' => $ro, 'date_start' => '2026-07-28', 'date_finish' => $index ? '2026-08-20' : null]);
            if ($index) QuantumRoLine::create([
                'source_uid' => 'sleeve-matrix:'.$index, 'source_hash' => str_repeat('b', 64), 'wo_number' => 'W'.$wo->number,
                'pn' => $part->part_number, 'serial_number' => null, 'description' => 'Quantum sleeve', 'bom_ref' => $name->code,
                'ro_number' => $ro, 'out_date' => '2026-08-12', 'returned_date' => '2026-08-21', 'apply_status' => 'applied',
                'applied_target_table' => 'tdr_processes', 'applied_target_id' => $process->id,
                'applied_targets' => $index === 1 ? [['table' => 'tdr_processes', 'id' => $process->id]] : null,
            ]);
        }
        $html = $this->actingAs($this->createUserWithRole('Manager'))->getJson(route('mains.quantum-ro-lines', $wo))->assertOk()->json('html');
        $this->assertSame(3, substr_count($html, '<th scope="col"'));
        $this->assertSame(3, substr_count($html, 'data-ro-serial="NSN"'));
        foreach (['AT', 'R-QA-NDT', 'R-QA-CAD'] as $ro) $this->assertStringContainsString('>'.$ro.'</button>', $html);
        $this->assertStringContainsString('12/Aug/2026', $html);
        $this->assertStringContainsString('21/Aug/2026', $html);
        $this->assertStringNotContainsString('20/Aug/2026', $html);
        $this->assertStringNotContainsString('main-ro-part-complete', $html);
    }

    public function test_missing_serial_resolution_requires_one_verified_applied_identity(): void
    {
        $name = \App\Models\ProcessName::create(['name' => 'QA Link', 'code' => 'QALINK', 'process_sheet_name' => 'QA', 'show_in_process_picker' => true]);
        $makeTdr = function ($id, $serial) use ($name) {
            $process = new \App\Models\TdrProcess(['repair_order' => 'AT', 'date_finish' => '2026-08-01']);
            $process->id = $id;
            $process->setRelation('processName', $name);
            return (new \App\Models\Tdr(['serial_number' => $serial]))
                ->setRelation('component', new \App\Models\Component(['part_number' => 'P-LINK', 'name' => 'Linked part']))
                ->setRelation('tdrProcesses', collect([$process]));
        };
        $tdrs = collect([$makeTdr(101, 'SN1'), $makeTdr(102, 'SN2')]);
        $base = ['pn' => 'P-LINK', 'bom_ref' => 'QALINK', 'ro_number' => 'R1', 'apply_status' => 'applied',
            'applied_targets' => [['table' => 'tdr_processes', 'id' => 101]], 'returned_date' => '2026-08-01'];
        $builder = app(\App\Services\QuantumRoMatrix::class);
        foreach ([
            ['serial_number' => 'SN-OTHER'],
            ['apply_status' => 'unresolved'],
            ['applied_targets' => [['table' => 'tdr_processes', 'id' => 999]]],
            ['applied_targets' => [['table' => 'tdr_processes', 'id' => 101], ['table' => 'tdr_processes', 'id' => 102]]],
            ['pn' => 'P-OTHER'],
        ] as $override) {
            $line = new QuantumRoLine(array_replace($base, $override));
            $matrix = $builder->build(collect([$line]), $tdrs);
            $this->assertCount(3, $matrix['columns']);
            $key = json_encode([$line->pn, $line->serial_number ?: '']);
            $this->assertSame($line, $matrix['rows'][0]['cells'][$key][0]);
        }
        $matrix = $builder->build(collect([new QuantumRoLine($base)]), $tdrs);
        $this->assertCount(2, $matrix['columns']);
        $this->assertArrayHasKey(json_encode(['P-LINK', 'SN1']), $matrix['rows'][0]['cells']);
    }

    public function test_empty_state_and_authentication(): void
    {
        $wo = $this->createWorkorder(['number' => 887322]);
        $this->getJson(route('mains.quantum-ro-lines', $wo))->assertUnauthorized();
        $user = $this->createUserWithRole('Admin');
        $response = $this->actingAs($user)
            ->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()])
            ->getJson(route('mains.quantum-ro-lines', $wo))->assertOk();
        $this->assertStringContainsString('No synchronized RO lines', $response->json('html'));
    }

    public function test_only_managers_and_admins_can_see_the_tab_and_read_ro_data(): void
    {
        $wo = $this->createWorkorder(['number' => 887323]);
        foreach (['Admin', 'Manager', 'Technician', 'Team Leader', 'Paint'] as $role) {
            $user = $this->createUserWithRole($role);
            $this->actingAs($user)->withSession([
                'auth.version' => (int) $user->auth_version,
                'password_hash_web' => $user->getAuthPassword(),
            ]);
            $allowed = in_array($role, ['Admin', 'Manager'], true);
            $this->getJson(route('mains.quantum-ro-lines', $wo))->assertStatus($allowed ? 200 : 403);
            $page = $this->get(route('mains.show', $wo))->assertOk();
            if ($allowed) {
                $page->assertSee('id="main-ro-panel"', false)->assertSee("allowedTabs.push('ro')", false);
            } else {
                $page->assertDontSee('id="main-ro-panel"', false)
                    ->assertDontSee('data-main-tab="ro" role="tab"', false)
                    ->assertDontSee("allowedTabs.push('ro')", false);
            }
        }
    }
}
