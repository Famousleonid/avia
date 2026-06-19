<?php
// sync_manager.php
// Read-only Quantum manager discovery runner.
// Writes local CSV/XLS diagnostics only. Does not write to Quantum or avia DB.

require __DIR__ . '/quantum_manager_query.php';

date_default_timezone_set(getenv('AVIA_SYNC_TIMEZONE') ?: 'America/Toronto');

const MANAGER_OUTPUT_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'log';
const MANAGER_SYNC_LOG_FILE = MANAGER_OUTPUT_DIR . DIRECTORY_SEPARATOR . 'quantum_manager_sync.log';

$startedAt = date('c');
$params = readManagerCliParams();

$oracleUser = envFirst(['QUANTUM_MANAGER_ORACLE_USER', 'ORACLE_USER']);
$oraclePass = envFirst(['QUANTUM_MANAGER_ORACLE_PASS', 'ORACLE_PASS']);
$oracleDsn = envFirst(['QUANTUM_MANAGER_ORACLE_DSN', 'ORACLE_DSN']) ?: 'MAXQPROD';

if (!function_exists('oci_connect')) {
    failManagerSync($startedAt, 'PHP OCI8 extension is not available');
}

if (!$oracleUser || !$oraclePass) {
    failManagerSync($startedAt, 'Missing Oracle environment variables. Use QUANTUM_MANAGER_ORACLE_USER / QUANTUM_MANAGER_ORACLE_PASS or ORACLE_USER / ORACLE_PASS');
}

$conn = oci_connect($oracleUser, $oraclePass, $oracleDsn, 'AL32UTF8');
if (!$conn) {
    $e = oci_error();
    failManagerSync($startedAt, 'Oracle connect error: ' . ($e['message'] ?? 'unknown'));
}

try {
    if (strtolower((string)($params['mode'] ?? '')) === 'wo-money-wide') {
        $scanResult = runManagerWoMoneyWideScan($conn, $params);
        oci_close($conn);

        echo "Quantum manager discovery\n";
        echo "Mode: wo-money-wide" . PHP_EOL;
        echo "Rows: " . $scanResult['rows'] . PHP_EOL;
        echo "CSV: " . $scanResult['file'] . PHP_EOL;

        writeManagerLog($startedAt, date('c'), [
            'status' => 'ok',
            'mode' => 'wo-money-wide',
            'rows' => $scanResult['rows'],
            'file' => $scanResult['file'],
        ]);
        exit(0);
    }

    if (strtolower((string)($params['mode'] ?? '')) === 'amount-scan') {
        $scanResult = runManagerAmountScan($conn, $params);
        oci_close($conn);

        echo "Quantum manager discovery\n";
        echo "Mode: amount-scan" . PHP_EOL;
        echo "Rows: " . $scanResult['rows'] . PHP_EOL;
        echo "CSV: " . $scanResult['file'] . PHP_EOL;

        writeManagerLog($startedAt, date('c'), [
            'status' => 'ok',
            'mode' => 'amount-scan',
            'rows' => $scanResult['rows'],
            'file' => $scanResult['file'],
        ]);
        exit(0);
    }

    if (strtolower((string)($params['mode'] ?? '')) === 'wo-spb-quotes-proc') {
        $scanResult = runManagerWoSpbQuotesProc($conn, $params);
        oci_close($conn);

        echo "Quantum manager discovery\n";
        echo "Mode: wo-spb-quotes-proc" . PHP_EOL;
        echo "Rows: " . $scanResult['rows'] . PHP_EOL;
        echo "CSV: " . $scanResult['file'] . PHP_EOL;

        writeManagerLog($startedAt, date('c'), [
            'status' => 'ok',
            'mode' => 'wo-spb-quotes-proc',
            'rows' => $scanResult['rows'],
            'file' => $scanResult['file'],
        ]);
        exit(0);
    }

    $queryList = buildManagerQueryList($params);
    $files = [];
    $totalRows = 0;
    $modeNames = [];

    foreach ($queryList as $queryData) {
        assertManagerReadOnlySql($queryData['sql']);

        $rows = fetchManagerRows($conn, $queryData['sql'], $queryData['binds'] ?? []);
        $totalRows += count($rows);
        $modeNames[] = (string)($queryData['name'] ?? $params['mode']);

        $format = strtolower((string)($params['format'] ?? 'csv'));
        $columns = $queryData['columns'] ?? columnsFromRows($rows);
        $prefix = $queryData['filename_prefix'] ?? 'quantum_manager_export';

        if ($format === 'csv' || $format === 'both') {
            $files[] = writeManagerCsv($rows, $columns, $prefix);
        } elseif ($format === 'xls') {
            $files[] = writeManagerXls($rows, $columns, $prefix);
        } else {
            throw new RuntimeException('Invalid --format value. Use csv, xls, or both');
        }

        if ($format === 'both') {
            $files[] = writeManagerXls($rows, $columns, $prefix);
        }
    }

    oci_close($conn);

    echo "Quantum manager discovery\n";
    echo "Mode: " . implode(',', $modeNames) . PHP_EOL;
    echo "Rows: " . $totalRows . PHP_EOL;
    foreach ($files as $file) {
        echo strtoupper(pathinfo($file, PATHINFO_EXTENSION)) . ": {$file}" . PHP_EOL;
    }

    writeManagerLog($startedAt, date('c'), [
        'status' => 'ok',
        'mode' => implode(',', $modeNames),
        'rows' => $totalRows,
        'file' => implode(',', $files),
    ]);
} catch (Throwable $e) {
    if (isset($conn) && is_resource($conn)) {
        oci_close($conn);
    }

    failManagerSync($startedAt, $e->getMessage());
}

