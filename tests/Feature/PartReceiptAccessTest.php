<?php

namespace Tests\Feature;

use App\Models\{Component, Necessary, Tdr};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Tests\{BuildsDomainData, TestCase};

class PartReceiptAccessTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_receipts_require_manager_or_admin_and_record_changed_values(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manager = $this->createUserWithRole('Manager');
        $technician = $this->createUserWithRole('Technician');
        $wo = $this->createWorkorder(['user_id' => $technician->id]);
        $part = Component::create(['manual_id' => $wo->unit->manual_id, 'name' => 'Audit part', 'part_number' => 'AUDIT-PN', 'ipl_num' => '1-1']);
        $tdr = Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id, 'order_component_id' => $part->id,
            'necessaries_id' => Necessary::firstOrCreate(['name' => 'Order New'])->id, 'qty' => 1]);
        $url = route('workorders.part-receipt.update', $wo);
        $payload = ['row_key' => 'tdr:'.$tdr->id, 'field' => 'po_num', 'value' => '123'];
        $login = function ($user) { $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()]); };
        $login($technician);
        $this->postJson($url, $payload)->assertForbidden();
        $this->postJson($url, array_merge($payload, ['field' => 'received_qty', 'value' => '1']))->assertForbidden();
        $this->postJson(route('tdrs.updatePartField', $tdr), $payload)->assertForbidden();
        $this->postJson(route('workorders.log-card-transfer.store', $wo), [])->assertForbidden();
        $this->deleteJson(route('workorders.log-card-transfer.destroy', $wo), [])->assertForbidden();
        $this->assertNull($tdr->fresh()->po_num);
        $html = $this->get(route('mains.show', $wo))->assertOk()->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        foreach (['po-no-select', 'po-no-input', 'received-date', 'received-qty'] as $class) {
            $this->assertGreaterThan(0, $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " '.$class.' ") and @disabled]')->length);
        }
        $login($manager);
        $this->postJson($url, $payload)->assertOk();
        $log = Activity::where('log_name', 'part_receipt')->latest('id')->firstOrFail();
        $this->assertEquals($manager->id, $log->causer_id);
        $this->assertSame('123', $log->properties['attributes']['po_num']);
        $this->assertNull($log->properties['old']['po_num']);
        $count = Activity::where('log_name', 'part_receipt')->count();
        $this->postJson($url, $payload)->assertOk();
        $this->assertSame($count, Activity::where('log_name', 'part_receipt')->count());
        $this->postJson($url, array_merge($payload, ['field' => 'received_qty', 'value' => '1']))->assertOk();
        $log = Activity::where('log_name', 'part_receipt')->latest('id')->firstOrFail();
        $this->assertSame(1, $log->properties['attributes']['received_qty']);
        $this->assertEquals($manager->id, $log->causer_id);
        $login($admin);
        $this->postJson($url, array_merge($payload, ['value' => '456']))->assertOk();
        $log = Activity::where('log_name', 'part_receipt')->latest('id')->firstOrFail();
        $this->assertEquals($admin->id, $log->causer_id);
        $this->assertSame('123', $log->properties['old']['po_num']);
        $this->assertSame('456', $log->properties['attributes']['po_num']);
        $this->postJson($url, array_merge($payload, ['field' => 'received', 'value' => '2026-09-23']))->assertOk();
        $this->postJson($url, array_merge($payload, ['field' => 'received', 'value' => '']))->assertOk();
        $log = Activity::where('log_name', 'part_receipt')->latest('id')->firstOrFail();
        $this->assertSame('2026-09-23', $log->properties['old']['received']);
        $this->assertNull($log->properties['attributes']['received']);
    }
}
