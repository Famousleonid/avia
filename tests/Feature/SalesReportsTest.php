<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class SalesReportsTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_manager_can_build_customer_sales_report(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $customer = $this->createCustomer(['name' => 'Liebherr-Aerospace LLI']);
        $plane = $this->createPlane(['type' => 'E170']);
        $manual = $this->createManual(['planes_id' => $plane->id]);
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => '2309-2200-153',
            'description' => 'MLG Shock Strut',
        ]);

        $workorder = $this->createWorkorder([
            'customer_id' => $customer->id,
            'unit_id' => $unit->id,
            'number' => 107548,
            'serial_number' => 'DL5263',
            'description' => 'MLG Shock Strut',
            'open_at' => '2026-06-10 08:00:00',
        ]);

        $response = $this->actingAs($manager)->get(route('sales-reports.index', [
            'run' => 1,
            'report_type' => 'customer',
            'customer_id' => $customer->id,
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]));

        $response->assertOk();
        $response->assertSee('Sales Report - Customer');
        $response->assertSee('Liebherr-Aerospace LLI');
        $response->assertSee('W' . $workorder->number);
        $response->assertSee('2309-2200-153');
        $response->assertSee('DL5263');
        $response->assertSee('MLG Shock Strut');
        $response->assertSee('from 01/Jan/2026 till 31/Dec/2026');
    }

    public function test_component_sales_report_filters_by_aircraft_type(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $customer = $this->createCustomer(['name' => 'Jazz Aviation LP']);
        $selectedPlane = $this->createPlane(['type' => 'E170']);
        $otherPlane = $this->createPlane(['type' => 'ATR-72']);
        $selectedManual = $this->createManual(['planes_id' => $selectedPlane->id]);
        $otherManual = $this->createManual(['planes_id' => $otherPlane->id]);
        $selectedUnit = $this->createUnit([
            'manual_id' => $selectedManual->id,
            'part_number' => '2309-2200-154',
            'description' => 'NLG Shock Strut',
        ]);
        $otherUnit = $this->createUnit([
            'manual_id' => $otherManual->id,
            'part_number' => 'OTHER-PN',
        ]);

        $this->createWorkorder([
            'customer_id' => $customer->id,
            'unit_id' => $selectedUnit->id,
            'number' => 107549,
            'serial_number' => 'DL5264',
            'description' => 'NLG Shock Strut',
            'open_at' => '2026-03-01 08:00:00',
        ]);
        $this->createWorkorder([
            'customer_id' => $customer->id,
            'unit_id' => $otherUnit->id,
            'number' => 107550,
            'serial_number' => 'DL5265',
            'description' => 'Should Not Show',
            'open_at' => '2026-03-01 08:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('sales-reports.index', [
            'run' => 1,
            'report_type' => 'component',
            'plane_id' => $selectedPlane->id,
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]));

        $response->assertOk();
        $response->assertSee('Sales Report - Components');
        $response->assertSee('E170');
        $response->assertSee('W107549');
        $response->assertSee('2309-2200-154');
        $response->assertDontSee('W107550');
        $response->assertDontSee('Should Not Show');
    }

    public function test_non_manager_cannot_open_sales_reports(): void
    {
        $technician = $this->createUserWithRole('Technician');

        $this->actingAs($technician)
            ->get(route('sales-reports.index'))
            ->assertForbidden();
    }
}
