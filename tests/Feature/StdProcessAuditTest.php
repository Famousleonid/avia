<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\StdProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class StdProcessAuditTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_manual_std_tab_warns_when_same_numeric_ipl_has_mixed_processes(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual([
            'number' => 'CMM-AUDIT-WARN',
            'title' => 'Audit Warning Manual',
        ]);

        $base = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-445',
            'part_number' => '170-70254-003',
            'name' => 'Stay Upper',
            'units_assy' => 1,
            'ndt_list' => false,
        ]);
        $variant = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '1-445A',
            'part_number' => '170-70254-001',
            'name' => 'Different Name Is Still Same IPL Group',
            'units_assy' => 1,
            'ndt_list' => false,
        ]);
        $base->updateQuietly(['ndt_list' => true]);
        $variant->updateQuietly(['ndt_list' => true]);

        StdProcess::query()->create([
            'manual_id' => $manual->id,
            'component_id' => $base->id,
            'std' => StdProcess::STD_NDT,
            'process' => '1',
            'qty' => 1,
        ]);
        StdProcess::query()->create([
            'manual_id' => $manual->id,
            'component_id' => $variant->id,
            'std' => StdProcess::STD_NDT,
            'process' => '4',
            'qty' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('manuals.show', [
            'manual' => $manual->id,
            'tab' => 'std',
            'std_inner' => StdProcess::STD_NDT,
        ]));

        $response->assertOk();
        $response->assertSee('Mixed process', false);
        $response->assertSee('Same numeric IPL group 1-445 has mixed process values: 1, 4', false);
        $response->assertSee('1-445A', false);
        $response->assertSee('170-70254-001', false);
    }

    public function test_system_std_audit_page_lists_conflicts_for_system_admin_only(): void
    {
        $systemAdmin = $this->createUserWithRole('Admin');
        $plainAdmin = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $manual = $this->createManual([
            'number' => 'CMM-AUDIT-PAGE',
            'title' => 'Audit Page Manual',
        ]);

        $first = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '6-70RS',
            'part_number' => 'PN-A',
            'name' => 'First Audit Part',
            'units_assy' => 1,
            'cad_list' => false,
        ]);
        $second = Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '6-70RS20',
            'part_number' => 'PN-B',
            'name' => 'Second Audit Part',
            'units_assy' => 1,
            'cad_list' => false,
        ]);
        $first->updateQuietly(['cad_list' => true]);
        $second->updateQuietly(['cad_list' => true]);

        StdProcess::query()->create([
            'manual_id' => $manual->id,
            'component_id' => $first->id,
            'std' => StdProcess::STD_CAD,
            'process' => 'CAD-1',
            'qty' => 1,
        ]);
        StdProcess::query()->create([
            'manual_id' => $manual->id,
            'component_id' => $second->id,
            'std' => StdProcess::STD_CAD,
            'process' => 'CAD-2',
            'qty' => 1,
        ]);

        $this->actingAs($plainAdmin)
            ->get(route('admin.std-process-audit.index'))
            ->assertForbidden();

        $response = $this->actingAs($systemAdmin)
            ->get(route('admin.std-process-audit.index', ['std' => StdProcess::STD_CAD]));

        $response->assertOk();
        $response->assertSee('CMM-AUDIT-PAGE');
        $response->assertSee('6-70');
        $response->assertSee('CAD-1');
        $response->assertSee('CAD-2');
        $response->assertSee('6-70RS20');
    }
}