function runManagerAmountScan($conn, array $params): array
{
    $amount = trim((string)($params['amount'] ?? ''));
    if ($amount === '') {
        throw new RuntimeException('Missing --amount value for --mode=amount-scan');
    }

    $numericAmount = (float)str_replace([',', '$', ' '], '', $amount);
    $candidateLimit = max(1, min(5000, (int)($params['limit'] ?? 1500)));

    $candidateSql = "
SELECT table_name, column_name
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND data_type = 'NUMBER'
  AND table_name NOT LIKE 'PVIEW_%'
  AND REGEXP_LIKE(table_name, 'WO|WQ|QUOTE|BILL|INVC|KAC|CQ|RPT|WIP|SPS|SPR|AUDIT|LOG|COST|REV|STOCK|PROJECT|LOT|SA_', 'i')
  AND REGEXP_LIKE(column_name, 'PRICE|COST|AMOUNT|TOTAL|REVENUE|FOREIGN|LABOR|PARTS|MISC|OSV|CONS|DEPOSIT|QUOTE|EST', 'i')
ORDER BY table_name, column_id
FETCH FIRST :candidate_limit ROWS ONLY";

    $candidates = fetchManagerRowsQuiet($conn, $candidateSql, [
        ':candidate_limit' => $candidateLimit,
    ]);

    $tables = array_values(array_unique(array_map(
        static fn(array $row): string => strtoupper((string)$row['TABLE_NAME']),
        $candidates
    )));
    $metadata = loadManagerColumnMap($conn, $tables);

    $hits = [];
    foreach ($candidates as $candidate) {
        $table = strtoupper((string)$candidate['TABLE_NAME']);
        $column = strtoupper((string)$candidate['COLUMN_NAME']);

        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $table) || !preg_match('/^[A-Z][A-Z0-9_]*$/', $column)) {
            continue;
        }

        $columnMap = $metadata[$table] ?? [];
        if (!isset($columnMap[$column])) {
            continue;
        }

        $selects = [
            quoteManagerSqlString($table) . ' AS SOURCE_TABLE',
            quoteManagerSqlString($column) . ' AS SOURCE_COLUMN',
            keyExpr($columnMap, ['SI_NUMBER', 'WO_NUMBER', 'WO']) . ' AS WO_NUMBER',
            keyExpr($columnMap, ['WOO_AUTO_KEY', 'WOO', 'WOO_REF', 'WOK_WOO_REF']) . ' AS WOO_AUTO_KEY',
            keyExpr($columnMap, ['WQH_AUTO_KEY', 'WQH', 'QUOTE_WQH']) . ' AS WQH_AUTO_KEY',
            keyExpr($columnMap, ['WQD_AUTO_KEY', 'WQD']) . ' AS WQD_AUTO_KEY',
            keyExpr($columnMap, ['INH_AUTO_KEY']) . ' AS INH_AUTO_KEY',
            keyExpr($columnMap, ['INVC_NUMBER', 'BILLING_NUMBER', 'WQH_NUMBER']) . ' AS DOC_NUMBER',
            $column . ' AS AMOUNT_VALUE',
            keyExpr($columnMap, ['ENTRY_DATE', 'TRAN_DATE', 'POST_DATE', 'INVOICE_DATE', 'QUOTE_DATE', 'SENT_DATE', 'APPROVED_DATE', 'STATUS_CHANGE_DATE']) . ' AS SOURCE_DATE',
        ];

        $sql = "
