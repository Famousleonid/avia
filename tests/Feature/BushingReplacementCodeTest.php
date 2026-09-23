<?php

namespace Tests\Feature;

use App\Models\{Code, Component, Process, ProcessName, WoBushing, WoBushingLine, WoBushingProcess};
use App\Services\WoBushingRelationalSync;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class BushingReplacementCodeTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

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
