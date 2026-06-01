<?php
// audit_quantum_bom_ref_usage.php
// Read-only audit: checks whether Quantum WO_BOM.REF was used on RO rows
// during the last N days.
//
// Usage:
// php audit_quantum_bom_ref_usage.php
// php audit_quantum_bom_ref_usage.php --days=730

$oracleUser = getenv('ORACLE_USER');
$oraclePass = getenv('ORACLE_PASS');
$oracleDsn  = getenv('ORACLE_DSN') ?: 'MAXQPROD';

if (!$oracleUser || !$oraclePass) {
    fwrite(STDERR, "Missing Oracle environment variables ORACLE_USER / ORACLE_PASS\n");
    exit(1);
}

$params = readCliParams();
$daysBack = isset($params['days']) ? max(1, (int)$params['days']) : 730;
$stamp = date('Ymd_His');
$allTimePath = __DIR__ . DIRECTORY_SEPARATOR . "quantum_bom_ref_usage_all_time_summary_{$stamp}.csv";
$summaryPath = __DIR__ . DIRECTORY_SEPARATOR . "quantum_bom_ref_usage_summary_{$stamp}.csv";
$detailsPath = __DIR__ . DIRECTORY_SEPARATOR . "quantum_bom_ref_usage_details_{$stamp}.csv";

$conn = oci_connect($oracleUser, $oraclePass, $oracleDsn, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    fwrite(STDERR, "Oracle connect error: " . $e['message'] . "\n");
    exit(1);
}

$allTimeSql = "
SELECT
    TRIM(REF) AS BOM_REF,
    COUNT(*) AS ROW_COUNT
FROM QCTL.WO_BOM
WHERE REF IS NOT NULL
  AND TRIM(REF) IS NOT NULL
  AND TRIM(REF) <> '-'
GROUP BY TRIM(REF)
ORDER BY ROW_COUNT DESC, BOM_REF
";

$summarySql = "
SELECT
    TRIM(wb.REF) AS BOM_REF,
    COUNT(*) AS ROW_COUNT,
    COUNT(DISTINCT rh.RO_NUMBER) AS RO_COUNT,
    COUNT(DISTINCT wo.SI_NUMBER) AS WO_COUNT,
    TO_CHAR(MIN(NVL(rh.OUT_DATE, rh.ENTRY_DATE)), 'YYYY-MM-DD HH24:MI:SS') AS FIRST_RO_DATE,
    TO_CHAR(MAX(NVL(rh.OUT_DATE, rh.ENTRY_DATE)), 'YYYY-MM-DD HH24:MI:SS') AS LAST_RO_DATE,
    TO_CHAR(MAX(GREATEST(
        NVL(rd.LAST_MODIFIED, DATE '1900-01-01'),
        NVL(rh.LAST_MODIFIED, DATE '1900-01-01'),
        NVL(rh.ENTRY_DATE, DATE '1900-01-01')
    )), 'YYYY-MM-DD HH24:MI:SS') AS LAST_SOURCE_MODIFIED
FROM QCTL.RO_HEADER rh
JOIN QCTL.RO_DETAIL rd
    ON rd.ROH_AUTO_KEY = rh.ROH_AUTO_KEY
LEFT JOIN QCTL.WO_BOM wb
    ON wb.WOB_AUTO_KEY = rd.WOB_AUTO_KEY
LEFT JOIN QCTL.WO_OPERATION wo
    ON wo.WOO_AUTO_KEY = wb.WOO_AUTO_KEY
WHERE (
      rh.ENTRY_DATE >= TRUNC(SYSDATE) - :days_back
      OR rh.OUT_DATE >= TRUNC(SYSDATE) - :days_back
      OR rh.LAST_MODIFIED >= TRUNC(SYSDATE) - :days_back
      OR rd.LAST_MODIFIED >= TRUNC(SYSDATE) - :days_back
      OR rd.LAST_DELIVERY_DATE >= TRUNC(SYSDATE) - :days_back
  )
  AND wb.REF IS NOT NULL
  AND TRIM(wb.REF) IS NOT NULL
  AND TRIM(wb.REF) <> '-'
GROUP BY TRIM(wb.REF)
ORDER BY ROW_COUNT DESC, BOM_REF
";

