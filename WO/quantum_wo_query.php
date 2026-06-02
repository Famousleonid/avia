<?php
// quantum_wo_query.php
// Frequently changed file for Quantum WO extraction.
// Keep this file read-only from Quantum: SELECT/WITH queries only.

function quantumWoMetadataTables(): array
{
    return [
        'WO_OPERATION',
        'PARTS_MASTER',
        'STOCK_MASTER',
        'COMPANIES',
    ];
}

function buildQuantumWoQuery(array $metadata, array $params): array
{
    $woColumns = tableColumnNames($metadata, 'WO_OPERATION');
    $pmColumns = tableColumnNames($metadata, 'PARTS_MASTER');
    $stockColumns = tableColumnNames($metadata, 'STOCK_MASTER');
    $companyColumns = tableColumnNames($metadata, 'COMPANIES');
    $woColumnMap = tableColumnMap($metadata, 'WO_OPERATION');
    $pmColumnMap = tableColumnMap($metadata, 'PARTS_MASTER');
    $stockColumnMap = tableColumnMap($metadata, 'STOCK_MASTER');
    $companyColumnMap = tableColumnMap($metadata, 'COMPANIES');

    $hasPartsMaster = $pmColumns !== [];
    $hasStockMaster = $stockColumns !== [];
    $hasCompanies = $companyColumns !== [];

    $joins = [];

    if ($hasPartsMaster && hasColumn($woColumns, 'PNM_AUTO_KEY')) {
        $joins[] = "LEFT JOIN QCTL.PARTS_MASTER pm_wo\n    ON pm_wo.PNM_AUTO_KEY = wo.PNM_AUTO_KEY";
    }

    $hasStockJoin = $hasStockMaster
        && hasColumn($woColumns, 'STM_AUTO_KEY')
        && hasColumn($stockColumns, 'STM_AUTO_KEY');

    if ($hasStockJoin) {
        $joins[] = "LEFT JOIN QCTL.STOCK_MASTER sm\n    ON sm.STM_AUTO_KEY = wo.STM_AUTO_KEY";

        if ($hasPartsMaster && hasColumn($stockColumns, 'PNM_AUTO_KEY')) {
            $joins[] = "LEFT JOIN QCTL.PARTS_MASTER pm_stock\n    ON pm_stock.PNM_AUTO_KEY = sm.PNM_AUTO_KEY";
        }
    }

    $hasCompanyWoJoin = $hasCompanies
        && hasColumn($woColumns, 'CMP_AUTO_KEY')
        && hasColumn($companyColumns, 'CMP_AUTO_KEY');

    if ($hasCompanyWoJoin) {
        $joins[] = "LEFT JOIN QCTL.COMPANIES cmp_wo\n    ON cmp_wo.CMP_AUTO_KEY = wo.CMP_AUTO_KEY";
    }

    $hasCompanyStockJoin = $hasCompanies
        && $hasStockJoin
        && hasColumn($stockColumns, 'CMP_AUTO_KEY')
        && hasColumn($companyColumns, 'CMP_AUTO_KEY');

    if ($hasCompanyStockJoin) {
        $joins[] = "LEFT JOIN QCTL.COMPANIES cmp_stock\n    ON cmp_stock.CMP_AUTO_KEY = sm.CMP_AUTO_KEY";
    }

    $unitPnCandidates = [];
    if ($hasStockJoin && hasColumn($stockColumns, 'PNM_AUTO_KEY') && hasTextValueColumn($pmColumnMap, 'PN')) {
        $unitPnCandidates[] = textCandidate('pm_stock.PN', 'STOCK_MASTER.PNM_AUTO_KEY -> PARTS_MASTER.PN');
    }
    if (hasColumn($woColumns, 'PNM_AUTO_KEY') && hasTextValueColumn($pmColumnMap, 'PN')) {
        $unitPnCandidates[] = textCandidate('pm_wo.PN', 'WO_OPERATION.PNM_AUTO_KEY -> PARTS_MASTER.PN');
    }
    foreach (['PART_NUMBER', 'PN', 'UNIT_PN', 'AIRCRAFT_PN'] as $column) {
        if (hasTextValueColumn($woColumnMap, $column)) {
            $unitPnCandidates[] = textCandidate('wo.' . $column, 'WO_OPERATION.' . $column);
        }
    }

    $descriptionCandidates = [];
    if ($hasStockJoin && hasColumn($stockColumns, 'PNM_AUTO_KEY') && hasTextValueColumn($pmColumnMap, 'DESCRIPTION')) {
        $descriptionCandidates[] = textCandidate('pm_stock.DESCRIPTION', 'STOCK_MASTER.PNM_AUTO_KEY -> PARTS_MASTER.DESCRIPTION');
    }
    if (hasColumn($woColumns, 'PNM_AUTO_KEY') && hasTextValueColumn($pmColumnMap, 'DESCRIPTION')) {
        $descriptionCandidates[] = textCandidate('pm_wo.DESCRIPTION', 'WO_OPERATION.PNM_AUTO_KEY -> PARTS_MASTER.DESCRIPTION');
    }
    foreach (['DESCRIPTION', 'WO_DESCRIPTION', 'WORK_DESCRIPTION', 'DESC_TEXT', 'REMARKS', 'NOTES', 'COMMENTS'] as $column) {
        if (hasTextValueColumn($woColumnMap, $column)) {
            $descriptionCandidates[] = textCandidate('wo.' . $column, 'WO_OPERATION.' . $column);
        }
    }

    $customerCandidates = [];
    foreach (['COMPANY_NAME', 'NAME', 'CUSTOMER_NAME', 'COMPANY_CODE', 'CUSTOMER_CODE'] as $column) {
        if ($hasCompanyWoJoin && hasTextValueColumn($companyColumnMap, $column)) {
            $customerCandidates[] = textCandidate('cmp_wo.' . $column, 'WO_OPERATION.CMP_AUTO_KEY -> COMPANIES.' . $column);
        }
    }
    foreach (['COMPANY_NAME', 'NAME', 'CUSTOMER_NAME', 'COMPANY_CODE', 'CUSTOMER_CODE'] as $column) {
        if ($hasCompanyStockJoin && hasTextValueColumn($companyColumnMap, $column)) {
            $customerCandidates[] = textCandidate('cmp_stock.' . $column, 'STOCK_MASTER.CMP_AUTO_KEY -> COMPANIES.' . $column);
        }
    }
    foreach (['CUSTOMER_NAME', 'CUST_NAME', 'CUSTOMER', 'CUST_REF', 'CUSTOMER_REF', 'COMPANY_NAME', 'COMPANY_REF_NUMBER'] as $column) {
        if (hasTextValueColumn($woColumnMap, $column)) {
            $customerCandidates[] = textCandidate('wo.' . $column, 'WO_OPERATION.' . $column);
        }
    }

    $serialCandidates = [];
    foreach (['SERIAL_NUMBER', 'SERIAL_NUM', 'SERIAL_NO', 'SERIAL', 'SN', 'S_N', 'UNIT_SERIAL_NUMBER', 'UNIT_SN'] as $column) {
        if (hasTextValueColumn($woColumnMap, $column)) {
            $serialCandidates[] = textCandidate('wo.' . $column, 'WO_OPERATION.' . $column);
        }
    }
    foreach (['SERIAL_NUMBER', 'SERIAL_NUM', 'SERIAL_NO', 'SERIAL', 'SN', 'S_N', 'UNIT_SERIAL_NUMBER', 'UNIT_SN'] as $column) {
        if ($hasStockJoin && hasTextValueColumn($stockColumnMap, $column)) {
            $serialCandidates[] = textCandidate('sm.' . $column, 'WO_OPERATION.STM_AUTO_KEY -> STOCK_MASTER.' . $column);
        }
    }

    $openDateCandidate = firstDateCandidate($woColumnMap, [
        'OPEN_DATE',
        'DATE_OPENED',
        'ENTRY_DATE',
        'DATE_CREATED',
        'CREATED_DATE',
        'CREATION_DATE',
        'START_DATE',
        'WO_DATE',
    ]);

    $sourceDateCandidate = firstDateCandidate($woColumnMap, [
        'LAST_MODIFIED',
        'SYSUR_MODIFIED',
        'LAST_STATUS_CHG',
        'SYSUR_LAST_STATUS_CHG',
        'LAST_UPDATE_DATE',
        'UPDATED_DATE',
        'MODIFIED_DATE',
        'DATE_MODIFIED',
        'CHANGE_DATE',
        'ENTRY_DATE',
        'OPEN_DATE',
        'DATE_OPENED',
    ]);

    $unitPnExpr = coalesceTextExpr($unitPnCandidates);
    $descriptionExpr = coalesceTextExpr($descriptionCandidates);
    $customerExpr = coalesceTextExpr($customerCandidates);
    $serialExpr = coalesceTextExpr($serialCandidates);
    $openDateExpr = $openDateCandidate ? 'wo.' . $openDateCandidate : null;
    $sourceDateExpr = $sourceDateCandidate ? 'wo.' . $sourceDateCandidate : $openDateExpr;

    $binds = [];
    $where = [
        'wo.SI_NUMBER IS NOT NULL',
    ];

    $woNumber = strtoupper(trim((string)($params['wo_number'] ?? '')));
    if ($woNumber !== '') {
        $where[] = 'UPPER(wo.SI_NUMBER) = :wo_number';
        $binds[':wo_number'] = $woNumber;
    }

    $status = strtoupper(trim((string)($params['status'] ?? '')));
    if ($status !== '' && hasColumn($woColumns, 'WO_DISP')) {
        $where[] = 'UPPER(wo.WO_DISP) LIKE :status';
        $binds[':status'] = '%' . $status . '%';
    }

    $customer = trim((string)($params['customer'] ?? ''));
    if ($customer !== '' && $customerExpr !== 'NULL') {
        $where[] = 'UPPER(' . $customerExpr . ') LIKE UPPER(:customer)';
        $binds[':customer'] = '%' . $customer . '%';
    }

    $allRows = (bool)($params['all_rows'] ?? false);
    $changedSince = trim((string)($params['changed_since'] ?? ''));
    $daysBack = max(1, (int)($params['days_back'] ?? 30));

    if ($woNumber === '' && !$allRows && $sourceDateExpr !== null) {
        if ($changedSince !== '') {
            $where[] = $sourceDateExpr . " >= TO_DATE(:changed_since, 'YYYY-MM-DD HH24:MI:SS')";
            $binds[':changed_since'] = $changedSince;
        } else {
            $where[] = $sourceDateExpr . ' >= SYSDATE - :days_back';
            $binds[':days_back'] = $daysBack;
        }
    }

    $limit = max(0, (int)($params['limit'] ?? 1000));
    $fetchClause = $limit > 0 ? "\nFETCH FIRST {$limit} ROWS ONLY" : '';

    $statusSelect = hasColumn($woColumns, 'WO_DISP') ? 'wo.WO_DISP' : 'NULL';
    $companyRefSelect = hasColumn($woColumns, 'COMPANY_REF_NUMBER') ? 'wo.COMPANY_REF_NUMBER' : 'NULL';
    $woPnmSelect = hasColumn($woColumns, 'PNM_AUTO_KEY') ? 'wo.PNM_AUTO_KEY' : 'NULL';
    $stockKeySelect = hasColumn($woColumns, 'STM_AUTO_KEY') ? 'wo.STM_AUTO_KEY' : 'NULL';
    $companyKeySelect = hasColumn($woColumns, 'CMP_AUTO_KEY') ? 'wo.CMP_AUTO_KEY' : 'NULL';

    $openDateSelect = $openDateExpr
        ? "TO_CHAR({$openDateExpr}, 'YYYY-MM-DD HH24:MI:SS')"
        : 'NULL';
    $sourceDateSelect = $sourceDateExpr
        ? "TO_CHAR({$sourceDateExpr}, 'YYYY-MM-DD HH24:MI:SS')"
        : 'NULL';

    $orderDateExpr = $sourceDateExpr ?? $openDateExpr;
    $orderBy = $orderDateExpr
        ? "{$orderDateExpr} DESC NULLS LAST, wo.SI_NUMBER"
        : 'wo.WOO_AUTO_KEY DESC';

    $sql = "
SELECT
    wo.WOO_AUTO_KEY,
    wo.SI_NUMBER AS WO_NUMBER,
    {$statusSelect} AS WO_STATUS,
    {$companyRefSelect} AS COMPANY_REF_NUMBER,
    {$woPnmSelect} AS WO_PNM_AUTO_KEY,
    {$stockKeySelect} AS STM_AUTO_KEY,
    {$companyKeySelect} AS CMP_AUTO_KEY,
    {$unitPnExpr} AS UNIT_PN,
    " . sourceCaseExpr($unitPnCandidates) . " AS UNIT_PN_SOURCE,
    {$customerExpr} AS CUSTOMER,
    " . sourceCaseExpr($customerCandidates) . " AS CUSTOMER_SOURCE,
    {$serialExpr} AS SERIAL_NUMBER,
    " . sourceCaseExpr($serialCandidates) . " AS SERIAL_SOURCE,
    {$descriptionExpr} AS DESCRIPTION,
    " . sourceCaseExpr($descriptionCandidates) . " AS DESCRIPTION_SOURCE,
    {$openDateSelect} AS OPEN_DATE_ISO,
    " . ($openDateCandidate ? quoteSqlString('WO_OPERATION.' . $openDateCandidate) : 'NULL') . " AS OPEN_DATE_SOURCE,
    {$sourceDateSelect} AS SOURCE_LAST_MODIFIED
FROM QCTL.WO_OPERATION wo
" . implode("\n", $joins) . "
WHERE " . implode("\n  AND ", $where) . "
ORDER BY {$orderBy}{$fetchClause}
";

    return [
        'sql' => $sql,
        'binds' => $binds,
        'diagnostics' => [
            'unit_pn_candidates' => candidateSources($unitPnCandidates),
            'customer_candidates' => candidateSources($customerCandidates),
            'serial_candidates' => candidateSources($serialCandidates),
            'description_candidates' => candidateSources($descriptionCandidates),
            'open_date_source' => $openDateCandidate ? 'WO_OPERATION.' . $openDateCandidate : null,
            'source_date_source' => $sourceDateCandidate ? 'WO_OPERATION.' . $sourceDateCandidate : null,
            'stock_join' => $hasStockJoin,
            'company_wo_join' => $hasCompanyWoJoin,
            'company_stock_join' => $hasCompanyStockJoin,
        ],
    ];
}

