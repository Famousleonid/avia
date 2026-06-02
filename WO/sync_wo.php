<?php
// sync_wo.php
// Read-only Quantum WO preview/export runner.
// Writes local CSV/XLS only. Does not call avia API and does not write to avia DB.

require __DIR__ . '/quantum_wo_query.php';

date_default_timezone_set(getenv('AVIA_SYNC_TIMEZONE') ?: 'America/Toronto');

const WO_OUTPUT_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'log';
const WO_SYNC_LOG_FILE = WO_OUTPUT_DIR . DIRECTORY_SEPARATOR . 'quantum_wo_sync.log';

$startedAt = date('c');
$params = readCliParams();

$oracleUser = getenv('ORACLE_USER');
$oraclePass = getenv('ORACLE_PASS');
$oracleDsn  = getenv('ORACLE_DSN') ?: 'MAXQPROD';

if (!function_exists('oci_connect')) {
    failWoSync($startedAt, 'PHP OCI8 extension is not available');
}

if (!$oracleUser || !$oraclePass) {
    failWoSync($startedAt, 'Missing Oracle environment variables ORACLE_USER / ORACLE_PASS');
}

$conn = oci_connect($oracleUser, $oraclePass, $oracleDsn, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    failWoSync($startedAt, 'Oracle connect error: ' . ($e['message'] ?? 'unknown'));
}

try {
    $metadata = loadQuantumMetadata($conn, quantumWoMetadataTables());

    if (empty($metadata['WO_OPERATION'])) {
        throw new RuntimeException('QCTL.WO_OPERATION columns were not found or are not accessible');
    }

    if (!empty($params['count'])) {
        $countRows = countQuantumWoRows($conn, $metadata);
        $countPath = writeCountCsv($countRows);
        $total = $countRows[0]['ROW_COUNT'] ?? 0;
        $distinct = $countRows[0]['DISTINCT_WO_NUMBERS'] ?? 0;

        echo "Quantum WO count\n";
        echo "Rows: {$total}\n";
        echo "Distinct WO numbers: {$distinct}\n";
        echo "CSV: {$countPath}\n";

        writeWoLog($startedAt, date('c'), [
            'status' => 'ok',
            'mode' => 'count',
            'rows' => (int)$distinct,
            'file' => $countPath,
        ]);
        oci_close($conn);
        exit(0);
    }

    if (!empty($params['field_search'])) {
        $fieldRows = searchQuantumWoFieldCandidates($conn);
        $fieldPath = writeFieldSearchCsv($fieldRows);
        echo "Quantum WO field search\n";
        echo "Rows: " . count($fieldRows) . "\n";
        echo "CSV: {$fieldPath}\n";
        writeWoLog($startedAt, date('c'), [
            'status' => 'ok',
            'mode' => 'field_search',
            'rows' => count($fieldRows),
            'file' => $fieldPath,
        ]);
        oci_close($conn);
        exit(0);
    }

    if (!empty($params['metadata'])) {
        $metadataPath = writeMetadataCsv($metadata);
        echo "Quantum WO metadata\n";
        echo "CSV: {$metadataPath}\n";
        writeWoLog($startedAt, date('c'), [
            'status' => 'ok',
            'mode' => 'metadata',
            'rows' => countMetadataRows($metadata),
            'file' => $metadataPath,
        ]);
        oci_close($conn);
        exit(0);
    }

    $queryData = buildQuantumWoQuery($metadata, $params);
    assertReadOnlySql($queryData['sql']);

    $stid = oci_parse($conn, $queryData['sql']);
    foreach ($queryData['binds'] as $name => $value) {
        oci_bind_by_name($stid, $name, $queryData['binds'][$name]);
    }

    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        oci_free_statement($stid);
        throw new RuntimeException('Oracle query error: ' . ($e['message'] ?? 'unknown'));
    }

    $rows = [];
    while ($row = oci_fetch_assoc($stid)) {
        $rows[] = normalizeQuantumWoRow($row);
    }

    oci_free_statement($stid);
    oci_close($conn);

    $columns = quantumWoExportColumns();
    $format = strtolower((string)($params['format'] ?? 'csv'));
    $files = [];

    if ($format === 'csv' || $format === 'both') {
        $files[] = writeCsvExport($rows, $columns);
    } elseif ($format === 'xls') {
        $files[] = writeXlsExport($rows, $columns);
    } else {
        throw new RuntimeException('Invalid --format value. Use csv, xls, or both');
    }

    if ($format === 'both') {
        $files[] = writeXlsExport($rows, $columns);
    }

    echo "Quantum WO export\n";
    echo "Rows: " . count($rows) . "\n";
    foreach ($files as $file) {
        echo strtoupper(pathinfo($file, PATHINFO_EXTENSION)) . ": {$file}\n";
    }
    echo "\n";
    echo "Detected sources:\n";
    echo "Unit PN: " . sourceList($queryData['diagnostics']['unit_pn_candidates'] ?? []) . "\n";
    echo "Customer: " . sourceList($queryData['diagnostics']['customer_candidates'] ?? []) . "\n";
    echo "S/N: " . sourceList($queryData['diagnostics']['serial_candidates'] ?? []) . "\n";
    echo "Description: " . sourceList($queryData['diagnostics']['description_candidates'] ?? []) . "\n";
    echo "Open date: " . (($queryData['diagnostics']['open_date_source'] ?? null) ?: '-') . "\n";

    writeWoLog($startedAt, date('c'), [
        'status' => 'ok',
        'mode' => filterMode($params),
        'rows' => count($rows),
        'file' => implode(',', $files),
    ]);
} catch (Throwable $e) {
    if (isset($conn) && is_resource($conn)) {
        oci_close($conn);
    }

    failWoSync($startedAt, $e->getMessage());
}

