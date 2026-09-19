<?php

namespace Tests\Feature;

use App\Models\RmReport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class RmReportProtectedTemplatesTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_only_admin_can_manage_protected_templates_and_they_are_listed_first(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $tech = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $wo = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $admin->id]);
        $payload = ['workorder_id' => $wo->id, 'part_description' => 'Protected fixture', 'mod_repair' => 'Repair', 'mod_repair_description' => 'Repair description', 'is_admin_template' => true];
        $ordinary = RmReport::create(['manual_id' => $manual->id, 'part_description' => 'Ordinary fixture', 'mod_repair' => 'Repair', 'description' => 'Existing']);
        $this->actingAs($tech)->withSession(['auth.version' => (int) $tech->auth_version, 'password_hash_web' => $tech->getAuthPassword()])->postJson(route('rm_reports.store'), $payload)->assertForbidden();
        $created = $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()])->postJson(route('rm_reports.store'), $payload)->assertOk()->assertJsonPath('data.is_admin_template', true);
        $id = $created->json('data.id');
        $this->actingAs($tech)->withSession(['auth.version' => (int) $tech->auth_version, 'password_hash_web' => $tech->getAuthPassword()])->putJson(route('rm_reports.updateRecord', $id), array_merge($payload, ['is_admin_template' => false]))->assertForbidden();
        $this->deleteJson(route('rm_reports.destroy', $id))->assertForbidden();
        $this->deleteJson(route('rm_reports.destroy.multiple'), ['selected_records' => json_encode([$ordinary->id, $id]), 'workorder_id' => $wo->id])->assertForbidden();
        $this->assertDatabaseHas('rm_reports', ['id' => $ordinary->id]);
        $this->get(route('rm_reports.partial', $wo->id))->assertOk()
            ->assertSeeInOrder(['Protected fixture', 'Ordinary fixture'])
            ->assertSee('id="record_'.$id.'"', false)
            ->assertDontSee('window.rmPartialEditRecord('.$id.')', false)
            ->assertDontSee('window.rmPartialDeleteRecord('.$id.')', false)
            ->assertDontSee('name="is_admin_template"', false);
        $this->putJson(route('rm_reports.update', $wo->id), ['workorder_id' => $wo->id, 'selected_records' => json_encode([$id])])->assertOk();
        $this->assertEquals($id, json_decode($wo->fresh()->rm_report, true)['rm_records'][0]['id']);
        $this->putJson(route('rm_reports.update', $wo->id), ['workorder_id' => $wo->id, 'selected_records' => '[]'])->assertOk();
        $this->assertEmpty(json_decode($wo->fresh()->rm_report, true)['rm_records'] ?? []);
        $this->putJson(route('rm_reports.updateRecord', $ordinary->id), array_merge($payload, ['is_admin_template' => false]))->assertOk();
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()])->putJson(route('rm_reports.updateRecord', $id), array_merge($payload, ['mod_repair_description' => 'Admin revision']))->assertOk();
        $this->assertDatabaseHas('rm_reports', ['id' => $id, 'description' => 'Admin revision', 'is_admin_template' => true]);
        $this->deleteJson(route('rm_reports.destroy', $id), ['workorder_id' => $wo->id])->assertOk();
        $this->assertDatabaseMissing('rm_reports', ['id' => $id]);
    }
}
