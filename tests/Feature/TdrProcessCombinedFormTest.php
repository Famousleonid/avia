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

class TdrProcessCombinedFormTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_combined_form_prints_five_selected_rows_without_a_maximum_limit(): void
    {
        [$admin, $tdr, $processName] = $this->createFormContext();
        $selections = [];

        foreach (range(1, 5) as $number) {
            $process = Process::query()->create([
                'process_names_id' => $processName->id,
                'process' => "Combined selected process {$number}",
            ]);
            ManualProcess::query()->create([
                'manual_id' => $tdr->workorder->unit->manual_id,
                'processes_id' => $process->id,
            ]);
            $tdrProcess = TdrProcess::query()->create([
                'tdrs_id' => $tdr->id,
                'process_names_id' => $processName->id,
                'processes' => [$process->id],
                'sort_order' => $number,
            ]);
            $selections[] = [
                'tdr_process_id' => $tdrProcess->id,
                'process_id' => $process->id,
            ];
        }

        $response = $this->actingAs($admin)->post(route('tdr-processes.combinedForm', [
            'tdrId' => $tdr->id,
        ]), [
            'selections' => json_encode($selections),
            'omit_form_header_date' => 1,
        ]);

        $response->assertOk();
        foreach (range(1, 5) as $number) {
            $response->assertSee("Combined selected process {$number}");
        }
    }

    public function test_combined_form_keeps_only_the_selected_process_from_a_multi_process_row(): void
    {
        [$admin, $tdr, $processName] = $this->createFormContext();
        $processes = collect(['Selected Alpha', 'Not Selected', 'Selected Beta'])->map(function ($text) use ($tdr, $processName) {
            $process = Process::query()->create([
                'process_names_id' => $processName->id,
                'process' => $text,
            ]);
            ManualProcess::query()->create([
                'manual_id' => $tdr->workorder->unit->manual_id,
                'processes_id' => $process->id,
            ]);

            return $process;
        });
        $multiRow = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'processes' => $processes->take(2)->pluck('id')->all(),
            'sort_order' => 1,
        ]);
        $secondRow = TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'processes' => [$processes[2]->id],
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($admin)->post(route('tdr-processes.combinedForm', [
            'tdrId' => $tdr->id,
        ]), [
            'selections' => json_encode([
                ['tdr_process_id' => $multiRow->id, 'process_id' => $processes[0]->id],
                ['tdr_process_id' => $secondRow->id, 'process_id' => $processes[2]->id],
            ]),
        ]);

        $response->assertOk()
            ->assertSee('Selected Alpha')
            ->assertSee('Selected Beta')
            ->assertDontSee('Not Selected');
    }

    public function test_combined_form_rejects_different_process_names(): void
    {
        [$admin, $tdr, $processName] = $this->createFormContext();
        $otherProcessName = ProcessName::query()->create([
            'name' => 'Other combined name ' . uniqid(),
            'process_sheet_name' => 'OTHER',
            'form_number' => '018',
            'print_form' => true,
            'show_in_process_picker' => true,
        ]);
        $rows = collect([$processName, $otherProcessName])->map(function (ProcessName $name, int $index) use ($tdr) {
            $process = Process::query()->create([
                'process_names_id' => $name->id,
                'process' => 'Mismatch process ' . $index,
            ]);
            ManualProcess::query()->create([
                'manual_id' => $tdr->workorder->unit->manual_id,
                'processes_id' => $process->id,
            ]);
            $row = TdrProcess::query()->create([
                'tdrs_id' => $tdr->id,
                'process_names_id' => $name->id,
                'processes' => [$process->id],
                'sort_order' => $index + 1,
            ]);

            return ['tdr_process_id' => $row->id, 'process_id' => $process->id];
        });

        $this->actingAs($admin)
            ->post(route('tdr-processes.combinedForm', [
                'tdrId' => $tdr->id,
            ]), [
                'selections' => json_encode($rows->all()),
            ])
            ->assertStatus(422);
    }

    public function test_machining_at_and_ec_combine_but_blend_does_not(): void
    {
        [$admin, $tdr] = $this->createFormContext();
        $selections = [];
        foreach (['Machining (AT)', 'Machining (EC)', 'Machining (Blend)'] as $index => $label) {
            $name = ProcessName::query()->firstOrCreate(['name' => $label], [
                'process_sheet_name' => 'MACHINING', 'form_number' => '018',
                'print_form' => true, 'show_in_process_picker' => true,
            ]);
            $process = Process::query()->create([
                'process_names_id' => $name->id, 'process' => 'Combined machining specification '.$index,
            ]);
            ManualProcess::query()->create([
                'manual_id' => $tdr->workorder->unit->manual_id, 'processes_id' => $process->id,
            ]);
            $row = TdrProcess::query()->create([
                'tdrs_id' => $tdr->id, 'process_names_id' => $name->id,
                'processes' => [$process->id], 'sort_order' => $index + 1,
            ]);
            $selections[] = ['tdr_process_id' => $row->id, 'process_id' => $process->id];
        }
        $url = route('tdr-processes.combinedForm', ['tdrId' => $tdr->id]);
        foreach ([[$selections[0], $selections[1]], [$selections[1], $selections[0]]] as $pair) {
            $this->actingAs($admin)->post($url, ['selections' => json_encode($pair)])
                ->assertOk()->assertSee('Combined machining specification 0')
                ->assertSee('Combined machining specification 1')->assertDontSee('Combined machining specification 2');
        }
        $this->actingAs($admin)->post($url, ['selections' => json_encode([$selections[0], $selections[2]])])
            ->assertStatus(422);
        // Even within the allowed pair, a specification must belong to its own source row.
        $selections[0]['process_id'] = $selections[1]['process_id'];
        $this->actingAs($admin)->post($url, ['selections' => json_encode(array_slice($selections, 0, 2))])
            ->assertStatus(422);
    }

    public function test_machining_multiple_specs_save_edit_and_print_one_part_label(): void
    {
        [$admin, $tdr, $name] = $this->createFormContext();
        $name->update(['process_sheet_name' => 'MACHINING']);
        $ids = [];
        foreach (range(1, 3) as $n) {
            $spec = Process::create(['process_names_id' => $name->id, 'process' => 'Machining choice '.$n]);
            ManualProcess::create(['manual_id' => $tdr->workorder->unit->manual_id, 'processes_id' => $spec->id]);
            $ids[] = $spec->id;
        }
        $this->actingAs($admin)->postJson(route('tdr-processes.store'), [
            'tdrs_id' => $tdr->id,
            'processes' => [['process_names_id' => $name->id, 'processes' => $ids, 'description' => 'Fig. 6107',
                'specification_descriptions' => [$ids[0] => 'Alpha Fig. 6107', $ids[1] => 'Beta Fig. 6108', $ids[2] => 'Gamma Fig. 6109']]],
        ])->assertSuccessful();
        $row = TdrProcess::where('tdrs_id', $tdr->id)->where('process_names_id', $name->id)->sole();
        $this->assertEquals($ids, $row->processes);
        $this->assertSame('Beta Fig. 6108', $row->descriptionForSpecification($ids[1]));
        $body = $this->get(route('tdr-processes.processesBody', ['tdrId' => $tdr->id]))->assertOk();
        $tableDom = new \DOMDocument();
        @$tableDom->loadHTML($body->getContent());
        $tableXpath = new \DOMXPath($tableDom);
        $visualRows = $tableXpath->query('//tr[@data-id="'.$row->id.'"]');
        $this->assertCount(1, $visualRows, 'One saved Machining record must render one table row.');
        foreach (range(1, 3) as $n) $this->assertStringContainsString('Machining choice '.$n, $visualRows[0]->textContent);
        $this->assertCount(1, $tableXpath->query('.//*[contains(concat(" ",normalize-space(@class)," ")," load-edit-process ")]', $visualRows[0]));
        $this->assertCount(1, $tableXpath->query('.//a[contains(@href,"process_id") or contains(@href,"tdr-processes")]', $visualRows[0]));
        $html = $this->get(route('tdr-processes.show', ['tdr_process' => $row->id, 'process_id' => $ids[0]]))->assertOk();
        foreach (range(1, 3) as $n) $html->assertSee('Machining choice '.$n);
        $dom = new \DOMDocument();
        @$dom->loadHTML($html->getContent());
        $xpath = new \DOMXPath($dom);
        $cells = $xpath->query('//div[contains(concat(" ",normalize-space(@class)," ")," process-cell ")]');
        $this->assertCount(3, $cells);
        foreach ($cells as $i => $cell) {
            $this->assertStringContainsString(['Alpha Fig. 6107', 'Beta Fig. 6108', 'Gamma Fig. 6109'][$i], $cell->textContent);
            $details = $xpath->query('./div', $cell->parentNode);
            $this->assertSame($i === 0 ? '10-100' : '', trim($details[0]->textContent));
            $this->assertSame($i === 0 ? 'Combined form component' : '', trim($details[2]->textContent));
        }
        $this->putJson(route('tdr-processes.update', $row->id), [
            'tdrs_id' => $tdr->id,
            'processes' => [['process_names_id' => $name->id, 'process' => [$ids[0], $ids[2]]]],
            'description' => 'Fig. 6109',
            'specification_descriptions' => [$ids[0] => 'Changed Fig. 6200', $ids[2] => '', $ids[1] => 'Removed'],
        ])->assertSuccessful();
        $this->assertEquals([$ids[0], $ids[2]], $row->fresh()->processes);
        $this->assertSame('Changed Fig. 6200', $row->fresh()->descriptionForSpecification($ids[0]));
        $this->assertSame('', $row->fresh()->descriptionForSpecification($ids[2]));
        $this->assertArrayNotHasKey($ids[1], $row->fresh()->specification_descriptions);
        $row->update(['in_traveler' => true, 'traveler_group' => 1]);
        $this->deleteJson(route('tdr-processes.destroy', $row->id), ['tdrId' => $tdr->id])->assertStatus(422);
        $this->assertNotNull($row->fresh());
        $row->update(['in_traveler' => false]);
        $this->deleteJson(route('tdr-processes.destroy', $row->id), ['tdrId' => $tdr->id])->assertOk()->assertJson(['success' => true]);
        $this->assertNull($row->fresh());
    }

    public function test_anodizing_combines_two_rows_with_one_part_identity_and_separate_descriptions(): void
    {
        [$admin, $tdr, $name] = $this->createFormContext();
        $name->update(['process_sheet_name' => 'ANODIZING']);
        $tdr->update(['serial_number' => 'L161']);
        $specs = ['MIL-DTL-5541, Class 1A.', 'MIL-A-8625, Type I or IB, Class 2.'];
        $descriptions = ['pg.6074, fig.6005(5). Where indicated with ****', 'P.6074. Fig 6005. All over except where indicated with ****'];
        $selections = [];
        foreach ($specs as $i => $text) {
            $spec = Process::create(['process_names_id' => $name->id, 'process' => $text]);
            ManualProcess::create(['manual_id' => $tdr->workorder->unit->manual_id, 'processes_id' => $spec->id]);
            $row = TdrProcess::create([
                'tdrs_id' => $tdr->id, 'process_names_id' => $name->id,
                'processes' => [$spec->id], 'description' => $descriptions[$i], 'sort_order' => $i + 1,
            ]);
            $selections[] = ['tdr_process_id' => $row->id, 'process_id' => $spec->id];
        }
        $response = $this->actingAs($admin)->post(route('tdr-processes.combinedForm', ['tdrId' => $tdr->id]), [
            'selections' => json_encode($selections),
        ])->assertOk();
        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $cells = $xpath->query('//div[contains(concat(" ",normalize-space(@class)," ")," process-cell ")]');
        $this->assertCount(2, $cells);
        foreach ($cells as $i => $cell) {
            $this->assertStringContainsString($specs[$i], $cell->textContent);
            $this->assertStringContainsString($descriptions[$i], $cell->textContent);
            $this->assertStringNotContainsString($descriptions[1 - $i], $cell->textContent);
            $details = $xpath->query('./div', $cell->parentNode);
            $this->assertSame($i === 0 ? '10-100' : '', trim($details[0]->textContent));
            if ($i === 0) {
                $this->assertStringContainsString($tdr->component->part_number, $details[1]->textContent);
                $this->assertStringContainsString('SN L161', $details[1]->textContent);
            } else {
                $this->assertSame('', trim($details[1]->textContent));
            }
            $this->assertSame($i === 0 ? 'Combined form component' : '', trim($details[2]->textContent));
            $this->assertSame('1', trim($details[4]->textContent));
            $this->assertDatabaseHas('tdr_processes', ['id' => $selections[$i]['tdr_process_id'], 'description' => $descriptions[$i]]);
            // Each process is still printable independently, with its own part and S/N.
            $this->get(route('tdr-processes.show', ['tdr_process' => $selections[$i]['tdr_process_id']]))
                ->assertOk()->assertSee('SN L161')->assertSee($specs[$i])->assertDontSee($specs[1 - $i]);
        }
    }

    public function test_technician_can_edit_descriptions_without_ro_but_assigned_ro_is_locked(): void
    {
        [, $tdr, $name] = $this->createFormContext();
        $technician = $this->createUserWithRole('Technician');
        $name->update(['process_sheet_name' => 'MACHINING']);
        $ids = [];
        foreach (range(1, 2) as $n) {
            $spec = Process::create(['process_names_id' => $name->id, 'process' => 'Technician specification '.$n]);
            ManualProcess::create(['manual_id' => $tdr->workorder->unit->manual_id, 'processes_id' => $spec->id]);
            $ids[] = $spec->id;
        }
        $row = TdrProcess::create(['tdrs_id' => $tdr->id, 'process_names_id' => $name->id, 'processes' => $ids]);
        $this->actingAs($technician);
        $this->get(route('tdr-processes.edit', $row->id))->assertOk();
        $this->get(route('tdr-processes.editForm', ['id' => $row->id, 'modal' => 1]))->assertOk();
        $payload = [
            'tdrs_id' => $tdr->id,
            'processes' => [['process_names_id' => $name->id, 'process' => $ids]],
            'description' => 'Technician shared description',
            'specification_descriptions' => [$ids[0] => 'Technician first description', $ids[1] => 'Technician second description'],
        ];
        $this->putJson(route('tdr-processes.update', $row->id), $payload)->assertOk();
        $this->assertSame($payload['description'], $row->fresh()->description);
        $this->assertSame($payload['specification_descriptions'][$ids[0]], $row->fresh()->descriptionForSpecification($ids[0]));
        $this->assertSame($payload['specification_descriptions'][$ids[1]], $row->fresh()->descriptionForSpecification($ids[1]));
        $row->update(['repair_order' => 'R-QA-LOCK']);
        $this->get(route('tdr-processes.edit', $row->id))->assertForbidden();
        $this->get(route('tdr-processes.editForm', ['id' => $row->id, 'modal' => 1]))->assertForbidden();
        $payload['description'] = 'Must not save';
        $payload['specification_descriptions'][$ids[0]] = 'Must not save';
        $this->putJson(route('tdr-processes.update', $row->id), $payload)->assertForbidden();
        $this->assertSame('Technician shared description', $row->fresh()->description);
        $this->assertSame('Technician first description', $row->fresh()->descriptionForSpecification($ids[0]));
    }

    private function createFormContext(): array
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'COMBINED-' . uniqid(),
            'name' => 'Combined form component',
            'ipl_num' => '10-100',
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'Combined form process ' . uniqid(),
            'process_sheet_name' => 'OTHER',
            'form_number' => '018',
            'print_form' => true,
            'show_in_process_picker' => true,
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        return [$admin, $tdr->load('workorder.unit'), $processName];
    }
}