function readCliParams(): array
{
    global $argv;

    $params = [
        'days_back' => 30,
        'limit' => 1000,
        'format' => 'csv',
        'all_rows' => false,
        'metadata' => false,
        'field_search' => false,
        'count' => false,
    ];

    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--days=')) {
            $params['days_back'] = max(1, (int)substr($arg, 7));
        } elseif (str_starts_with($arg, '--since=')) {
            $params['changed_since'] = normalizeSinceValue(trim(substr($arg, 8), "\"'"));
        } elseif (str_starts_with($arg, '--wo=')) {
            $params['wo_number'] = normalizeWoNumber(substr($arg, 5));
        } elseif (str_starts_with($arg, '--status=')) {
            $params['status'] = trim(substr($arg, 9));
        } elseif (str_starts_with($arg, '--customer=')) {
            $params['customer'] = trim(substr($arg, 11));
        } elseif (str_starts_with($arg, '--limit=')) {
            $params['limit'] = max(0, (int)substr($arg, 8));
        } elseif (str_starts_with($arg, '--format=')) {
            $params['format'] = strtolower(trim(substr($arg, 9)));
        } elseif ($arg === '--all') {
            $params['all_rows'] = true;
        } elseif ($arg === '--metadata' || $arg === '--columns') {
            $params['metadata'] = true;
        } elseif ($arg === '--field-search' || $arg === '--discover-fields') {
            $params['field_search'] = true;
        } elseif ($arg === '--count') {
            $params['count'] = true;
        }
    }

    return $params;
}

function normalizeWoNumber(string $value): string
{
    $value = strtoupper(trim($value));

    if (preg_match('/^[0-9]+$/', $value)) {
        return 'W' . $value;
    }

    return $value;
}

