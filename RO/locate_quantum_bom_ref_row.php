<?php
// locate_quantum_bom_ref_row.php
// Read-only helper: locate the Quantum WO_BOM row that stores the UI Ref field.
//
// Usage:
// php locate_quantum_bom_ref_row.php --rod=32330
// php locate_quantum_bom_ref_row.php --wob=709300
// php locate_quantum_bom_ref_row.php --ro=R8934 --wo=W107731

$oracleUser = getenv('ORACLE_USER');
$oraclePass = getenv('ORACLE_PASS');
$oracleDsn  = getenv('ORACLE_DSN') ?: 'MAXQPROD';

if (!$oracleUser || !$oraclePass) {
    fwrite(STDERR, "Missing Oracle environment variables ORACLE_USER / ORACLE_PASS\n");
    exit(1);
}

$params = readCliParams();
$conn = oci_connect($oracleUser, $oraclePass, $oracleDsn, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    fwrite(STDERR, "Oracle connect error: " . $e['message'] . "\n");
    exit(1);
}

$target = findTarget($conn, $params);

if (!$target) {
    fwrite(STDERR, "Target row not found\n");
    oci_close($conn);
    exit(1);
}

$bomRows = fetchBomRows($conn, (int)$target['WOO_AUTO_KEY']);
$refRows = array_values(array_filter($bomRows, static fn(array $row): bool => trim((string)($row['REF'] ?? '')) !== ''));

$stamp = date('Ymd_His');
$path = __DIR__ . DIRECTORY_SEPARATOR . "quantum_bom_ref_location_{$stamp}.csv";

$handle = fopen($path, 'wb');
fputcsv($handle, [
    'MARK',
    'RO_NUMBER',
    'WO_NUMBER',
    'ROD_AUTO_KEY',
    'WOB_AUTO_KEY',
    'WOO_AUTO_KEY',
    'ITEM_NUMBER',
    'SEQUENCE',
    'PN',
    'DESCRIPTION',
    'REF',
    'QTY_NEEDED',
    'QTY_RESERVED',
    'QTY_ISSUED',
    'ENTRY_DATE',
    'CREATED_BY',
    'REQUESTED_BY',
]);

foreach ($bomRows as $row) {
    fputcsv($handle, [
        (int)$row['WOB_AUTO_KEY'] === (int)$target['WOB_AUTO_KEY'] ? 'TARGET' : '',
        $target['RO_NUMBER'],
        $target['WO_NUMBER'],
        $target['ROD_AUTO_KEY'],
        $row['WOB_AUTO_KEY'],
        $row['WOO_AUTO_KEY'],
        $row['ITEM_NUMBER'] ?? '',
        $row['SEQUENCE'] ?? '',
        $row['PN'] ?? '',
        $row['DESCRIPTION'] ?? '',
        $row['REF'] ?? '',
        $row['QTY_NEEDED'] ?? '',
        $row['QTY_RESERVED'] ?? '',
        $row['QTY_ISSUED'] ?? '',
        $row['ENTRY_DATE'] ?? '',
        $row['CREATED_BY'] ?? '',
        $row['REQUESTED_BY'] ?? '',
    ]);
}

fclose($handle);

echo "Quantum BOM Ref location\n";
echo "RO: " . ($target['RO_NUMBER'] ?? '-') . "\n";
echo "WO: " . ($target['WO_NUMBER'] ?? '-') . "\n";
echo "ROD_AUTO_KEY: " . ($target['ROD_AUTO_KEY'] ?? '-') . "\n";
echo "WOB_AUTO_KEY: " . ($target['WOB_AUTO_KEY'] ?? '-') . "\n";
echo "WOO_AUTO_KEY: " . ($target['WOO_AUTO_KEY'] ?? '-') . "\n";
echo "PN: " . ($target['PN'] ?? '-') . "\n";
echo "Description: " . ($target['DESCRIPTION'] ?? '-') . "\n";
echo "REF: " . ($target['REF'] ?? '-') . "\n";
echo "\n";
echo "BOM rows on this WO: " . count($bomRows) . "\n";
echo "BOM rows with REF on this WO: " . count($refRows) . "\n";

foreach ($refRows as $row) {
    echo "REF ROW: WOB_AUTO_KEY=" . $row['WOB_AUTO_KEY']
        . " PN=" . ($row['PN'] ?? '-')
        . " DESC=" . ($row['DESCRIPTION'] ?? '-')
        . " REF=" . ($row['REF'] ?? '-')
        . "\n";
}

echo "CSV: {$path}\n";

oci_close($conn);