function tableColumnNames(array $metadata, string $table): array
{
    return array_map(
        static fn(array $row): string => strtoupper((string)$row['COLUMN_NAME']),
        $metadata[strtoupper($table)] ?? []
    );
}

function tableColumnMap(array $metadata, string $table): array
{
    $map = [];

    foreach ($metadata[strtoupper($table)] ?? [] as $row) {
        $column = strtoupper((string)$row['COLUMN_NAME']);
        if ($column !== '') {
            $map[$column] = $row;
        }
    }

    return $map;
}

function hasColumn(array $columns, string $column): bool
{
    return in_array(strtoupper($column), $columns, true);
}

function hasTextValueColumn(array $columnMap, string $column): bool
{
    $column = strtoupper($column);
    if (!isset($columnMap[$column])) {
        return false;
    }

    $type = strtoupper((string)($columnMap[$column]['DATA_TYPE'] ?? ''));

    return !in_array($type, ['BLOB', 'CLOB', 'NCLOB', 'LONG', 'LONG RAW', 'RAW'], true);
}

function hasDateValueColumn(array $columnMap, string $column): bool
{
    $column = strtoupper($column);
    if (!isset($columnMap[$column])) {
        return false;
    }

    $type = strtoupper((string)($columnMap[$column]['DATA_TYPE'] ?? ''));

    return $type === 'DATE' || str_starts_with($type, 'TIMESTAMP');
}

