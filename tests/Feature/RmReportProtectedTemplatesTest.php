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
        $this->putJson(route('rm_reports.updateRecord', $ordinary->id), array_merge($payload, ['is_admin_template' => false]))->assertForbidden();
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()])->putJson(route('rm_reports.updateRecord', $id), array_merge($payload, ['mod_repair_description' => 'Admin revision']))->assertOk();
        $this->assertDatabaseHas('rm_reports', ['id' => $id, 'description' => 'Admin revision', 'is_admin_template' => true]);
        $this->deleteJson(route('rm_reports.destroy', $id), ['workorder_id' => $wo->id])->assertOk();
        $this->assertDatabaseMissing('rm_reports', ['id' => $id]);
    }

    public function test_templates_require_manager_or_admin_but_technicians_can_use_and_customize_them(): void
    {
        $manager = $this->createUserWithRole('Manager', ['is_admin' => false]);
        $tech = $this->createUserWithRole('Technician');
        $manual = $this->createManual();
        $otherManual = $this->createManual();
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $wo = $this->createWorkorder(['unit_id' => $unit->id, 'user_id' => $tech->id]);
        $payload = ['workorder_id' => $wo->id, 'part_description' => 'Shared manual solution', 'mod_repair' => 'Repair', 'mod_repair_description' => 'Repair template'];
        $this->actingAs($manager)->withSession(['auth.version' => (int) $manager->auth_version, 'password_hash_web' => $manager->getAuthPassword()]);
        $id = $this->postJson(route('rm_reports.store'), $payload)->assertOk()->json('data.id');
        $secondId = $this->postJson(route('rm_reports.store'), $payload)->assertOk()->json('data.id');
        $this->putJson(route('rm_reports.updateRecord', $id), array_merge($payload, ['mod_repair_description' => 'Manager revision']))->assertOk();
        $protected = RmReport::create(['manual_id' => $manual->id, 'part_description' => 'Protected manager boundary', 'mod_repair' => 'Repair', 'description' => 'Protected', 'is_admin_template' => true]);
        RmReport::create(['manual_id' => $otherManual->id, 'part_description' => 'Other manual solution', 'mod_repair' => 'Repair', 'description' => 'Unrelated']);
        $this->postJson(route('rm_reports.store'), array_merge($payload, ['is_admin_template' => true]))->assertForbidden();
        $this->putJson(route('rm_reports.updateRecord', $protected->id), $payload)->assertForbidden();
        $this->deleteJson(route('rm_reports.destroy', $protected->id))->assertForbidden();
        $this->get(route('rm_reports.partial', $wo->id))->assertOk()->assertSee('id="addRmRecordModal"', false)
            ->assertSee('window.rmPartialEditRecord('.$id.')', false)->assertDontSee('window.rmPartialEditRecord('.$protected->id.')', false);

        $this->actingAs($tech)->withSession(['auth.version' => (int) $tech->auth_version, 'password_hash_web' => $tech->getAuthPassword()]);
        $this->postJson(route('rm_reports.store'), $payload)->assertForbidden();
        $this->putJson(route('rm_reports.updateRecord', $id), $payload)->assertForbidden();
        $this->deleteJson(route('rm_reports.destroy', $id))->assertForbidden();
        $this->deleteJson(route('rm_reports.destroy.multiple'), ['selected_records' => json_encode([$id, $secondId]), 'workorder_id' => $wo->id])->assertForbidden();
        $this->get(route('rm_reports.partial', $wo->id))->assertOk()
            ->assertSee('id="rmTemplatesDrawer"', false)->assertSee('data-bs-target="#rmTemplatesDrawer"', false)
            ->assertSee('id="record_'.$id.'"', false)->assertDontSee('Other manual solution')
            ->assertDontSee('id="addRmRecordModal"', false)->assertDontSee('id="editRmRecordModal"', false)
            ->assertDontSee('window.rmPartialEditRecord('.$id.')', false)->assertDontSee('window.rmPartialDeleteRecord('.$id.')', false);
        $this->putJson(route('rm_reports.update', $wo->id), [
            'workorder_id' => $wo->id, 'selected_records' => json_encode([$id]), 'notes' => ['WO note'],
            'editor_rows' => [['key' => 'record:'.$id, 'ipl' => '13-70RS', 'part_description' => 'WO repair sleeve', 'description' => 'WO repair only', 'mod_repair' => 'Repair', 'deleted' => false]],
        ])->assertOk();
        $saved = json_decode($wo->fresh()->rm_report, true);
        $this->assertEquals($id, $saved['rm_records'][0]['id']);
        $this->assertSame('WO repair sleeve', collect($saved['editor_rows'])->firstWhere('key', 'record:'.$id)['part_description']);
        $this->assertSame('WO note', $saved['technical_notes'][0]);
        $this->assertDatabaseHas('rm_reports', ['id' => $id, 'description' => 'Manager revision', 'part_description' => 'Shared manual solution']);
        $this->putJson(route('rm_reports.update', $wo->id), ['workorder_id' => $wo->id, 'selected_records' => '[]'])->assertOk();
        $this->actingAs($manager)->withSession(['auth.version' => (int) $manager->auth_version, 'password_hash_web' => $manager->getAuthPassword()]);
        $this->deleteJson(route('rm_reports.destroy', $id), ['workorder_id' => $wo->id])->assertOk();
        $this->deleteJson(route('rm_reports.destroy.multiple'), ['selected_records' => json_encode([$secondId]), 'workorder_id' => $wo->id])->assertRedirect();
        $this->assertDatabaseMissing('rm_reports', ['id' => $id]);
        $this->assertDatabaseMissing('rm_reports', ['id' => $secondId]);
    }
}
