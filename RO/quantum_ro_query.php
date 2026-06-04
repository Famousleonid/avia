<?php
// quantum_ro_query.php
// Frequently changed file.
// Change SQL here when searching for real Quantum fields.

function buildQuantumRoQuery(array $params): array
{
    $daysBack = (int)($params['days_back'] ?? 14);
    $hasDaysFilter = (bool)($params['has_days_filter'] ?? false);
    $changedSince = trim((string)($params['changed_since'] ?? ''));
    $roNumber = $params['ro_number'] ?? null;
    $vendor   = $params['vendor'] ?? null;
    $unresolvedRoNumbers = array_values(array_filter(array_unique(array_map(
        static fn($value): string => strtoupper(trim((string)$value)),
        (array)($params['unresolved_ro_numbers'] ?? [])
    ))));
    $trackedRefRoNumbers = array_values(array_filter(array_unique(array_map(
        static fn($value): string => strtoupper(trim((string)$value)),
        (array)($params['tracked_ref_ro_numbers'] ?? [])
    ))));
    $wobChangeColumn = strtoupper(trim((string)($params['wob_change_column'] ?? '')));
    $wobChangeExpression = preg_match('/^[A-Z][A-Z0-9_]*$/', $wobChangeColumn)
        ? 'wb.' . $wobChangeColumn
        : "DATE '1900-01-01'";
    $wobChangeSelect = preg_match('/^[A-Z][A-Z0-9_]*$/', $wobChangeColumn)
        ? "TO_CHAR({$wobChangeExpression}, 'YYYY-MM-DD HH24:MI:SS')"
        : 'NULL';

    // Current RO part-matching diagnostic mode:
    // Print real RO rows with Quantum part master data from RO_DETAIL.PNM_AUTO_KEY.
    //
    // Current parser routing hint:
    // - PN = NDT or CAD Plate means STD/list bucket
    // - PN = NDTB, CADB, Anodizing, Passivation means bushing batch bucket
    // - PN = real part number means detail/part matching candidate
    //
    // PN/DESC/CLASS are returned as separate columns for easier XLS filtering.

    $sql = "
SELECT
    rh.ROH_AUTO_KEY,
    rd.ROD_AUTO_KEY,
    rd.WOB_AUTO_KEY,
    wb.WOO_AUTO_KEY,
    rd.PNM_AUTO_KEY,
    rh.RO_NUMBER,
    rh.VENDOR_NAME,
    TO_CHAR(rh.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
    TO_CHAR(rh.OUT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS OUT_DATE,
    TO_CHAR(rd.LAST_DELIVERY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS LAST_DELIVERY_DATE,
    TO_CHAR(rh.LAST_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS RO_LAST_MODIFIED,
    TO_CHAR(rd.LAST_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS DETAIL_LAST_MODIFIED,
    {$wobChangeSelect} AS BOM_LAST_MODIFIED,
    TO_CHAR(
        GREATEST(
            NVL(rd.LAST_MODIFIED, DATE '1900-01-01'),
            NVL(rh.LAST_MODIFIED, DATE '1900-01-01'),
            NVL({$wobChangeExpression}, DATE '1900-01-01'),
            NVL(rh.ENTRY_DATE, DATE '1900-01-01')
        ),
        'YYYY-MM-DD HH24:MI:SS'
    ) AS SOURCE_LAST_MODIFIED,
    wo.SI_NUMBER AS WO_NUMBER,
    pm_rd.PN,
    pm_rd.DESCRIPTION AS \"DESC\",
    CASE
        WHEN REPLACE(REPLACE(REPLACE(UPPER(TRIM(pm_rd.PN)), ' ', ''), '-', ''), '_', '') = 'NDT'
            THEN 'STD_LIST_NDT'
        WHEN REPLACE(REPLACE(REPLACE(UPPER(TRIM(pm_rd.PN)), ' ', ''), '-', ''), '_', '') IN ('CAD', 'CADPLATE')
            THEN 'STD_LIST_CAD'
        WHEN REPLACE(REPLACE(REPLACE(UPPER(TRIM(pm_rd.PN)), ' ', ''), '-', ''), '_', '') IN ('NDTB', 'CADB', 'ANODIZING', 'ANODISING', 'PASSIVATION')
            THEN 'BUSHING_' || REPLACE(REPLACE(REPLACE(UPPER(TRIM(pm_rd.PN)), ' ', ''), '-', ''), '_', '')
        WHEN REGEXP_LIKE(pm_rd.PN, '[[:digit:]]') THEN 'DETAIL_PART'
        WHEN pm_rd.PN IS NOT NULL THEN 'UNSUPPORTED_PN'
        ELSE 'UNKNOWN'
    END AS CLASS,
    wb.REF AS BOM_REF,
    rd.QTY_REPAIR,
    rd.QTY_RESERVED,
    rd.QTY_REPAIRED,
    'RD_PNM=' || NVL(TO_CHAR(rd.PNM_AUTO_KEY), '-') ||
        ', WB_PNM=' || NVL(TO_CHAR(wb.PNM_AUTO_KEY), '-') ||
        ', WO_PNM=' || NVL(TO_CHAR(wo.PNM_AUTO_KEY), '-') ||
        ', TO_REPAIR=' || NVL(TO_CHAR(rd.QTY_REPAIR), '-') ||
        ', RESERVED=' || NVL(TO_CHAR(rd.QTY_RESERVED), '-') ||
        ', REPAIRED=' || NVL(TO_CHAR(rd.QTY_REPAIRED), '-') AS QTY
FROM QCTL.RO_HEADER rh
JOIN QCTL.RO_DETAIL rd
    ON rd.ROH_AUTO_KEY = rh.ROH_AUTO_KEY
LEFT JOIN QCTL.WO_BOM wb
    ON wb.WOB_AUTO_KEY = rd.WOB_AUTO_KEY
LEFT JOIN QCTL.WO_OPERATION wo
    ON wo.WOO_AUTO_KEY = wb.WOO_AUTO_KEY
LEFT JOIN QCTL.PARTS_MASTER pm_rd
    ON pm_rd.PNM_AUTO_KEY = rd.PNM_AUTO_KEY
WHERE 1 = 1
";

    $binds = [];
    $buildRoNumberCondition = static function (array $roNumbers, string $prefix, ?int $limit = null) use (&$binds): string {
        $conditions = [];
        $index = 0;
        $roNumbers = $limit === null ? $roNumbers : array_slice($roNumbers, 0, $limit);

        foreach (array_chunk($roNumbers, 900) as $chunk) {
            $bindNames = [];

            foreach ($chunk as $roNumber) {
                if ($roNumber === '' || !preg_match('/^R[0-9A-Z_-]+$/', $roNumber)) {
                    continue;
                }

                $bindName = ':' . $prefix . '_' . $index;
                $binds[$bindName] = $roNumber;
                $bindNames[] = $bindName;
                $index++;
            }

            if ($bindNames) {
                $conditions[] = 'rh.RO_NUMBER IN (' . implode(', ', $bindNames) . ')';
            }
        }

        if ($conditions === []) {
            return '';
        }

        return ' OR (' . implode(' OR ', $conditions) . ')';
    };

    $unresolvedCondition = $buildRoNumberCondition($unresolvedRoNumbers, 'unresolved_ro', 50);
    $trackedRefCondition = $buildRoNumberCondition($trackedRefRoNumbers, 'tracked_ref_ro');

    $refWatchCondition = '';
    if (!$roNumber) {
        $refWatchCondition = "
      OR TRIM(wb.REF) IS NOT NULL
      {$trackedRefCondition}
";
    }

    if ($roNumber) {
        $sql .= "
  AND rh.RO_NUMBER = :ro_number
";
        $binds[':ro_number'] = $roNumber;
    } elseif ($changedSince !== '') {
        $sql .= "
  AND (
      GREATEST(
      NVL(rd.LAST_MODIFIED, DATE '1900-01-01'),
      NVL(rh.LAST_MODIFIED, DATE '1900-01-01'),
      NVL({$wobChangeExpression}, DATE '1900-01-01'),
      NVL(rh.ENTRY_DATE, DATE '1900-01-01')
      ) >= TO_DATE(:changed_since, 'YYYY-MM-DD HH24:MI:SS')
      {$unresolvedCondition}
      {$refWatchCondition}
  )
";
        $binds[':changed_since'] = $changedSince;
    } elseif ($hasDaysFilter) {
        $sql .= "
  AND (
      rh.LAST_MODIFIED >= SYSDATE - :days_back
      OR rd.LAST_MODIFIED >= SYSDATE - :days_back
      OR {$wobChangeExpression} >= SYSDATE - :days_back
      {$unresolvedCondition}
      {$refWatchCondition}
  )
";
        $binds[':days_back'] = $daysBack;
    } else {
        $sql .= "
  AND (
      rh.ENTRY_DATE >= TRUNC(SYSDATE)
      OR rh.LAST_MODIFIED >= TRUNC(SYSDATE)
      OR rd.LAST_MODIFIED >= TRUNC(SYSDATE)
      OR {$wobChangeExpression} >= TRUNC(SYSDATE)
      {$unresolvedCondition}
      {$refWatchCondition}
  )
";
    }

    if ($vendor) {
        $sql .= "
  AND UPPER(rh.VENDOR_NAME) LIKE UPPER(:vendor)
";
        $binds[':vendor'] = '%' . $vendor . '%';
    }

    $sql .= "
ORDER BY
    GREATEST(
        NVL(rd.LAST_MODIFIED, DATE '1900-01-01'),
        NVL(rh.LAST_MODIFIED, DATE '1900-01-01'),
        NVL({$wobChangeExpression}, DATE '1900-01-01'),
        NVL(rh.ENTRY_DATE, DATE '1900-01-01')
    ) DESC,
    rh.RO_NUMBER,
    wo.SI_NUMBER,
    rd.ITEM_NUMBER
";

    return [
        'sql'   => $sql,
        'binds' => $binds,
    ];
}
