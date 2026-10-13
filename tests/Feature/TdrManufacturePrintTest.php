<?php

namespace Tests\Feature;

use App\Models\{Code, Component, Necessary, Tdr};
use App\Services\TdrInspectionLinesBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class TdrManufacturePrintTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_manufacture_pair_prints_once_without_scrap_and_keeps_unpaired_repair(): void
    {
        $wo = $this->createWorkorder();
        $component = Component::create(['manual_id' => $wo->unit->manual_id,
            'part_number' => '170-RS', 'ipl_num' => '1-272RS', 'name' => 'Repair Sleeve (EC)']);
        $code = Code::firstOrCreate(['name' => 'Manufacture']);
        $order = Necessary::firstOrCreate(['name' => 'Order New']);
        $repair = Necessary::firstOrCreate(['name' => 'Repair']);
        $base = ['workorder_id' => $wo->id, 'component_id' => $component->id,
            'manufacture_pair_id' => (string) \Illuminate\Support\Str::uuid(),
            'codes_id' => $code->id, 'qty' => 4, 'description' => $component->name, 'use_tdr' => true];
        $ordering = Tdr::create($base + ['tdr_type' => Tdr::TYPE_MANUFACTURE_ORDER, 'necessaries_id' => $order->id]);
        Tdr::create($base + ['tdr_type' => Tdr::TYPE_MANUFACTURE_REPAIR, 'necessaries_id' => $repair->id]);
        $lines = app(TdrInspectionLinesBuilder::class)->build($wo->fresh());
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('Manufacture: (1-272RS, 4pcs)', $lines[0]);
        $this->assertSame(1, substr_count($lines[0], 'REPAIR SLEEVE (EC)'));
        $this->assertStringNotContainsString('scrap', $lines[0]);
        $this->assertSame(2, $wo->tdrs()->count());
        $ordering->delete();
        $this->assertCount(1, app(TdrInspectionLinesBuilder::class)->build($wo->fresh()));
    }
}
