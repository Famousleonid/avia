<?php
// sync.php
// Stable runner file.
// Reads input parameters, connects to Oracle, runs the query,
// sends rows to the Laravel staging API, and writes a compact cron log.

require __DIR__ . '/quantum_ro_query.php';

date_default_timezone_set(getenv('AVIA_SYNC_TIMEZONE') ?: 'America/Toronto');

$startedAt = date('c');

// =========================
// Avia API sync config
// =========================
//
// These values are used when AVIA_SYNC_API_URL / AVIA_SYNC_API_TOKEN
// are not set in the bridge environment.

const DEFAULT_AVIA_SYNC_API_URL = 'http://avia.loc/api/quantum/ro-sync';
const DEFAULT_AVIA_SYNC_API_TOKEN = 'local-quantum-ro-sync-20260529';
const DEFAULT_INITIAL_BACKFILL_DAYS = 90;
const SYNC_LOG_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'quantum_ro_sync.log';

// =========================
// Oracle connection config
// =========================

$oracleUser = getenv('ORACLE_USER');
$oraclePass = getenv('ORACLE_PASS');
$oracleDsn  = getenv('ORACLE_DSN') ?: 'MAXQPROD';

if (!$oracleUser || !$oraclePass) {
    failSync($startedAt, "Missing Oracle environment variables");
}

// =========================
// Input parameters
// =========================
//
// Usage examples:
//
// php sync.php
// php sync.php --days=7
// php sync.php --since="2026-05-29 14:00:00"
// php sync.php --ro=R8917
// php sync.php --vendor=SkyService
//
// Parameters:
// --days=N       Search RO changed during last N days
// --since=date   Search RO changed since YYYY-MM-DD HH:MI:SS
// --ro=R8917     Search specific RO number
// --vendor=text  Search vendor name by partial text

$params = readCliParams();

$roNumber = $params['ro'] ?? null;
$vendor   = $params['vendor'] ?? null;
$daysBack = isset($params['days']) ? (int)$params['days'] : DEFAULT_INITIAL_BACKFILL_DAYS;
$hasDaysFilter = false;
$changedSince = null;
$filterMode = 'incremental';
$syncState = null;
$unresolvedRoNumbers = [];
$trackedRefRoNumbers = [];

if ($daysBack <= 0) {
    $daysBack = DEFAULT_INITIAL_BACKFILL_DAYS;
}

if ($roNumber) {
    $filterMode = 'ro';
} elseif (isset($params['days'])) {
    $hasDaysFilter = true;
    $filterMode = 'days';
} elseif (isset($params['since'])) {
    $changedSince = normalizeSinceValue($params['since']);
    $filterMode = 'since';
} else {
    $syncState = fetchSyncState();
    $changedSince = $syncState['state']['recommended_since'] ?? null;
    $unresolvedRoNumbers = $syncState['state']['unresolved_ro_numbers'] ?? [];
    $trackedRefRoNumbers = $syncState['state']['tracked_ref_ro_numbers'] ?? [];

    if ($changedSince) {
        $filterMode = 'incremental';
    } else {
        $hasDaysFilter = true;
        $filterMode = 'initial_backfill';
    }
}

// =========================
// Connect to Oracle
// =========================

$conn = oci_connect($oracleUser, $oraclePass, $oracleDsn, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    failSync($startedAt, "Oracle connect error: " . $e['message']);
}

// =========================
// Build and execute query
// =========================

$wobChangeColumn = detectWoBomChangeColumn($conn);

$queryData = buildQuantumRoQuery([
    'days_back' => $daysBack,
    'has_days_filter' => $hasDaysFilter,
    'changed_since' => $changedSince,
    'ro_number' => $roNumber,
    'vendor'    => $vendor,
    'wob_change_column' => $wobChangeColumn,
    'unresolved_ro_numbers' => $unresolvedRoNumbers ?? [],
    'tracked_ref_ro_numbers' => $trackedRefRoNumbers ?? [],
]);

assertReadOnlySql($queryData['sql']);
$stid = oci_parse($conn, $queryData['sql']);

foreach ($queryData['binds'] as $name => $value) {
    oci_bind_by_name($stid, $name, $queryData['binds'][$name]);
}

if (!oci_execute($stid)) {
    $e = oci_error($stid);
    oci_free_statement($stid);
    oci_close($conn);
    failSync($startedAt, "Oracle query error: " . $e['message']);
}

// =========================
// Fetch rows
// =========================