$detailsSql = "
SELECT
    rh.RO_NUMBER,
    wo.SI_NUMBER AS WO_NUMBER,
    rh.VENDOR_NAME,
    pm.PN,
    pm.DESCRIPTION,
    TRIM(wb.REF) AS BOM_REF,
    rd.ROD_AUTO_KEY,
    rd.WOB_AUTO_KEY,
    wb.WOO_AUTO_KEY,
    TO_CHAR(rh.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
    TO_CHAR(rh.OUT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS OUT_DATE,
    TO_CHAR(rd.LAST_DELIVERY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS LAST_DELIVERY_DATE,
    TO_CHAR(GREATEST(
        NVL(rd.LAST_MODIFIED, DATE '1900-01-01'),
        NVL(rh.LAST_MODIFIED, DATE '1900-01-01'),
        NVL(rh.ENTRY_DATE, DATE '1900-01-01')
    ), 'YYYY-MM-DD HH24:MI:SS') AS SOURCE_LAST_MODIFIED
FROM QCTL.RO_HEADER rh
JOIN QCTL.RO_DETAIL rd
    ON rd.ROH_AUTO_KEY = rh.ROH_AUTO_KEY
LEFT JOIN QCTL.WO_BOM wb
    ON wb.WOB_AUTO_KEY = rd.WOB_AUTO_KEY
LEFT JOIN QCTL.WO_OPERATION wo
    ON wo.WOO_AUTO_KEY = wb.WOO_AUTO_KEY
LEFT JOIN QCTL.PARTS_MASTER pm
    ON pm.PNM_AUTO_KEY = rd.PNM_AUTO_KEY
WHERE (
      rh.ENTRY_DATE >= TRUNC(SYSDATE) - :days_back
      OR rh.OUT_DATE >= TRUNC(SYSDATE) - :days_back
      OR rh.LAST_MODIFIED >= TRUNC(SYSDATE) - :days_back
      OR rd.LAST_MODIFIED >= TRUNC(SYSDATE) - :days_back
      OR rd.LAST_DELIVERY_DATE >= TRUNC(SYSDATE) - :days_back
  )
  AND wb.REF IS NOT NULL
  AND TRIM(wb.REF) IS NOT NULL
  AND TRIM(wb.REF) <> '-'
ORDER BY
    GREATEST(
        NVL(rd.LAST_MODIFIED, DATE '1900-01-01'),
        NVL(rh.LAST_MODIFIED, DATE '1900-01-01'),
        NVL(rh.ENTRY_DATE, DATE '1900-01-01')
    ) DESC,
    rh.RO_NUMBER,
    wo.SI_NUMBER,
    rd.ROD_AUTO_KEY
";

$allTimeSummary = fetchAll($conn, $allTimeSql);
$summary = fetchAll($conn, $summarySql, [':days_back' => $daysBack]);
$details = fetchAll($conn, $detailsSql, [':days_back' => $daysBack]);

writeAssocCsv($allTimePath, $allTimeSummary, [
    'BOM_REF',
    'ROW_COUNT',
]);

writeAssocCsv($summaryPath, $summary, [
    'BOM_REF',
    'ROW_COUNT',
    'RO_COUNT',
    'WO_COUNT',
    'FIRST_RO_DATE',
    'LAST_RO_DATE',
    'LAST_SOURCE_MODIFIED',
]);

writeAssocCsv($detailsPath, $details, [
    'RO_NUMBER',
    'WO_NUMBER',
    'VENDOR_NAME',
    'PN',
    'DESCRIPTION',
    'BOM_REF',
    'ROD_AUTO_KEY',
    'WOB_AUTO_KEY',
    'WOO_AUTO_KEY',
    'ENTRY_DATE',
    'OUT_DATE',
    'LAST_DELIVERY_DATE',
    'SOURCE_LAST_MODIFIED',
]);

echo "Quantum WO_BOM.REF usage audit\n";
echo "Days back: {$daysBack}\n";
echo "All-time non-empty REF groups: " . count($allTimeSummary) . "\n";
echo "All-time non-empty REF rows: " . array_sum(array_map(static fn (array $row): int => (int)$row['ROW_COUNT'], $allTimeSummary)) . "\n";
echo "Non-empty REF groups: " . count($summary) . "\n";
echo "Non-empty REF rows: " . count($details) . "\n";

foreach (array_slice($allTimeSummary, 0, 20) as $row) {
    echo 'ALL TIME: ' . $row['BOM_REF'] . ' | rows=' . $row['ROW_COUNT'] . "\n";
}

foreach (array_slice($summary, 0, 20) as $row) {
    echo 'RO WINDOW: ' . $row['BOM_REF'] . ' | rows=' . $row['ROW_COUNT'] . ' | ROs=' . $row['RO_COUNT'] . ' | WOs=' . $row['WO_COUNT'] . "\n";
}

echo "All-time CSV: {$allTimePath}\n";
echo "Summary CSV: {$summaryPath}\n";
echo "Details CSV: {$detailsPath}\n";

oci_close($conn);

function readCliParams(): array
{
    global $argv;

    $params = [];

    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--days=')) {
            $params['days'] = substr($arg, 7);
        }
    }

    return $params;
}

function fetchAll($conn, string $sql, array $binds = []): array
{
    if (str_contains($sql, ';')) {
        throw new RuntimeException('Blocked: semicolon is not allowed in SQL');
    }

    $stid = oci_parse($conn, $sql);

    foreach ($binds as $name => $value) {
        oci_bind_by_name($stid, $name, $binds[$name]);
    }

    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        oci_free_statement($stid);
        throw new RuntimeException('Oracle query error: ' . $e['message']);
    }

    $rows = [];

    while ($row = oci_fetch_assoc($stid)) {
        $rows[] = $row;
    }

    oci_free_statement($stid);

    return $rows;
}

function writeAssocCsv(string $path, array $rows, array $columns): void
{
    $handle = fopen($path, 'wb');
    fputcsv($handle, $columns);

    foreach ($rows as $row) {
        $line = [];

        foreach ($columns as $column) {
            $line[] = $row[$column] ?? null;
        }

        fputcsv($handle, $line);
    }

    fclose($handle);
}