function textCandidate(string $expr, string $source): array
{
    return [
        'expr' => "NULLIF(TRIM(TO_CHAR({$expr})), '')",
        'source' => $source,
    ];
}

function coalesceTextExpr(array $candidates): string
{
    if ($candidates === []) {
        return 'NULL';
    }

    if (count($candidates) === 1) {
        return $candidates[0]['expr'];
    }

    return 'COALESCE(' . implode(', ', array_column($candidates, 'expr')) . ')';
}

function sourceCaseExpr(array $candidates): string
{
    if ($candidates === []) {
        return 'NULL';
    }

    $parts = ['CASE'];
    foreach ($candidates as $candidate) {
        $parts[] = 'WHEN ' . $candidate['expr'] . ' IS NOT NULL THEN ' . quoteSqlString($candidate['source']);
    }
    $parts[] = 'ELSE NULL END';

    return implode(' ', $parts);
}

function candidateSources(array $candidates): array
{
    return array_values(array_map(
        static fn(array $candidate): string => $candidate['source'],
        $candidates
    ));
}

function quoteSqlString(string $value): string
{
    return "'" . str_replace("'", "''", $value) . "'";
}

function firstDateCandidate(array $columnMap, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (hasDateValueColumn($columnMap, $candidate)) {
            return strtoupper($candidate);
        }
    }

    return null;
}