$rows = [];

while ($row = oci_fetch_assoc($stid)) {
    $rows[] = normalizeQuantumRow($row);
}

oci_free_statement($stid);
oci_close($conn);
$finishedAt = date('c');

// =========================
// Send to API and write cron log
// =========================

$apiResult = sendRowsToApi($rows, [
    'bridge_id' => getenv('COMPUTERNAME') ?: gethostname(),
    'started_at' => $startedAt,
    'finished_at' => $finishedAt,
    'filters' => [
        'days_back' => $daysBack,
        'has_days_filter' => $hasDaysFilter,
        'changed_since' => $changedSince,
        'filter_mode' => $filterMode,
        'state_last_source_modified' => $syncState['state']['last_source_modified'] ?? null,
        'ro_number' => $roNumber,
        'vendor' => $vendor,
    ],
]);

writeSyncLog($startedAt, date('c'), [
    'filter_mode' => $filterMode,
    'changed_since' => $changedSince,
    'days_back' => $daysBack,
    'ro_number' => $roNumber,
    'vendor' => $vendor,
    'state_last_source_modified' => $syncState['state']['last_source_modified'] ?? null,
    'rows_from_quantum' => count($rows),
    'api_result' => $apiResult,
]);

// =========================
// Helper functions
// =========================

function readCliParams(): array
{
    global $argv;

    $params = [];

    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--days=')) {
            $params['days'] = substr($arg, 7);
        }

        if (str_starts_with($arg, '--since=')) {
            $params['since'] = trim(substr($arg, 8), "\"'");
        }

        if (str_starts_with($arg, '--ro=')) {
            $params['ro'] = strtoupper(trim(substr($arg, 5)));
        }

        if (str_starts_with($arg, '--vendor=')) {
            $params['vendor'] = trim(substr($arg, 9));
        }
    }

    return $params;
}

function failSync(string $startedAt, string $message): never
{
    writeSyncLog($startedAt, date('c'), [
        'error' => $message,
    ]);

    exit(1);
}

function detectWoBomChangeColumn($conn): ?string
{
    $preferred = [
        'LAST_MODIFIED',
        'SYSUR_MODIFIED',
        'LAST_UPDATE_DATE',
        'UPDATED_DATE',
        'MODIFIED_DATE',
        'DATE_MODIFIED',
        'CHANGE_DATE',
        'LAST_STATUS_CHG',
        'SYSUR_LAST_STATUS_CHG',
    ];

    $sql = "
SELECT column_name
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND table_name = 'WO_BOM'
  AND column_name IN (
      'LAST_MODIFIED',
      'SYSUR_MODIFIED',
      'LAST_UPDATE_DATE',
      'UPDATED_DATE',
      'MODIFIED_DATE',
      'DATE_MODIFIED',
      'CHANGE_DATE',
      'LAST_STATUS_CHG',
      'SYSUR_LAST_STATUS_CHG'
  )
";

    $stid = oci_parse($conn, $sql);

    if (!oci_execute($stid)) {
        oci_free_statement($stid);
        return null;
    }

    $found = [];
    while ($row = oci_fetch_assoc($stid)) {
        $column = strtoupper(trim((string)($row['COLUMN_NAME'] ?? '')));
        if (preg_match('/^[A-Z][A-Z0-9_]*$/', $column)) {
            $found[] = $column;
        }
    }

    oci_free_statement($stid);

    foreach ($preferred as $column) {
        if (in_array($column, $found, true)) {
            return $column;
        }
    }

    return null;
}

function writeSyncLog(string $startedAt, string $finishedAt, array $context): void
{
    $parts = ['[' . date('Y-m-d H:i:s') . ']'];

    if (!empty($context['error'])) {
        $parts[] = 'status=error';
        $parts[] = 'quantum_rows=' . (int)($context['rows_from_quantum'] ?? 0);
        $parts[] = 'received=0';
        $parts[] = 'inserted=0';
        $parts[] = 'updated=0';
        $parts[] = 'unchanged=0';
        $parts[] = 'result="' . logValue($context['error']) . '"';
        appendSyncLog(implode(' ', $parts));
        return;
    }

    $apiResult = $context['api_result'] ?? [];
    $stats = is_array($apiResult) ? ($apiResult['stats'] ?? []) : [];

    $parts[] = 'status=' . ($apiResult['status'] ?? 'unknown');
    $parts[] = 'quantum_rows=' . (int)($context['rows_from_quantum'] ?? 0);
    $parts[] = 'received=' . (int)($stats['rows_received'] ?? 0);
    $parts[] = 'inserted=' . (int)($stats['rows_inserted'] ?? 0);
    $parts[] = 'updated=' . (int)($stats['rows_updated'] ?? 0);
    $parts[] = 'unchanged=' . (int)($stats['rows_unchanged'] ?? 0);

    if ($stats) {
        if ((int)($stats['rows_inserted'] ?? 0) === 0 && (int)($stats['rows_updated'] ?? 0) === 0) {
            $parts[] = 'result="nothing new"';
        } else {
            $parts[] = 'result="rows transferred"';
        }
    } else {
        $parts[] = 'result="' . logValue($apiResult['message'] ?? 'no API stats') . '"';
    }

    appendSyncLog(implode(' ', $parts));
}