function readCliParams(): array
{
    global $argv;

    $params = [];

    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--rod=')) {
            $params['rod'] = substr($arg, 6);
        }

        if (str_starts_with($arg, '--wob=')) {
            $params['wob'] = substr($arg, 6);
        }

        if (str_starts_with($arg, '--ro=')) {
            $params['ro'] = strtoupper(trim(substr($arg, 5)));
        }

        if (str_starts_with($arg, '--wo=')) {
            $params['wo'] = strtoupper(trim(substr($arg, 5)));
        }
    }

    return $params;
}

function findTarget($conn, array $params): ?array
{
    $where = '';
    $binds = [];

    if (!empty($params['rod'])) {
        $where = 'rd.ROD_AUTO_KEY = :rod_auto_key';
        $binds[':rod_auto_key'] = (int)$params['rod'];
    } elseif (!empty($params['wob'])) {
        $where = 'wb.WOB_AUTO_KEY = :wob_auto_key';
        $binds[':wob_auto_key'] = (int)$params['wob'];
    } else {
        $where = 'rh.RO_NUMBER = :ro_number AND wo.SI_NUMBER = :wo_number';
        $binds[':ro_number'] = $params['ro'] ?? 'R8934';
        $binds[':wo_number'] = $params['wo'] ?? 'W107731';
    }

    $sql = "
SELECT
    rh.RO_NUMBER,
    wo.SI_NUMBER AS WO_NUMBER,
    rd.ROD_AUTO_KEY,
    wb.WOB_AUTO_KEY,
    wb.WOO_AUTO_KEY,
    pm.PN,
    pm.DESCRIPTION,
    wb.REF
FROM QCTL.RO_DETAIL rd
JOIN QCTL.RO_HEADER rh
    ON rh.ROH_AUTO_KEY = rd.ROH_AUTO_KEY
LEFT JOIN QCTL.WO_BOM wb
    ON wb.WOB_AUTO_KEY = rd.WOB_AUTO_KEY
LEFT JOIN QCTL.WO_OPERATION wo
    ON wo.WOO_AUTO_KEY = wb.WOO_AUTO_KEY
LEFT JOIN QCTL.PARTS_MASTER pm
    ON pm.PNM_AUTO_KEY = rd.PNM_AUTO_KEY
WHERE {$where}
ORDER BY rd.ROD_AUTO_KEY DESC
FETCH FIRST 1 ROWS ONLY
";

    $rows = fetchAll($conn, $sql, $binds);

    return $rows[0] ?? null;
}

function fetchBomRows($conn, int $wooAutoKey): array
{
    $available = availableColumns($conn, 'WO_BOM');
    $selects = [
        'wb.WOB_AUTO_KEY',
        'wb.WOO_AUTO_KEY',
        in_array('ITEM_NUMBER', $available, true) ? 'wb.ITEM_NUMBER' : 'NULL AS ITEM_NUMBER',
        in_array('SEQUENCE', $available, true) ? 'wb.SEQUENCE' : 'NULL AS SEQUENCE',
        'pm.PN',
        'pm.DESCRIPTION',
        'wb.REF',
        in_array('QTY_NEEDED', $available, true) ? 'wb.QTY_NEEDED' : 'NULL AS QTY_NEEDED',
        in_array('QTY_RESERVED', $available, true) ? 'wb.QTY_RESERVED' : 'NULL AS QTY_RESERVED',
        in_array('QTY_ISSUED', $available, true) ? 'wb.QTY_ISSUED' : 'NULL AS QTY_ISSUED',
        in_array('ENTRY_DATE', $available, true) ? "TO_CHAR(wb.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE" : 'NULL AS ENTRY_DATE',
        in_array('CREATED_BY', $available, true) ? 'wb.CREATED_BY' : 'NULL AS CREATED_BY',
        in_array('REQUESTED_BY', $available, true) ? 'wb.REQUESTED_BY' : 'NULL AS REQUESTED_BY',
    ];

    $orderBy = in_array('ITEM_NUMBER', $available, true)
        ? 'wb.ITEM_NUMBER, wb.WOB_AUTO_KEY'
        : 'wb.WOB_AUTO_KEY';

    $sql = "
SELECT
    " . implode(",\n    ", $selects) . "
FROM QCTL.WO_BOM wb
LEFT JOIN QCTL.PARTS_MASTER pm
    ON pm.PNM_AUTO_KEY = wb.PNM_AUTO_KEY
WHERE wb.WOO_AUTO_KEY = :woo_auto_key
ORDER BY {$orderBy}
";

    return fetchAll($conn, $sql, [':woo_auto_key' => $wooAutoKey]);
}

function availableColumns($conn, string $tableName): array
{
    $sql = "
SELECT column_name
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND table_name = :table_name
";

    $rows = fetchAll($conn, $sql, [':table_name' => strtoupper($tableName)]);

    return array_map(static fn(array $row): string => strtoupper((string)$row['COLUMN_NAME']), $rows);
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