function normalizeSinceValue(string $value): string
{
    global $startedAt;

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        failWoSync($startedAt, 'Invalid --since value. Use YYYY-MM-DD HH:MI:SS');
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function loadQuantumMetadata($conn, array $tables): array
{
    $safeTables = array_values(array_filter(array_map(
        static fn(string $table): string => strtoupper(trim($table)),
        $tables
    ), static fn(string $table): bool => preg_match('/^[A-Z][A-Z0-9_]*$/', $table) === 1));

    if ($safeTables === []) {
        return [];
    }

    $tableList = implode(', ', array_map(static fn(string $table): string => "'" . $table . "'", $safeTables));
    $sql = "
SELECT
    table_name,
    column_name,
    data_type,
    data_length,
    nullable,
    column_id
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND table_name IN ({$tableList})
ORDER BY table_name, column_id
";

    assertReadOnlySql($sql);
    $rows = fetchAll($conn, $sql);
    $metadata = [];

    foreach ($rows as $row) {
        $table = strtoupper((string)$row['TABLE_NAME']);
        $metadata[$table][] = $row;
    }

    return $metadata;
}

function fetchAll($conn, string $sql, array $binds = []): array
{
    assertReadOnlySql($sql);

    $stid = oci_parse($conn, $sql);
    foreach ($binds as $name => $value) {
        oci_bind_by_name($stid, $name, $binds[$name]);
    }

    if (!oci_execute($stid)) {
        $e = oci_error($stid);
        oci_free_statement($stid);
        throw new RuntimeException('Oracle query error: ' . ($e['message'] ?? 'unknown'));
    }

    $rows = [];
    while ($row = oci_fetch_assoc($stid)) {
        $rows[] = $row;
    }

    oci_free_statement($stid);

    return $rows;
}

function searchQuantumWoFieldCandidates($conn): array
{
    $sql = "
SELECT
    table_name,
    column_id,
    column_name,
    data_type,
    data_length,
    nullable,
    CASE
        WHEN column_name LIKE '%SERIAL%'
          OR column_name IN ('SN', 'S_N')
          OR column_name LIKE '%SER_NO%'
          OR column_name LIKE '%SER_NUM%'
            THEN 'serial'
        WHEN column_name LIKE '%CUST%'
          OR column_name LIKE '%CMP%'
          OR column_name LIKE '%COMPANY%'
          OR column_name LIKE '%BILL%'
          OR column_name LIKE '%SHIP%'
            THEN 'customer'
        WHEN column_name LIKE '%PNM%'
          OR column_name LIKE '%PART%'
          OR column_name IN ('PN', 'PN_STRIPPED', 'PN_UPPER')
            THEN 'part/pn'
        WHEN column_name LIKE '%DESC%'
          OR column_name LIKE '%NOTE%'
          OR column_name LIKE '%REMARK%'
          OR column_name LIKE '%COMMENT%'
            THEN 'description'
        WHEN column_name LIKE '%OPEN%'
          OR column_name LIKE '%ENTRY_DATE%'
          OR column_name LIKE '%START%'
          OR column_name LIKE '%CREATED%'
          OR column_name LIKE '%MOD%'
          OR column_name LIKE '%CHG%'
            THEN 'date/status'
        WHEN column_name LIKE '%STM_AUTO_KEY%'
          OR column_name LIKE '%WOO_AUTO_KEY%'
          OR column_name LIKE '%WOB_AUTO_KEY%'
          OR column_name LIKE '%ROH_AUTO_KEY%'
            THEN 'relationship'
        ELSE 'candidate'
    END AS candidate_purpose
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND (
      table_name IN (
          'WO_OPERATION',
          'WO_BOM',
          'PARTS_MASTER',
          'STOCK_MASTER',
          'COMPANIES',
          'RO_HEADER',
          'RO_DETAIL'
      )
      OR table_name LIKE 'WO%'
      OR table_name LIKE '%WORK%'
      OR table_name LIKE '%STOCK%'
      OR table_name LIKE '%SERIAL%'
      OR table_name LIKE '%COMP%'
      OR table_name LIKE '%CUST%'
      OR column_name LIKE '%SERIAL%'
      OR column_name IN ('SN', 'S_N')
      OR column_name LIKE '%SER_NO%'
      OR column_name LIKE '%SER_NUM%'
      OR column_name LIKE '%CUST%'
      OR column_name LIKE '%CMP%'
      OR column_name LIKE '%COMPANY%'
      OR column_name LIKE '%PNM%'
      OR column_name LIKE '%PART%'
      OR column_name IN ('PN', 'PN_STRIPPED', 'PN_UPPER')
      OR column_name LIKE '%DESC%'
      OR column_name LIKE '%OPEN%'
  )
  AND (
      column_name LIKE '%SERIAL%'
      OR column_name IN ('SN', 'S_N')
      OR column_name LIKE '%SER_NO%'
      OR column_name LIKE '%SER_NUM%'
      OR column_name LIKE '%CUST%'
      OR column_name LIKE '%CMP%'
      OR column_name LIKE '%COMPANY%'
      OR column_name LIKE '%BILL%'
      OR column_name LIKE '%SHIP%'
      OR column_name LIKE '%PNM%'
      OR column_name LIKE '%PART%'
      OR column_name IN ('PN', 'PN_STRIPPED', 'PN_UPPER')
      OR column_name LIKE '%DESC%'
      OR column_name LIKE '%NOTE%'
      OR column_name LIKE '%REMARK%'
      OR column_name LIKE '%COMMENT%'
      OR column_name LIKE '%OPEN%'
      OR column_name LIKE '%ENTRY_DATE%'
      OR column_name LIKE '%START%'
      OR column_name LIKE '%CREATED%'
      OR column_name LIKE '%MOD%'
      OR column_name LIKE '%CHG%'
      OR column_name LIKE '%STM_AUTO_KEY%'
      OR column_name LIKE '%WOO_AUTO_KEY%'
      OR column_name LIKE '%WOB_AUTO_KEY%'
      OR column_name LIKE '%ROH_AUTO_KEY%'
  )
ORDER BY
    CASE
        WHEN column_name LIKE '%SERIAL%'
          OR column_name IN ('SN', 'S_N')
          OR column_name LIKE '%SER_NO%'
          OR column_name LIKE '%SER_NUM%'
            THEN 1
        WHEN table_name = 'WO_OPERATION' THEN 2
        WHEN table_name = 'STOCK_MASTER' THEN 3
        WHEN table_name = 'COMPANIES' THEN 4
        ELSE 5
    END,
    table_name,
    column_id
";

    return fetchAll($conn, $sql);
}

function countQuantumWoRows($conn, array $metadata): array
{
    $woColumns = tableColumnNames($metadata, 'WO_OPERATION');
    $hasStatus = hasColumn($woColumns, 'WO_DISP');

    if ($hasStatus) {
        $statusExpr = "NVL(NULLIF(TRIM(wo.WO_DISP), ''), '-')";
        $sql = "
SELECT *
FROM (
SELECT
    'ALL' AS WO_STATUS,
    COUNT(*) AS ROW_COUNT,
    COUNT(DISTINCT wo.SI_NUMBER) AS DISTINCT_WO_NUMBERS
FROM QCTL.WO_OPERATION wo
WHERE wo.SI_NUMBER IS NOT NULL
  AND TRIM(wo.SI_NUMBER) IS NOT NULL
UNION ALL
SELECT
    {$statusExpr} AS WO_STATUS,
    COUNT(*) AS ROW_COUNT,
    COUNT(DISTINCT wo.SI_NUMBER) AS DISTINCT_WO_NUMBERS
FROM QCTL.WO_OPERATION wo
WHERE wo.SI_NUMBER IS NOT NULL
  AND TRIM(wo.SI_NUMBER) IS NOT NULL
GROUP BY {$statusExpr}
)
ORDER BY
    CASE WHEN WO_STATUS = 'ALL' THEN 0 ELSE 1 END,
    ROW_COUNT DESC,
    WO_STATUS
";

        return fetchAll($conn, $sql);
    }

    $sql = "
SELECT
    'ALL' AS WO_STATUS,
    COUNT(*) AS ROW_COUNT,
    COUNT(DISTINCT wo.SI_NUMBER) AS DISTINCT_WO_NUMBERS
FROM QCTL.WO_OPERATION wo
WHERE wo.SI_NUMBER IS NOT NULL
  AND TRIM(wo.SI_NUMBER) IS NOT NULL
";

    return fetchAll($conn, $sql);
}

function normalizeQuantumWoRow(array $row): array
{
    return [
        'WO_NUMBER' => clean($row['WO_NUMBER'] ?? null),
        'UNIT_PN' => clean($row['UNIT_PN'] ?? null),
        'CUSTOMER' => clean($row['CUSTOMER'] ?? null),
        'SERIAL_NUMBER' => clean($row['SERIAL_NUMBER'] ?? null),
        'DESCRIPTION' => clean($row['DESCRIPTION'] ?? null),
        'OPEN_DATE' => formatProjectDate($row['OPEN_DATE_ISO'] ?? null),
        'WO_STATUS' => clean($row['WO_STATUS'] ?? null),
        'WOO_AUTO_KEY' => clean($row['WOO_AUTO_KEY'] ?? null),
        'WO_PNM_AUTO_KEY' => clean($row['WO_PNM_AUTO_KEY'] ?? null),
        'STM_AUTO_KEY' => clean($row['STM_AUTO_KEY'] ?? null),
        'CMP_AUTO_KEY' => clean($row['CMP_AUTO_KEY'] ?? null),
        'COMPANY_REF_NUMBER' => clean($row['COMPANY_REF_NUMBER'] ?? null),
        'UNIT_PN_SOURCE' => clean($row['UNIT_PN_SOURCE'] ?? null),
        'CUSTOMER_SOURCE' => clean($row['CUSTOMER_SOURCE'] ?? null),
        'SERIAL_SOURCE' => clean($row['SERIAL_SOURCE'] ?? null),
        'DESCRIPTION_SOURCE' => clean($row['DESCRIPTION_SOURCE'] ?? null),
        'OPEN_DATE_SOURCE' => clean($row['OPEN_DATE_SOURCE'] ?? null),
        'SOURCE_LAST_MODIFIED' => clean($row['SOURCE_LAST_MODIFIED'] ?? null),
    ];
}

function quantumWoExportColumns(): array
{
    return [
        'WO_NUMBER',
        'UNIT_PN',
        'CUSTOMER',
        'SERIAL_NUMBER',
        'DESCRIPTION',
        'OPEN_DATE',
        'WO_STATUS',
    ];
}

function writeCsvExport(array $rows, array $columns): string
{
    $stamp = date('Ymd_His');
    $path = woOutputPath("quantum_wo_preview_{$stamp}.csv");
    $handle = fopen($path, 'wb');

    if (!$handle) {
        throw new RuntimeException('Cannot write CSV: ' . $path);
    }

    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, $columns);

    foreach ($rows as $row) {
        fputcsv($handle, rowForColumns($row, $columns));
    }

    fclose($handle);

    return $path;
}