function appendSyncLog(string $line): void
{
    file_put_contents(SYNC_LOG_FILE, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function fmtLogDate(string $value): string
{
    $timestamp = strtotime($value);

    return $timestamp === false ? $value : date('Y-m-d H:i:s', $timestamp);
}

function logValue(mixed $value): string
{
    return str_replace(["\r", "\n", '"'], [' ', ' ', "'"], trim((string)$value));
}

function normalizeQuantumRow(array $row): array
{
    return [
        'ro'            => clean($row['RO_NUMBER'] ?? null),
        'vendor'        => clean($row['VENDOR_NAME'] ?? null),
        'wo'            => clean($row['WO_NUMBER'] ?? null),
        'pn'            => clean($row['PN'] ?? null),
        'desc'          => clean($row['DESC'] ?? null),
        'class'         => clean($row['CLASS'] ?? null),
        'bom_ref'       => clean($row['BOM_REF'] ?? null),
        'roh_auto_key'  => clean($row['ROH_AUTO_KEY'] ?? null),
        'rod_auto_key'  => clean($row['ROD_AUTO_KEY'] ?? null),
        'wob_auto_key'  => clean($row['WOB_AUTO_KEY'] ?? null),
        'woo_auto_key'  => clean($row['WOO_AUTO_KEY'] ?? null),
        'pnm_auto_key'  => clean($row['PNM_AUTO_KEY'] ?? null),

        // Current confirmed meaning:
        // OUT_DATE = sent to vendor
        // LAST_DELIVERY_DATE = returned from vendor
        'entry_date'    => fmtDate($row['ENTRY_DATE'] ?? null),
        'sent_date'     => fmtDate($row['OUT_DATE'] ?? null),
        'returned_date' => fmtDate($row['LAST_DELIVERY_DATE'] ?? null),
        'ro_last_modified' => fmtDate($row['RO_LAST_MODIFIED'] ?? null),
        'detail_last_modified' => fmtDate($row['DETAIL_LAST_MODIFIED'] ?? null),
        'bom_last_modified' => fmtDate($row['BOM_LAST_MODIFIED'] ?? null),
        'source_last_modified' => fmtDate($row['SOURCE_LAST_MODIFIED'] ?? null),
        'qty_repair'    => clean($row['QTY_REPAIR'] ?? null),
        'qty_reserved'  => clean($row['QTY_RESERVED'] ?? null),
        'qty_repaired'  => clean($row['QTY_REPAIRED'] ?? null),

        // Quantity field is still being searched.
        // Currently returned by query as placeholder.
        'qty'           => clean($row['QTY'] ?? null),
    ];
}

function normalizeSinceValue(string $value): string
{
    global $startedAt;

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        failSync($startedAt, "Invalid --since value. Use YYYY-MM-DD HH:MI:SS");
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function fetchSyncState(): ?array
{
    $url = rtrim(syncApiUrl(), '/') . '/state';
    $token = syncApiToken();

    if ($url === '' || $token === '') {
        return null;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ]),
            'ignore_errors' => true,
            'timeout' => 15,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? '';

    if (preg_match('/\s2\d\d\s/', $statusLine) !== 1 || !is_string($response)) {
        return null;
    }

    $decoded = json_decode($response, true);

    return is_array($decoded) ? $decoded : null;
}

function sendRowsToApi(array $rows, array $run): array
{
    $url = syncApiUrl();
    $token = syncApiToken();

    if ($url === '' || $token === '') {
        return [
            'status' => 'skipped',
            'message' => 'skipped, AVIA_SYNC_API_URL or AVIA_SYNC_API_TOKEN is missing',
        ];
    }

    $payload = json_encode([
        'run' => $run,
        'rows' => array_map('apiQuantumRow', $rows),
    ], JSON_UNESCAPED_UNICODE);

    if ($payload === false) {
        return [
            'status' => 'error',
            'message' => 'failed to encode API payload',
        ];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ]),
            'content' => $payload,
            'ignore_errors' => true,
            'timeout' => 30,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? '';
    $ok = preg_match('/\s2\d\d\s/', $statusLine) === 1;
    $decoded = is_string($response) ? json_decode($response, true) : null;

    return [
        'status' => $ok ? 'ok' : 'error',
        'message' => $ok ? 'sent to API' : 'API request failed: ' . ($statusLine ?: 'no HTTP response'),
        'stats' => is_array($decoded) ? ($decoded['stats'] ?? []) : [],
        'response' => is_string($response) ? cut($response, 1000) : '',
    ];
}

function syncApiUrl(): string
{
    return getenv('AVIA_SYNC_API_URL') ?: getenv('QUANTUM_SYNC_API_URL') ?: DEFAULT_AVIA_SYNC_API_URL;
}

function syncApiToken(): string
{
    return getenv('AVIA_SYNC_API_TOKEN') ?: getenv('QUANTUM_SYNC_TOKEN') ?: DEFAULT_AVIA_SYNC_API_TOKEN;
}

function apiQuantumRow(array $row): array
{
    return [
        'source_uid' => $row['rod_auto_key'] !== '-' ? 'rod:' . $row['rod_auto_key'] : null,
        'roh_auto_key' => nullableValue($row['roh_auto_key']),
        'rod_auto_key' => nullableValue($row['rod_auto_key']),
        'wob_auto_key' => nullableValue($row['wob_auto_key']),
        'woo_auto_key' => nullableValue($row['woo_auto_key']),
        'pnm_auto_key' => nullableValue($row['pnm_auto_key']),
        'ro_number' => nullableValue($row['ro']),
        'wo_number' => nullableValue($row['wo']),
        'vendor_name' => nullableValue($row['vendor']),
        'pn' => nullableValue($row['pn']),
        'description' => nullableValue($row['desc']),
        'class' => nullableValue($row['class']),
        'bom_ref' => nullableValue($row['bom_ref']),
        'entry_date' => nullableValue($row['entry_date']),
        'out_date' => nullableValue($row['sent_date']),
        'returned_date' => nullableValue($row['returned_date']),
        'ro_last_modified' => nullableValue($row['ro_last_modified']),
        'detail_last_modified' => nullableValue($row['detail_last_modified']),
        'bom_last_modified' => nullableValue($row['bom_last_modified']),
        'source_last_modified' => nullableValue($row['source_last_modified']),
        'qty_repair' => nullableValue($row['qty_repair']),
        'qty_reserved' => nullableValue($row['qty_reserved']),
        'qty_repaired' => nullableValue($row['qty_repaired']),
    ];
}

function fmtDate($value): string
{
    $value = trim((string)$value);

    return $value === '' ? '-' : $value;
}

function clean($value): string
{
    $value = trim((string)$value);

    if ($value === '') {
        return '-';
    }

    return preg_replace('/\s+/', ' ', $value);
}

function nullableValue(string $value): ?string
{
    return $value === '-' ? null : $value;
}

function cut(string $value, int $length): string
{
    if (strlen($value) <= $length) {
        return $value;
    }

    return substr($value, 0, $length - 1) . '...';
}

function assertReadOnlySql(string $sql): void
{
    global $startedAt;

    $sqlClean = trim($sql);
    $upperSql = strtoupper($sqlClean);

    // Allow only SELECT or WITH queries.
    if (!preg_match('/^(SELECT|WITH)\b/', $upperSql)) {
        failSync($startedAt, "Blocked: only SELECT/WITH queries are allowed.");
    }

    // Block dangerous SQL keywords.
    $blocked = [
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
        'FOR UPDATE',
        'DBMS_',
        'UTL_',
    ];

    foreach ($blocked as $word) {
        if (str_contains($upperSql, $word)) {
            failSync($startedAt, "Blocked dangerous SQL keyword: {$word}");
        }
    }

    // Do not allow semicolon to reduce risk of multi-statement tricks.
    if (str_contains($sqlClean, ';')) {
        failSync($startedAt, "Blocked: semicolon is not allowed in SQL.");
    }
}