SELECT " . implode(",\n       ", $selects) . "
FROM QCTL.{$table}
WHERE {$column} BETWEEN :amount_min AND :amount_max
FETCH FIRST 50 ROWS ONLY";

        try {
            $rows = fetchManagerRowsQuiet($conn, $sql, [
                ':amount_min' => $numericAmount - 0.01,
                ':amount_max' => $numericAmount + 0.01,
            ]);
        } catch (Throwable $e) {
            continue;
        }

        foreach ($rows as $row) {
            $hits[] = $row;
        }
    }

    $columns = [
        'SOURCE_TABLE',
        'SOURCE_COLUMN',
        'WO_NUMBER',
        'WOO_AUTO_KEY',
        'WQH_AUTO_KEY',
        'WQD_AUTO_KEY',
        'INH_AUTO_KEY',
        'DOC_NUMBER',
        'AMOUNT_VALUE',
        'SOURCE_DATE',
    ];

    $path = writeManagerCsv($hits, $columns, 'quantum_manager_amount_scan_' . str_replace(['.', ','], '_', $amount));

    return [
        'rows' => count($hits),
        'file' => $path,
    ];
}

function runManagerWoSpbQuotesProc($conn, array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-spb-quotes-proc');
    }

    $columns = [
        'TARGET_WO',
        'CALL_WO_NUM',
        'CALL_SHOW_WO_TYPE',
        'WQH_AUTO_KEY',
        'WQH_NUMBER',
        'QUOTE_VERSION',
        'QUOTE_SEQUENCE',
        'ENTRY_DATE',
        'CMP_AUTO_KEY',
        'CUST_REF',
        'COMPANY_NAME',
        'COMPANY_CODE',
        'WOO_AUTO_KEY',
        'SI_NUMBER',
        'WO_DESCRIPTION',
        'WQS_AUTO_KEY',
        'STATUS_CODE',
        'STATUS_CHANGE_DATE',
        'ACT_AUTO_KEY',
        'TAIL_NUMBER',
        'BILLING_NUMBER',
        'CUR_AUTO_KEY',
        'CURRENCY_CODE',
        'TOTAL_FOREIGN_PRICE',
    ];

    $rows = [];
    $sql = "
BEGIN
    :rc := QCTL.QC_WO_PKG.SPB_WO_QUOTES(
        :p_wqh_number,
        :p_entry_date,
        :p_company_name,
        :p_cust_ref,
        :p_wo_num,
        :p_tail_num,
        :p_show_wo_type,
        :p_quote_status,
        :p_include_total_foreign_price,
        :p_dept_name
    );
END;";

    try {
        foreach ($woNumbers as $woNumber) {
            $woNumCandidates = array_values(array_unique([
                $woNumber,
                preg_replace('/^W/i', '', $woNumber),
            ]));
            $showWoTypeCandidates = ['T', 'Y', 'N', null];

            foreach ($woNumCandidates as $woNumCandidate) {
                foreach ($showWoTypeCandidates as $showWoTypeCandidate) {
            $stid = oci_parse($conn, $sql);
            $cursor = oci_new_cursor($conn);

            $wqhNumber = null;
            $entryDate = null;
            $companyName = null;
            $custRef = null;
            $woNum = (string)$woNumCandidate;
            $tailNum = null;
            $showWoType = $showWoTypeCandidate;
            $quoteStatus = null;
            $includeTotalForeignPrice = 'T';
            $deptName = null;

            oci_bind_by_name($stid, ':rc', $cursor, -1, OCI_B_CURSOR);
            oci_bind_by_name($stid, ':p_wqh_number', $wqhNumber, 64);
            oci_bind_by_name($stid, ':p_entry_date', $entryDate, -1);
            oci_bind_by_name($stid, ':p_company_name', $companyName, 255);
            oci_bind_by_name($stid, ':p_cust_ref', $custRef, 255);
            oci_bind_by_name($stid, ':p_wo_num', $woNum, 64);
            oci_bind_by_name($stid, ':p_tail_num', $tailNum, 64);
            oci_bind_by_name($stid, ':p_show_wo_type', $showWoType, 8);
            oci_bind_by_name($stid, ':p_quote_status', $quoteStatus, 64);
            oci_bind_by_name($stid, ':p_include_total_foreign_price', $includeTotalForeignPrice, 8);
            oci_bind_by_name($stid, ':p_dept_name', $deptName, 128);

            if (!oci_execute($stid, OCI_NO_AUTO_COMMIT)) {
                $e = oci_error($stid);
                oci_free_statement($stid);
                throw new RuntimeException('Oracle SPB_WO_QUOTES call error for ' . $woNumber . ': ' . ($e['message'] ?? 'unknown'));
            }

            if (!oci_execute($cursor, OCI_NO_AUTO_COMMIT)) {
                $e = oci_error($cursor);
                oci_free_statement($cursor);
                oci_free_statement($stid);
                throw new RuntimeException('Oracle SPB_WO_QUOTES cursor error for ' . $woNumber . ': ' . ($e['message'] ?? 'unknown'));
            }

            while ($row = oci_fetch_assoc($cursor)) {
                $normalized = normalizeManagerRow($row);
                $rows[] = [
                    'TARGET_WO' => $woNumber,
                    'CALL_WO_NUM' => $woNum,
                    'CALL_SHOW_WO_TYPE' => $showWoType ?? '(null)',
                ] + $normalized;
            }

            oci_free_statement($cursor);
            oci_free_statement($stid);
                }
            }
        }
    } finally {
        oci_rollback($conn);
    }

    $path = writeManagerCsv(
        $rows,
        $columns,
        'quantum_manager_wo_spb_quotes_proc_' . implode('_', array_map(static fn(string $wo): string => strtolower($wo), $woNumbers))
    );

    return [
        'rows' => count($rows),
        'file' => $path,
    ];
}

