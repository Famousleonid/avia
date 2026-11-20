<?php

namespace Tests\Feature;

use App\Models\{Code, Component, Condition, Necessary, Tdr, WorkorderUnitInspection};
use App\Services\TdrInspectionLinesBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class TdrReportIplOrderTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_general_lines_stay_first_and_parts_sort_naturally_across_reasons_without_mutating_tdr(): void
    {
        $wo = $this->createWorkorder();
        $order = Necessary::firstOrCreate(['name' => 'Order New']);
        $repair = Necessary::firstOrCreate(['name' => 'Repair']);
        $worn = Code::firstOrCreate(['name' => 'Worn'], ['code' => 'K']);
        $missing = Code::firstOrCreate(['name' => 'Missing'], ['code' => 'P']);
        Condition::firstOrCreate(['name' => Condition::NAME_PARTS_MISSING], ['unit' => false]);
        $condition = Condition::firstOrCreate(['name' => 'GENERAL INSPECTION'], ['unit' => true]);
        WorkorderUnitInspection::create(['workorder_id' => $wo->id, 'condition_id' => $condition->id,
            'notes' => 'retain this note', 'qty' => 1, 'use_tdr' => true]);
        $sourceIpls = ['10-10', '9A-20', '2-100', '2-20A', '2-20', '9-280B', '2-20RS01'];
        foreach ($sourceIpls as $index => $ipl) {
            $part = Component::create(['manual_id' => $wo->unit->manual_id, 'ipl_num' => $ipl,
                'part_number' => 'QA-'.$index, 'name' => 'PART '.$index]);
            Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id,
                'tdr_type' => Tdr::TYPE_COMPONENT_TDR, 'codes_id' => $worn->id,
                'necessaries_id' => $index % 2 ? $repair->id : $order->id,
                'qty' => 2, 'description' => 'detail '.$index, 'use_tdr' => true]);
        }
        Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR, 'codes_id' => $missing->id,
            'necessaries_id' => $order->id, 'qty' => 1, 'use_tdr' => true]);
        Tdr::create(['workorder_id' => $wo->id, 'component_id' => null,
            'tdr_type' => Tdr::TYPE_STD_LIST_CARRIER, 'conditions_id' => $condition->id,
            'description' => 'legacy condition', 'qty' => 1, 'use_tdr' => true]);
        $before = $wo->tdrs()->get()->toArray();
        $lines = app(TdrInspectionLinesBuilder::class)->build($wo->fresh());
        $this->assertSame('GENERAL INSPECTION (retain this note)', $lines[0]);
        $this->assertSame('GENERAL INSPECTION legacy condition', $lines[1]);
        $this->assertSame(Condition::NAME_PARTS_MISSING, $lines[2]);
        $parts = array_slice($lines, 3);
        $this->assertCount(7, $parts); // No merging of equal defect reasons.
        $ipls = array_map(function ($line) {
            preg_match('/^\(([^,)]+)/', $line, $match);
            return $match[1] ?? null;
        }, $parts);
        $this->assertSame(['2-20', '2-20A', '2-20RS01', '2-100', '9-280B', '9A-20', '10-10'], $ipls);
        $this->assertStringContainsString('(2-20, 2pcs) <b>PART 4</b> - Worn (scrap) ( DETAIL 4 )', $parts[0]);
        $this->assertStringContainsString('(2-20A) <b>PART 3</b> - WORN ( DETAIL 3 )', $parts[1]);
        $this->assertSame($before, $wo->tdrs()->get()->toArray());
    }
}
