<?php
// Read-only Quantum RO count helper.
// Usage:
//   php count_quantum_ro.php
//   php count_quantum_ro.php --env=C:\path\to\.env.sync_quantum

date_default_timezone_set(getenv('AVIA_SYNC_TIMEZONE') ?: 'America/Toronto');

$params = readCliParams($argv);
loadEnvFile($params['env'] ?? (__DIR__ . DIRECTORY_SEPARATOR . '.env.sync_quantum'));

$oracleUser = getenv('ORACLE_USER');
$oraclePass = getenv('ORACLE_PASS');
$oracleDsn = getenv('ORACLE_DSN') ?: 'MAXQPROD';

if (!$oracleUser || !$oraclePass) {
    fwrite(STDERR, "Missing ORACLE_USER / ORACLE_PASS environment variables.\n");
    exit(1);
}

if (!extension_loaded('oci8')) {
    fwrite(STDERR, "PHP OCI8 extension is not loaded.\n");
    exit(1);
}

$conn = oci_connect($oracleUser, $oraclePass, $oracleDsn, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    fwrite(STDERR, "Oracle connect error: " . ($e['message'] ?? 'unknown') . "\n");
    exit(1);
}

$sql = "
SELECT
    COUNT(*) AS RO_PROCESS_ROWS,
    COUNT(DISTINCT rh.ROH_AUTO_KEY) AS UNIQUE_RO_HEADERS,
    COUNT(DISTINCT rh.RO_NUMBER) AS UNIQUE_RO_NUMBERS,
    SUM(CASE WHEN rh.OPEN_FLAG = 'T' THEN 1 ELSE 0 END) AS OPEN_RO_PROCESS_ROWS,
    COUNT(DISTINCT CASE WHEN rh.OPEN_FLAG = 'T' THEN rh.ROH_AUTO_KEY END) AS OPEN_RO_HEADERS,
    SUM(CASE WHEN rd.WOB_AUTO_KEY IS NOT NULL THEN 1 ELSE 0 END) AS ROWS_WITH_WO_BOM_KEY,
    SUM(CASE WHEN wb.WOB_AUTO_KEY IS NOT NULL THEN 1 ELSE 0 END) AS ROWS_LINKED_TO_WO_BOM
FROM QCTL.RO_HEADER rh
JOIN QCTL.RO_DETAIL rd
    ON rd.ROH_AUTO_KEY = rh.ROH_AUTO_KEY
LEFT JOIN QCTL.WO_BOM wb
    ON wb.WOB_AUTO_KEY = rd.WOB_AUTO_KEY
";

$stid = oci_parse($conn, $sql);
if (!$stid || !oci_execute($stid)) {
    $e = oci_error($stid ?: $conn);
    if ($stid) {
        oci_free_statement($stid);
    }
    oci_close($conn);
    fwrite(STDERR, "Oracle query error: " . ($e['message'] ?? 'unknown') . "\n");
    exit(1);
}

$row = oci_fetch_assoc($stid) ?: [];
oci_free_statement($stid);
oci_close($conn);

echo "Quantum RO counts\n";
echo "As of: " . date('Y-m-d H:i:s T') . "\n";
echo "RO process rows (RO_DETAIL): " . formatCount($row['RO_PROCESS_ROWS'] ?? 0) . "\n";
echo "Unique RO headers: " . formatCount($row['UNIQUE_RO_HEADERS'] ?? 0) . "\n";
echo "Unique RO numbers: " . formatCount($row['UNIQUE_RO_NUMBERS'] ?? 0) . "\n";
echo "Open RO process rows: " . formatCount($row['OPEN_RO_PROCESS_ROWS'] ?? 0) . "\n";
echo "Open RO headers: " . formatCount($row['OPEN_RO_HEADERS'] ?? 0) . "\n";
echo "Rows with WO_BOM key: " . formatCount($row['ROWS_WITH_WO_BOM_KEY'] ?? 0) . "\n";
echo "Rows linked to WO_BOM: " . formatCount($row['ROWS_LINKED_TO_WO_BOM'] ?? 0) . "\n";

function readCliParams(array $argv): array
{
    $params = [];

    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--env=')) {
            $params['env'] = substr($arg, 6);
        }
    }

    return $params;
}

function loadEnvFile(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $rawLine) {
        $line = trim($rawLine);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ($key === '') {
            continue;
        }

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function formatCount($value): string
{
    return number_format((int)$value, 0, '.', ',');
}