function writeXlsExport(array $rows, array $columns): string
{
    $stamp = date('Ymd_His');
    $path = woOutputPath("quantum_wo_preview_{$stamp}.xls");
    $handle = fopen($path, 'wb');

    if (!$handle) {
        throw new RuntimeException('Cannot write XLS: ' . $path);
    }

    fwrite($handle, "<html><head><meta charset=\"UTF-8\"></head><body><table border=\"1\">\n");
    fwrite($handle, "<tr>");
    foreach ($columns as $column) {
        fwrite($handle, '<th>' . html($column) . '</th>');
    }
    fwrite($handle, "</tr>\n");

    foreach ($rows as $row) {
        fwrite($handle, "<tr>");
        foreach (rowForColumns($row, $columns) as $value) {
            fwrite($handle, '<td style="mso-number-format:\'\\@\';">' . html($value) . '</td>');
        }
        fwrite($handle, "</tr>\n");
    }

    fwrite($handle, "</table></body></html>\n");
    fclose($handle);

    return $path;
}

function writeMetadataCsv(array $metadata): string
{
    $stamp = date('Ymd_His');
    $path = woOutputPath("quantum_wo_metadata_{$stamp}.csv");
    $handle = fopen($path, 'wb');

    if (!$handle) {
        throw new RuntimeException('Cannot write metadata CSV: ' . $path);
    }

    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, ['TABLE_NAME', 'COLUMN_NAME', 'DATA_TYPE', 'DATA_LENGTH', 'NULLABLE', 'COLUMN_ID', 'CANDIDATE_PURPOSE']);

    foreach ($metadata as $tableRows) {
        foreach ($tableRows as $row) {
            fputcsv($handle, [
                $row['TABLE_NAME'] ?? '',
                $row['COLUMN_NAME'] ?? '',
                $row['DATA_TYPE'] ?? '',
                $row['DATA_LENGTH'] ?? '',
                $row['NULLABLE'] ?? '',
                $row['COLUMN_ID'] ?? '',
                candidatePurpose((string)($row['TABLE_NAME'] ?? ''), (string)($row['COLUMN_NAME'] ?? '')),
            ]);
        }
    }

    fclose($handle);

    return $path;
}

