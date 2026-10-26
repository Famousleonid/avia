<?php

namespace Tests\Feature;

use App\Models\{ManualProcess, Process, ProcessName};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ProcessPickerIsolationTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_all_process_catalogs_are_separate_by_selected_id_even_after_renaming(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $otherManual = $this->createManual();
        $fixtures = [];
        foreach ([
            ['Machining (AT)', 'MACHINING'], ['Machining (EC)', 'MACHINING'],
            ['Machining (OUT)', 'MACHINING'], ['Machining (Blend)', 'MACHINING'],
            ['NDT-1', 'NDT'], ['NDT-4', 'NDT'], ['NDT-6', 'NDT'],
            ['Cad plate', 'CAD'], ['Cad stripping', 'CAD'],
            ['Anodizing (AT)', 'ANODIZING'], ['Anodizing (OUT)', 'ANODIZING'],
            ['Paint', 'PAINT'], ['Stress Relief', 'STRESS'], ['Passivation', 'PASSIVATION'],
        ] as [$name, $sheet]) {
            $type = ProcessName::firstOrCreate(['name' => $name], [
                'process_sheet_name' => $sheet, 'form_number' => '013',
            ]);
            $linked = Process::create(['process_names_id' => $type->id, 'process' => 'Same specification text']);
            $available = Process::create(['process_names_id' => $type->id, 'process' => 'Other manual specification']);
            ManualProcess::create(['manual_id' => $manual->id, 'processes_id' => $linked->id,
                'process_comment' => 'Comment '.$name, 'requires_fig' => true, 'requires_zone' => false]);
            ManualProcess::create(['manual_id' => $otherManual->id, 'processes_id' => $available->id]);
            $fixtures[] = [$type, $linked, $available];
        }
        // Display labels are editable; ownership must still follow the ID.
        $fixtures[2][0]->update(['name' => 'Machining (OUTSIDE)']);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword()]);
        foreach ($fixtures as [$type, $linked, $available]) {
            $data = $this->getJson(route('processes.getProcesses', [
                'processNameId' => $type->id, 'manualId' => $manual->id,
            ]))->assertOk()->json();
            $existing = collect($data['existingProcesses']);
            $catalog = collect($data['availableProcesses']);
            $this->assertSame([$linked->id], $existing->pluck('id')->all());
            $this->assertContains($available->id, $catalog->pluck('id')->all());
            $this->assertNotContains($linked->id, $catalog->pluck('id')->all());
            $this->assertTrue($existing->concat($catalog)->every(fn ($row) => (int) $row['process_names_id'] === $type->id));
            $this->assertTrue($existing->first()['requires_fig']);
            $this->assertNotEmpty($existing->first()['process_comment']);
            $direct = $this->getJson(route('tdr-processes.get-process', [
                'processNameId' => $type->id, 'manual_id' => $manual->id,
            ]))->assertOk()->json();
            $this->assertSame([$linked->id], collect($direct)->pluck('id')->all());
        }
    }
}
