<?php
// find_quantum_ref_field.php
// Read-only diagnostic for locating the Quantum UI "Ref" field.
//
// Usage:
// php find_quantum_ref_field.php --rod=32330 --value="C P"
// php find_quantum_ref_field.php --ro=R8934 --wo=W107731 --value="C P"

$oracleUser = getenv('ORACLE_USER');
$oraclePass = getenv('ORACLE_PASS');
$oracleDsn  = getenv('ORACLE_DSN') ?: 'MAXQPROD';

if (!$oracleUser || !$oraclePass) {
    fwrite(STDERR, "Missing Oracle environment variables ORACLE_USER / ORACLE_PASS\n");
    exit(1);
}

$params = readCliParams();
$needle = trim((string)($params['value'] ?? 'C P'));
$rodAutoKey = isset($params['rod']) ? (int)$params['rod'] : null;
$roNumber = strtoupper(trim((string)($params['ro'] ?? 'R8934')));
$woNumber = strtoupper(trim((string)($params['wo'] ?? 'W107731')));

$conn = oci_connect($oracleUser, $oraclePass, $oracleDsn, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    fwrite(STDERR, "Oracle connect error: " . $e['message'] . "\n");
    exit(1);
}

$target = findTargetRow($conn, $rodAutoKey, $roNumber, $woNumber);
if (!$target) {
    fwrite(STDERR, "Target Quantum row not found\n");
    oci_close($conn);
    exit(1);
}

$checks = [
    [
        'table' => 'RO_DETAIL',
        'key_column' => 'ROD_AUTO_KEY',
        'key_value' => $target['ROD_AUTO_KEY'],
    ],
    [
        'table' => 'WO_BOM',
        'key_column' => 'WOB_AUTO_KEY',
        'key_value' => $target['WOB_AUTO_KEY'],
    ],
    [
        'table' => 'WO_OPERATION',
        'key_column' => 'WOO_AUTO_KEY',
        'key_value' => $target['WOO_AUTO_KEY'],
    ],
];

$matches = [];
$columnInventory = [];

foreach ($checks as $check) {
    $columns = textColumnsForTable($conn, $check['table']);

    foreach ($columns as $column) {
        $columnInventory[] = [
            'table_name' => $check['table'],
            'column_name' => $column['COLUMN_NAME'],
            'data_type' => $column['DATA_TYPE'],
            'data_length' => $column['DATA_LENGTH'],
        ];

        $match = findColumnValue($conn, $check, $column['COLUMN_NAME'], $needle);

        if ($match !== null) {
            $matches[] = [
                'table_name' => $check['table'],
                'key_column' => $check['key_column'],
                'key_value' => $check['key_value'],
                'column_name' => $column['COLUMN_NAME'],
                'matched_value' => $match,
            ];
        }
    }
}

$stamp = date('Ymd_His');
$csvPath = __DIR__ . DIRECTORY_SEPARATOR . "quantum_ref_field_search_{$stamp}.csv";
writeCsv($csvPath, [
    ['section', 'table_name', 'key_column', 'key_value', 'column_name', 'data_type', 'data_length', 'value'],
    ['target', '', '', '', 'RO_NUMBER', '', '', $target['RO_NUMBER']],
    ['target', '', '', '', 'WO_NUMBER', '', '', $target['WO_NUMBER']],
    ['target', '', '', '', 'PN', '', '', $target['PN']],
    ['target', '', '', '', 'ROD_AUTO_KEY', '', '', $target['ROD_AUTO_KEY']],
    ['target', '', '', '', 'WOB_AUTO_KEY', '', '', $target['WOB_AUTO_KEY']],
    ['target', '', '', '', 'WOO_AUTO_KEY', '', '', $target['WOO_AUTO_KEY']],
    [],
    ['matches', '', '', '', '', '', '', ''],
    ...array_map(static fn (array $row): array => [
        'match',
        $row['table_name'],
        $row['key_column'],
        $row['key_value'],
        $row['column_name'],
        '',
        '',
        $row['matched_value'],
    ], $matches),
    [],
    ['text_columns_checked', '', '', '', '', '', '', ''],
    ...array_map(static fn (array $row): array => [
        'column',
        $row['table_name'],
        '',
        '',
        $row['column_name'],
        $row['data_type'],
        $row['data_length'],
        '',
    ], $columnInventory),
]);

echo "Quantum Ref field search\n";
echo "Needle: {$needle}\n";
echo "RO: {$target['RO_NUMBER']}\n";
echo "WO: {$target['WO_NUMBER']}\n";
echo "PN: {$target['PN']}\n";
echo "ROD_AUTO_KEY: {$target['ROD_AUTO_KEY']}\n";
echo "WOB_AUTO_KEY: {$target['WOB_AUTO_KEY']}\n";
echo "WOO_AUTO_KEY: {$target['WOO_AUTO_KEY']}\n";
echo "Matches: " . count($matches) . "\n";

foreach ($matches as $match) {
    echo $match['table_name'] . '.' . $match['column_name'] . ' = ' . $match['matched_value'] . "\n";
}

echo "CSV: {$csvPath}\n";

oci_close($conn);