function runManagerWoMoneyWideScan($conn, array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-money-wide');
    }

    $candidateLimit = max(1, min(5000, (int)($params['limit'] ?? 2500)));
    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    $woRows = fetchManagerRowsQuiet($conn, "
SELECT WOO_AUTO_KEY, SI_NUMBER AS WO_NUMBER
FROM QCTL.WO_OPERATION
WHERE UPPER(SI_NUMBER) IN ({$woList})");

    $wooToWo = [];
    foreach ($woRows as $row) {
        $woo = (string)($row['WOO_AUTO_KEY'] ?? '');
        if ($woo !== '') {
            $wooToWo[$woo] = (string)$row['WO_NUMBER'];
        }
    }

    if ($wooToWo === []) {
        throw new RuntimeException('No WO_OPERATION rows found for supplied WO numbers');
    }

    $wooList = implode(', ', array_map(static fn(string $woo): string => quoteManagerSqlString($woo), array_keys($wooToWo)));
    $keyMaps = [
        'WOO_AUTO_KEY' => $wooToWo,
        'WOO_MASTER' => $wooToWo,
        'SI_NUMBER' => array_combine(array_values($wooToWo), array_values($wooToWo)),
        'WOB_AUTO_KEY' => loadManagerKeyMap($conn, "
SELECT WOB_AUTO_KEY AS LINK_KEY, WOO_AUTO_KEY
FROM QCTL.WO_BOM
WHERE WOO_AUTO_KEY IN ({$wooList})", $wooToWo),
        'WOT_AUTO_KEY' => loadManagerKeyMap($conn, "
SELECT WOT_AUTO_KEY AS LINK_KEY, WOO_AUTO_KEY
FROM QCTL.WO_TASK
WHERE WOO_AUTO_KEY IN ({$wooList})", $wooToWo),
    ];

    $wqhMap = loadManagerKeyMap($conn, "
SELECT WQH_AUTO_KEY AS LINK_KEY, WOO_MASTER AS WOO_AUTO_KEY
FROM QCTL.WO_QUOTE_HEADER
WHERE WOO_MASTER IN ({$wooList})", $wooToWo);
    $keyMaps['WQH_AUTO_KEY'] = $wqhMap;

    if ($wqhMap !== []) {
        $wqhList = implode(', ', array_map(static fn(string $wqh): string => quoteManagerSqlString($wqh), array_keys($wqhMap)));
        $keyMaps['WQD_AUTO_KEY'] = loadManagerKeyMap($conn, "
SELECT d.WQD_AUTO_KEY AS LINK_KEY, h.WOO_MASTER AS WOO_AUTO_KEY
FROM QCTL.WO_QUOTE_DETAIL d
JOIN QCTL.WO_QUOTE_HEADER h
  ON h.WQH_AUTO_KEY = d.WQH_AUTO_KEY
WHERE d.WQH_AUTO_KEY IN ({$wqhList})", $wooToWo);
    } else {
        $keyMaps['WQD_AUTO_KEY'] = [];
    }

    $keyMaps['INH_AUTO_KEY'] = loadManagerKeyMap($conn, "
SELECT DISTINCT ih.INH_AUTO_KEY AS LINK_KEY, wo.WOO_AUTO_KEY
FROM QCTL.INVC_HEADER ih
JOIN QCTL.WO_OPERATION wo
  ON REGEXP_REPLACE(UPPER(ih.COMPANY_PO_NUMBER), '[^A-Z0-9]', '') =
     REGEXP_REPLACE(UPPER(wo.COMPANY_REF_NUMBER), '[^A-Z0-9]', '')
WHERE wo.WOO_AUTO_KEY IN ({$wooList})", $wooToWo);

    $candidateSql = "
SELECT c.table_name, c.column_name
FROM all_tab_columns c
WHERE c.owner = 'QCTL'
  AND c.data_type = 'NUMBER'
  AND c.table_name NOT LIKE 'PVIEW_%'
  AND REGEXP_LIKE(c.table_name, 'WO|WQ|QUOTE|BILL|INVC|KAC|CQ|RPT|WIP|SPS|SPR|AUDIT|LOG|COST|REV|STOCK|PROJECT|LOT|SA_', 'i')
  AND REGEXP_LIKE(c.column_name, 'PRICE|COST|AMOUNT|TOTAL|REVENUE|FOREIGN|LABOR|PARTS|MISC|OSV|CONS|DEPOSIT|QUOTE|EST|RATE|TAX|CHARGE', 'i')
  AND EXISTS (
      SELECT 1
      FROM all_tab_columns k
      WHERE k.owner = c.owner
        AND k.table_name = c.table_name
        AND k.column_name IN (
            'WOO_AUTO_KEY',
            'WOO_MASTER',
            'WOB_AUTO_KEY',
            'WOT_AUTO_KEY',
            'WQH_AUTO_KEY',
            'WQD_AUTO_KEY',
            'INH_AUTO_KEY',
            'SI_NUMBER'
        )
  )
ORDER BY c.table_name, c.column_id
FETCH FIRST :candidate_limit ROWS ONLY";

    $candidates = fetchManagerRowsQuiet($conn, $candidateSql, [
        ':candidate_limit' => $candidateLimit,
    ]);

    $tables = array_values(array_unique(array_map(
        static fn(array $row): string => strtoupper((string)$row['TABLE_NAME']),
        $candidates
    )));
    $metadata = loadManagerColumnMap($conn, $tables);

    $rows = [];
    foreach ($candidates as $candidate) {
        $table = strtoupper((string)$candidate['TABLE_NAME']);
        $column = strtoupper((string)$candidate['COLUMN_NAME']);

        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $table) || !preg_match('/^[A-Z][A-Z0-9_]*$/', $column)) {
            continue;
        }

        $columnMap = $metadata[$table] ?? [];
        if (!isset($columnMap[$column])) {
            continue;
        }

        $linkColumn = managerBestLinkColumn($columnMap);
        if ($linkColumn === null || ($keyMaps[$linkColumn] ?? []) === []) {
            continue;
        }

        $linkMap = $keyMaps[$linkColumn];
        foreach (array_chunk(array_keys($linkMap), 800) as $linkChunk) {
            $linkList = implode(', ', array_map(static fn(string $link): string => quoteManagerSqlString($link), $linkChunk));
            $linkExpr = $linkColumn === 'SI_NUMBER' ? "UPPER({$linkColumn})" : "TO_CHAR({$linkColumn})";

            $docExpr = managerMinStringExpr($columnMap, [
                'SI_NUMBER',
                'INVC_NUMBER',
                'BILLING_NUMBER',
                'WQH_NUMBER',
                'WQH_NUMBER_QUOTE',
                'COMPANY_REF_NUMBER',
                'COMPANY_PO_NUMBER',
            ]);
            $dateExpr = managerMinDateExpr($columnMap, [
                'ENTRY_DATE',
                'TRAN_DATE',
                'POST_DATE',
                'INVOICE_DATE',
                'QUOTE_DATE',
                'SENT_DATE',
                'APPROVED_DATE',
                'STATUS_CHANGE_DATE',
                'LAST_MODIFIED',
            ]);

            $sql = "
SELECT
    {$linkExpr} AS LINK_KEY,
    COUNT(*) AS ROW_COUNT,
    SUM(CASE WHEN {$column} IS NOT NULL THEN 1 ELSE 0 END) AS VALUE_ROWS,
    SUM(CASE WHEN NVL({$column}, 0) <> 0 THEN 1 ELSE 0 END) AS NONZERO_ROWS,
    SUM(NVL({$column}, 0)) AS AMOUNT_SUM,
    SUM(ABS(NVL({$column}, 0))) AS AMOUNT_ABS_SUM,
    MIN({$column}) AS MIN_AMOUNT,
    MAX({$column}) AS MAX_AMOUNT,
    {$docExpr} AS SAMPLE_DOC,
    {$dateExpr} AS SAMPLE_DATE
FROM QCTL.{$table}
WHERE {$linkExpr} IN ({$linkList})
GROUP BY {$linkExpr}
HAVING SUM(ABS(NVL({$column}, 0))) > 0";

            try {
                $scanRows = fetchManagerRowsQuiet($conn, $sql);
            } catch (Throwable $e) {
                continue;
            }

            foreach ($scanRows as $scanRow) {
                $linkKey = (string)($scanRow['LINK_KEY'] ?? '');
                $woNumber = $linkMap[$linkKey] ?? null;
                if ($woNumber === null) {
                    continue;
                }

                $rows[] = [
                    'WO_NUMBER' => $woNumber,
                    'SOURCE_TABLE' => $table,
                    'SOURCE_COLUMN' => $column,
                    'LINK_COLUMN' => $linkColumn,
                    'LINK_KEY' => $linkKey,
                    'ROW_COUNT' => $scanRow['ROW_COUNT'] ?? null,
                    'VALUE_ROWS' => $scanRow['VALUE_ROWS'] ?? null,
                    'NONZERO_ROWS' => $scanRow['NONZERO_ROWS'] ?? null,
                    'AMOUNT_SUM' => $scanRow['AMOUNT_SUM'] ?? null,
                    'AMOUNT_ABS_SUM' => $scanRow['AMOUNT_ABS_SUM'] ?? null,
                    'MIN_AMOUNT' => $scanRow['MIN_AMOUNT'] ?? null,
                    'MAX_AMOUNT' => $scanRow['MAX_AMOUNT'] ?? null,
                    'SAMPLE_DOC' => $scanRow['SAMPLE_DOC'] ?? null,
                    'SAMPLE_DATE' => $scanRow['SAMPLE_DATE'] ?? null,
                ];
            }
        }
    }

    usort($rows, static function (array $a, array $b): int {
        return [$a['WO_NUMBER'], $a['SOURCE_TABLE'], $a['SOURCE_COLUMN']] <=> [$b['WO_NUMBER'], $b['SOURCE_TABLE'], $b['SOURCE_COLUMN']];
    });

    $columns = [
        'WO_NUMBER',
        'SOURCE_TABLE',
        'SOURCE_COLUMN',
        'LINK_COLUMN',
        'LINK_KEY',
        'ROW_COUNT',
        'VALUE_ROWS',
        'NONZERO_ROWS',
        'AMOUNT_SUM',
        'AMOUNT_ABS_SUM',
        'MIN_AMOUNT',
        'MAX_AMOUNT',
        'SAMPLE_DOC',
        'SAMPLE_DATE',
    ];

    $path = writeManagerCsv($rows, $columns, 'quantum_manager_wo_money_wide');

    return [
        'rows' => count($rows),
        'file' => $path,
    ];
}

function loadManagerKeyMap($conn, string $sql, array $wooToWo): array
{
    try {
        $rows = fetchManagerRowsQuiet($conn, $sql);
    } catch (Throwable $e) {
        return [];
    }

    $map = [];
    foreach ($rows as $row) {
        $link = (string)($row['LINK_KEY'] ?? '');
        $woo = (string)($row['WOO_AUTO_KEY'] ?? '');
        if ($link !== '' && isset($wooToWo[$woo])) {
            $map[$link] = $wooToWo[$woo];
        }
    }

    return $map;
}

function managerBestLinkColumn(array $columnMap): ?string
{
    foreach ([
        'WOO_AUTO_KEY',
        'WOO_MASTER',
        'WOB_AUTO_KEY',
        'WOT_AUTO_KEY',
        'WQH_AUTO_KEY',
        'WQD_AUTO_KEY',
        'INH_AUTO_KEY',
        'SI_NUMBER',
    ] as $column) {
        if (isset($columnMap[$column])) {
            return $column;
        }
    }

    return null;
}

function managerMinStringExpr(array $columnMap, array $candidates): string
{
    foreach ($candidates as $candidate) {
        $column = strtoupper($candidate);
        if (isset($columnMap[$column])) {
            return "MIN(TO_CHAR({$column}))";
        }
    }

    return 'NULL';
}

function managerMinDateExpr(array $columnMap, array $candidates): string
{
    foreach ($candidates as $candidate) {
        $column = strtoupper($candidate);
        if (isset($columnMap[$column])) {
            $type = strtoupper((string)($columnMap[$column]['DATA_TYPE'] ?? ''));
            if ($type === 'DATE' || str_starts_with($type, 'TIMESTAMP')) {
                return "MIN(TO_CHAR({$column}, 'YYYY-MM-DD HH24:MI:SS'))";
            }

            return "MIN(TO_CHAR({$column}))";
        }
    }

    return 'NULL';
}

function loadManagerColumnMap($conn, array $tables): array
{
    $safeTables = array_values(array_filter($tables, static fn(string $table): bool => preg_match('/^[A-Z][A-Z0-9_]*$/', $table) === 1));
    if ($safeTables === []) {
        return [];
    }

    $tableList = implode(', ', array_map(static fn(string $table): string => quoteManagerSqlString($table), $safeTables));
    $rows = fetchManagerRows($conn, "
SELECT table_name, column_name, data_type
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND table_name IN ({$tableList})
ORDER BY table_name, column_id");

    $map = [];
    foreach ($rows as $row) {
        $map[strtoupper((string)$row['TABLE_NAME'])][strtoupper((string)$row['COLUMN_NAME'])] = $row;
    }

    return $map;
}

function keyExpr(array $columnMap, array $candidates): string
{
    foreach ($candidates as $candidate) {
        $column = strtoupper($candidate);
        if (isset($columnMap[$column])) {
            $type = strtoupper((string)($columnMap[$column]['DATA_TYPE'] ?? ''));
            if ($type === 'DATE' || str_starts_with($type, 'TIMESTAMP')) {
                return "TO_CHAR({$column}, 'YYYY-MM-DD HH24:MI:SS')";
            }

            return "TO_CHAR({$column})";
        }
    }

    return 'NULL';
}

function buildManagerQueryList(array $params): array
{
    if (strtolower((string)($params['mode'] ?? 'tables')) !== 'all') {
        return [buildQuantumManagerQuery($params)];
    }

    $queries = [];
    foreach (['tables', 'columns', 'links'] as $mode) {
        $modeParams = $params;
        $modeParams['mode'] = $mode;
        $queries[] = buildQuantumManagerQuery($modeParams);
    }

    return $queries;
}

function readManagerCliParams(): array
{
    global $argv;

    $params = [
        'mode' => 'tables',
        'format' => 'csv',
        'limit' => 5000,
    ];

    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--mode=')) {
            $params['mode'] = strtolower(trim(substr($arg, 7)));
        } elseif (str_starts_with($arg, '--format=')) {
            $params['format'] = strtolower(trim(substr($arg, 9)));
        } elseif (str_starts_with($arg, '--limit=')) {
            $params['limit'] = max(1, (int)substr($arg, 8));
        } elseif (str_starts_with($arg, '--wo=')) {
            $params['wo_number'] = trim(substr($arg, 5));
        } elseif (str_starts_with($arg, '--wos=')) {
            $params['wos'] = trim(substr($arg, 6), "\"'");
        } elseif (str_starts_with($arg, '--ref=')) {
            $params['ref'] = trim(substr($arg, 6));
        } elseif (str_starts_with($arg, '--pn=')) {
            $params['pn'] = trim(substr($arg, 5));
        } elseif (str_starts_with($arg, '--amount=')) {
            $params['amount'] = trim(substr($arg, 9));
        } elseif (str_starts_with($arg, '--date=')) {
            $params['date'] = trim(substr($arg, 7), "\"'");
        } elseif (str_starts_with($arg, '--table=')) {
            $params['table'] = trim(substr($arg, 8));
        } elseif (str_starts_with($arg, '--from=')) {
            $params['from'] = trim(substr($arg, 7), "\"'");
        } elseif (str_starts_with($arg, '--to=')) {
            $params['to'] = trim(substr($arg, 5), "\"'");
        }
    }

    return $params;
}

function envFirst(array $names): string
{
    foreach ($names as $name) {
        $value = getenv($name);
        if ($value !== false && trim((string)$value) !== '') {
            return trim((string)$value);
        }
    }

    return '';
}

function fetchManagerRows($conn, string $sql, array $binds = []): array
{
    assertManagerReadOnlySql($sql);

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
        $rows[] = normalizeManagerRow($row);
    }

    oci_free_statement($stid);

    return $rows;
}

function fetchManagerRowsQuiet($conn, string $sql, array $binds = []): array
{
    assertManagerReadOnlySql($sql);

    $stid = oci_parse($conn, $sql);
    foreach ($binds as $name => $value) {
        oci_bind_by_name($stid, $name, $binds[$name]);
    }

    if (!@oci_execute($stid)) {
        $e = oci_error($stid);
        oci_free_statement($stid);
        throw new RuntimeException('Oracle query error: ' . ($e['message'] ?? 'unknown'));
    }

    $rows = [];
    while ($row = oci_fetch_assoc($stid)) {
        $rows[] = normalizeManagerRow($row);
    }

    oci_free_statement($stid);

    return $rows;
}

function normalizeManagerRow(array $row): array
{
    $normalized = [];

    foreach ($row as $key => $value) {
        if ($value instanceof OCILob) {
            $value = $value->load();
        }

        $normalized[$key] = is_string($value) ? trim($value) : $value;
    }

    return $normalized;
}

function columnsFromRows(array $rows): array
{
    if ($rows === []) {
        return [];
    }

    return array_keys($rows[0]);
}

function writeManagerCsv(array $rows, array $columns, string $prefix): string
{
    $path = managerOutputPath($prefix . '_' . date('Ymd_His') . '.csv');
    $handle = fopen($path, 'wb');

    if (!$handle) {
        throw new RuntimeException('Cannot write CSV: ' . $path);
    }

    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, $columns);

    foreach ($rows as $row) {
        fputcsv($handle, rowForManagerColumns($row, $columns));
    }

    fclose($handle);

    return $path;
}