function writeFieldSearchCsv(array $rows): string
{
    $stamp = date('Ymd_His');
    $path = woOutputPath("quantum_wo_field_search_{$stamp}.csv");
    $handle = fopen($path, 'wb');

    if (!$handle) {
        throw new RuntimeException('Cannot write field search CSV: ' . $path);
    }

    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, ['TABLE_NAME', 'COLUMN_ID', 'COLUMN_NAME', 'DATA_TYPE', 'DATA_LENGTH', 'NULLABLE', 'CANDIDATE_PURPOSE']);

    foreach ($rows as $row) {
        fputcsv($handle, [
            $row['TABLE_NAME'] ?? '',
            $row['COLUMN_ID'] ?? '',
            $row['COLUMN_NAME'] ?? '',
            $row['DATA_TYPE'] ?? '',
            $row['DATA_LENGTH'] ?? '',
            $row['NULLABLE'] ?? '',
            $row['CANDIDATE_PURPOSE'] ?? '',
        ]);
    }

    fclose($handle);

    return $path;
}

function writeCountCsv(array $rows): string
{
    $stamp = date('Ymd_His');
    $path = woOutputPath("quantum_wo_count_{$stamp}.csv");
    $handle = fopen($path, 'wb');

    if (!$handle) {
        throw new RuntimeException('Cannot write count CSV: ' . $path);
    }

    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, ['WO_STATUS', 'ROW_COUNT', 'DISTINCT_WO_NUMBERS']);

    foreach ($rows as $row) {
        fputcsv($handle, [
            $row['WO_STATUS'] ?? '',
            $row['ROW_COUNT'] ?? '',
            $row['DISTINCT_WO_NUMBERS'] ?? '',
        ]);
    }

    fclose($handle);

    return $path;
}

