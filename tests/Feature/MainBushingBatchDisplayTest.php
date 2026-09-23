<?php

namespace Tests\Feature;

use App\Models\{Component, Process, ProcessName, WoBushing, WoBushingBatch, WoBushingLine, WoBushingProcess};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class MainBushingBatchDisplayTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_labels_follow_saved_batches_and_main_dates_drive_bushing_status(): void
    {
        $user = $this->createUserWithRole('Technician');
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $type = ProcessName::whereIdentityName('Machining')->first()
            ?? ProcessName::create(['name' => 'Machining', 'process_sheet_name' => 'Machining']);
        $type->update(['name' => 'Machining (AT)']);
        $process = Process::create(['process_names_id' => $type->id, 'process' => 'Machine']);
        $batches = [];
        foreach ([1 => '8-361', 2 => '8-231', 10 => '8-100'] as $number => $ipl) {
            $part = Component::create(['manual_id' => $wo->unit->manual_id, 'name' => 'Bushing', 'ipl_num' => $ipl, 'part_number' => 'BUSH-'.$number]);
            $line = WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id, 'component_id' => $part->id, 'qty' => 2, 'qty_remaining' => 2]);
            $batches[$number] = WoBushingBatch::create(['workorder_id' => $wo->id, 'process_id' => $process->id,
                'process_column_key' => 'machining', 'legacy_number' => $number === 1 ? 1 : null, 'route_number' => $number !== 1 ? $number : null]);
            WoBushingProcess::create(['wo_bushing_line_id' => $line->id, 'process_id' => $process->id, 'qty' => 2, 'batch_id' => $batches[$number]->id]);
        }
        $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()]);
        $response = $this->get(route('mains.show', $wo))->assertOk();
        $groups = collect($response->viewData('bushingProcessSections'))->flatMap(fn ($s) => $s['rows'] ?? []);
        // Natural batch order takes priority over the opposite IPL order.
        $this->assertSame(['B1', 'B2', 'B10'], collect($groups->first()['batches'])->pluck('batch_label')->all());
        $url = route('wo_bushing_batches.updateDate', $batches[1]);
        $this->patchJson($url, ['date_finish' => '2026-09-23'])->assertUnprocessable();
        $this->patchJson($url, ['date_start' => '2026-09-22'])->assertOk()->assertJsonPath('repair_order', 'AT');
        $this->assertSame('AT', $batches[1]->fresh()->repair_order);
        $batches[1]->update(['repair_order' => 'R123']);
        $assignment = new \ReflectionMethod(\App\Http\Controllers\Admin\WoBushingController::class, 'buildProcessAssignments');
        $rows = $assignment->invoke(app(\App\Http\Controllers\Admin\WoBushingController::class), $bushing);
        $this->assertTrue(array_values($rows)[0]['machining']['locked']);
        $this->assertFalse(array_values($rows)[0]['machining']['finished']);
        $this->patchJson($url, ['date_finish' => '2026-09-23'])->assertOk();
        $this->assertSame('R123', $batches[1]->fresh()->repair_order);
        $rows = $assignment->invoke(app(\App\Http\Controllers\Admin\WoBushingController::class), $bushing);
        $this->assertTrue(array_values($rows)[0]['machining']['finished']);
        $this->assertNull($batches[2]->fresh()->date_start);
        $this->assertNull($batches[2]->fresh()->date_finish);
    }
}