function writeManagerXls(array $rows, array $columns, string $prefix): string
{
    $path = managerOutputPath($prefix . '_' . date('Ymd_His') . '.xls');
    $handle = fopen($path, 'wb');

    if (!$handle) {
        throw new RuntimeException('Cannot write XLS: ' . $path);
    }

    fwrite($handle, "<html><head><meta charset=\"UTF-8\"></head><body><table border=\"1\">\n");
    fwrite($handle, "<tr>");
    foreach ($columns as $column) {
        fwrite($handle, '<th>' . managerHtml((string)$column) . '</th>');
    }
    fwrite($handle, "</tr>\n");

    foreach ($rows as $row) {
        fwrite($handle, "<tr>");
        foreach (rowForManagerColumns($row, $columns) as $value) {
            fwrite($handle, '<td style="mso-number-format:\'\\@\';">' . managerHtml($value) . '</td>');
        }
        fwrite($handle, "</tr>\n");
    }

    fwrite($handle, "</table></body></html>\n");
    fclose($handle);

    return $path;
}

function rowForManagerColumns(array $row, array $columns): array
{
    return array_map(
        static fn(string $column): string => (string)($row[$column] ?? ''),
        $columns
    );
}

function managerHtml(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function managerOutputPath(string $filename): string
{
    ensureManagerOutputDir();

    return MANAGER_OUTPUT_DIR . DIRECTORY_SEPARATOR . $filename;
}

function ensureManagerOutputDir(): void
{
    if (!is_dir(MANAGER_OUTPUT_DIR)) {
        mkdir(MANAGER_OUTPUT_DIR, 0777, true);
    }
}

function writeManagerLog(string $startedAt, string $finishedAt, array $context): void
{
    ensureManagerOutputDir();

    $parts = ['[' . date('Y-m-d H:i:s') . ']'];
    $parts[] = 'status=' . logManagerValue($context['status'] ?? 'unknown');
    $parts[] = 'mode=' . logManagerValue($context['mode'] ?? '-');
    $parts[] = 'rows=' . (int)($context['rows'] ?? 0);

    if (!empty($context['file'])) {
        $parts[] = 'file="' . logManagerValue($context['file']) . '"';
    }

    if (!empty($context['message'])) {
        $parts[] = 'message="' . logManagerValue($context['message']) . '"';
    }

    file_put_contents(MANAGER_SYNC_LOG_FILE, implode(' ', $parts) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function logManagerValue(mixed $value): string
{
    return str_replace(["\r", "\n", '"'], [' ', ' ', "'"], trim((string)$value));
}

function failManagerSync(string $startedAt, string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    writeManagerLog($startedAt, date('c'), [
        'status' => 'error',
        'message' => $message,
    ]);
    exit(1);
}

function assertManagerReadOnlySql(string $sql): void
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