function readCliParams(): array
{
    global $argv;

    $params = [];

    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--rod=')) {
            $params['rod'] = substr($arg, 6);
        }

        if (str_starts_with($arg, '--ro=')) {
            $params['ro'] = substr($arg, 5);
        }

        if (str_starts_with($arg, '--wo=')) {
            $params['wo'] = substr($arg, 5);
        }

        if (str_starts_with($arg, '--value=')) {
            $params['value'] = trim(substr($arg, 8), "\"'");
        }
    }

    return $params;
}

function findTargetRow($conn, ?int $rodAutoKey, string $roNumber, string $woNumber): ?array
{
    if ($rodAutoKey) {
        $sql = "
SELECT
    rh.RO_NUMBER,
    wo.SI_NUMBER AS WO_NUMBER,
    rd.ROD_AUTO_KEY,
    rd.WOB_AUTO_KEY,
    wb.WOO_AUTO_KEY,
    pm.PN
FROM QCTL.RO_DETAIL rd
JOIN QCTL.RO_HEADER rh
    ON rh.ROH_AUTO_KEY = rd.ROH_AUTO_KEY
LEFT JOIN QCTL.WO_BOM wb
    ON wb.WOB_AUTO_KEY = rd.WOB_AUTO_KEY
LEFT JOIN QCTL.WO_OPERATION wo
    ON wo.WOO_AUTO_KEY = wb.WOO_AUTO_KEY
LEFT JOIN QCTL.PARTS_MASTER pm
    ON pm.PNM_AUTO_KEY = rd.PNM_AUTO_KEY
WHERE rd.ROD_AUTO_KEY = :rod_auto_key
";

        return fetchOne($conn, $sql, [':rod_auto_key' => $rodAutoKey]);
    }

    $sql = "
SELECT
    rh.RO_NUMBER,
    wo.SI_NUMBER AS WO_NUMBER,
    rd.ROD_AUTO_KEY,
    rd.WOB_AUTO_KEY,
    wb.WOO_AUTO_KEY,
    pm.PN
FROM QCTL.RO_HEADER rh
JOIN QCTL.RO_DETAIL rd
    ON rd.ROH_AUTO_KEY = rh.ROH_AUTO_KEY
LEFT JOIN QCTL.WO_BOM wb
    ON wb.WOB_AUTO_KEY = rd.WOB_AUTO_KEY
LEFT JOIN QCTL.WO_OPERATION wo
    ON wo.WOO_AUTO_KEY = wb.WOO_AUTO_KEY
LEFT JOIN QCTL.PARTS_MASTER pm
    ON pm.PNM_AUTO_KEY = rd.PNM_AUTO_KEY
WHERE rh.RO_NUMBER = :ro_number
  AND wo.SI_NUMBER = :wo_number
ORDER BY rd.ROD_AUTO_KEY DESC
FETCH FIRST 1 ROWS ONLY
";

    return fetchOne($conn, $sql, [
        ':ro_number' => $roNumber,
        ':wo_number' => $woNumber,
    ]);
}

function textColumnsForTable($conn, string $tableName): array
{
    $sql = "
SELECT
    column_name,
    data_type,
    data_length
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND table_name = :table_name
  AND data_type IN ('CHAR', 'VARCHAR2', 'NCHAR', 'NVARCHAR2')
ORDER BY
    CASE
        WHEN UPPER(column_name) = 'REF' THEN 0
        WHEN UPPER(column_name) LIKE '%REF%' THEN 1
        WHEN UPPER(column_name) LIKE '%UDF%' THEN 2
        WHEN UPPER(column_name) LIKE '%NOTE%' THEN 3
        WHEN UPPER(column_name) LIKE '%REMARK%' THEN 4
        ELSE 5
    END,
    column_id
";

    return fetchAll($conn, $sql, [':table_name' => $tableName]);
}

function findColumnValue($conn, array $check, string $columnName, string $needle): ?string
{
    $table = $check['table'];
    $keyColumn = $check['key_column'];

    $sql = "
SELECT {$columnName} AS FOUND_VALUE
FROM QCTL.{$table}
WHERE {$keyColumn} = :key_value
  AND {$columnName} IS NOT NULL
  AND (
      UPPER(TRIM({$columnName})) = UPPER(TRIM(:needle))
      OR REPLACE(UPPER(TRIM({$columnName})), ' ', '') = REPLACE(UPPER(TRIM(:needle)), ' ', '')
  )
";

    $row = fetchOne($conn, $sql, [
        ':key_value' => $check['key_value'],
        ':needle' => $needle,
    ]);

    return $row ? trim((string)$row['FOUND_VALUE']) : null;
}

function fetchOne($conn, string $sql, array $binds): ?array
{
    $rows = fetchAll($conn, $sql, $binds, 1);

    return $rows[0] ?? null;
}

function fetchAll($conn, string $sql, array $binds = [], ?int $limit = null): array
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

        if ($limit !== null && count($rows) >= $limit) {
            break;
        }
    }

    oci_free_statement($stid);

    return $rows;
}

function writeCsv(string $path, array $rows): void
{
    $handle = fopen($path, 'wb');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);
}