function candidatePurpose(string $table, string $column): string
{
    $column = strtoupper($column);

    if (in_array($column, ['SI_NUMBER'], true)) {
        return 'WO number';
    }

    if (str_contains($column, 'PN') || str_contains($column, 'PART')) {
        return 'PN candidate';
    }

    if (str_contains($column, 'CUST') || str_contains($column, 'COMP') || str_contains($column, 'CMP')) {
        return 'customer/company candidate';
    }

    if (str_contains($column, 'SERIAL') || in_array($column, ['SN', 'S_N'], true)) {
        return 'serial candidate';
    }

    if (str_contains($column, 'DESC') || str_contains($column, 'NOTE') || str_contains($column, 'REMARK')) {
        return 'description candidate';
    }

    if (str_contains($column, 'DATE') || str_contains($column, 'MOD') || str_contains($column, 'CHG')) {
        return 'date/change candidate';
    }

    return '';
}

function rowForColumns(array $row, array $columns): array
{
    return array_map(
        static fn(string $column): string => (string)($row[$column] ?? ''),
        $columns
    );
}

function clean($value): string
{
    $value = trim((string)$value);

    return $value === '' ? '' : preg_replace('/\s+/', ' ', $value);
}

function formatProjectDate($value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    $months = [
        1 => 'jan',
        2 => 'feb',
        3 => 'mar',
        4 => 'apr',
        5 => 'may',
        6 => 'jun',
        7 => 'jul',
        8 => 'aug',
        9 => 'sep',
        10 => 'oct',
        11 => 'nov',
        12 => 'dec',
    ];

    return date('d', $timestamp) . '/' . $months[(int)date('n', $timestamp)] . '/' . date('Y', $timestamp);
}

