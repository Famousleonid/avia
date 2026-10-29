<?php

namespace Tests\Feature;

use App\Models\{Code, Component, Process, ProcessName, WoBushing, WoBushingLine, WoBushingProcess};
use App\Services\WoBushingRelationalSync;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class BushingReplacementCodeTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_explicit_editor_save_commits_selection_code_quantity_and_process_together(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $part = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '8-230',
            'bush_ipl_num' => '8-230', 'part_number' => 'EXPLICIT-SAVE', 'name' => 'Bushing',
            'is_bush' => true, 'units_assy' => 2]);
        $type = ProcessName::whereIdentityName('NDT-4')->firstOrFail();
        $processes = collect(['ASTM E1417', 'DCMP-161'])->map(function ($spec) use ($type, $wo) {
            $process = Process::create(['process_names_id' => $type->id, 'process' => $spec]);
            $process->manuals()->attach($wo->unit->manual_id);
            return $process;
        });
        $worn = Code::firstOrCreate(['name' => 'Worn'], ['code' => 'K']);
        $corroded = Code::firstOrCreate(['name' => 'Corroded'], ['code' => 'L']);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword()])->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);
        $item = ['selected' => 1, 'qty' => 1, 'codes_id' => $worn->id,
            'need_processes' => 1, 'ndt' => [$processes[0]->id]];
        $payload = ['workorder_id' => $wo->id, 'group_bushings' => ['8-230' => ['items' => [$part->id => $item]]]];
        $this->getJson(route('workorders.bushing-selection.show', $wo))->assertOk();
        $this->assertFalse(WoBushing::where('workorder_id', $wo->id)->exists());
        $this->postJson(route('wo_bushings.store'), $payload)->assertOk()->assertJsonPath('success', true);
        $bushing = WoBushing::where('workorder_id', $wo->id)->firstOrFail();
        $line = $bushing->lines()->firstOrFail();
        $this->assertSame($processes[0]->id, $line->processes()->value('process_id'));
        $payload['draft_token'] = app(WoBushingRelationalSync::class)->editState($bushing)['token'];
        $item = array_merge($item, ['qty' => 3, 'codes_id' => $corroded->id, 'ndt' => [$processes[1]->id]]);
        $payload['group_bushings']['8-230']['items'][$part->id] = $item;
        $this->putJson(route('wo_bushings.update', $bushing), $payload)->assertUnprocessable();
        $this->assertSame(1, $line->fresh()->qty);
        $this->assertSame($worn->id, $line->fresh()->codes_id);
        $this->assertSame($processes[0]->id, $line->processes()->value('process_id'));
        $payload['group_bushings']['8-230']['items'][$part->id]['qty'] = 2;
        $this->putJson(route('wo_bushings.update', $bushing), $payload)->assertOk()->assertJsonPath('success', true);
        $line = $bushing->lines()->where('selection_active', true)->firstOrFail();
        $this->assertSame(2, $line->qty);
        $this->assertSame($corroded->id, $line->codes_id);
        $this->assertSame([$processes[1]->id], $line->processes()->pluck('process_id')->all());
        $this->assertSame([2], $line->processes()->pluck('qty')->all());
        $snapshot = $this->getJson(route('workorders.bushing-selection.show', $wo))->assertOk()->json();
        $option = collect($snapshot['groups'])->flatMap(fn ($group) => $group['options'])->firstWhere('id', $part->id);
        $this->assertTrue($option['selected']);
        $this->assertSame(2, $option['qty']);
        $this->assertSame($corroded->id, $option['codes_id']);
        // A stale editor cannot overwrite the successful save.
        $this->putJson(route('wo_bushings.update', $bushing), $payload)->assertUnprocessable();
        $this->assertSame(2, $line->fresh()->qty);
    }

    public function test_codes_save_reopen_and_print_separately_for_two_variants(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id, 'instruction_id' => $this->createInstruction(['name' => 'Repair'])->id]);
        $parts = collect(['8-100', '8-100A'])->map(fn ($ipl) => Component::create([
            'manual_id' => $wo->unit->manual_id, 'ipl_num' => $ipl, 'bush_ipl_num' => '8-100',
            'name' => 'Bushing', 'part_number' => 'CODE-'.$ipl, 'is_bush' => true, 'units_assy' => 2,
        ]));
        $worn = Code::firstOrCreate(['name' => 'Worn'], ['code' => 'K']);
        $cracked = Code::firstOrCreate(['name' => 'Cracked'], ['code' => 'G']);
        $items = [];
        foreach ($parts as $i => $part) $items[$part->id] = ['selected' => 1, 'qty' => 1, 'codes_id' => $i ? $cracked->id : $worn->id];
        $payload = ['workorder_id' => $wo->id, 'group_bushings' => ['8-100' => ['items' => $items]]];
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()]);
        $missing = $payload;
        unset($missing['group_bushings']['8-100']['items'][$parts[0]->id]['codes_id']);
        $this->postJson(route('wo_bushings.store'), $missing)->assertUnprocessable()->assertJsonValidationErrors('group_bushings');
        $this->assertFalse(WoBushing::where('workorder_id', $wo->id)->exists());
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('wo_bushings.store'), $payload)->assertOk()->assertJsonPath('success', true);
        $bushing = WoBushing::where('workorder_id', $wo->id)->firstOrFail();
        $this->assertSame([$worn->id, $cracked->id], $bushing->lines()->orderBy('sort_order')->pluck('codes_id')->all());
        $this->get(route('wo_bushings.edit', $bushing))->assertOk()->assertSee('data-code-id="'.$worn->id.'"', false);
        $rows = collect($this->get(route('tdrs.prlForm', $wo))->assertOk()->viewData('ordersParts'));
        $this->assertCount(2, $rows);
        $this->assertSame(['K', 'G'], $rows->map(fn ($row) => $row['codes']['code'])->all());
        $this->assertSame([1, 1], $rows->pluck('qty')->all());

        // Every selected row must explicitly provide a valid code.
        unset($payload['group_bushings']['8-100']['items'][$parts[0]->id]['codes_id']);
        $this->putJson(route('wo_bushings.update', $bushing), $payload)->assertUnprocessable();
        $this->assertSame($worn->id, $bushing->lines()->where('component_id', $parts[0]->id)->value('codes_id'));
        $payload['group_bushings']['8-100']['items'][$parts[0]->id]['codes_id'] = null;
        $this->putJson(route('wo_bushings.update', $bushing), $payload)->assertUnprocessable();
        $this->assertSame($worn->id, $bushing->lines()->where('component_id', $parts[0]->id)->value('codes_id'));
        $payload['group_bushings']['8-100']['items'][$parts[0]->id]['codes_id'] = 999999999;
        $this->putJson(route('wo_bushings.update', $bushing), $payload)->assertUnprocessable();
    }

    public function test_started_line_cannot_change_even_its_replacement_code(): void
    {
        $wo = $this->createWorkorder();
        $part = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => '8-100', 'part_number' => 'CODE-OLD', 'name' => 'Bushing']);
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $line = WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id, 'component_id' => $part->id, 'qty' => 2, 'qty_remaining' => 2]);
        $type = ProcessName::firstOrCreate(['name' => 'NDT-4'], ['process_sheet_name' => 'NDT']);
        $process = Process::create(['process_names_id' => $type->id, 'process' => 'Inspection']);
        $step = WoBushingProcess::create(['wo_bushing_line_id' => $line->id, 'process_id' => $process->id, 'qty' => 2, 'date_start' => '2026-09-01', 'repair_order' => 'RO-123']);
        $before = $step->fresh()->toArray();
        $code = Code::firstOrCreate(['name' => 'Worn'], ['code' => 'K']);
        app(WoBushingRelationalSync::class)->syncFromGroupBushings($bushing, ['8-100' => ['items' => [$part->id => ['selected' => 1, 'qty' => 1, 'codes_id' => $code->id]]]]);
        $this->assertNull($line->fresh()->codes_id);
        $this->assertSame(2, $line->fresh()->qty);
        $this->assertSame($before, $step->fresh()->toArray());
    }
}
