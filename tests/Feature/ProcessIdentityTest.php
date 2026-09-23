<?php

namespace Tests\Feature;

use App\Models\{ManualProcess, Process, ProcessName, StdProcess, TdrProcess};
use App\Services\PaintFinishAccess;
use App\Support\WoBushingProcessColumnKey;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\{BuildsDomainData, TestCase};

class ProcessIdentityTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_ndt_form_alias_ids_survive_renaming_even_with_a_separate_sheet_type(): void
    {
        $eddy = ProcessName::firstOrCreate(['name' => 'Eddy Current Test'], ['process_sheet_name' => 'EDDY CURRENT']);
        $bni = ProcessName::firstOrCreate(['name' => 'BNI'], ['process_sheet_name' => 'BNI']);
        foreach ([$eddy, $bni] as $type) {
            $type->update(['print_form' => true]);
        }
        $controller = app(\App\Http\Controllers\Admin\TdrProcessController::class);
        $lookup = new \ReflectionMethod($controller, 'ndtFormNameIds');
        $lookup->setAccessible(true);
        $before = $lookup->invoke($controller);
        $this->assertSame($eddy->id, $before['ndt6_name_id']);
        $this->assertSame($bni->id, $before['ndt5_name_id']);
        $eddy->update(['name' => 'Electrical inspection']);
        $bni->update(['name' => 'Surface inspection']);
        $this->assertSame($before, $lookup->invoke($controller));
    }

    public function test_directory_rename_keeps_id_role_permissions_and_catalog_links(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $type = ProcessName::firstOrCreate(['name' => 'Machining (AT)'], ['process_sheet_name' => 'MACHINING']);
        $process = Process::create(['process_names_id' => $type->id, 'process' => 'Instruction']);
        $url = route('directories.field.update', ['directory' => 'process_names', 'id' => $type->id, 'field' => 'name']);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()])
            ->patchJson($url, ['name' => 'Workshop operation'])->assertOk();

        $type->refresh();
        $this->assertSame('Workshop operation', $type->name);
        $this->assertSame('Machining', $type->identityName());
        $this->assertContains($type->id, ProcessName::machiningMachiningEcMergeProcessNameIds());
        $this->assertTrue($type->allowsManualDateEditing());
        $this->assertSame('machining', WoBushingProcessColumnKey::fromProcess($process->fresh()));
        $this->assertSame($type->id, $process->fresh()->process_names_id);
        $this->assertSame('Machining', ProcessName::select('id', 'name')->findOrFail($type->id)->identityName());
        $partial = ProcessName::select('id', 'name')->findOrFail($type->id);
        $this->assertSame('Machining', $partial->identityName());
        $partial->update(['name' => 'Workshop operation v2']);
        $this->assertSame('Machining', $type->fresh()->identityName());
        $partial = ProcessName::select('id', 'name')->findOrFail($type->id);
        $partial->identity_name = 'Paint';
        $partial->save();
        $this->assertSame('Machining', $type->fresh()->identityName());

        $unrelated = ProcessName::create(['name' => 'Unrelated operation', 'process_sheet_name' => 'OTHER']);
        $unrelated->update(['name' => 'Machining']);
        $this->assertNotContains($unrelated->id, ProcessName::machiningMachiningEcMergeProcessNameIds());
        $this->assertFalse($unrelated->allowsManualDateEditing());
        $type->identity_name = 'Paint';
        $type->save();
        $this->assertSame('Machining', $type->fresh()->identityName());
    }

    public function test_renamed_paint_cannot_bypass_finish_permissions(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $type = ProcessName::firstOrCreate(['name' => 'Paint'], ['process_sheet_name' => 'PAINT APPLICATION']);
        $type->update(['name' => 'Surface finish']);
        $row = new TdrProcess(['date_finish' => '2026-09-01']);
        $row->setRelation('processName', $type);
        $this->expectException(HttpException::class);
        app(PaintFinishAccess::class)->authorizeUpdate($technician, [$row], ['date_finish' => null]);
    }

    public function test_renamed_traveler_is_still_excluded_and_renamed_std_processes_stay_available(): void
    {
        $traveler = ProcessName::firstOrCreate(['name' => 'Traveler'], ['process_sheet_name' => 'TRAVELER']);
        $traveler->update(['name' => 'Route card', 'show_in_process_picker' => true]);
        $this->assertFalse(ProcessName::forPicker()->whereKey($traveler->id)->exists());
        $wo = $this->createWorkorder();
        foreach (['NDT-4' => 'ndt', 'Cad plate' => 'cad', 'Stress Relief' => 'stress', 'Paint' => 'paint'] as $name => $std) {
            $type = ProcessName::firstOrCreate(['name' => $name], ['process_sheet_name' => strtoupper($std)]);
            $process = Process::create(['process_names_id' => $type->id, 'process' => 'Specification '.$std]);
            ManualProcess::create(['manual_id' => $wo->unit->manual_id, 'processes_id' => $process->id]);
            $before = StdProcess::processPicklistValuesForManual($wo->unit->manual_id, $std);
            $this->assertNotEmpty($before);
            $type->update(['name' => 'New label '.$std]);
            $this->assertSame($before, StdProcess::processPicklistValuesForManual($wo->unit->manual_id, $std));
            $this->assertContains($type->id, ProcessName::identityIds($name));
        }
    }
}
