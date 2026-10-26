<?php

namespace Tests\Feature;

use App\Models\GeneralTask;
use App\Models\Main;
use App\Models\Task;
use App\Services\Quality\ShipmentReleaseTechnician;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ShipmentReleaseTechnicianTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;

    public function test_latest_submission_then_persisted_qa_override(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $owner = $this->createUserWithRole('Technician');
        $latest = $this->createUserWithRole('Technician');
        $chosen = $this->createUserWithRole('Technician');
        $wo = $this->createWorkorder(['user_id' => $owner->id, 'certificate_data' => ['untouched' => 'keep']]);
        $resolver = app(ShipmentReleaseTechnician::class);
        $this->assertSame($owner->id, $resolver->resolve($wo)->id);
        $general = GeneralTask::create(['name' => 'Shipment test']);
        $task = Task::create(['name' => 'WO Submitted for Final Inspection', 'general_task_id' => $general->id]);
        foreach ([[$owner, '2026-01-01', false], [$latest, '2026-02-01', false], [$chosen, '2026-03-01', true]] as [$user, $date, $ignored]) {
            Main::create(['workorder_id' => $wo->id, 'user_id' => $user->id, 'general_task_id' => $general->id,
                'task_id' => $task->id, 'date_finish' => $date, 'ignore_row' => $ignored]);
        }
        $this->assertSame($latest->id, $resolver->resolve($wo)->id);
        $this->actingAs($admin)->get(route('quality.forms.shipment_release', $wo))->assertOk()
            ->assertViewHas('technician', fn ($user) => $user->id === $latest->id);
        $this->patchJson(route('quality.forms.shipment_technician.update', $wo), ['technician_id' => $chosen->id])->assertOk();
        $this->assertSame($chosen->id, $resolver->resolve($wo->fresh())->id);
        $this->assertSame('keep', $wo->fresh()->certificate_data['untouched']);
        $this->assertSame($owner->id, $wo->fresh()->user_id);
        $this->get(route('quality.forms.shipment_release', $wo))->assertOk()
            ->assertViewHas('technician', fn ($user) => $user->id === $chosen->id);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'shipment_release', 'causer_id' => $admin->id, 'subject_id' => $wo->id]);
        $this->patchJson(route('quality.forms.shipment_technician.update', $wo), ['technician_id' => 99999999])->assertUnprocessable();
    }

    public function test_user_without_qa_access_cannot_change_technician(): void
    {
        $user = $this->createUserWithRole('Technician');
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        $this->actingAs($user)->patchJson(route('quality.forms.shipment_technician.update', $wo), ['technician_id' => $user->id])->assertForbidden();
    }
}