function html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sourceList(array $sources): string
{
    return $sources === [] ? '-' : implode(', ', $sources);
}

function filterMode(array $params): string
{
    if (!empty($params['wo_number'])) {
        return 'wo';
    }

    if (!empty($params['changed_since'])) {
        return 'since';
    }

    if (!empty($params['all_rows'])) {
        return 'all';
    }

    return 'days';
}

function countMetadataRows(array $metadata): int
{
    return array_sum(array_map('count', $metadata));
}

function failWoSync(string $startedAt, string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    writeWoLog($startedAt, date('c'), [
        'status' => 'error',
        'message' => $message,
    ]);
    exit(1);
}

function writeWoLog(string $startedAt, string $finishedAt, array $context): void
{
    ensureWoOutputDir();

    $parts = ['[' . date('Y-m-d H:i:s') . ']'];
    $parts[] = 'status=' . logValue($context['status'] ?? 'unknown');
    $parts[] = 'mode=' . logValue($context['mode'] ?? '-');
    $parts[] = 'rows=' . (int)($context['rows'] ?? 0);

    if (!empty($context['file'])) {
        $parts[] = 'file="' . logValue($context['file']) . '"';
    }

    if (!empty($context['message'])) {
        $parts[] = 'message="' . logValue($context['message']) . '"';
    }

    file_put_contents(WO_SYNC_LOG_FILE, implode(' ', $parts) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function woOutputPath(string $filename): string
{
    ensureWoOutputDir();

    return WO_OUTPUT_DIR . DIRECTORY_SEPARATOR . $filename;
}

function ensureWoOutputDir(): void
{
    if (!is_dir(WO_OUTPUT_DIR)) {
        mkdir(WO_OUTPUT_DIR, 0777, true);
    }
}

function logValue(mixed $value): string
{
    return str_replace(["\r", "\n", '"'], [' ', ' ', "'"], trim((string)$value));
}

function assertReadOnlySql(string $sql): void
{
    $sqlClean = trim($sql);
    $upperSql = strtoupper($sqlClean);

    if (!preg_match('/^(SELECT|WITH)\b/', $upperSql)) {
        throw new RuntimeException('Blocked: only SELECT/WITH queries are allowed');
    }

    if (str_contains($sqlClean, ';')) {
        throw new RuntimeException('Blocked: semicolon is not allowed in SQL');
    }

    foreach (['DBMS_', 'UTL_'] as $blockedFragment) {
        if (str_contains($upperSql, $blockedFragment)) {
            throw new RuntimeException('Blocked dangerous SQL fragment: ' . $blockedFragment);
        }
    }

    $blockedWords = [
        'INSERT',
        'UPDATE',
        'DELETE',
        'MERGE',
        'DROP',
        'ALTER',
        'TRUNCATE',
        'CREATE',
        'GRANT',
        'REVOKE',
        'COMMIT',
        'ROLLBACK',
        'EXEC',
        'EXECUTE',
    ];

    foreach ($blockedWords as $word) {
        if (preg_match('/(^|[^A-Z0-9_])' . preg_quote($word, '/') . '([^A-Z0-9_]|$)/', $upperSql)) {
            throw new RuntimeException('Blocked dangerous SQL keyword: ' . $word);
        }
    }

    if (preg_match('/(^|[^A-Z0-9_])FOR\s+UPDATE([^A-Z0-9_]|$)/', $upperSql)) {
        throw new RuntimeException('Blocked dangerous SQL keyword: FOR UPDATE');
    }
}
