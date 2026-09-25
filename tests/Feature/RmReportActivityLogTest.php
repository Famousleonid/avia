<?php

namespace Tests\Feature;

use App\Models\RmReport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class RmReportActivityLogTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_record_lifecycle_logs_the_actor_and_changes_through_http(): void
    {
        $creator = $this->createUserWithRole('Admin');
        $editor = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $creator->id]);
        $payload = [
            'workorder_id' => $wo->id,
            'part_description' => 'Sliding Tube (3-180A)',
            'mod_repair' => 'Repair',
            'mod_repair_description' => 'Wheel Axle Flange',
            'ident_method' => 'Mark A',
        ];
        $created = $this->actingAs($creator)->postJson(route('rm_reports.store'), $payload)->assertOk();
        $id = $created->json('data.id');
        $logs = fn () => Activity::where('subject_type', RmReport::class)->where('subject_id', $id);
        $log = $logs()->where('event', 'created')->sole();
        $this->assertEquals($creator->id, $log->causer_id);
        $this->assertSame('rm_report', $log->log_name);
        $this->assertSame('Wheel Axle Flange', $log->properties['attributes']['description']);
        $this->assertEquals($wo->unit->manual_id, $log->properties['attributes']['manual_id']);
        $this->assertNotNull($log->created_at);

        $payload['mod_repair_description'] = 'Revised repair';
        $this->actingAs($editor)->withSession([
            'auth.version' => (int) $editor->auth_version,
            'password_hash_web' => $editor->getAuthPassword(),
        ])->putJson(route('rm_reports.updateRecord', $id), $payload)->assertOk();
        $log = $logs()->where('event', 'updated')->sole();
        $this->assertEquals($editor->id, $log->causer_id);
        $this->assertSame('Wheel Axle Flange', $log->properties['old']['description']);
        $this->assertSame('Revised repair', $log->properties['attributes']['description']);
        $this->putJson(route('rm_reports.updateRecord', $id), $payload)->assertOk();
        $this->assertSame(2, $logs()->count());

        $this->deleteJson(route('rm_reports.destroy', $id), ['workorder_id' => $wo->id])->assertOk();
        $log = $logs()->where('event', 'deleted')->sole();
        $this->assertEquals($editor->id, $log->causer_id);
        $this->assertStringContainsString('Revised repair', $log->properties->toJson());
        $this->assertDatabaseMissing('rm_reports', ['id' => $id]);
    }

    public function test_bulk_deletion_keeps_a_log_for_each_deleted_record(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $ids = [];
        foreach (['Repair A', 'Repair B'] as $description) {
            $ids[] = RmReport::create([
                'manual_id' => $wo->unit->manual_id,
                'part_description' => 'Sliding Tube',
                'mod_repair' => 'Repair',
                'description' => $description,
            ])->id;
        }
        $this->actingAs($admin)->deleteJson(route('rm_reports.destroy.multiple'), [
            'selected_records' => json_encode($ids),
            'workorder_id' => $wo->id,
        ])->assertRedirect();
        foreach ($ids as $id) {
            $log = Activity::where('subject_type', RmReport::class)
                ->where('subject_id', $id)->where('event', 'deleted')->sole();
            $this->assertEquals($admin->id, $log->causer_id);
            $this->assertDatabaseMissing('rm_reports', ['id' => $id]);
        }
    }
}
