<?php
// check_quantum_wob_change_columns.php
// Read-only diagnostic: finds change-date columns on QCTL.WO_BOM.
//
// Usage:
// php check_quantum_wob_change_columns.php

$oracleUser = getenv('ORACLE_USER');
$oraclePass = getenv('ORACLE_PASS');
$oracleDsn  = getenv('ORACLE_DSN') ?: 'MAXQPROD';

if (!$oracleUser || !$oraclePass) {
    fwrite(STDERR, "Missing Oracle environment variables ORACLE_USER / ORACLE_PASS\n");
    exit(1);
}

$conn = oci_connect($oracleUser, $oraclePass, $oracleDsn, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    fwrite(STDERR, "Oracle connect error: " . $e['message'] . "\n");
    exit(1);
}

$sql = "
SELECT
    column_name,
    data_type,
    data_length,
    nullable,
    column_id
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND table_name = 'WO_BOM'
  AND (
      column_name LIKE '%MOD%'
      OR column_name LIKE '%CHG%'
      OR column_name LIKE '%DATE%'
      OR column_name LIKE '%UPD%'
      OR column_name LIKE '%TIME%'
      OR column_name LIKE '%REF%'
  )
ORDER BY column_id
";

$rows = fetchAll($conn, $sql);
$stamp = date('Ymd_His');
$path = __DIR__ . DIRECTORY_SEPARATOR . "quantum_wob_change_columns_{$stamp}.csv";

$handle = fopen($path, 'wb');
fputcsv($handle, ['COLUMN_NAME', 'DATA_TYPE', 'DATA_LENGTH', 'NULLABLE', 'COLUMN_ID']);
foreach ($rows as $row) {
    fputcsv($handle, [
        $row['COLUMN_NAME'],
        $row['DATA_TYPE'],
        $row['DATA_LENGTH'],
        $row['NULLABLE'],
        $row['COLUMN_ID'],
    ]);
}
fclose($handle);

echo "QCTL.WO_BOM change/ref columns\n";
echo "Rows: " . count($rows) . "\n";
foreach ($rows as $row) {
    echo $row['COLUMN_NAME'] . " | " . $row['DATA_TYPE'] . " | nullable=" . $row['NULLABLE'] . "\n";
}
echo "CSV: {$path}\n";

oci_close($conn);

function fetchAll($conn, string $sql): array
{
    if (str_contains($sql, ';')) {
        throw new RuntimeException('Blocked: semicolon is not allowed in SQL');
    }

    $stid = oci_parse($conn, $sql);

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
