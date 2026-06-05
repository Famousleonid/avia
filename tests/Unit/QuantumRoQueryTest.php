<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../RO/quantum_ro_query.php';

class QuantumRoQueryTest extends TestCase
{
    public function test_incremental_query_watches_all_non_empty_bom_ref_rows_without_date_window(): void
    {
        $query = buildQuantumRoQuery([
            'days_back' => 90,
            'changed_since' => '2026-06-01 09:16:05',
            'wob_change_column' => '',
        ]);

        $this->assertStringContainsString('TRIM(wb.REF) IS NOT NULL', $query['sql']);
        $this->assertStringNotContainsString(':ref_watch_days', $query['sql']);
        $this->assertStringNotContainsString('SYSDATE - :ref_watch_days', $query['sql']);
        $this->assertArrayNotHasKey(':ref_watch_days', $query['binds']);
    }

    public function test_incremental_query_rechecks_tracked_ref_ro_numbers(): void
    {
        $query = buildQuantumRoQuery([
            'changed_since' => '2026-06-01 09:16:05',
            'tracked_ref_ro_numbers' => ['R8908', 'r1234', 'bad value', 'R8908'],
            'wob_change_column' => '',
        ]);

        $this->assertStringContainsString('rh.RO_NUMBER IN (:tracked_ref_ro_0, :tracked_ref_ro_1)', $query['sql']);
        $this->assertSame('R8908', $query['binds'][':tracked_ref_ro_0']);
        $this->assertSame('R1234', $query['binds'][':tracked_ref_ro_1']);
        $this->assertArrayNotHasKey(':tracked_ref_ro_2', $query['binds']);
    }

    public function test_specific_ro_query_does_not_add_ref_watch_filter(): void
    {
        $query = buildQuantumRoQuery([
            'ro_number' => 'R8908',
            'wob_change_column' => '',
        ]);

        $this->assertStringNotContainsString(':ref_watch_days', $query['sql']);
        $this->assertStringNotContainsString('TRIM(wb.REF) IS NOT NULL', $query['sql']);
        $this->assertArrayNotHasKey(':ref_watch_days', $query['binds']);
        $this->assertSame('R8908', $query['binds'][':ro_number']);
    }

    public function test_cad_plate_part_number_is_classified_as_cad_std_list(): void
    {
        $query = buildQuantumRoQuery([
            'wob_change_column' => '',
        ]);

        $this->assertStringContainsString("IN ('CAD', 'CADPLATE')", $query['sql']);
        $this->assertStringContainsString("THEN 'STD_LIST_CAD'", $query['sql']);
    }

    public function test_cad_plate_b_part_number_is_classified_as_bushing_candidate(): void
    {
        $query = buildQuantumRoQuery([
            'wob_change_column' => '',
        ]);

        $this->assertStringContainsString("'CADPLATEB'", $query['sql']);
        $this->assertStringContainsString("THEN 'BUSHING_'", $query['sql']);
    }
}
