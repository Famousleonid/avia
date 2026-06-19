<?php
// quantum_manager_query.php
// Frequently changed file for Quantum manager discovery.
// Keep this file read-only from Quantum: SELECT/WITH queries only.

function buildQuantumManagerQuery(array $params): array
{
    $mode = strtolower((string)($params['mode'] ?? 'tables'));

    return match ($mode) {
        'columns' => quantumManagerColumnsQuery($params),
        'table-columns' => quantumManagerTableColumnsQuery($params),
        'quote-source-candidates' => quantumManagerQuoteSourceCandidatesQuery($params),
        'pview-dependencies' => quantumManagerPviewDependenciesQuery($params),
        'package-source-search' => quantumManagerPackageSourceSearchQuery($params),
        'view-source' => quantumManagerViewSourceQuery($params),
        'report-source-candidates' => quantumManagerReportSourceCandidatesQuery($params),
        'sys-queries-search' => quantumManagerSysQueriesSearchQuery($params),
        'object-lookup' => quantumManagerObjectLookupQuery($params),
        'links' => quantumManagerLinksQuery($params),
        'wo-preview' => quantumManagerWoPreviewQuery($params),
        'wo-statuses' => quantumManagerWoStatusesQuery($params),
        'wo-batch' => quantumManagerWoBatchQuery($params),
        'wo-billing' => quantumManagerWoBillingQuery($params),
        'wo-cq' => quantumManagerWoCqQuery($params),
        'wo-quote' => quantumManagerWoQuoteQuery($params),
        'wo-spr' => quantumManagerWoSprQuery($params),
        'wo-backcharge' => quantumManagerWoBackchargeQuery($params),
        'wo-sps-billing' => quantumManagerWoSpsBillingQuery($params),
        'wo-report-lines' => quantumManagerWoReportLinesQuery($params),
        'wo-prices' => quantumManagerWoPricesQuery($params),
        'wo-task-prices' => quantumManagerWoTaskPricesQuery($params),
        'wo-task-totals' => quantumManagerWoTaskTotalsQuery($params),
        'wo-bill-task-format' => quantumManagerWoBillTaskFormatQuery($params),
        'wo-skill-estimates' => quantumManagerWoSkillEstimatesQuery($params),
        'wo-op-template-prices' => quantumManagerWoOpTemplatePricesQuery($params),
        'wo-wqd-pos' => quantumManagerWoWqdPosQuery($params),
        'wo-kac' => quantumManagerWoKacQuery($params),
        'wo-quote-deep' => quantumManagerWoQuoteDeepQuery($params),
        'wo-billing-summary' => quantumManagerWoBillingSummaryQuery($params),
        'wo-estimate-lines' => quantumManagerWoEstimateLinesQuery($params),
        'wo-summary-tat' => quantumManagerWoSummaryTatQuery($params),
        'wo-ro-detail' => quantumManagerWoRoDetailQuery($params),
        'wo-labor-raw' => quantumManagerWoLaborRawQuery($params),
        'wo-bom-money' => quantumManagerWoBomMoneyQuery($params),
        'wo-bom-lines' => quantumManagerWoBomLinesQuery($params),
        'wo-header-money' => quantumManagerWoHeaderMoneyQuery($params),
        'wo-sps-totals' => quantumManagerWoSpsTotalsQuery($params),
        'wo-money-matrix' => quantumManagerWoMoneyMatrixQuery($params),
        'wo-audit-money' => quantumManagerWoAuditMoneyQuery($params),
        'wo-change-audit' => quantumManagerWoChangeAuditQuery($params),
        'wo-spb-quotes' => quantumManagerWoSpbQuotesQuery($params),
        'wo-range-table' => quantumManagerLatestEstimatesQuery($params),
        'latest-estimates' => quantumManagerLatestEstimatesQuery($params),
        'latest-invoiced-wo' => quantumManagerLatestInvoicedWoQuery($params),
        'latest-billed-wo' => quantumManagerLatestBilledWoQuery($params),
        'invoice-wo-samples' => quantumManagerInvoiceWoSamplesQuery($params),
        'invoice-header-samples' => quantumManagerInvoiceHeaderSamplesQuery($params),
        'invoice-po-wo-samples' => quantumManagerInvoicePoWoSamplesQuery($params),
        'wo-manager-columns' => quantumManagerWoManagerColumnsQuery($params),
        'wo-custom-fields' => quantumManagerWoCustomFieldsQuery($params),
        'wo-date-fields' => quantumManagerWoDateFieldsQuery($params),
        'wo-invoice' => quantumManagerWoInvoiceQuery($params),
        'amount-search' => quantumManagerAmountSearchQuery($params),
        'wo-search' => quantumManagerWoSearchQuery($params),
        'part-search' => quantumManagerPartSearchQuery($params),
        'ro-search' => quantumManagerRoSearchQuery($params),
        'estimate-search' => quantumManagerEstimateSearchQuery($params),
        default => quantumManagerTablesQuery($params),
    };
}

function quantumManagerTablesQuery(array $params): array
{
    $limit = managerLimit($params);

    return [
        'name' => 'tables',
        'filename_prefix' => 'quantum_manager_tables',
        'columns' => [
            'TABLE_NAME',
            'ROW_COUNT',
            'CANDIDATE_REASON',
        ],
        'binds' => [
            ':limit_rows' => $limit,
        ],
        'sql' => "
SELECT *
FROM (
    SELECT
        c.table_name,
        COUNT(*) AS row_count,
        LISTAGG(DISTINCT
            CASE
                WHEN REGEXP_LIKE(c.table_name, '(^|_)WO($|_)|WORK|ORDER', 'i') THEN 'WO_TABLE'
                WHEN REGEXP_LIKE(c.table_name, 'EST|QUOTE|QUOT', 'i') THEN 'ESTIMATE_OR_QUOTE_TABLE'
                WHEN REGEXP_LIKE(c.table_name, 'INV|INVOICE|BILL|AR_', 'i') THEN 'INVOICE_OR_BILLING_TABLE'
                WHEN c.column_name IN ('WOO_AUTO_KEY', 'WOB_AUTO_KEY', 'SI_NUMBER', 'WO_NUMBER') THEN 'WO_LINK_COLUMN'
                WHEN REGEXP_LIKE(c.column_name, 'EST|QUOTE|QUOT|INV|INVOICE|BILL|AMOUNT|TOTAL', 'i') THEN 'MANAGER_FIELD_COLUMN'
                ELSE NULL
            END,
            ', '
        ) WITHIN GROUP (ORDER BY
            CASE
                WHEN REGEXP_LIKE(c.table_name, '(^|_)WO($|_)|WORK|ORDER', 'i') THEN 'WO_TABLE'
                WHEN REGEXP_LIKE(c.table_name, 'EST|QUOTE|QUOT', 'i') THEN 'ESTIMATE_OR_QUOTE_TABLE'
                WHEN REGEXP_LIKE(c.table_name, 'INV|INVOICE|BILL|AR_', 'i') THEN 'INVOICE_OR_BILLING_TABLE'
                WHEN c.column_name IN ('WOO_AUTO_KEY', 'WOB_AUTO_KEY', 'SI_NUMBER', 'WO_NUMBER') THEN 'WO_LINK_COLUMN'
                WHEN REGEXP_LIKE(c.column_name, 'EST|QUOTE|QUOT|INV|INVOICE|BILL|AMOUNT|TOTAL', 'i') THEN 'MANAGER_FIELD_COLUMN'
                ELSE NULL
            END
        ) AS candidate_reason
    FROM all_tab_columns c
    WHERE c.owner = 'QCTL'
      AND (
          REGEXP_LIKE(c.table_name, '(^|_)WO($|_)|WORK|ORDER|EST|QUOTE|QUOT|INV|INVOICE|BILL|AR_', 'i')
          OR c.column_name IN ('WOO_AUTO_KEY', 'WOB_AUTO_KEY', 'SI_NUMBER', 'WO_NUMBER')
          OR REGEXP_LIKE(c.column_name, 'EST|QUOTE|QUOT|INV|INVOICE|BILL|AMOUNT|TOTAL', 'i')
      )
    GROUP BY c.table_name
    ORDER BY c.table_name
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerWoStatusesQuery(array $params): array
{
    return [
        'name' => 'wo-statuses',
        'filename_prefix' => 'quantum_manager_wo_statuses',
        'columns' => [
            'WO_STATUS',
            'TAT_STATUS',
            'ROW_COUNT',
            'FIRST_ENTRY_DATE',
            'LAST_ENTRY_DATE',
            'LAST_CLOSE_DATE',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.WO_DISP AS WO_STATUS,
    v.STATUS AS TAT_STATUS,
    COUNT(*) AS ROW_COUNT,
    TO_CHAR(MIN(wo.ENTRY_DATE), 'YYYY-MM-DD HH24:MI:SS') AS FIRST_ENTRY_DATE,
    TO_CHAR(MAX(wo.ENTRY_DATE), 'YYYY-MM-DD HH24:MI:SS') AS LAST_ENTRY_DATE,
    TO_CHAR(MAX(wo.CLOSE_DATE), 'YYYY-MM-DD HH24:MI:SS') AS LAST_CLOSE_DATE
FROM QCTL.WO_OPERATION wo
LEFT JOIN QCTL.VIEW_WO_SUMMARY_WITH_TAT v
  ON v.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
GROUP BY wo.WO_DISP, v.STATUS
ORDER BY wo.WO_DISP, v.STATUS",
    ];
}

function quantumManagerColumnsQuery(array $params): array
{
    $limit = managerLimit($params);

    return [
        'name' => 'columns',
        'filename_prefix' => 'quantum_manager_columns',
        'columns' => [
            'TABLE_NAME',
            'COLUMN_ID',
            'COLUMN_NAME',
            'DATA_TYPE',
            'DATA_LENGTH',
            'NULLABLE',
            'CANDIDATE_REASON',
        ],
        'binds' => [
            ':limit_rows' => $limit,
        ],
        'sql' => "
SELECT *
FROM (
    SELECT
        c.table_name,
        c.column_id,
        c.column_name,
        c.data_type,
        c.data_length,
        c.nullable,
        CASE
            WHEN c.column_name IN ('WOO_AUTO_KEY', 'WOB_AUTO_KEY', 'SI_NUMBER', 'WO_NUMBER') THEN 'WO_LINK_COLUMN'
            WHEN REGEXP_LIKE(c.column_name, 'EST|QUOTE|QUOT', 'i') THEN 'ESTIMATE_OR_QUOTE_COLUMN'
            WHEN REGEXP_LIKE(c.column_name, 'INV|INVOICE|BILL|AR_', 'i') THEN 'INVOICE_OR_BILLING_COLUMN'
            WHEN REGEXP_LIKE(c.column_name, 'AMOUNT|TOTAL|PRICE|COST|BALANCE', 'i') THEN 'MONEY_COLUMN'
            WHEN REGEXP_LIKE(c.column_name, 'DATE|TIME|MOD|STATUS|OPEN|CLOSE|POST', 'i') THEN 'DATE_STATUS_CHANGE_COLUMN'
            WHEN REGEXP_LIKE(c.column_name, 'CUST|CMP|COMPANY|VENDOR', 'i') THEN 'PARTY_COLUMN'
            ELSE 'TABLE_NAME_MATCH'
        END AS candidate_reason
    FROM all_tab_columns c
    WHERE c.owner = 'QCTL'
      AND (
          REGEXP_LIKE(c.table_name, '(^|_)WO($|_)|WORK|ORDER|EST|QUOTE|QUOT|INV|INVOICE|BILL|AR_', 'i')
          OR c.column_name IN ('WOO_AUTO_KEY', 'WOB_AUTO_KEY', 'SI_NUMBER', 'WO_NUMBER')
          OR REGEXP_LIKE(c.column_name, 'EST|QUOTE|QUOT|INV|INVOICE|BILL|AMOUNT|TOTAL|PRICE|COST|BALANCE', 'i')
      )
    ORDER BY c.table_name, c.column_id
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerTableColumnsQuery(array $params): array
{
    $table = strtoupper(trim((string)($params['table'] ?? '')));
    if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $table)) {
        throw new RuntimeException('Missing or invalid --table value for --mode=table-columns');
    }

    return [
        'name' => 'table-columns',
        'filename_prefix' => 'quantum_manager_table_columns_' . strtolower($table),
        'columns' => [
            'TABLE_NAME',
            'COLUMN_ID',
            'COLUMN_NAME',
            'DATA_TYPE',
            'DATA_LENGTH',
            'NULLABLE',
        ],
        'binds' => [
            ':table_name' => $table,
        ],
        'sql' => "
SELECT
    table_name,
    column_id,
    column_name,
    data_type,
    data_length,
    nullable
FROM all_tab_columns
WHERE owner = 'QCTL'
  AND table_name = :table_name
ORDER BY column_id",
    ];
}

function quantumManagerObjectLookupQuery(array $params): array
{
    $ref = strtoupper(trim((string)($params['ref'] ?? '')));
    $names = $ref !== ''
        ? [$ref]
        : ['SPB_WO_QUOTES', 'SPB_QUOTES_FOR_WOO', 'SPB_WQD_MAIN', 'SPB_WQD_PRICES', 'SPB_WQH_PRICES'];

    foreach ($names as $name) {
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $name)) {
            throw new RuntimeException('Invalid object name for --mode=object-lookup');
        }
    }

    $nameRows = implode("\nUNION ALL\n", array_map(
        static fn(string $name): string => '    SELECT ' . quoteManagerSqlString($name) . ' AS object_name FROM dual',
        $names
    ));

    return [
        'name' => 'object-lookup',
        'filename_prefix' => 'quantum_manager_object_lookup_' . strtolower($ref !== '' ? $ref : 'spb'),
        'columns' => [
            'SOURCE_KIND',
            'OWNER',
            'OBJECT_NAME',
            'OBJECT_TYPE',
            'STATUS_OR_INFO',
            'TARGET_OWNER',
            'TARGET_NAME',
            'DB_LINK',
            'POSITION',
            'ARGUMENT_NAME',
            'DATA_TYPE',
            'IN_OUT',
            'DEFAULTED',
        ],
        'binds' => [],
        'sql' => "
WITH target_names AS (
{$nameRows}
)
SELECT
    'OBJECT' AS source_kind,
    o.owner,
    o.object_name,
    o.object_type,
    o.status AS status_or_info,
    CAST(NULL AS VARCHAR2(128)) AS target_owner,
    CAST(NULL AS VARCHAR2(128)) AS target_name,
    CAST(NULL AS VARCHAR2(128)) AS db_link,
    CAST(NULL AS NUMBER) AS position,
    CAST(NULL AS VARCHAR2(128)) AS argument_name,
    CAST(NULL AS VARCHAR2(128)) AS data_type,
    CAST(NULL AS VARCHAR2(32)) AS in_out,
    CAST(NULL AS VARCHAR2(8)) AS defaulted
FROM all_objects o
JOIN target_names n
  ON n.object_name = o.object_name
WHERE o.owner IN ('QCTL', USER)
UNION ALL
SELECT
    'SYNONYM' AS source_kind,
    s.owner,
    s.synonym_name AS object_name,
    'SYNONYM' AS object_type,
    CAST(NULL AS VARCHAR2(128)) AS status_or_info,
    s.table_owner AS target_owner,
    s.table_name AS target_name,
    s.db_link,
    CAST(NULL AS NUMBER) AS position,
    CAST(NULL AS VARCHAR2(128)) AS argument_name,
    CAST(NULL AS VARCHAR2(128)) AS data_type,
    CAST(NULL AS VARCHAR2(32)) AS in_out,
    CAST(NULL AS VARCHAR2(8)) AS defaulted
FROM all_synonyms s
JOIN target_names n
  ON n.object_name IN (s.synonym_name, s.table_name)
WHERE s.owner IN ('PUBLIC', USER, 'QCTL')
UNION ALL
SELECT
    'ARGUMENT' AS source_kind,
    a.owner,
    NVL(a.package_name, a.object_name) AS object_name,
    a.object_name AS object_type,
    CASE WHEN a.overload IS NULL THEN NULL ELSE 'OVERLOAD ' || a.overload END AS status_or_info,
    CAST(NULL AS VARCHAR2(128)) AS target_owner,
    CAST(NULL AS VARCHAR2(128)) AS target_name,
    CAST(NULL AS VARCHAR2(128)) AS db_link,
    a.position,
    a.argument_name,
    a.data_type,
    a.in_out,
    a.defaulted
FROM all_arguments a
JOIN target_names n
  ON n.object_name IN (a.object_name, a.package_name)
WHERE a.owner IN ('QCTL', USER)
ORDER BY source_kind, owner, object_name, object_type, position",
    ];
}

function quantumManagerQuoteSourceCandidatesQuery(array $params): array
{
    $limit = managerLimit($params);

    return [
        'name' => 'quote-source-candidates',
        'filename_prefix' => 'quantum_manager_quote_source_candidates',
        'columns' => [
            'TABLE_NAME',
            'TABLE_KIND',
            'HAS_WO_LINK',
            'HAS_QUOTE_LINK',
            'HAS_APPROVAL_FIELD',
            'HAS_MONEY_FIELD',
            'LINK_COLUMNS',
            'QUOTE_COLUMNS',
            'APPROVAL_COLUMNS',
            'MONEY_COLUMNS',
        ],
        'binds' => [
            ':limit_rows' => $limit,
        ],
        'sql' => "
WITH column_flags AS (
    SELECT
        c.table_name,
        CASE
            WHEN o.object_type IS NOT NULL THEN o.object_type
            ELSE 'TABLE'
        END AS table_kind,
        c.column_name,
        CASE
            WHEN c.column_name IN ('WOO_AUTO_KEY', 'WOO_MASTER', 'SI_NUMBER', 'WO_NUMBER', 'WOB_AUTO_KEY') THEN 1
            ELSE 0
        END AS is_wo_link,
        CASE
            WHEN c.column_name IN ('WQH_AUTO_KEY', 'WQD_AUTO_KEY', 'WQH_NUMBER', 'WQH_NUMBER_QUOTE', 'QUOTE_NUMBER')
              OR REGEXP_LIKE(c.column_name, 'QUOTE|QUOT|WQH|WQD', 'i') THEN 1
            ELSE 0
        END AS is_quote_link,
        CASE
            WHEN c.column_name IN ('APPROVAL_DATE', 'APPROVED_DATE', 'APPR_STATE', 'APPR_STATUS', 'QUOTE_STATUS', 'QUOTE_STATUS_DATE')
              OR REGEXP_LIKE(c.column_name, 'APPR|APPROV|STATUS', 'i') THEN 1
            ELSE 0
        END AS is_approval_field,
        CASE
            WHEN c.data_type = 'NUMBER'
             AND REGEXP_LIKE(c.column_name, 'TOTAL|PRICE|COST|AMOUNT|LABOR|PARTS|MISC|OSV|TAX|FOREIGN|QUOTE|EST', 'i') THEN 1
            ELSE 0
        END AS is_money_field
    FROM all_tab_columns c
    LEFT JOIN all_objects o
      ON o.owner = c.owner
     AND o.object_name = c.table_name
     AND o.object_type IN ('VIEW', 'TABLE', 'MATERIALIZED VIEW')
    WHERE c.owner = 'QCTL'
      AND c.table_name NOT LIKE 'PVIEW_%'
      AND (
          REGEXP_LIKE(c.table_name, 'WO|WQ|QUOTE|QUOT|CQ|WORKSHEET|RPT|APPROV|APPR|PORTAL|CACHE|PRICE|BILL', 'i')
          OR REGEXP_LIKE(c.column_name, 'WOO|WOB|SI_NUMBER|WQH|WQD|QUOTE|QUOT|APPR|APPROV|TOTAL_PRICE', 'i')
      )
)
SELECT *
FROM (
    SELECT
        table_name,
        MAX(table_kind) AS table_kind,
        MAX(is_wo_link) AS has_wo_link,
        MAX(is_quote_link) AS has_quote_link,
        MAX(is_approval_field) AS has_approval_field,
        MAX(is_money_field) AS has_money_field,
        LISTAGG(CASE WHEN is_wo_link = 1 THEN column_name END, ', ')
            WITHIN GROUP (ORDER BY column_name) AS link_columns,
        LISTAGG(CASE WHEN is_quote_link = 1 THEN column_name END, ', ')
            WITHIN GROUP (ORDER BY column_name) AS quote_columns,
        LISTAGG(CASE WHEN is_approval_field = 1 THEN column_name END, ', ')
            WITHIN GROUP (ORDER BY column_name) AS approval_columns,
        LISTAGG(CASE WHEN is_money_field = 1 THEN column_name END, ', ')
            WITHIN GROUP (ORDER BY column_name) AS money_columns
    FROM column_flags
    GROUP BY table_name
    HAVING MAX(is_wo_link) = 1
       AND MAX(is_money_field) = 1
       AND (MAX(is_quote_link) = 1 OR MAX(is_approval_field) = 1 OR REGEXP_LIKE(table_name, 'QUOTE|QUOT|WQ|WORKSHEET|RPT|APPROV|APPR', 'i'))
    ORDER BY
        MAX(is_approval_field) DESC,
        MAX(is_quote_link) DESC,
        table_name
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerPviewDependenciesQuery(array $params): array
{
    return [
        'name' => 'pview-dependencies',
        'filename_prefix' => 'quantum_manager_pview_dependencies',
        'columns' => [
            'OWNER',
            'NAME',
            'TYPE',
            'REFERENCED_OWNER',
            'REFERENCED_NAME',
            'REFERENCED_TYPE',
            'DEPENDENCY_TYPE',
        ],
        'binds' => [],
        'sql' => "
SELECT
    owner,
    name,
    type,
    referenced_owner,
    referenced_name,
    referenced_type,
    dependency_type
FROM all_dependencies
WHERE owner = 'QCTL'
  AND name IN (
      'PVIEW_WQD_MAIN',
      'PVIEW_WQD_PRICES',
      'PVIEW_WQD_TREEPARSE_VALUES',
      'PVIEW_WQH_PRICES'
  )
ORDER BY name, referenced_owner, referenced_name",
    ];
}

function quantumManagerPackageSourceSearchQuery(array $params): array
{
    $ref = strtoupper(trim((string)($params['ref'] ?? '')));
    $limit = managerLimit($params);

    if ($ref === '') {
        throw new RuntimeException('Missing --ref value for --mode=package-source-search');
    }

    return [
        'name' => 'package-source-search',
        'filename_prefix' => 'quantum_manager_package_source_search_' . strtolower(preg_replace('/[^A-Z0-9]+/i', '_', $ref)),
        'columns' => [
            'NAME',
            'TYPE',
            'LINE',
            'TEXT',
        ],
        'binds' => [
            ':ref_like' => '%' . $ref . '%',
            ':limit_rows' => $limit,
        ],
        'sql' => "
SELECT *
FROM (
    SELECT
        name,
        type,
        line,
        SUBSTR(text, 1, 3500) AS text
    FROM all_source
    WHERE owner = 'QCTL'
      AND name IN (
          'QC_CACHE_PKG',
          'QC_WO_PKG',
          'QC_WO_PKG2',
          'QC_WO_PKG3',
          'QC_WP_PKG'
      )
      AND UPPER(text) LIKE :ref_like
    ORDER BY name, type, line
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerViewSourceQuery(array $params): array
{
    $view = strtoupper(trim((string)($params['table'] ?? '')));
    if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $view)) {
        throw new RuntimeException('Missing or invalid --table value for --mode=view-source');
    }

    return [
        'name' => 'view-source',
        'filename_prefix' => 'quantum_manager_view_source_' . strtolower($view),
        'columns' => [
            'VIEW_NAME',
            'TEXT_LENGTH',
            'TEXT',
        ],
        'binds' => [
            ':view_name' => $view,
        ],
        'sql' => "
SELECT
    view_name,
    text_length,
    text
FROM all_views
WHERE owner = 'QCTL'
  AND view_name = :view_name",
    ];
}

function quantumManagerReportSourceCandidatesQuery(array $params): array
{
    $limit = managerLimit($params);

    return [
        'name' => 'report-source-candidates',
        'filename_prefix' => 'quantum_manager_report_source_candidates',
        'columns' => [
            'TABLE_NAME',
            'TABLE_KIND',
            'ROW_COUNT',
            'NAME_COLUMNS',
            'TEXT_COLUMNS',
            'PATH_COLUMNS',
            'DATE_COLUMNS',
        ],
        'binds' => [
            ':limit_rows' => $limit,
        ],
        'sql' => "
WITH report_cols AS (
    SELECT
        c.table_name,
        CASE
            WHEN o.object_type IS NOT NULL THEN o.object_type
            ELSE 'TABLE'
        END AS table_kind,
        c.column_name,
        c.data_type,
        CASE WHEN REGEXP_LIKE(c.column_name, 'NAME|TITLE|DESCR|REPORT|RPT|MODULE|MENU', 'i') THEN 1 ELSE 0 END AS is_name_col,
        CASE WHEN c.data_type IN ('CLOB', 'LONG', 'VARCHAR2', 'NVARCHAR2')
               AND REGEXP_LIKE(c.column_name, 'SQL|QUERY|COMMAND|FORMULA|TEXT|SOURCE|BODY|WHERE|SELECT|CRYSTAL|RPT|REPORT', 'i') THEN 1 ELSE 0 END AS is_text_col,
        CASE WHEN REGEXP_LIKE(c.column_name, 'PATH|FILE|FILENAME|DIRECTORY|LOCATION|URL', 'i') THEN 1 ELSE 0 END AS is_path_col,
        CASE WHEN c.data_type = 'DATE' OR REGEXP_LIKE(c.column_name, 'DATE|MOD|CHANGE', 'i') THEN 1 ELSE 0 END AS is_date_col
    FROM all_tab_columns c
    LEFT JOIN all_objects o
      ON o.owner = c.owner
     AND o.object_name = c.table_name
     AND o.object_type IN ('VIEW', 'TABLE', 'MATERIALIZED VIEW')
    WHERE c.owner = 'QCTL'
      AND (
          REGEXP_LIKE(c.table_name, 'REPORT|RPT|CRYSTAL|MENU|FORM|MODULE|PRINT|DOC', 'i')
          OR REGEXP_LIKE(c.column_name, 'SQL|QUERY|COMMAND|FORMULA|CRYSTAL|REPORT|RPT|SOURCE|BODY', 'i')
      )
)
SELECT *
FROM (
    SELECT
        rc.table_name,
        MAX(rc.table_kind) AS table_kind,
        NVL(ut.num_rows, 0) AS row_count,
        LISTAGG(CASE WHEN rc.is_name_col = 1 THEN rc.column_name END, ', ')
            WITHIN GROUP (ORDER BY rc.column_name) AS name_columns,
        LISTAGG(CASE WHEN rc.is_text_col = 1 THEN rc.column_name END, ', ')
            WITHIN GROUP (ORDER BY rc.column_name) AS text_columns,
        LISTAGG(CASE WHEN rc.is_path_col = 1 THEN rc.column_name END, ', ')
            WITHIN GROUP (ORDER BY rc.column_name) AS path_columns,
        LISTAGG(CASE WHEN rc.is_date_col = 1 THEN rc.column_name END, ', ')
            WITHIN GROUP (ORDER BY rc.column_name) AS date_columns
    FROM report_cols rc
    LEFT JOIN all_tables ut
      ON ut.owner = 'QCTL'
     AND ut.table_name = rc.table_name
    GROUP BY rc.table_name, ut.num_rows
    HAVING MAX(rc.is_text_col) = 1
        OR MAX(rc.is_path_col) = 1
        OR REGEXP_LIKE(rc.table_name, 'REPORT|RPT|CRYSTAL', 'i')
    ORDER BY rc.table_name
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerSysQueriesSearchQuery(array $params): array
{
    $ref = strtoupper(trim((string)($params['ref'] ?? '')));
    $limit = managerLimit($params);

    if ($ref === '') {
        throw new RuntimeException('Missing --ref value for --mode=sys-queries-search');
    }

    return [
        'name' => 'sys-queries-search',
        'filename_prefix' => 'quantum_manager_sys_queries_search_' . strtolower(preg_replace('/[^A-Z0-9]+/i', '_', $ref)),
        'columns' => [
            'SYSQR_AUTO_KEY',
            'QUERY_NAME',
            'DESCRIPTION',
            'DATABASE_ALIAS',
            'MAX_ROWS',
            'AUTO_LOAD',
            'SQL_SNIPPET',
            'FIELDS_SNIPPET',
            'CALC_FIELDS_SNIPPET',
        ],
        'binds' => [
            ':ref_like' => '%' . $ref . '%',
            ':limit_rows' => $limit,
        ],
        'sql' => "
SELECT *
FROM (
    SELECT
        SYSQR_AUTO_KEY,
        QUERY_NAME,
        DESCRIPTION,
        DATABASE_ALIAS,
        MAX_ROWS,
        AUTO_LOAD,
        SUBSTR(SQLDATA_STATEMENT, 1, 3000) AS SQL_SNIPPET,
        SUBSTR(FIELDS, 1, 1500) AS FIELDS_SNIPPET,
        SUBSTR(CALC_FIELDS, 1, 1500) AS CALC_FIELDS_SNIPPET
    FROM QCTL.SYS_QUERIES
    WHERE UPPER(QUERY_NAME) LIKE :ref_like
       OR UPPER(DESCRIPTION) LIKE :ref_like
       OR UPPER(SQLDATA_STATEMENT) LIKE :ref_like
       OR UPPER(FIELDS) LIKE :ref_like
       OR UPPER(CALC_FIELDS) LIKE :ref_like
    ORDER BY QUERY_NAME
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerLinksQuery(array $params): array
{
    $limit = managerLimit($params);

    return [
        'name' => 'links',
        'filename_prefix' => 'quantum_manager_links',
        'columns' => [
            'TABLE_NAME',
            'COLUMN_NAME',
            'DATA_TYPE',
            'LINK_KIND',
        ],
        'binds' => [
            ':limit_rows' => $limit,
        ],
        'sql' => "
SELECT *
FROM (
    SELECT
        c.table_name,
        c.column_name,
        c.data_type,
        CASE
            WHEN c.column_name = 'WOO_AUTO_KEY' THEN 'DIRECT_WO_OPERATION_KEY'
            WHEN c.column_name = 'WOB_AUTO_KEY' THEN 'WO_BOM_KEY'
            WHEN c.column_name IN ('SI_NUMBER', 'WO_NUMBER') THEN 'WO_NUMBER_TEXT'
            WHEN REGEXP_LIKE(c.column_name, 'EST|QUOTE|QUOT', 'i') THEN 'ESTIMATE_OR_QUOTE_KEY'
            WHEN REGEXP_LIKE(c.column_name, 'INV|INVOICE|BILL', 'i') THEN 'INVOICE_OR_BILLING_KEY'
            ELSE 'OTHER_CANDIDATE'
        END AS link_kind
    FROM all_tab_columns c
    WHERE c.owner = 'QCTL'
      AND (
          c.column_name IN ('WOO_AUTO_KEY', 'WOB_AUTO_KEY', 'SI_NUMBER', 'WO_NUMBER')
          OR REGEXP_LIKE(c.column_name, 'EST|QUOTE|QUOT|INV|INVOICE|BILL', 'i')
      )
      AND REGEXP_LIKE(c.table_name, '(^|_)WO($|_)|WORK|ORDER|EST|QUOTE|QUOT|INV|INVOICE|BILL|AR_', 'i')
    ORDER BY link_kind, c.table_name, c.column_name
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerWoPreviewQuery(array $params): array
{
    $woNumber = normalizeManagerWoNumber((string)($params['wo_number'] ?? ''));
    if ($woNumber === '') {
        throw new RuntimeException('Missing --wo value for --mode=wo-preview');
    }

    return [
        'name' => 'wo-preview',
        'filename_prefix' => 'quantum_manager_wo_preview',
        'columns' => [
            'WOO_AUTO_KEY',
            'WO_NUMBER',
            'WO_STATUS',
            'COMPANY_REF_NUMBER',
            'PNM_AUTO_KEY',
            'STM_AUTO_KEY',
            'CMP_AUTO_KEY',
            'ENTRY_DATE',
            'DUE_DATE',
            'CLOSE_DATE',
            'QUOTE_STATUS',
            'QUOTE_STATUS_DATE',
            'EST_TOTAL_COST',
            'EI_TOTAL_COST',
            'BILL_NAME',
            'SYSUR_APPROVAL',
        ],
        'binds' => [
            ':wo_number' => $woNumber,
        ],
        'sql' => "
SELECT
    wo.WOO_AUTO_KEY,
    wo.SI_NUMBER AS WO_NUMBER,
    wo.WO_DISP AS WO_STATUS,
    wo.COMPANY_REF_NUMBER,
    wo.PNM_AUTO_KEY,
    wo.STM_AUTO_KEY,
    wo.CMP_AUTO_KEY,
    TO_CHAR(wo.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
    TO_CHAR(wo.DUE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS DUE_DATE,
    TO_CHAR(wo.CLOSE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS CLOSE_DATE,
    wo.QUOTE_STATUS,
    TO_CHAR(wo.QUOTE_STATUS_DATE, 'YYYY-MM-DD HH24:MI:SS') AS QUOTE_STATUS_DATE,
    (
        NVL(wo.EST_PARTS_COST, 0)
        + NVL(wo.EST_LABOR_COST, 0)
        + NVL(wo.EST_VO_COST, 0)
        + NVL(wo.EST_FO_COST, 0)
        + NVL(wo.EST_OSV_COST, 0)
    ) AS EST_TOTAL_COST,
    (
        NVL(wo.EI_TOT_PARTS_COST, 0)
        + NVL(wo.EI_TOT_LABOR_COST, 0)
        + NVL(wo.EI_TOT_VO_COST, 0)
        + NVL(wo.EI_TOT_FO_COST, 0)
        + NVL(wo.EI_TOT_OSV_COST, 0)
    ) AS EI_TOTAL_COST,
    wo.BILL_NAME,
    wo.SYSUR_APPROVAL
FROM QCTL.WO_OPERATION wo
WHERE UPPER(wo.SI_NUMBER) = :wo_number",
    ];
}

function quantumManagerWoInvoiceQuery(array $params): array
{
    $woNumber = normalizeManagerWoNumber((string)($params['wo_number'] ?? ''));
    if ($woNumber === '') {
        throw new RuntimeException('Missing --wo value for --mode=wo-invoice');
    }

    return [
        'name' => 'wo-invoice',
        'filename_prefix' => 'quantum_manager_wo_invoice',
        'columns' => [
            'WOO_AUTO_KEY',
            'WO_NUMBER',
            'INH_AUTO_KEY',
            'INVC_NUMBER',
            'INVOICE_DATE',
            'POST_DATE',
            'TOTAL_PRICE',
            'PAYMENT_AMT',
            'OPEN_BALANCE',
            'POST_STATUS',
            'POST_DESC',
        ],
        'binds' => [
            ':wo_number' => $woNumber,
        ],
        'sql' => "
SELECT
    wo.WOO_AUTO_KEY,
    wo.SI_NUMBER AS WO_NUMBER,
    ih.INH_AUTO_KEY,
    ih.INVC_NUMBER,
    TO_CHAR(ih.INVOICE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INVOICE_DATE,
    TO_CHAR(ih.POST_DATE, 'YYYY-MM-DD HH24:MI:SS') AS POST_DATE,
    ih.TOTAL_PRICE,
    ih.PAYMENT_AMT,
    (NVL(ih.TOTAL_PRICE, 0) - NVL(ih.PAYMENT_AMT, 0)) AS OPEN_BALANCE,
    ih.POST_STATUS,
    ih.POST_DESC
FROM QCTL.WO_OPERATION wo
JOIN QCTL.INVC_HEADER ih
  ON ih.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) = :wo_number
ORDER BY ih.INH_AUTO_KEY",
    ];
}

function quantumManagerWoBatchQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-batch');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-batch',
        'filename_prefix' => 'quantum_manager_wo_batch',
        'columns' => [
            'WOO_AUTO_KEY',
            'WO_NUMBER',
            'WO_STATUS',
            'COMPANY_REF_NUMBER',
            'PN',
            'DESCRIPTION',
            'MAIN_COMPONENT_PN',
            'MAIN_COMPONENT_SN',
            'SERIAL_NUMBER',
            'BILL_NAME',
            'ENTRY_DATE',
            'CLOSE_DATE',
            'QUOTE_STATUS',
            'QUOTE_STATUS_DATE',
            'EST_TOTAL_COST',
            'EI_TOTAL_COST',
            'INVOICE_COUNT',
            'INVOICE_TOTAL_PRICE',
            'FIRST_INVOICE_DATE',
            'LAST_INVOICE_DATE',
            'INVOICE_NUMBERS',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.WOO_AUTO_KEY,
    wo.SI_NUMBER AS WO_NUMBER,
    wo.WO_DISP AS WO_STATUS,
    wo.COMPANY_REF_NUMBER,
    pm.PN,
    pm.DESCRIPTION,
    mc.PART_NUMBER AS MAIN_COMPONENT_PN,
    mc.SERIAL_NUMBER AS MAIN_COMPONENT_SN,
    sn.SERIAL_NUMBER,
    wo.BILL_NAME,
    TO_CHAR(wo.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
    TO_CHAR(wo.CLOSE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS CLOSE_DATE,
    wo.QUOTE_STATUS,
    TO_CHAR(wo.QUOTE_STATUS_DATE, 'YYYY-MM-DD HH24:MI:SS') AS QUOTE_STATUS_DATE,
    (
        NVL(wo.EST_PARTS_COST, 0)
        + NVL(wo.EST_LABOR_COST, 0)
        + NVL(wo.EST_VO_COST, 0)
        + NVL(wo.EST_FO_COST, 0)
        + NVL(wo.EST_OSV_COST, 0)
    ) AS EST_TOTAL_COST,
    (
        NVL(wo.EI_TOT_PARTS_COST, 0)
        + NVL(wo.EI_TOT_LABOR_COST, 0)
        + NVL(wo.EI_TOT_VO_COST, 0)
        + NVL(wo.EI_TOT_FO_COST, 0)
        + NVL(wo.EI_TOT_OSV_COST, 0)
    ) AS EI_TOTAL_COST,
    COUNT(ih.INH_AUTO_KEY) AS INVOICE_COUNT,
    SUM(ih.TOTAL_PRICE) AS INVOICE_TOTAL_PRICE,
    TO_CHAR(MIN(ih.INVOICE_DATE), 'YYYY-MM-DD HH24:MI:SS') AS FIRST_INVOICE_DATE,
    TO_CHAR(MAX(ih.INVOICE_DATE), 'YYYY-MM-DD HH24:MI:SS') AS LAST_INVOICE_DATE,
    LISTAGG(ih.INVC_NUMBER, ', ') WITHIN GROUP (ORDER BY ih.INVC_NUMBER) AS INVOICE_NUMBERS
FROM QCTL.WO_OPERATION wo
LEFT JOIN QCTL.PARTS_MASTER pm
  ON pm.PNM_AUTO_KEY = wo.PNM_AUTO_KEY
LEFT JOIN QCTL.VIEW_SPB_WO_MAINCOMPONENT mc
  ON mc.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
LEFT JOIN QCTL.VIEW_SPS_WO_SERIAL_NUMS sn
  ON sn.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
LEFT JOIN QCTL.INVC_HEADER ih
  ON ih.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
GROUP BY
    wo.WOO_AUTO_KEY,
    wo.SI_NUMBER,
    wo.WO_DISP,
    wo.COMPANY_REF_NUMBER,
    pm.PN,
    pm.DESCRIPTION,
    mc.PART_NUMBER,
    mc.SERIAL_NUMBER,
    sn.SERIAL_NUMBER,
    wo.BILL_NAME,
    wo.ENTRY_DATE,
    wo.CLOSE_DATE,
    wo.QUOTE_STATUS,
    wo.QUOTE_STATUS_DATE,
    wo.EST_PARTS_COST,
    wo.EST_LABOR_COST,
    wo.EST_VO_COST,
    wo.EST_FO_COST,
    wo.EST_OSV_COST,
    wo.EI_TOT_PARTS_COST,
    wo.EI_TOT_LABOR_COST,
    wo.EI_TOT_VO_COST,
    wo.EI_TOT_FO_COST,
    wo.EI_TOT_OSV_COST
ORDER BY wo.SI_NUMBER",
    ];
}

function quantumManagerWoBillingQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-billing');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-billing',
        'filename_prefix' => 'quantum_manager_wo_billing',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'BGS_AUTO_KEY',
            'GROUP_NUMBER',
            'DESCRIPTION',
            'BILLING_TYPE',
            'REF',
            'FLAT_PARTS_PRICE',
            'FLAT_LABOR_PRICE',
            'FLAT_OSV_PRICE',
            'FLAT_MISC_PRICE',
            'FLAT_CONS_PRICE',
            'GROUP_TOTAL',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    wo.WOO_AUTO_KEY,
    bg.BGS_AUTO_KEY,
    bg.GROUP_NUMBER,
    bg.DESCRIPTION,
    bg.BILLING_TYPE,
    bg.REF,
    bg.FLAT_PARTS_PRICE,
    bg.FLAT_LABOR_PRICE,
    bg.FLAT_OSV_PRICE,
    bg.FLAT_MISC_PRICE,
    bg.FLAT_CONS_PRICE,
    (
        NVL(bg.FLAT_PARTS_PRICE, 0)
        + NVL(bg.FLAT_LABOR_PRICE, 0)
        + NVL(bg.FLAT_OSV_PRICE, 0)
        + NVL(bg.FLAT_MISC_PRICE, 0)
        + NVL(bg.FLAT_CONS_PRICE, 0)
    ) AS GROUP_TOTAL
FROM QCTL.WO_OPERATION wo
JOIN QCTL.BILLING_GROUPS bg
  ON bg.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER, bg.GROUP_NUMBER, bg.BGS_AUTO_KEY",
    ];
}

function quantumManagerWoCqQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-cq');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-cq',
        'filename_prefix' => 'quantum_manager_wo_cq',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'WOB_AUTO_KEY',
            'CQD_AUTO_KEY',
            'QUOTE_DATE',
            'NO_QUOTE_FLAG',
            'QTY_QUOTED',
            'LIST_PRICE',
            'UNIT_PRICE',
            'CUSTOMER_PRICE',
            'PERIOD_PRICE',
            'TAX_AMOUNT',
            'LINE_TOTAL_UNIT_PRICE',
            'LINE_TOTAL_CUSTOMER_PRICE',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    wo.WOO_AUTO_KEY,
    wb.WOB_AUTO_KEY,
    cq.CQD_AUTO_KEY,
    TO_CHAR(cq.QUOTE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS QUOTE_DATE,
    cq.NO_QUOTE_FLAG,
    cq.QTY_QUOTED,
    cq.LIST_PRICE,
    cq.UNIT_PRICE,
    cq.CUSTOMER_PRICE,
    cq.PERIOD_PRICE,
    cq.TAX_AMOUNT,
    (NVL(cq.QTY_QUOTED, 1) * NVL(cq.UNIT_PRICE, 0)) AS LINE_TOTAL_UNIT_PRICE,
    (NVL(cq.QTY_QUOTED, 1) * NVL(cq.CUSTOMER_PRICE, 0)) AS LINE_TOTAL_CUSTOMER_PRICE
FROM QCTL.WO_OPERATION wo
JOIN QCTL.WO_BOM wb
  ON wb.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
JOIN QCTL.CQ_DETAIL cq
  ON cq.WOB_AUTO_KEY = wb.WOB_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER, cq.CQD_AUTO_KEY",
    ];
}

function quantumManagerWoQuoteQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-quote');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-quote',
        'filename_prefix' => 'quantum_manager_wo_quote',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'WQH_AUTO_KEY',
            'WQH_NUMBER',
            'WQD_AUTO_KEY',
            'ITEM_TYPE',
            'QUOTE_DESCRIPTION',
            'QUOTE_VERSION',
            'QUOTE_SEQUENCE',
            'QUOTE_FORMAT',
            'QUOTE_ENTRY_DATE',
            'SENT_DATE',
            'APPROVED_DATE',
            'DETAIL_APPROVAL_DATE',
            'DENIED_DATE',
            'STATUS_CHANGE_DATE',
            'STATUS_CODE',
            'STATUS_DESCRIPTION',
            'QUOTE_BILL_NAME',
            'COMPANY_REF_NUMBER',
            'QTY',
            'UNIT_PRICE',
            'LINE_TOTAL',
            'TOTAL_PRICE',
            'TOTAL_TAX',
            'PARTS_PRICE',
            'LABOR_PRICE',
            'MISC_PRICE',
            'REPAIR_PRICE',
            'CONS_PRICE',
            'DEPOSIT_PRICE',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    wo.WOO_AUTO_KEY,
    wqh.WQH_AUTO_KEY,
    wqh.WQH_NUMBER,
    wqd.WQD_AUTO_KEY,
    wqd.ITEM_TYPE,
    wqd.DESCRIPTION AS QUOTE_DESCRIPTION,
    wqh.QUOTE_VERSION,
    wqh.QUOTE_SEQUENCE,
    wqh.QUOTE_FORMAT,
    TO_CHAR(wqh.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS QUOTE_ENTRY_DATE,
    TO_CHAR(wqh.SENT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SENT_DATE,
    TO_CHAR(wqh.APPROVED_DATE, 'YYYY-MM-DD HH24:MI:SS') AS APPROVED_DATE,
    TO_CHAR(wqd.APPROVAL_DATE, 'YYYY-MM-DD HH24:MI:SS') AS DETAIL_APPROVAL_DATE,
    TO_CHAR(wqh.DENIED_DATE, 'YYYY-MM-DD HH24:MI:SS') AS DENIED_DATE,
    TO_CHAR(wqh.STATUS_CHANGE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS STATUS_CHANGE_DATE,
    wqs.STATUS_CODE,
    wqs.DESCRIPTION AS STATUS_DESCRIPTION,
    wqh.BILL_NAME AS QUOTE_BILL_NAME,
    wqh.COMPANY_REF_NUMBER,
    wqd.QTY,
    wqd.UNIT_PRICE,
    (NVL(wqd.QTY, 1) * NVL(wqd.UNIT_PRICE, 0)) AS LINE_TOTAL,
    vwp.TOTAL_PRICE,
    vwp.TOTAL_TAX,
    vwp.PARTS_PRICE,
    vwp.LABOR_PRICE,
    vwp.MISC_PRICE,
    vwp.REPAIR_PRICE,
    vwp.CONS_PRICE,
    vwp.DEPOSIT_PRICE
FROM QCTL.WO_OPERATION wo
LEFT JOIN QCTL.WO_QUOTE_DETAIL wqd
  ON (
      wqd.WOO_REF = wo.WOO_AUTO_KEY
      OR wqd.WOK_WOO_REF = wo.WOO_AUTO_KEY
  )
LEFT JOIN QCTL.WO_QUOTE_HEADER wqh
  ON wqh.WQH_AUTO_KEY = wqd.WQH_AUTO_KEY
LEFT JOIN QCTL.WO_QUOTE_STATUS wqs
  ON wqs.WQS_AUTO_KEY = wqh.WQS_AUTO_KEY
LEFT JOIN QCTL.VIEW_WQD_PRICES vwp
  ON vwp.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER, wqh.WQH_AUTO_KEY",
    ];
}

function quantumManagerWoSprQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-spr');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-spr',
        'filename_prefix' => 'quantum_manager_wo_spr',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'EST_PARTS_COST',
            'EST_LABOR_COST',
            'EST_MISC_COST',
            'EST_OSV_COST',
            'EST_TOTAL_COST',
            'MSG_QUOTE_SENT_DATE',
            'MSG_QUOTE_APPR_DATE',
            'MSG_QUOTE_REJECT_DATE',
            'QUOTE_STATUS',
            'QUOTE_STATUS_DATE',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    spr.WOO_AUTO_KEY,
    spr.EST_PARTS_COST,
    spr.EST_LABOR_COST,
    spr.EST_MISC_COST,
    spr.EST_OSV_COST,
    spr.EST_TOTAL_COST,
    TO_CHAR(spr.MSG_QUOTE_SENT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_SENT_DATE,
    TO_CHAR(spr.MSG_QUOTE_APPR_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_APPR_DATE,
    TO_CHAR(spr.MSG_QUOTE_REJECT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_REJECT_DATE,
    spr.QUOTE_STATUS,
    TO_CHAR(spr.QUOTE_STATUS_DATE, 'YYYY-MM-DD HH24:MI:SS') AS QUOTE_STATUS_DATE
FROM QCTL.WO_OPERATION wo
JOIN QCTL.VIEW_SPR_WO_OPERATION spr
  ON spr.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER",
    ];
}

function quantumManagerWoBackchargeQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-backcharge');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-backcharge',
        'filename_prefix' => 'quantum_manager_wo_backcharge',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'EXCH_STOCK_COST',
            'WO_TOTAL_COST',
            'TOTAL_PRICE',
            'TOTAL_FOREIGN_PRICE',
        ],
        'binds' => [],
        'sql' => "
SELECT
    v.SI_NUMBER AS WO_NUMBER,
    v.WOO_AUTO_KEY,
    v.EXCH_STOCK_COST,
    v.WO_TOTAL_COST,
    v.TOTAL_PRICE,
    v.TOTAL_FOREIGN_PRICE
FROM QCTL.VIEW_WQH_SO_BACK_CHARGE v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
ORDER BY v.SI_NUMBER",
    ];
}

function quantumManagerWoSpsBillingQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-sps-billing');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-sps-billing',
        'filename_prefix' => 'quantum_manager_wo_sps_billing',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'BILL_TYPE',
            'SEQUENCE',
            'PRICE',
            'COST',
            'QTY_BILLED',
            'QTY_TOTAL',
            'TRAN_DATE',
            'TASK',
            'PN',
            'DESCRIPTION',
            'SOURCE_TABLE',
            'SOURCE_PK',
            'SERIAL_NUMBER',
            'QUOTE_ITEM',
        ],
        'binds' => [],
        'sql' => "
SELECT
    v.WO AS WO_NUMBER,
    v.WOO_AUTO_KEY,
    v.BILL_TYPE,
    v.SEQUENCE,
    v.PRICE,
    v.COST,
    v.QTY_BILLED,
    v.QTY_TOTAL,
    TO_CHAR(v.TRAN_DATE, 'YYYY-MM-DD HH24:MI:SS') AS TRAN_DATE,
    v.TASK,
    v.PN,
    v.DESCRIPTION,
    v.SOURCE_TABLE,
    v.SOURCE_PK,
    v.SERIAL_NUMBER,
    v.QUOTE_ITEM
FROM QCTL.VIEW_SPS_WOBILLING3 v
WHERE UPPER(v.WO) IN ({$woList})
ORDER BY v.WO, v.SEQUENCE, v.SOURCE_TABLE, v.SOURCE_PK",
    ];
}

function quantumManagerAmountSearchQuery(array $params): array
{
    $amount = trim((string)($params['amount'] ?? ''));
    if ($amount === '') {
        throw new RuntimeException('Missing --amount value for --mode=amount-search');
    }

    $numericAmount = (float)str_replace([',', '$', ' '], '', $amount);

    return [
        'name' => 'amount-search',
        'filename_prefix' => 'quantum_manager_amount_search',
        'columns' => [
            'SOURCE_TABLE',
            'SOURCE_COLUMN',
            'SOURCE_KEY',
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'AMOUNT_VALUE',
            'SOURCE_DATE',
            'EXTRA',
        ],
        'binds' => [
            ':amount_min' => $numericAmount - 0.01,
            ':amount_max' => $numericAmount + 0.01,
        ],
        'sql' => "
SELECT 'WO_QUOTE_DETAIL' AS SOURCE_TABLE, 'UNIT_PRICE' AS SOURCE_COLUMN, TO_CHAR(wqd.WQD_AUTO_KEY) AS SOURCE_KEY,
       wo.SI_NUMBER AS WO_NUMBER, wo.WOO_AUTO_KEY, wqd.UNIT_PRICE AS AMOUNT_VALUE,
       TO_CHAR(wqd.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SOURCE_DATE,
       wqd.DESCRIPTION AS EXTRA
FROM QCTL.WO_QUOTE_DETAIL wqd
LEFT JOIN QCTL.WO_OPERATION wo
  ON wo.WOO_AUTO_KEY IN (wqd.WOO_REF, wqd.WOK_WOO_REF)
WHERE wqd.UNIT_PRICE BETWEEN :amount_min AND :amount_max
UNION ALL
SELECT 'WO_QUOTE_DETAIL', 'BG_PRICE', TO_CHAR(wqd.WQD_AUTO_KEY),
       wo.SI_NUMBER, wo.WOO_AUTO_KEY, wqd.BG_PRICE,
       TO_CHAR(wqd.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS'),
       wqd.DESCRIPTION
FROM QCTL.WO_QUOTE_DETAIL wqd
LEFT JOIN QCTL.WO_OPERATION wo
  ON wo.WOO_AUTO_KEY IN (wqd.WOO_REF, wqd.WOK_WOO_REF)
WHERE wqd.BG_PRICE BETWEEN :amount_min AND :amount_max
UNION ALL
SELECT 'VIEW_WQD_PRICES', 'TOTAL_PRICE', NULL,
       wo.SI_NUMBER, vwp.WOO_AUTO_KEY, vwp.TOTAL_PRICE,
       NULL,
       NULL
FROM QCTL.VIEW_WQD_PRICES vwp
LEFT JOIN QCTL.WO_OPERATION wo
  ON wo.WOO_AUTO_KEY = vwp.WOO_AUTO_KEY
WHERE vwp.TOTAL_PRICE BETWEEN :amount_min AND :amount_max
UNION ALL
SELECT 'INVC_HEADER', 'TOTAL_PRICE', TO_CHAR(ih.INH_AUTO_KEY),
       wo.SI_NUMBER, ih.WOO_AUTO_KEY, ih.TOTAL_PRICE,
       TO_CHAR(ih.INVOICE_DATE, 'YYYY-MM-DD HH24:MI:SS'),
       ih.INVC_NUMBER
FROM QCTL.INVC_HEADER ih
LEFT JOIN QCTL.WO_OPERATION wo
  ON wo.WOO_AUTO_KEY = ih.WOO_AUTO_KEY
WHERE ih.TOTAL_PRICE BETWEEN :amount_min AND :amount_max
UNION ALL
SELECT 'KAC_BILLING', 'TOTAL_COST', TO_CHAR(kb.WIP_AUTO_KEY),
       kb.WO_NUM, kb.WOO_AUTO_KEY, kb.TOTAL_COST,
       TO_CHAR(kb.TRAN_DATE, 'YYYY-MM-DD HH24:MI:SS'),
       kb.TRAN_DESC
FROM QCTL.KAC_BILLING kb
WHERE kb.TOTAL_COST BETWEEN :amount_min AND :amount_max
UNION ALL
SELECT 'WO_OPERATION', 'EST_TOTAL_SUM', TO_CHAR(wo.WOO_AUTO_KEY),
       wo.SI_NUMBER, wo.WOO_AUTO_KEY,
       (
           NVL(wo.EST_PARTS_COST, 0)
           + NVL(wo.EST_LABOR_COST, 0)
           + NVL(wo.EST_VO_COST, 0)
           + NVL(wo.EST_FO_COST, 0)
           + NVL(wo.EST_OSV_COST, 0)
       ) AS AMOUNT_VALUE,
       TO_CHAR(wo.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS'),
       wo.BILL_NAME
FROM QCTL.WO_OPERATION wo
WHERE (
    NVL(wo.EST_PARTS_COST, 0)
    + NVL(wo.EST_LABOR_COST, 0)
    + NVL(wo.EST_VO_COST, 0)
    + NVL(wo.EST_FO_COST, 0)
    + NVL(wo.EST_OSV_COST, 0)
) BETWEEN :amount_min AND :amount_max",
    ];
}

function quantumManagerWoReportLinesQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-report-lines');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-report-lines',
        'filename_prefix' => 'quantum_manager_wo_report_lines',
        'columns' => [
            'SOURCE_VIEW',
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'ITEM_TYPE',
            'INVC_NUMBER',
            'BILLING_NUMBER',
            'SOURCE_DATE',
            'DESCRIPTION',
            'PN',
            'SERIAL_NUMBER',
            'QTY',
            'EST_COST',
            'QUOTED_PRICE',
            'HOME_EXTENDED_PRICE',
            'FOREIGN_EXTENDED_PRICE',
            'EXTENDED_PRICE',
            'TOTAL_REVENUE',
            'TOTAL_FOREIGN_PRICE',
            'TOTAL_COST',
        ],
        'binds' => [],
        'sql' => "
SELECT 'VIEW_WO_BILLED_PARTS' AS SOURCE_VIEW,
       v.SI_NUMBER AS WO_NUMBER,
       v.WOO_AUTO_KEY,
       v.ITEM_TYPE,
       v.INVC_NUMBER,
       v.BILLING_NUMBER,
       TO_CHAR(v.ISSUE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SOURCE_DATE,
       v.PN_DESCRIPTION AS DESCRIPTION,
       v.PN,
       v.SERIAL_NUMBER,
       v.QTY,
       v.EST_COST,
       v.QUOTED_PRICE,
       v.HOME_EXTENDED_PRICE,
       v.FOREIGN_EXTENDED_PRICE,
       NULL AS EXTENDED_PRICE,
       NULL AS TOTAL_REVENUE,
       NULL AS TOTAL_FOREIGN_PRICE,
       NULL AS TOTAL_COST
FROM QCTL.VIEW_WO_BILLED_PARTS v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
UNION ALL
SELECT 'VIEW_WO_UNBILLED_PARTS',
       v.SI_NUMBER,
       v.WOO_AUTO_KEY,
       v.ITEM_TYPE,
       NULL,
       NULL,
       TO_CHAR(v.ISSUE_DATE, 'YYYY-MM-DD HH24:MI:SS'),
       v.PN_DESCRIPTION,
       v.PN,
       v.SERIAL_NUMBER,
       v.QTY,
       v.EST_COST,
       v.QUOTED_PRICE,
       NULL,
       NULL,
       v.EXTENDED_PRICE,
       NULL,
       NULL,
       NULL
FROM QCTL.VIEW_WO_UNBILLED_PARTS v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
UNION ALL
SELECT 'VIEW_WO_BILLED_LABOR',
       v.SI_NUMBER,
       v.WOO_AUTO_KEY,
       v.ITEM_TYPE,
       NULL,
       v.BILLING_NUMBER,
       NULL,
       v.SKILL_DESCRIPTION,
       NULL,
       NULL,
       v.QTY,
       NULL,
       NULL,
       NULL,
       NULL,
       v.EXTENDED_PRICE,
       NULL,
       NULL,
       NULL
FROM QCTL.VIEW_WO_BILLED_LABOR v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
UNION ALL
SELECT 'VIEW_WO_BILLED_MISC',
       v.SI_NUMBER,
       v.WOO_AUTO_KEY,
       v.ITEM_TYPE,
       v.INVC_NUMBER,
       v.BILLING_NUMBER,
       TO_CHAR(v.POST_DATE, 'YYYY-MM-DD HH24:MI:SS'),
       v.DESCRIPTION,
       NULL,
       NULL,
       v.QTY,
       v.EST_COST,
       v.QUOTED_PRICE,
       NULL,
       NULL,
       v.EXTENDED_HOME_PRICE,
       NULL,
       NULL,
       NULL
FROM QCTL.VIEW_WO_BILLED_MISC v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
UNION ALL
SELECT 'VIEW_WO_UNBILLED_MISC',
       v.SI_NUMBER,
       v.WOO_AUTO_KEY,
       v.ITEM_TYPE,
       NULL,
       NULL,
       TO_CHAR(v.VEND_INVC_DATE, 'YYYY-MM-DD HH24:MI:SS'),
       v.DESCRIPTION,
       NULL,
       NULL,
       v.QTY,
       v.RO_EST_COST,
       v.QUOTED_PRICE,
       NULL,
       NULL,
       v.EXTENDED_PRICE,
       NULL,
       NULL,
       NULL
FROM QCTL.VIEW_WO_UNBILLED_MISC v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
UNION ALL
SELECT 'VIEW_WO_BILLING',
       v.SI_NUMBER,
       v.WOO_AUTO_KEY,
       v.WO_TYPE,
       v.INVC_NUMBER,
       v.BILLING_NUMBER,
       TO_CHAR(v.POST_DATE, 'YYYY-MM-DD HH24:MI:SS'),
       v.MODEL_DESCRIPTION,
       NULL,
       NULL,
       NULL,
       NULL,
       NULL,
       NULL,
       NULL,
       NULL,
       v.TOTAL_REVENUE,
       v.TOTAL_FOREIGN_PRICE,
       v.TOTAL_COST
FROM QCTL.VIEW_WO_BILLING v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
ORDER BY 2, 1, 7",
    ];
}

function quantumManagerWoPricesQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-prices');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-prices',
        'filename_prefix' => 'quantum_manager_wo_prices',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'TOTAL_PRICE',
            'TOTAL_FOREIGN_PRICE',
            'PARTS_PRICE',
            'LABOR_PRICE',
            'MISC_PRICE',
            'REPAIR_PRICE',
            'DEPOSIT_PRICE',
            'CONS_PRICE',
            'QUOTE_WQH',
            'QUOTE_NUMBER',
            'QUOTE_TOTAL_PRICE',
            'QUOTE_TOTAL_FOREIGN_PRICE',
            'QUOTE_PARTS_PRICE',
            'QUOTE_LABOR_PRICE',
            'QUOTE_MISC_PRICE',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    v.WOO_AUTO_KEY,
    v.TOTAL_PRICE,
    v.TOTAL_FOREIGN_PRICE,
    v.PARTS_PRICE,
    v.LABOR_PRICE,
    v.MISC_PRICE,
    v.REPAIR_PRICE,
    v.DEPOSIT_PRICE,
    v.CONS_PRICE,
    v.QUOTE_WQH,
    v.QUOTE_NUMBER,
    v.QUOTE_TOTAL_PRICE,
    v.QUOTE_TOTAL_FOREIGN_PRICE,
    v.QUOTE_PARTS_PRICE,
    v.QUOTE_LABOR_PRICE,
    v.QUOTE_MISC_PRICE
FROM QCTL.WO_OPERATION wo
JOIN QCTL.VIEW_WOO_PRICES v
  ON v.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER",
    ];
}

function quantumManagerWoTaskPricesQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-task-prices');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-task-prices',
        'filename_prefix' => 'quantum_manager_wo_task_prices',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'WOT_AUTO_KEY',
            'SEQUENCE',
            'PRINT_QUOTE_FLAG',
            'QUOTE_MISC',
            'SKILLS_EST_HOURS',
            'LABOR_HOURS',
            'FLAT_LABOR_QTY',
            'CONTRACT_LABOR_QTY',
            'PARTS_PRICE',
            'LABOR_PRICE',
            'MISC_PRICE',
            'TASK_PRICE_TOTAL',
            'SQUAWK_DESC',
            'LONG_DESCR',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    task.WOO_AUTO_KEY,
    task.WOT_AUTO_KEY,
    task.SEQUENCE,
    task.PRINT_QUOTE_FLAG,
    task.QUOTE_MISC,
    task.SKILLS_EST_HOURS,
    task.LABOR_HOURS,
    task.FLAT_LABOR_QTY,
    task.CONTRACT_LABOR_QTY,
    task.PARTS_PRICE,
    task.LABOR_PRICE,
    task.MISC_PRICE,
    (
        NVL(task.PARTS_PRICE, 0)
        + NVL(task.LABOR_PRICE, 0)
        + NVL(task.MISC_PRICE, 0)
    ) AS TASK_PRICE_TOTAL,
    task.SQUAWK_DESC,
    task.LONG_DESCR
FROM QCTL.WO_OPERATION wo
JOIN QCTL.VGQL_WO_TASK task
  ON task.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER, task.SEQUENCE, task.WOT_AUTO_KEY",
    ];
}

function quantumManagerWoTaskTotalsQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-task-totals');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-task-totals',
        'filename_prefix' => 'quantum_manager_wo_task_totals',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'WOT_AUTO_KEY',
            'TASK_TYPE',
            'TOTAL_PRICE',
            'PARTS_PRICE',
            'LABOR_PRICE',
            'MISC_PRICE',
            'REPAIR_PRICE',
            'CONS_PRICE',
            'DEPOSIT_PRICE',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    v.WOO_AUTO_KEY,
    v.WOT_AUTO_KEY,
    v.TASK_TYPE,
    v.TOTAL_PRICE,
    v.PARTS_PRICE,
    v.LABOR_PRICE,
    v.MISC_PRICE,
    v.REPAIR_PRICE,
    v.CONS_PRICE,
    v.DEPOSIT_PRICE
FROM QCTL.WO_OPERATION wo
JOIN QCTL.VIEW_WO_TASK_TOTALS v
  ON v.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER, v.WOT_AUTO_KEY",
    ];
}

function quantumManagerWoBillTaskFormatQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-bill-task-format');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-bill-task-format',
        'filename_prefix' => 'quantum_manager_wo_bill_task_format',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'WOT_AUTO_KEY',
            'WQH_AUTO_KEY',
            'ITEM_TYPE',
            'TASK_TYPE',
            'PN',
            'WORK_FLOW',
            'QTY',
            'QTY_BOOKED',
            'QTY_BILLED',
            'UNIT_PRICE',
            'LINE_TOTAL',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    v.WOO_AUTO_KEY,
    v.WOT_AUTO_KEY,
    v.WQH_AUTO_KEY,
    v.ITEM_TYPE,
    v.TASK_TYPE,
    v.PN,
    v.WORK_FLOW,
    v.QTY,
    v.QTY_BOOKED,
    v.QTY_BILLED,
    v.UNIT_PRICE,
    NVL(v.QTY, 1) * NVL(v.UNIT_PRICE, 0) AS LINE_TOTAL
FROM QCTL.VIEW_WO_BILL_TASK_FORMAT v
JOIN QCTL.WO_OPERATION wo
  ON wo.WOO_AUTO_KEY = v.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER, v.WOT_AUTO_KEY, v.ITEM_TYPE",
    ];
}

function quantumManagerWoSkillEstimatesQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-skill-estimates');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-skill-estimates',
        'filename_prefix' => 'quantum_manager_wo_skill_estimates',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'WOT_AUTO_KEY',
            'WTS_AUTO_KEY',
            'SEQUENCE',
            'TASK_DESC',
            'QUOTE_ITEM',
            'EST_HOURS',
            'EST_BILLING_RATE',
            'EST_BURDEN_RATE',
            'EST_BILLING_TOTAL',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    task.WOO_AUTO_KEY,
    task.WOT_AUTO_KEY,
    skills.WTS_AUTO_KEY,
    task.SEQUENCE,
    task.SQUAWK_DESC AS TASK_DESC,
    skills.QUOTE_ITEM,
    skills.EST_HOURS,
    skills.EST_BILLING_RATE,
    skills.EST_BURDEN_RATE,
    (NVL(skills.EST_HOURS, 0) * NVL(skills.EST_BILLING_RATE, 0)) AS EST_BILLING_TOTAL
FROM QCTL.WO_OPERATION wo
JOIN QCTL.WO_TASK task
  ON task.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
JOIN QCTL.WO_TASK_SKILLS skills
  ON skills.WOT_AUTO_KEY = task.WOT_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER, task.SEQUENCE, skills.WTS_AUTO_KEY",
    ];
}

function quantumManagerWoOpTemplatePricesQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-op-template-prices');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-op-template-prices',
        'filename_prefix' => 'quantum_manager_wo_op_template_prices',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'OPM_AUTO_KEY',
            'OPT_AUTO_KEY',
            'SEQUENCE',
            'STD_HOURS',
            'PARTS_PRICE',
            'LABOR_PRICE',
            'MISC_PRICE',
            'EST_MISC_PRICE',
            'LABOR_TIME_FIXED_COST',
            'FLAT_LABOR_QTY',
            'CONTRACT_LABOR_QTY',
            'TEMPLATE_TOTAL',
            'SQUAWK_DESC',
            'LONG_DESCR',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    wo.WOO_AUTO_KEY,
    wo.OPM_AUTO_KEY,
    opt.OPT_AUTO_KEY,
    opt.SEQUENCE,
    opt.STD_HOURS,
    opt.PARTS_PRICE,
    opt.LABOR_PRICE,
    opt.MISC_PRICE,
    opt.EST_MISC_PRICE,
    opt.LABOR_TIME_FIXED_COST,
    opt.FLAT_LABOR_QTY,
    opt.CONTRACT_LABOR_QTY,
    (
        NVL(opt.PARTS_PRICE, 0)
        + NVL(opt.LABOR_PRICE, 0)
        + NVL(opt.MISC_PRICE, 0)
        + NVL(opt.EST_MISC_PRICE, 0)
        + NVL(opt.LABOR_TIME_FIXED_COST, 0)
    ) AS TEMPLATE_TOTAL,
    opt.SQUAWK_DESC,
    opt.LONG_DESCR
FROM QCTL.WO_OPERATION wo
JOIN QCTL.OPERATION_TASKS opt
  ON opt.OPM_AUTO_KEY = wo.OPM_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER, opt.SEQUENCE, opt.OPT_AUTO_KEY",
    ];
}

function quantumManagerWoWqdPosQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-wqd-pos');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-wqd-pos',
        'filename_prefix' => 'quantum_manager_wo_wqd_pos',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'WQD_AUTO_KEY',
            'WQH_AUTO_KEY',
            'BILLING_GROUP',
            'PN',
            'QTY',
            'QTY_QUOTED',
            'QTY_BILLED',
            'UNIT_PRICE',
            'PRICE_BILLED',
            'RO_EST_PRICE',
            'PO_EST_PRICE',
            'LINE_UNIT_TOTAL',
            'LINE_QUOTED_TOTAL',
            'ENTRY_DATE',
        ],
        'binds' => [],
        'sql' => "
SELECT
    v.SI_NUMBER AS WO_NUMBER,
    v.WOO_AUTO_KEY,
    v.WQD_AUTO_KEY,
    v.WQH_AUTO_KEY,
    v.BILLING_GROUP,
    v.PN,
    v.QTY,
    v.QTY_QUOTED,
    v.QTY_BILLED,
    v.UNIT_PRICE,
    v.PRICE_BILLED,
    v.RO_EST_PRICE,
    v.PO_EST_PRICE,
    (NVL(v.QTY, 1) * NVL(v.UNIT_PRICE, 0)) AS LINE_UNIT_TOTAL,
    (NVL(v.QTY_QUOTED, 1) * NVL(v.UNIT_PRICE, 0)) AS LINE_QUOTED_TOTAL,
    TO_CHAR(v.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE
FROM QCTL.VIEW_WQD_POS_INFO v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
ORDER BY v.SI_NUMBER, v.WQD_AUTO_KEY",
    ];
}

function quantumManagerWoKacQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-kac');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-kac',
        'filename_prefix' => 'quantum_manager_wo_kac',
        'columns' => [
            'SOURCE_TABLE',
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'TRAN_DATE',
            'COST_CAT',
            'QTY',
            'PN',
            'PART_DESC',
            'RO_NUMBER',
            'VENDOR_INVOICE',
            'TRAN_DESC',
            'EXT_COST',
            'FRG_BILLING_RATE',
            'OVHD_BILLING_RATE',
            'MAT_BILLING_RATE',
            'GNA_BILLING_RATE',
            'TOTAL_COST',
        ],
        'binds' => [],
        'sql' => "
SELECT 'KAC_BILLING' AS SOURCE_TABLE,
       kb.WO_NUM AS WO_NUMBER,
       kb.WOO_AUTO_KEY,
       TO_CHAR(kb.TRAN_DATE, 'YYYY-MM-DD HH24:MI:SS') AS TRAN_DATE,
       kb.COST_CAT,
       kb.QTY,
       kb.PN,
       kb.PART_DESC,
       kb.RO_NUMBER,
       kb.VENDOR_INVOICE,
       kb.TRAN_DESC,
       kb.EXT_COST,
       kb.FRG_BILLING_RATE,
       kb.OVHD_BILLING_RATE,
       kb.MAT_BILLING_RATE,
       kb.GNA_BILLING_RATE,
       kb.TOTAL_COST
FROM QCTL.KAC_BILLING kb
WHERE UPPER(kb.WO_NUM) IN ({$woList})
   OR kb.WOO_AUTO_KEY IN (
       SELECT wo.WOO_AUTO_KEY
       FROM QCTL.WO_OPERATION wo
       WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
   )
UNION ALL
SELECT 'KAC_BASE_LABOR',
       kb.WO_NUM,
       kb.WOO_AUTO_KEY,
       TO_CHAR(kb.TRAN_DATE, 'YYYY-MM-DD HH24:MI:SS'),
       kb.COST_CAT,
       kb.QTY,
       kb.PN,
       kb.PART_DESC,
       kb.RO_NUMBER,
       kb.VENDOR_INVOICE,
       kb.TRAN_DESC,
       kb.EXT_COST,
       kb.FRG_BILLING_RATE,
       kb.OVHD_BILLING_RATE,
       kb.MAT_BILLING_RATE,
       kb.GNA_BILLING_RATE,
       kb.TOTAL_COST
FROM QCTL.KAC_BASE_LABOR kb
WHERE UPPER(kb.WO_NUM) IN ({$woList})
   OR kb.WOO_AUTO_KEY IN (
       SELECT wo.WOO_AUTO_KEY
       FROM QCTL.WO_OPERATION wo
       WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
   )
ORDER BY 2, 1, 3",
    ];
}

function quantumManagerWoPviewWqdQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-pview-wqd');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-pview-wqd',
        'filename_prefix' => 'quantum_manager_wo_pview_wqd',
        'columns' => [
            'SOURCE_VIEW',
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'ROW_COUNT',
            'QUOTE_NUMBERS',
            'APPROVAL_DATE_MIN',
            'APPROVAL_DATE_MAX',
            'APPR_STATES',
            'TOTAL_PRICE',
            'EXT_PRICE',
            'UNIT_EXT_PRICE',
            'PARTS_PRICE',
            'LABOR_PRICE',
            'MISC_PRICE',
            'REPAIR_PRICE',
            'CONS_PRICE',
            'PREPARSE_TOTAL_PRICE',
        ],
        'binds' => [],
        'sql' => "
SELECT
    'PVIEW_WQD_MAIN' AS SOURCE_VIEW,
    v.SI_NUMBER AS WO_NUMBER,
    v.WOO_AUTO_KEY,
    COUNT(*) AS ROW_COUNT,
    LISTAGG(DISTINCT v.WQH_NUMBER_QUOTE, ', ') WITHIN GROUP (ORDER BY v.WQH_NUMBER_QUOTE) AS QUOTE_NUMBERS,
    TO_CHAR(MIN(v.APPROVAL_DATE), 'YYYY-MM-DD HH24:MI:SS') AS APPROVAL_DATE_MIN,
    TO_CHAR(MAX(v.APPROVAL_DATE), 'YYYY-MM-DD HH24:MI:SS') AS APPROVAL_DATE_MAX,
    LISTAGG(DISTINCT v.APPR_STATE, ', ') WITHIN GROUP (ORDER BY v.APPR_STATE) AS APPR_STATES,
    SUM(NVL(v.TOTAL_PRICE, 0)) AS TOTAL_PRICE,
    SUM(NVL(v.EXT_PRICE, 0)) AS EXT_PRICE,
    SUM(NVL(v.UNIT_PRICE, 0) * NVL(v.QTY, 1)) AS UNIT_EXT_PRICE,
    NULL AS PARTS_PRICE,
    NULL AS LABOR_PRICE,
    NULL AS MISC_PRICE,
    NULL AS REPAIR_PRICE,
    NULL AS CONS_PRICE,
    NULL AS PREPARSE_TOTAL_PRICE
FROM QCTL.PVIEW_WQD_MAIN v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
GROUP BY v.SI_NUMBER, v.WOO_AUTO_KEY
UNION ALL
SELECT
    'PVIEW_WQD_PRICES',
    wo.SI_NUMBER,
    v.WOO_AUTO_KEY,
    COUNT(*),
    NULL,
    NULL,
    NULL,
    NULL,
    SUM(NVL(v.TOTAL_PRICE, 0)),
    NULL,
    NULL,
    SUM(NVL(v.PARTS_PRICE, 0)),
    SUM(NVL(v.LABOR_PRICE, 0)),
    SUM(NVL(v.MISC_PRICE, 0)),
    SUM(NVL(v.REPAIR_PRICE, 0)),
    SUM(NVL(v.CONS_PRICE, 0)),
    NULL
FROM QCTL.PVIEW_WQD_PRICES v
JOIN QCTL.WO_OPERATION wo
  ON wo.WOO_AUTO_KEY = v.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
GROUP BY wo.SI_NUMBER, v.WOO_AUTO_KEY
UNION ALL
SELECT
    'PVIEW_WQD_TREEPARSE_VALUES',
    wo.SI_NUMBER,
    v.WOO_AUTO_KEY,
    COUNT(*),
    NULL,
    NULL,
    NULL,
    LISTAGG(DISTINCT v.ACTIVE_PRICE_FLAG, ', ') WITHIN GROUP (ORDER BY v.ACTIVE_PRICE_FLAG),
    SUM(NVL(v.TOTAL_PRICE, 0)),
    NULL,
    SUM(NVL(v.UNIT_PRICE, 0) * NVL(v.QTY, 1)),
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    SUM(NVL(v.PREPARSE_TOTAL_PRICE, 0))
FROM QCTL.PVIEW_WQD_TREEPARSE_VALUES v
JOIN QCTL.WO_OPERATION wo
  ON wo.WOO_AUTO_KEY = v.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
GROUP BY wo.SI_NUMBER, v.WOO_AUTO_KEY
ORDER BY 2, 1",
    ];
}

function quantumManagerWoQuoteDeepQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-quote-deep');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-quote-deep',
        'filename_prefix' => 'quantum_manager_wo_quote_deep',
        'columns' => [
            'MATCH_PATH',
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'WQH_AUTO_KEY',
            'WQH_NUMBER',
            'QUOTE_VERSION',
            'WQH_VERSION',
            'HEADER_ENTRY_DATE',
            'SENT_DATE',
            'APPROVED_DATE',
            'STATUS_CHANGE_DATE',
            'SHIP_DATE',
            'WQS_AUTO_KEY',
            'POST_STATUS',
            'WQD_AUTO_KEY',
            'ITEM_TYPE',
            'DESCRIPTION',
            'QTY',
            'UNIT_PRICE',
            'BG_PRICE',
            'FOREIGN_PRICE',
            'BASE_COST',
            'DYN_BASE_PRICE',
            'FREIGHT',
            'DISCOUNT',
            'MARKUP',
            'WQD_ENTRY_DATE',
            'APPROVAL_DATE',
            'APPROVAL_TRAN',
            'APPR_STATE',
            'MOD_STATE',
            'VERSION_NUMBER',
            'VERSION_DATE',
            'APPROVAL_VERSION_LAST',
            'LINE_EXT_UNIT_PRICE',
            'LINE_BG_PRICE',
        ],
        'binds' => [],
        'sql' => "
WITH target_wo AS (
    SELECT wo.WOO_AUTO_KEY, wo.SI_NUMBER
    FROM QCTL.WO_OPERATION wo
    WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
),
quote_lines AS (
    SELECT 'HEADER_WOO_MASTER' AS MATCH_PATH, t.SI_NUMBER AS WO_NUMBER, t.WOO_AUTO_KEY, wqh.WQH_AUTO_KEY, wqd.WQD_AUTO_KEY
    FROM target_wo t
    JOIN QCTL.WO_QUOTE_HEADER wqh ON wqh.WOO_MASTER = t.WOO_AUTO_KEY
    LEFT JOIN QCTL.WO_QUOTE_DETAIL wqd ON wqd.WQH_AUTO_KEY = wqh.WQH_AUTO_KEY
    UNION
    SELECT 'DETAIL_WOO_REF', t.SI_NUMBER, t.WOO_AUTO_KEY, wqd.WQH_AUTO_KEY, wqd.WQD_AUTO_KEY
    FROM target_wo t
    JOIN QCTL.WO_QUOTE_DETAIL wqd ON wqd.WOO_REF = t.WOO_AUTO_KEY
    UNION
    SELECT 'DETAIL_WOK_WOO_REF', t.SI_NUMBER, t.WOO_AUTO_KEY, wqd.WQH_AUTO_KEY, wqd.WQD_AUTO_KEY
    FROM target_wo t
    JOIN QCTL.WO_QUOTE_DETAIL wqd ON wqd.WOK_WOO_REF = t.WOO_AUTO_KEY
    UNION
    SELECT 'DETAIL_WOB_REF', t.SI_NUMBER, t.WOO_AUTO_KEY, wqd.WQH_AUTO_KEY, wqd.WQD_AUTO_KEY
    FROM target_wo t
    JOIN QCTL.WO_BOM wb ON wb.WOO_AUTO_KEY = t.WOO_AUTO_KEY
    JOIN QCTL.WO_QUOTE_DETAIL wqd ON wqd.WOB_REF = wb.WOB_AUTO_KEY
    UNION
    SELECT 'DETAIL_WOT_REF', t.SI_NUMBER, t.WOO_AUTO_KEY, wqd.WQH_AUTO_KEY, wqd.WQD_AUTO_KEY
    FROM target_wo t
    JOIN QCTL.WO_TASK wt ON wt.WOO_AUTO_KEY = t.WOO_AUTO_KEY
    JOIN QCTL.WO_QUOTE_DETAIL wqd ON wqd.WOT_REF = wt.WOT_AUTO_KEY OR wqd.WOK_WOT_REF = wt.WOT_AUTO_KEY
    UNION
    SELECT 'DETAIL_WTS_REF', t.SI_NUMBER, t.WOO_AUTO_KEY, wqd.WQH_AUTO_KEY, wqd.WQD_AUTO_KEY
    FROM target_wo t
    JOIN QCTL.WO_TASK wt ON wt.WOO_AUTO_KEY = t.WOO_AUTO_KEY
    JOIN QCTL.WO_TASK_SKILLS wts ON wts.WOT_AUTO_KEY = wt.WOT_AUTO_KEY
    JOIN QCTL.WO_QUOTE_DETAIL wqd ON wqd.WTS_REF = wts.WTS_AUTO_KEY
)
SELECT
    ql.MATCH_PATH,
    ql.WO_NUMBER,
    ql.WOO_AUTO_KEY,
    wqh.WQH_AUTO_KEY,
    wqh.WQH_NUMBER,
    wqh.QUOTE_VERSION,
    wqh.WQH_VERSION,
    TO_CHAR(wqh.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS HEADER_ENTRY_DATE,
    TO_CHAR(wqh.SENT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SENT_DATE,
    TO_CHAR(wqh.APPROVED_DATE, 'YYYY-MM-DD HH24:MI:SS') AS APPROVED_DATE,
    TO_CHAR(wqh.STATUS_CHANGE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS STATUS_CHANGE_DATE,
    TO_CHAR(wqh.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SHIP_DATE,
    wqh.WQS_AUTO_KEY,
    wqh.POST_STATUS,
    wqd.WQD_AUTO_KEY,
    wqd.ITEM_TYPE,
    wqd.DESCRIPTION,
    wqd.QTY,
    wqd.UNIT_PRICE,
    wqd.BG_PRICE,
    wqd.FOREIGN_PRICE,
    wqd.BASE_COST,
    wqd.DYN_BASE_PRICE,
    wqd.FREIGHT,
    wqd.DISCOUNT,
    wqd.MARKUP,
    TO_CHAR(wqd.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS WQD_ENTRY_DATE,
    TO_CHAR(wqd.APPROVAL_DATE, 'YYYY-MM-DD HH24:MI:SS') AS APPROVAL_DATE,
    TO_CHAR(wqd.APPROVAL_TRAN, 'YYYY-MM-DD HH24:MI:SS') AS APPROVAL_TRAN,
    wqd.APPR_STATE,
    wqd.MOD_STATE,
    wqd.VERSION_NUMBER,
    TO_CHAR(wqd.VERSION_DATE, 'YYYY-MM-DD HH24:MI:SS') AS VERSION_DATE,
    wqd.APPROVAL_VERSION_LAST,
    NVL(wqd.QTY, 1) * NVL(wqd.UNIT_PRICE, 0) AS LINE_EXT_UNIT_PRICE,
    NVL(wqd.BG_PRICE, 0) AS LINE_BG_PRICE
FROM quote_lines ql
JOIN QCTL.WO_QUOTE_HEADER wqh ON wqh.WQH_AUTO_KEY = ql.WQH_AUTO_KEY
LEFT JOIN QCTL.WO_QUOTE_DETAIL wqd ON wqd.WQD_AUTO_KEY = ql.WQD_AUTO_KEY
ORDER BY ql.WO_NUMBER, wqh.WQH_AUTO_KEY, wqd.SEQUENCE, wqd.WQD_AUTO_KEY",
    ];
}

function quantumManagerWoBillingSummaryQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-billing-summary');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-billing-summary',
        'filename_prefix' => 'quantum_manager_wo_billing_summary',
        'columns' => [
            'SOURCE_VIEW',
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'ROW_COUNT',
            'SOURCE_DATE_MIN',
            'SOURCE_DATE_MAX',
            'INVOICE_FLAG',
            'INVOICE_NUMBERS',
            'PARTS_AMOUNT',
            'LABOR_AMOUNT',
            'MISC_AMOUNT',
            'OSV_AMOUNT',
            'TOTAL_AMOUNT',
            'TOTAL_COST',
            'TOTAL_REVENUE',
            'TOTAL_FOREIGN_PRICE',
        ],
        'binds' => [],
        'sql' => "
SELECT 'VIEW_WO_BILLING' AS SOURCE_VIEW,
       v.SI_NUMBER AS WO_NUMBER,
       v.WOO_AUTO_KEY,
       COUNT(*) AS ROW_COUNT,
       TO_CHAR(MIN(v.POST_DATE), 'YYYY-MM-DD HH24:MI:SS') AS SOURCE_DATE_MIN,
       TO_CHAR(MAX(v.POST_DATE), 'YYYY-MM-DD HH24:MI:SS') AS SOURCE_DATE_MAX,
       NULL AS INVOICE_FLAG,
       LISTAGG(DISTINCT v.INVC_NUMBER, ', ') WITHIN GROUP (ORDER BY v.INVC_NUMBER) AS INVOICE_NUMBERS,
       SUM(NVL(v.PARTS_REVENUE, 0)) AS PARTS_AMOUNT,
       SUM(NVL(v.LABOR_REVENUE, 0)) AS LABOR_AMOUNT,
       SUM(NVL(v.MISC_REVENUE, 0)) AS MISC_AMOUNT,
       SUM(NVL(v.OSV_REVENUE, 0)) AS OSV_AMOUNT,
       SUM(NVL(v.TOTAL_REVENUE, 0)) AS TOTAL_AMOUNT,
       SUM(NVL(v.TOTAL_COST, 0)) AS TOTAL_COST,
       SUM(NVL(v.TOTAL_REVENUE, 0)) AS TOTAL_REVENUE,
       SUM(NVL(v.TOTAL_FOREIGN_PRICE, 0)) AS TOTAL_FOREIGN_PRICE
FROM QCTL.VIEW_WO_BILLING v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
GROUP BY v.SI_NUMBER, v.WOO_AUTO_KEY
UNION ALL
SELECT 'VIEW_WIP_TRANS',
       v.SI_NUMBER,
       v.WOO_AUTO_KEY,
       COUNT(*),
       NULL,
       NULL,
       NULL,
       NULL,
       SUM(NVL(v.PARTS_COST, 0)),
       SUM(NVL(v.LABOR_COST, 0)),
       SUM(NVL(v.MISC_COST, 0)),
       SUM(NVL(v.OSV_COST, 0)),
       SUM(NVL(v.PARTS_COST, 0) + NVL(v.LABOR_COST, 0) + NVL(v.MISC_COST, 0) + NVL(v.OSV_COST, 0) + NVL(v.FO_COST, 0) + NVL(v.VO_COST, 0)),
       SUM(NVL(v.TOTAL_BILLED_COST, 0)),
       NULL,
       NULL
FROM QCTL.VIEW_WIP_TRANS v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
GROUP BY v.SI_NUMBER, v.WOO_AUTO_KEY
UNION ALL
SELECT 'VIEW_WIP_VALUATION',
       v.SI_NUMBER,
       v.WOO_AUTO_KEY,
       COUNT(*),
       NULL,
       NULL,
       NULL,
       NULL,
       SUM(NVL(v.PARTS_AMOUNT, 0)),
       SUM(NVL(v.LABOR_AMOUNT, 0)),
       SUM(NVL(v.MISC_AMOUNT, 0)),
       NULL,
       SUM(NVL(v.PARTS_AMOUNT, 0) + NVL(v.LABOR_AMOUNT, 0) + NVL(v.MISC_AMOUNT, 0)),
       SUM(NVL(v.HEADER_TOTAL_COST, 0)),
       NULL,
       NULL
FROM QCTL.VIEW_WIP_VALUATION v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
GROUP BY v.SI_NUMBER, v.WOO_AUTO_KEY
UNION ALL
SELECT 'VIEW_WIP_DETAIL_LABOR',
       v.SI_NUMBER,
       NULL,
       COUNT(*),
       TO_CHAR(MIN(v.EST_COMPL_DATE), 'YYYY-MM-DD HH24:MI:SS'),
       TO_CHAR(MAX(v.EST_COMPL_DATE), 'YYYY-MM-DD HH24:MI:SS'),
       LISTAGG(DISTINCT v.INVOICE, ', ') WITHIN GROUP (ORDER BY v.INVOICE),
       NULL,
       NULL,
       SUM(NVL(v.WO_COST, 0)),
       NULL,
       NULL,
       SUM(NVL(v.WO_COST, 0)),
       NULL,
       NULL,
       NULL
FROM QCTL.VIEW_WIP_DETAIL_LABOR v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
GROUP BY v.SI_NUMBER
UNION ALL
SELECT 'VIEW_WIP_DETAIL_PART',
       v.SI_NUMBER,
       NULL,
       COUNT(*),
       TO_CHAR(MIN(v.EST_COMPL_DATE), 'YYYY-MM-DD HH24:MI:SS'),
       TO_CHAR(MAX(v.EST_COMPL_DATE), 'YYYY-MM-DD HH24:MI:SS'),
       LISTAGG(DISTINCT v.INVOICE, ', ') WITHIN GROUP (ORDER BY v.INVOICE),
       NULL,
       SUM(NVL(v.WO_COST, 0)),
       NULL,
       NULL,
       NULL,
       SUM(NVL(v.WO_COST, 0)),
       NULL,
       NULL,
       NULL
FROM QCTL.VIEW_WIP_DETAIL_PART v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
GROUP BY v.SI_NUMBER
UNION ALL
SELECT 'VIEW_WIP_DETAIL_CHARGE',
       v.SI_NUMBER,
       NULL,
       COUNT(*),
       TO_CHAR(MIN(v.EST_COMPL_DATE), 'YYYY-MM-DD HH24:MI:SS'),
       TO_CHAR(MAX(v.EST_COMPL_DATE), 'YYYY-MM-DD HH24:MI:SS'),
       LISTAGG(DISTINCT v.INVOICE, ', ') WITHIN GROUP (ORDER BY v.INVOICE),
       NULL,
       NULL,
       NULL,
       SUM(NVL(v.WO_COST, 0)),
       NULL,
       SUM(NVL(v.WO_COST, 0)),
       NULL,
       NULL,
       NULL
FROM QCTL.VIEW_WIP_DETAIL_CHARGE v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
GROUP BY v.SI_NUMBER
ORDER BY 2, 1",
    ];
}

function quantumManagerWoEstimateLinesQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-estimate-lines');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-estimate-lines',
        'filename_prefix' => 'quantum_manager_wo_estimate_lines',
        'columns' => [
            'SOURCE_TABLE',
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'SOURCE_KEY',
            'SEQUENCE',
            'SOURCE_DATE',
            'DESCRIPTION',
            'QUOTE_PARTS',
            'QUOTE_LABOR',
            'QUOTE_MISC',
            'PARTS_EST_PRICE',
            'LABOR_EST_PRICE',
            'MISC_EST_PRICE',
            'SKILLS_EST_HOURS',
            'LABOR_HOURS',
            'LABOR_PRICE',
            'EST_HOURS',
            'EST_BILLING_RATE',
            'QUOTE_ITEM',
            'LINE_TOTAL',
        ],
        'binds' => [],
        'sql' => "
SELECT 'WO_REPAIRS' AS SOURCE_TABLE,
       wo.SI_NUMBER AS WO_NUMBER,
       wrp.WOO_AUTO_KEY,
       TO_CHAR(wrp.WRP_AUTO_KEY) AS SOURCE_KEY,
       NULL AS SEQUENCE,
       TO_CHAR(wrp.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SOURCE_DATE,
       NULL AS DESCRIPTION,
       wrp.QUOTE_PARTS,
       wrp.QUOTE_LABOR,
       wrp.QUOTE_MISC,
       wrp.PARTS_EST_PRICE,
       wrp.LABOR_EST_PRICE,
       wrp.MISC_EST_PRICE,
       NULL AS SKILLS_EST_HOURS,
       NULL AS LABOR_HOURS,
       NULL AS LABOR_PRICE,
       NULL AS EST_HOURS,
       NULL AS EST_BILLING_RATE,
       NULL AS QUOTE_ITEM,
       NVL(wrp.PARTS_EST_PRICE, 0) + NVL(wrp.LABOR_EST_PRICE, 0) + NVL(wrp.MISC_EST_PRICE, 0) AS LINE_TOTAL
FROM QCTL.WO_REPAIRS wrp
JOIN QCTL.WO_OPERATION wo ON wo.WOO_AUTO_KEY = wrp.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
UNION ALL
SELECT 'WO_TASK',
       wo.SI_NUMBER,
       wt.WOO_AUTO_KEY,
       TO_CHAR(wt.WOT_AUTO_KEY),
       wt.SEQUENCE,
       TO_CHAR(wt.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS'),
       COALESCE(wt.SQUAWK_DESC, wt.LONG_DESCR),
       NULL,
       NULL,
       wt.QUOTE_MISC,
       NULL,
       NULL,
       wt.MISC_EST_PRICE,
       wt.SKILLS_EST_HOURS,
       wt.LABOR_HOURS,
       wt.LABOR_PRICE,
       NULL,
       NULL,
       wt.PRINT_QUOTE_FLAG,
       NVL(wt.PARTS_PRICE, 0) + NVL(wt.LABOR_PRICE, 0) + NVL(wt.MISC_PRICE, 0) + NVL(wt.MISC_EST_PRICE, 0)
FROM QCTL.WO_TASK wt
JOIN QCTL.WO_OPERATION wo ON wo.WOO_AUTO_KEY = wt.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
UNION ALL
SELECT 'WO_TASK_SKILLS',
       wo.SI_NUMBER,
       wt.WOO_AUTO_KEY,
       TO_CHAR(wts.WTS_AUTO_KEY),
       wt.SEQUENCE,
       NULL,
       COALESCE(wt.SQUAWK_DESC, wt.LONG_DESCR),
       NULL,
       NULL,
       NULL,
       NULL,
       NULL,
       NULL,
       NULL,
       NULL,
       NULL,
       wts.EST_HOURS,
       wts.EST_BILLING_RATE,
       wts.QUOTE_ITEM,
       NVL(wts.EST_HOURS, 0) * NVL(wts.EST_BILLING_RATE, 0)
FROM QCTL.WO_TASK_SKILLS wts
JOIN QCTL.WO_TASK wt ON wt.WOT_AUTO_KEY = wts.WOT_AUTO_KEY
JOIN QCTL.WO_OPERATION wo ON wo.WOO_AUTO_KEY = wt.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY 2, 1, 5, 4",
    ];
}

function quantumManagerWoSummaryTatQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-summary-tat');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-summary-tat',
        'filename_prefix' => 'quantum_manager_wo_summary_tat',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'STATUS',
            'WO_DISP',
            'COMPANY_NAME',
            'COMPANY_CODE',
            'PN',
            'DESCRIPTION',
            'SERIAL_NUMBER',
            'SCOPE',
            'TOTAL_COST',
            'TOTAL_QUOTED_PRICE',
            'ENTRY_DATE',
            'ARRIVAL_DATE',
            'SHIP_DATE',
            'INVC_POST_DATE',
            'TARGET_TAT_DAYS',
            'CUSTOMER_APPROVAL_TIME',
            'GROSS_TAT',
            'WARRANTY_CODE',
            'WARRANTEE_FLAG',
            'WO_UDF_007',
            'WO_UDF_008',
            'WO_UDF_010',
            'WO_UDF_016',
            'HAS_SHORTAGE',
        ],
        'binds' => [],
        'sql' => "
SELECT
    v.SI_NUMBER AS WO_NUMBER,
    v.WOO_AUTO_KEY,
    v.STATUS,
    v.WO_DISP,
    v.COMPANY_NAME,
    v.COMPANY_CODE,
    v.PN,
    v.DESCRIPTION,
    v.SERIAL_NUMBER,
    v.SCOPE,
    v.TOTAL_COST,
    v.TOTAL_QUOTED_PRICE,
    TO_CHAR(v.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
    TO_CHAR(v.ARRIVAL_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ARRIVAL_DATE,
    TO_CHAR(v.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SHIP_DATE,
    TO_CHAR(v.INVC_POST_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INVC_POST_DATE,
    v.TARGET_TAT_DAYS,
    v.CUSTOMER_APPROVAL_TIME,
    v.GROSS_TAT,
    v.WARRANTY_CODE,
    v.WARRANTEE_FLAG,
    v.WO_UDF_007,
    v.WO_UDF_008,
    v.WO_UDF_010,
    v.WO_UDF_016,
    v.HAS_SHORTAGE
FROM QCTL.VIEW_WO_SUMMARY_WITH_TAT v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
ORDER BY v.SI_NUMBER",
    ];
}

function quantumManagerWoRoDetailQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-ro-detail');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-ro-detail',
        'filename_prefix' => 'quantum_manager_wo_ro_detail',
        'columns' => [
            'MATCH_PATH',
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'RO_NUMBER',
            'ROD_AUTO_KEY',
            'WOB_AUTO_KEY',
            'ITEM_NUMBER',
            'RO_ENTRY_DATE',
            'RO_HEADER_ENTRY_DATE',
            'RO_APPROVED_DATE',
            'RO_QUOTE_DATE',
            'RO_OUT_DATE',
            'RO_NEXT_ACT_DATE',
            'RO_LAST_MODIFIED',
            'RO_COMPANY_REF_NUMBER',
            'RO_VENDOR_NAME',
            'SERIAL_NUMBER',
            'PN',
            'DESCRIPTION',
            'QTY_REPAIR',
            'WO_PRICE',
            'EST_PRICE',
            'LABOR_COST',
            'PARTS_COST',
            'MISC_COST',
            'QUOTED_PARTS_HOME',
            'QUOTED_LABOR_HOME',
            'QUOTED_MISC_HOME',
            'QUOTED_HOME_TOTAL',
            'MSG_QUOTE_REC_DATE',
            'MSG_QUOTE_APPR_DATE',
            'MSG_QUOTE_AUTO_APPR_DATE',
            'MSG_QUOTE_REJECT_DATE',
            'MSG_EST_DELIV_DATE',
            'MSG_SHIP_NOTICE_DATE',
            'SHIP_DATE',
            'LAST_DELIVERY_DATE',
            'NEXT_DELIVERY_DATE',
            'MSG_QUOTE_REC_CNT',
            'MSG_QUOTE_APPR_CNT',
            'MSG_QUOTE_REJECT_CNT',
            'MSG_EST_DELIV_CNT',
            'MSG_SHIP_NOTICE_CNT',
            'RO_TYPE',
            'ECC',
        ],
        'binds' => [],
        'sql' => "
WITH target_wo AS (
    SELECT wo.WOO_AUTO_KEY, wo.SI_NUMBER
    FROM QCTL.WO_OPERATION wo
    WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
),
rod_matches AS (
    SELECT 'ROD_WOO_AUTO_KEY' AS MATCH_PATH, t.SI_NUMBER AS WO_NUMBER, t.WOO_AUTO_KEY, rod.ROD_AUTO_KEY
    FROM target_wo t
    JOIN QCTL.RO_DETAIL rod ON rod.WOO_AUTO_KEY = t.WOO_AUTO_KEY
    UNION
    SELECT 'ROD_WOB_AUTO_KEY', t.SI_NUMBER, t.WOO_AUTO_KEY, rod.ROD_AUTO_KEY
    FROM target_wo t
    JOIN QCTL.WO_BOM wob ON wob.WOO_AUTO_KEY = t.WOO_AUTO_KEY
    JOIN QCTL.RO_DETAIL rod ON rod.WOB_AUTO_KEY = wob.WOB_AUTO_KEY
)
SELECT
    rm.MATCH_PATH,
    rm.WO_NUMBER,
    rm.WOO_AUTO_KEY,
    roh.RO_NUMBER,
    rod.ROD_AUTO_KEY,
    rod.WOB_AUTO_KEY,
    rod.ITEM_NUMBER,
    TO_CHAR(rod.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RO_ENTRY_DATE,
    TO_CHAR(roh.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RO_HEADER_ENTRY_DATE,
    TO_CHAR(roh.APPROVED_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RO_APPROVED_DATE,
    TO_CHAR(roh.QUOTE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RO_QUOTE_DATE,
    TO_CHAR(roh.OUT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RO_OUT_DATE,
    TO_CHAR(roh.NEXT_ACT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RO_NEXT_ACT_DATE,
    TO_CHAR(roh.LAST_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS RO_LAST_MODIFIED,
    roh.COMPANY_REF_NUMBER AS RO_COMPANY_REF_NUMBER,
    roh.VENDOR_NAME AS RO_VENDOR_NAME,
    rod.SERIAL_NUMBER,
    pm.PN,
    pm.DESCRIPTION,
    rod.QTY_REPAIR,
    rod.WO_PRICE,
    rod.EST_PRICE,
    rod.LABOR_COST,
    rod.PARTS_COST,
    rod.MISC_COST,
    rod.QUOTED_PARTS_HOME,
    rod.QUOTED_LABOR_HOME,
    rod.QUOTED_MISC_HOME,
    NVL(rod.QUOTED_PARTS_HOME, 0) + NVL(rod.QUOTED_LABOR_HOME, 0) + NVL(rod.QUOTED_MISC_HOME, 0) AS QUOTED_HOME_TOTAL,
    TO_CHAR(rod.MSG_QUOTE_REC_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_REC_DATE,
    TO_CHAR(rod.MSG_QUOTE_APPR_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_APPR_DATE,
    TO_CHAR(rod.MSG_QUOTE_AUTO_APPR_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_AUTO_APPR_DATE,
    TO_CHAR(rod.MSG_QUOTE_REJECT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_REJECT_DATE,
    TO_CHAR(rod.MSG_EST_DELIV_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_EST_DELIV_DATE,
    TO_CHAR(rod.MSG_SHIP_NOTICE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_SHIP_NOTICE_DATE,
    TO_CHAR(rod.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SHIP_DATE,
    TO_CHAR(rod.LAST_DELIVERY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS LAST_DELIVERY_DATE,
    TO_CHAR(rod.NEXT_DELIVERY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS NEXT_DELIVERY_DATE,
    rod.MSG_QUOTE_REC_CNT,
    rod.MSG_QUOTE_APPR_CNT,
    rod.MSG_QUOTE_REJECT_CNT,
    rod.MSG_EST_DELIV_CNT,
    rod.MSG_SHIP_NOTICE_CNT,
    rod.RO_TYPE,
    rod.ECC
FROM rod_matches rm
JOIN QCTL.RO_DETAIL rod ON rod.ROD_AUTO_KEY = rm.ROD_AUTO_KEY
LEFT JOIN QCTL.RO_HEADER roh ON roh.ROH_AUTO_KEY = rod.ROH_AUTO_KEY
LEFT JOIN QCTL.PARTS_MASTER pm ON pm.PNM_AUTO_KEY = rod.PNM_AUTO_KEY
ORDER BY rm.WO_NUMBER, roh.RO_NUMBER, rod.ITEM_NUMBER, rod.ROD_AUTO_KEY",
    ];
}

function quantumManagerWoLaborRawQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-labor-raw');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-labor-raw',
        'filename_prefix' => 'quantum_manager_wo_labor_raw',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'WOT_AUTO_KEY',
            'WTL_AUTO_KEY',
            'SEQUENCE',
            'TASK_DESC',
            'START_TIME',
            'STOP_TIME',
            'ENTRY_DATE',
            'HOURS',
            'HOURS_BILLED',
            'HOURS_BILLABLE',
            'BILLING_RATE',
            'BURDEN_RATE',
            'FIXED_OVERHEAD',
            'VARIABLE_OVERHEAD',
            'BILLABLE_AMOUNT',
            'BILLED_AMOUNT',
            'RAW_AMOUNT',
            'REWORK_FLAG',
            'MFG_APPLIED',
            'PART_MERGE',
            'CLOSED_UPDATE',
            'BATCH_ID',
            'LBC_AUTO_KEY',
            'DPT_AUTO_KEY',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    wt.WOO_AUTO_KEY,
    wt.WOT_AUTO_KEY,
    wtl.WTL_AUTO_KEY,
    wt.SEQUENCE,
    COALESCE(wt.SQUAWK_DESC, wt.LONG_DESCR) AS TASK_DESC,
    TO_CHAR(wtl.START_TIME, 'YYYY-MM-DD HH24:MI:SS') AS START_TIME,
    TO_CHAR(wtl.STOP_TIME, 'YYYY-MM-DD HH24:MI:SS') AS STOP_TIME,
    TO_CHAR(wtl.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
    wtl.HOURS,
    wtl.HOURS_BILLED,
    wtl.HOURS_BILLABLE,
    wtl.BILLING_RATE,
    wtl.BURDEN_RATE,
    wtl.FIXED_OVERHEAD,
    wtl.VARIABLE_OVERHEAD,
    NVL(wtl.HOURS_BILLABLE, 0) * NVL(wtl.BILLING_RATE, 0) AS BILLABLE_AMOUNT,
    NVL(wtl.HOURS_BILLED, 0) * NVL(wtl.BILLING_RATE, 0) AS BILLED_AMOUNT,
    NVL(wtl.HOURS, 0) * NVL(wtl.BILLING_RATE, 0) AS RAW_AMOUNT,
    wtl.REWORK_FLAG,
    wtl.MFG_APPLIED,
    wtl.PART_MERGE,
    wtl.CLOSED_UPDATE,
    wtl.BATCH_ID,
    wtl.LBC_AUTO_KEY,
    wtl.DPT_AUTO_KEY
FROM QCTL.WO_TASK_LABOR wtl
JOIN QCTL.WO_TASK wt ON wt.WOT_AUTO_KEY = wtl.WOT_AUTO_KEY
JOIN QCTL.WO_OPERATION wo ON wo.WOO_AUTO_KEY = wt.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER, wt.SEQUENCE, wtl.START_TIME, wtl.WTL_AUTO_KEY",
    ];
}

function quantumManagerWoBomMoneyQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-bom-money');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-bom-money',
        'filename_prefix' => 'quantum_manager_wo_bom_money',
        'columns' => [
            'SOURCE_VIEW',
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'ROW_COUNT',
            'QTY_NEEDED_SUM',
            'QTY_ISSUED_SUM',
            'UNIT_PRICE_SUM',
            'UNIT_PRICE_EXT_SUM',
            'TOTAL_PRICE_SUM',
            'TOTAL_COST_SUM',
            'EST_COST_SUM',
            'EST_BILL_PRICE_SUM',
            'LIST_PRICE_SUM',
            'CONS_PRICE_SUM',
            'EXCHANGE_REPAIR_PRICE_SUM',
            'EXCHANGE_REPAIR_COST_SUM',
        ],
        'binds' => [],
        'sql' => "
SELECT 'VIEW_SPR_WO_BOM' AS SOURCE_VIEW,
       wo.SI_NUMBER AS WO_NUMBER,
       v.WOO_AUTO_KEY,
       COUNT(*) AS ROW_COUNT,
       SUM(NVL(v.QTY_NEEDED, 0)) AS QTY_NEEDED_SUM,
       SUM(NVL(v.QTY_ISSUED, 0)) AS QTY_ISSUED_SUM,
       SUM(NVL(v.UNIT_PRICE, 0)) AS UNIT_PRICE_SUM,
       SUM(NVL(v.UNIT_PRICE, 0) * NVL(v.QTY_NEEDED, 0)) AS UNIT_PRICE_EXT_SUM,
       SUM(NVL(v.TOTAL_PRICE, 0)) AS TOTAL_PRICE_SUM,
       SUM(NVL(v.TOTAL_COST, 0)) AS TOTAL_COST_SUM,
       NULL AS EST_COST_SUM,
       NULL AS EST_BILL_PRICE_SUM,
       NULL AS LIST_PRICE_SUM,
       NULL AS CONS_PRICE_SUM,
       NULL AS EXCHANGE_REPAIR_PRICE_SUM,
       NULL AS EXCHANGE_REPAIR_COST_SUM
FROM QCTL.VIEW_SPR_WO_BOM v
JOIN QCTL.WO_OPERATION wo ON wo.WOO_AUTO_KEY = v.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
GROUP BY wo.SI_NUMBER, v.WOO_AUTO_KEY
UNION ALL
SELECT 'VIEW_WOBOM',
       wo.SI_NUMBER,
       v.WOO_AUTO_KEY,
       COUNT(*),
       SUM(NVL(v.QTY_NEEDED, 0)),
       SUM(NVL(v.QTY_ISSUED, 0)),
       SUM(NVL(v.UNIT_PRICE, 0)),
       SUM(NVL(v.UNIT_PRICE, 0) * NVL(v.QTY_NEEDED, 0)),
       NULL,
       NULL,
       SUM(NVL(v.EST_COST, 0)),
       SUM(NVL(v.EST_BILL_PRICE, 0)),
       SUM(NVL(v.LIST_PRICE, 0)),
       NULL,
       NULL,
       NULL
FROM QCTL.VIEW_WOBOM v
JOIN QCTL.WO_OPERATION wo ON wo.WOO_AUTO_KEY = v.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
GROUP BY wo.SI_NUMBER, v.WOO_AUTO_KEY
UNION ALL
SELECT 'WO_BOM',
       wo.SI_NUMBER,
       wb.WOO_AUTO_KEY,
       COUNT(*),
       SUM(NVL(wb.QTY_NEEDED, 0)),
       SUM(NVL(wb.QTY_ISSUED, 0)),
       SUM(NVL(wb.UNIT_PRICE, 0)),
       SUM(NVL(wb.UNIT_PRICE, 0) * NVL(wb.QTY_NEEDED, 0)),
       NULL,
       NULL,
       SUM(NVL(wb.EST_COST, 0)),
       NULL,
       NULL,
       SUM(NVL(wb.CONS_PRICE, 0)),
       SUM(NVL(wb.EXCHANGE_REPAIR_PRICE, 0)),
       SUM(NVL(wb.EXCHANGE_REPAIR_COST, 0))
FROM QCTL.WO_BOM wb
JOIN QCTL.WO_OPERATION wo ON wo.WOO_AUTO_KEY = wb.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
GROUP BY wo.SI_NUMBER, wb.WOO_AUTO_KEY
ORDER BY 2, 1",
    ];
}

function quantumManagerWoBomLinesQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-bom-lines');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-bom-lines',
        'filename_prefix' => 'quantum_manager_wo_bom_lines',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'WOB_AUTO_KEY',
            'WOT_AUTO_KEY',
            'SEQUENCE',
            'REF',
            'PN',
            'DESCRIPTION',
            'QTY_NEEDED',
            'UNIT_PRICE',
            'UNIT_EXT',
            'EST_COST',
            'LIST_PRICE',
            'LIST_EXT',
            'EST_BILL_PRICE',
            'EST_BILL_EXT',
            'CONS_PRICE',
            'CONS_EXT',
            'EXCHANGE_REPAIR_PRICE',
            'EXCHANGE_REPAIR_EXT',
            'QUOTE_ITEM',
            'EXTRA_PART',
            'MANUAL_OVERRIDE',
            'FIXED_EST_COST',
            'BGS_AUTO_KEY',
            'ACTIVITY',
            'REMARKS',
        ],
        'binds' => [],
        'sql' => "
SELECT
    vwb.SI_NUMBER AS WO_NUMBER,
    vwb.WOO_AUTO_KEY,
    vwb.WOB_AUTO_KEY,
    vwb.WOT AS WOT_AUTO_KEY,
    vwb.SEQUENCE,
    vwb.REF,
    vwb.PN,
    vwb.DESCRIPTION,
    vwb.QTY_NEEDED,
    vwb.UNIT_PRICE,
    NVL(vwb.UNIT_PRICE, 0) * NVL(vwb.QTY_NEEDED, 0) AS UNIT_EXT,
    vwb.EST_COST,
    vwb.LIST_PRICE,
    NVL(vwb.LIST_PRICE, 0) * NVL(vwb.QTY_NEEDED, 0) AS LIST_EXT,
    vwb.EST_BILL_PRICE,
    NVL(vwb.EST_BILL_PRICE, 0) * NVL(vwb.QTY_NEEDED, 0) AS EST_BILL_EXT,
    wb.CONS_PRICE,
    NVL(wb.CONS_PRICE, 0) * NVL(vwb.QTY_NEEDED, 0) AS CONS_EXT,
    wb.EXCHANGE_REPAIR_PRICE,
    NVL(wb.EXCHANGE_REPAIR_PRICE, 0) * NVL(vwb.QTY_NEEDED, 0) AS EXCHANGE_REPAIR_EXT,
    wb.QUOTE_ITEM,
    wb.EXTRA_PART,
    wb.MANUAL_OVERRIDE,
    vwb.FIXED_EST_COST,
    wb.BGS_AUTO_KEY,
    vwb.ACTIVITY,
    wb.REMARKS
FROM QCTL.VIEW_WOBOM vwb
LEFT JOIN QCTL.WO_BOM wb
  ON wb.WOB_AUTO_KEY = vwb.WOB_AUTO_KEY
WHERE UPPER(vwb.SI_NUMBER) IN ({$woList})
ORDER BY vwb.SI_NUMBER, vwb.SEQUENCE, vwb.WOB_AUTO_KEY",
    ];
}

function quantumManagerWoHeaderMoneyQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-header-money');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-header-money',
        'filename_prefix' => 'quantum_manager_wo_header_money',
        'columns' => [
            'SOURCE_TABLE',
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'PARTS_FLAT_PRICE',
            'LABOR_FLAT_PRICE',
            'MISC_FLAT_PRICE',
            'TAX_TOTAL',
            'EST_PARTS_COST',
            'EST_LABOR_COST',
            'EST_MISC_COST',
            'EST_OSV_COST',
            'EST_TOTAL_COST',
            'ACT_LABOR_COST',
            'APPLD_LABOR_COST',
            'EI_TOT_LABOR_COST',
            'QB_LABOR_COST',
            'STD_LABOR_COST',
            'TOTAL_LABOR',
        ],
        'binds' => [],
        'sql' => "
SELECT 'WO_OPERATION' AS SOURCE_TABLE,
       wo.SI_NUMBER AS WO_NUMBER,
       wo.WOO_AUTO_KEY,
       wo.PARTS_FLAT_PRICE,
       wo.LABOR_FLAT_PRICE,
       wo.MISC_FLAT_PRICE,
       wo.TAX_TOTAL,
       wo.EST_PARTS_COST,
       wo.EST_LABOR_COST,
       wo.EST_MISC_COST,
       wo.EST_OSV_COST,
       NVL(wo.EST_PARTS_COST, 0) + NVL(wo.EST_LABOR_COST, 0) + NVL(wo.EST_MISC_COST, 0) + NVL(wo.EST_OSV_COST, 0) AS EST_TOTAL_COST,
       wo.ACT_LABOR_COST,
       wo.APPLD_LABOR_COST,
       wo.EI_TOT_LABOR_COST,
       wo.QB_LABOR_COST,
       wo.STD_LABOR_COST,
       NULL AS TOTAL_LABOR
FROM QCTL.WO_OPERATION wo
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
UNION ALL
SELECT 'VIEW_SPS_WO_OPERATION',
       v.SI_NUMBER,
       v.WOO_AUTO_KEY,
       v.PARTS_FLAT_PRICE,
       v.LABOR_FLAT_PRICE,
       v.MISC_FLAT_PRICE,
       v.TAX_TOTAL,
       NULL,
       NULL,
       NULL,
       NULL,
       0,
       NULL,
       NULL,
       NULL,
       NULL,
       NULL,
       v.TOTAL_LABOR
FROM QCTL.VIEW_SPS_WO_OPERATION v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
ORDER BY 2, 1",
    ];
}

function quantumManagerWoSpsTotalsQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-sps-totals');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-sps-totals',
        'filename_prefix' => 'quantum_manager_wo_sps_totals',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'PARTS_FLAT_PRICE',
            'LABOR_FLAT_PRICE',
            'MISC_FLAT_PRICE',
            'TAX_TOTAL',
            'TOTAL_PARTS',
            'TOTAL_LABOR',
            'TOTAL_MISC',
            'TOTAL_SPS',
        ],
        'binds' => [],
        'sql' => "
SELECT
    v.SI_NUMBER AS WO_NUMBER,
    v.WOO_AUTO_KEY,
    v.PARTS_FLAT_PRICE,
    v.LABOR_FLAT_PRICE,
    v.MISC_FLAT_PRICE,
    v.TAX_TOTAL,
    v.TOTAL_PARTS,
    v.TOTAL_LABOR,
    v.TOTAL_MISC,
    NVL(v.TOTAL_PARTS, 0) + NVL(v.TOTAL_LABOR, 0) + NVL(v.TOTAL_MISC, 0) AS TOTAL_SPS
FROM QCTL.VIEW_SPS_WO_OPERATION v
WHERE UPPER(v.SI_NUMBER) IN ({$woList})
ORDER BY v.SI_NUMBER",
    ];
}

function quantumManagerWoMoneyMatrixQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-money-matrix');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-money-matrix',
        'filename_prefix' => 'quantum_manager_wo_money_matrix',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'TARGET_SCREENSHOT_AMOUNT',
            'BOM_UNIT_EXT',
            'HEADER_PARTS_FLAT',
            'HEADER_LABOR_FLAT',
            'HEADER_MISC_FLAT',
            'BASE_FORMULA',
            'DIFF_TARGET_MINUS_BASE',
            'UNBILLED_PARTS_EXT',
            'UNBILLED_PARTS_QUOTED',
            'UNBILLED_PARTS_WOB_PRICE_EXT',
            'UNBILLED_PARTS_EXPECTED_EXT',
            'UNBILLED_MISC_EXT',
            'UNBILLED_MISC_QUOTED',
            'WO_CHARGE_UNIT_EXT',
            'WO_CHARGE_EST_COST_EXT',
            'WO_CHARGE_VENDOR_EXT',
            'WO_CHARGE_EXTRA_MISC_EXT',
            'WIP_CHARGE_WO_COST',
            'WIP_CHARGE_UNIT_PRICE',
            'TASK_TOTAL_PRICE',
            'TASK_PARTS_PRICE',
            'TASK_LABOR_PRICE',
            'TASK_MISC_PRICE',
            'WO_REPAIRS_EST_TOTAL',
            'RO_DETAIL_QUOTED_HOME_TOTAL',
        ],
        'binds' => [],
        'sql' => "
WITH wo_base AS (
    SELECT
        wo.WOO_AUTO_KEY,
        wo.SI_NUMBER AS WO_NUMBER,
        CASE wo.SI_NUMBER
            WHEN 'W107731' THEN 26696.80
            WHEN 'W107732' THEN 32343.50
            WHEN 'W107734' THEN 59753.60
            WHEN 'W107735' THEN 201843.80
            WHEN 'W107736' THEN 174020.80
            WHEN 'W107737' THEN 65420.80
            ELSE NULL
        END AS TARGET_SCREENSHOT_AMOUNT,
        NVL(wo.PARTS_FLAT_PRICE, 0) AS HEADER_PARTS_FLAT,
        NVL(wo.LABOR_FLAT_PRICE, 0) AS HEADER_LABOR_FLAT,
        NVL(wo.MISC_FLAT_PRICE, 0) AS HEADER_MISC_FLAT
    FROM QCTL.WO_OPERATION wo
    WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
),
cte_bom AS (
    SELECT
        wb.WOO_AUTO_KEY,
        SUM(NVL(wb.UNIT_PRICE, 0) * NVL(wb.QTY_NEEDED, 0)) AS BOM_UNIT_EXT
    FROM QCTL.WO_BOM wb
    GROUP BY wb.WOO_AUTO_KEY
),
cte_unbilled_parts AS (
    SELECT
        v.WOO_AUTO_KEY,
        SUM(NVL(v.EXTENDED_PRICE, 0)) AS UNBILLED_PARTS_EXT,
        SUM(NVL(v.QUOTED_PRICE, 0) * NVL(v.QTY, 1)) AS UNBILLED_PARTS_QUOTED,
        SUM(NVL(v.WOB_PRICE, 0) * NVL(v.QTY, 1)) AS UNBILLED_PARTS_WOB_PRICE_EXT,
        SUM(NVL(v.EXPECTED_PRICE, 0) * NVL(v.QTY, 1)) AS UNBILLED_PARTS_EXPECTED_EXT
    FROM QCTL.VIEW_WO_UNBILLED_PARTS v
    GROUP BY v.WOO_AUTO_KEY
),
cte_unbilled_misc AS (
    SELECT
        v.WOO_AUTO_KEY,
        SUM(NVL(v.EXTENDED_PRICE, 0)) AS UNBILLED_MISC_EXT,
        SUM(NVL(v.QUOTED_PRICE, 0) * NVL(v.QTY, 1)) AS UNBILLED_MISC_QUOTED
    FROM QCTL.VIEW_WO_UNBILLED_MISC v
    GROUP BY v.WOO_AUTO_KEY
),
cte_wo_charge AS (
    SELECT
        COALESCE(wt.WOO_AUTO_KEY, wb.WOO_AUTO_KEY) AS WOO_AUTO_KEY,
        SUM(NVL(woc.UNIT_PRICE, 0) * NVL(woc.QTY, 0)) AS WO_CHARGE_UNIT_EXT,
        SUM(NVL(woc.EST_UNIT_COST, 0) * NVL(woc.QTY, 0)) AS WO_CHARGE_EST_COST_EXT,
        SUM(NVL(woc.VENDOR_PRICE, 0) * NVL(woc.QTY, 0)) AS WO_CHARGE_VENDOR_EXT,
        SUM(CASE WHEN woc.EXTRA_MISC = 'T' THEN NVL(woc.UNIT_PRICE, 0) * NVL(woc.QTY, 0) ELSE 0 END) AS WO_CHARGE_EXTRA_MISC_EXT
    FROM QCTL.WO_CHARGE woc
    LEFT JOIN QCTL.WO_TASK wt
      ON wt.WOT_AUTO_KEY = woc.WOT_AUTO_KEY
    LEFT JOIN QCTL.WO_BOM wb
      ON wb.WOB_AUTO_KEY = woc.WOB_AUTO_KEY
    WHERE COALESCE(wt.WOO_AUTO_KEY, wb.WOO_AUTO_KEY) IS NOT NULL
    GROUP BY COALESCE(wt.WOO_AUTO_KEY, wb.WOO_AUTO_KEY)
),
cte_wip_charge AS (
    SELECT
        wo.WOO_AUTO_KEY,
        SUM(NVL(v.WO_COST, 0)) AS WIP_CHARGE_WO_COST,
        SUM(NVL(v.UNIT_PRICE, 0)) AS WIP_CHARGE_UNIT_PRICE
    FROM QCTL.VIEW_WIP_DETAIL_CHARGE v
    JOIN QCTL.WO_OPERATION wo
      ON wo.SI_NUMBER = v.SI_NUMBER
    GROUP BY wo.WOO_AUTO_KEY
),
cte_task_totals AS (
    SELECT
        v.WOO_AUTO_KEY,
        SUM(NVL(v.TOTAL_PRICE, 0)) AS TASK_TOTAL_PRICE,
        SUM(NVL(v.PARTS_PRICE, 0)) AS TASK_PARTS_PRICE,
        SUM(NVL(v.LABOR_PRICE, 0)) AS TASK_LABOR_PRICE,
        SUM(NVL(v.MISC_PRICE, 0)) AS TASK_MISC_PRICE
    FROM QCTL.VIEW_WO_TASK_TOTALS v
    GROUP BY v.WOO_AUTO_KEY
),
cte_wo_repairs AS (
    SELECT
        wrp.WOO_AUTO_KEY,
        SUM(NVL(wrp.PARTS_EST_PRICE, 0) + NVL(wrp.LABOR_EST_PRICE, 0) + NVL(wrp.MISC_EST_PRICE, 0)) AS WO_REPAIRS_EST_TOTAL
    FROM QCTL.WO_REPAIRS wrp
    GROUP BY wrp.WOO_AUTO_KEY
),
cte_ro_detail AS (
    SELECT
        wb.WOO_AUTO_KEY,
        SUM(NVL(rod.QUOTED_PARTS_HOME, 0) + NVL(rod.QUOTED_LABOR_HOME, 0) + NVL(rod.QUOTED_MISC_HOME, 0)) AS RO_DETAIL_QUOTED_HOME_TOTAL
    FROM QCTL.RO_DETAIL rod
    JOIN QCTL.WO_BOM wb
      ON wb.WOB_AUTO_KEY = rod.WOB_AUTO_KEY
    GROUP BY wb.WOO_AUTO_KEY
)
SELECT
    wo_base.WO_NUMBER,
    wo_base.WOO_AUTO_KEY,
    wo_base.TARGET_SCREENSHOT_AMOUNT,
    NVL(cte_bom.BOM_UNIT_EXT, 0) AS BOM_UNIT_EXT,
    wo_base.HEADER_PARTS_FLAT,
    wo_base.HEADER_LABOR_FLAT,
    wo_base.HEADER_MISC_FLAT,
    NVL(cte_bom.BOM_UNIT_EXT, 0) + wo_base.HEADER_PARTS_FLAT + wo_base.HEADER_LABOR_FLAT + wo_base.HEADER_MISC_FLAT AS BASE_FORMULA,
    wo_base.TARGET_SCREENSHOT_AMOUNT - (
        NVL(cte_bom.BOM_UNIT_EXT, 0) + wo_base.HEADER_PARTS_FLAT + wo_base.HEADER_LABOR_FLAT + wo_base.HEADER_MISC_FLAT
    ) AS DIFF_TARGET_MINUS_BASE,
    NVL(up.UNBILLED_PARTS_EXT, 0) AS UNBILLED_PARTS_EXT,
    NVL(up.UNBILLED_PARTS_QUOTED, 0) AS UNBILLED_PARTS_QUOTED,
    NVL(up.UNBILLED_PARTS_WOB_PRICE_EXT, 0) AS UNBILLED_PARTS_WOB_PRICE_EXT,
    NVL(up.UNBILLED_PARTS_EXPECTED_EXT, 0) AS UNBILLED_PARTS_EXPECTED_EXT,
    NVL(um.UNBILLED_MISC_EXT, 0) AS UNBILLED_MISC_EXT,
    NVL(um.UNBILLED_MISC_QUOTED, 0) AS UNBILLED_MISC_QUOTED,
    NVL(woc.WO_CHARGE_UNIT_EXT, 0) AS WO_CHARGE_UNIT_EXT,
    NVL(woc.WO_CHARGE_EST_COST_EXT, 0) AS WO_CHARGE_EST_COST_EXT,
    NVL(woc.WO_CHARGE_VENDOR_EXT, 0) AS WO_CHARGE_VENDOR_EXT,
    NVL(woc.WO_CHARGE_EXTRA_MISC_EXT, 0) AS WO_CHARGE_EXTRA_MISC_EXT,
    NVL(wipc.WIP_CHARGE_WO_COST, 0) AS WIP_CHARGE_WO_COST,
    NVL(wipc.WIP_CHARGE_UNIT_PRICE, 0) AS WIP_CHARGE_UNIT_PRICE,
    NVL(tt.TASK_TOTAL_PRICE, 0) AS TASK_TOTAL_PRICE,
    NVL(tt.TASK_PARTS_PRICE, 0) AS TASK_PARTS_PRICE,
    NVL(tt.TASK_LABOR_PRICE, 0) AS TASK_LABOR_PRICE,
    NVL(tt.TASK_MISC_PRICE, 0) AS TASK_MISC_PRICE,
    NVL(wrp.WO_REPAIRS_EST_TOTAL, 0) AS WO_REPAIRS_EST_TOTAL,
    NVL(rd.RO_DETAIL_QUOTED_HOME_TOTAL, 0) AS RO_DETAIL_QUOTED_HOME_TOTAL
FROM wo_base
LEFT JOIN cte_bom
  ON cte_bom.WOO_AUTO_KEY = wo_base.WOO_AUTO_KEY
LEFT JOIN cte_unbilled_parts up
  ON up.WOO_AUTO_KEY = wo_base.WOO_AUTO_KEY
LEFT JOIN cte_unbilled_misc um
  ON um.WOO_AUTO_KEY = wo_base.WOO_AUTO_KEY
LEFT JOIN cte_wo_charge woc
  ON woc.WOO_AUTO_KEY = wo_base.WOO_AUTO_KEY
LEFT JOIN cte_wip_charge wipc
  ON wipc.WOO_AUTO_KEY = wo_base.WOO_AUTO_KEY
LEFT JOIN cte_task_totals tt
  ON tt.WOO_AUTO_KEY = wo_base.WOO_AUTO_KEY
LEFT JOIN cte_wo_repairs wrp
  ON wrp.WOO_AUTO_KEY = wo_base.WOO_AUTO_KEY
LEFT JOIN cte_ro_detail rd
  ON rd.WOO_AUTO_KEY = wo_base.WOO_AUTO_KEY
ORDER BY wo_base.WO_NUMBER",
    ];
}

function quantumManagerWoAuditMoneyQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-audit-money');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-audit-money',
        'filename_prefix' => 'quantum_manager_wo_audit_money',
        'columns' => [
            'SOURCE_TABLE',
            'WO_NUMBER',
            'GROUP_KEY',
            'TRAN_TYPE',
            'CONTEXT',
            'TRAN_DESC',
            'FIRST_DATE',
            'LAST_DATE',
            'ROW_COUNT',
            'QTY_SUM',
            'UNIT_MIN',
            'UNIT_MAX',
            'EXT_SUM',
            'EXT_ABS_SUM',
            'EXT_MIN',
            'EXT_MAX',
            'EXT_NEW_SUM',
            'EXT_OLD_SUM',
            'SAMPLE_WQH',
            'SAMPLE_WQD',
        ],
        'binds' => [],
        'sql' => "
WITH target_wo AS (
    SELECT wo.WOO_AUTO_KEY, wo.SI_NUMBER AS WO_NUMBER
    FROM QCTL.WO_OPERATION wo
    WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
),
wip_keys AS (
    SELECT DISTINCT
        t.WO_NUMBER,
        wip.WIP_AUTO_KEY
    FROM QCTL.WIP_AUDIT_LOG wip
    JOIN target_wo t
      ON t.WOO_AUTO_KEY = wip.WOO_AUTO_KEY
)
SELECT
    'WIP_AUDIT_LOG' AS source_table,
    t.WO_NUMBER,
    NVL(wip.TRAN_TYPE, '(blank)') AS group_key,
    wip.TRAN_TYPE,
    CAST(NULL AS VARCHAR2(30)) AS context,
    wip.TRAN_DESC,
    TO_CHAR(MIN(wip.TRAN_DATE), 'YYYY-MM-DD HH24:MI:SS') AS first_date,
    TO_CHAR(MAX(wip.TRAN_DATE), 'YYYY-MM-DD HH24:MI:SS') AS last_date,
    COUNT(*) AS row_count,
    SUM(NVL(wip.QTY, 0)) AS qty_sum,
    MIN(wip.UNIT_COST) AS unit_min,
    MAX(wip.UNIT_COST) AS unit_max,
    SUM(NVL(wip.EXT_COST, 0)) AS ext_sum,
    SUM(ABS(NVL(wip.EXT_COST, 0))) AS ext_abs_sum,
    MIN(wip.EXT_COST) AS ext_min,
    MAX(wip.EXT_COST) AS ext_max,
    CAST(NULL AS NUMBER) AS ext_new_sum,
    CAST(NULL AS NUMBER) AS ext_old_sum,
    CAST(NULL AS NUMBER) AS sample_wqh,
    CAST(NULL AS NUMBER) AS sample_wqd
FROM QCTL.WIP_AUDIT_LOG wip
JOIN target_wo t
  ON t.WOO_AUTO_KEY = wip.WOO_AUTO_KEY
GROUP BY t.WO_NUMBER, NVL(wip.TRAN_TYPE, '(blank)'), wip.TRAN_TYPE, wip.TRAN_DESC
UNION ALL
SELECT
    'REV_AUDIT_LOG' AS source_table,
    wk.WO_NUMBER,
    NVL(rev.TRAN_DESC, '(blank)') AS group_key,
    CAST(NULL AS VARCHAR2(15)) AS tran_type,
    CAST(NULL AS VARCHAR2(30)) AS context,
    rev.TRAN_DESC,
    TO_CHAR(MIN(rev.TRAN_DATE), 'YYYY-MM-DD HH24:MI:SS') AS first_date,
    TO_CHAR(MAX(rev.TRAN_DATE), 'YYYY-MM-DD HH24:MI:SS') AS last_date,
    COUNT(*) AS row_count,
    SUM(NVL(rev.QTY, 0)) AS qty_sum,
    MIN(rev.UNIT_PRICE) AS unit_min,
    MAX(rev.UNIT_PRICE) AS unit_max,
    SUM(NVL(rev.EXT_PRICE, 0)) AS ext_sum,
    SUM(ABS(NVL(rev.EXT_PRICE, 0))) AS ext_abs_sum,
    MIN(rev.EXT_PRICE) AS ext_min,
    MAX(rev.EXT_PRICE) AS ext_max,
    CAST(NULL AS NUMBER) AS ext_new_sum,
    CAST(NULL AS NUMBER) AS ext_old_sum,
    MIN(rev.WQH_AUTO_KEY) AS sample_wqh,
    MIN(rev.WQD_AUTO_KEY) AS sample_wqd
FROM QCTL.REV_AUDIT_LOG rev
JOIN wip_keys wk
  ON wk.WIP_AUTO_KEY = rev.WIP_AUTO_KEY
GROUP BY wk.WO_NUMBER, NVL(rev.TRAN_DESC, '(blank)'), rev.TRAN_DESC
UNION ALL
SELECT
    'REV_CHANGE_LOG' AS source_table,
    wk.WO_NUMBER,
    rvc.CONTEXT AS group_key,
    CAST(NULL AS VARCHAR2(15)) AS tran_type,
    rvc.CONTEXT,
    CAST(NULL AS VARCHAR2(255)) AS tran_desc,
    TO_CHAR(MIN(rvc.TRAN_DATE), 'YYYY-MM-DD HH24:MI:SS') AS first_date,
    TO_CHAR(MAX(rvc.TRAN_DATE), 'YYYY-MM-DD HH24:MI:SS') AS last_date,
    COUNT(*) AS row_count,
    CAST(NULL AS NUMBER) AS qty_sum,
    MIN(rvc.UNIT_PRICE_NEW) AS unit_min,
    MAX(rvc.UNIT_PRICE_NEW) AS unit_max,
    SUM(NVL(rvc.EXT_PRICE, 0)) AS ext_sum,
    SUM(ABS(NVL(rvc.EXT_PRICE, 0))) AS ext_abs_sum,
    MIN(rvc.EXT_PRICE) AS ext_min,
    MAX(rvc.EXT_PRICE) AS ext_max,
    SUM(NVL(rvc.EXT_PRICE_NEW, 0)) AS ext_new_sum,
    SUM(NVL(rvc.EXT_PRICE_OLD, 0)) AS ext_old_sum,
    MIN(rvc.WQH_AUTO_KEY) AS sample_wqh,
    MIN(rvc.WQD_AUTO_KEY) AS sample_wqd
FROM QCTL.REV_CHANGE_LOG rvc
JOIN QCTL.REV_AUDIT_LOG rev
  ON rev.REV_AUTO_KEY = rvc.REV_AUTO_KEY
JOIN wip_keys wk
  ON wk.WIP_AUTO_KEY = rev.WIP_AUTO_KEY
GROUP BY wk.WO_NUMBER, rvc.CONTEXT
ORDER BY 2, 1, 3, 6",
    ];
}

function quantumManagerWoChangeAuditQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-change-audit');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-change-audit',
        'filename_prefix' => 'quantum_manager_wo_change_audit',
        'columns' => [
            'SOURCE_TABLE',
            'WO_NUMBER',
            'STAMP_DATE',
            'SOURCE_FIELD',
            'DESCR',
            'ORIG_VALUE',
            'NEW_VALUE',
            'AMOUNT',
            'QTY',
            'WOB_AUTO_KEY',
            'WOT_AUTO_KEY',
            'WQH_AUTO_KEY',
            'WQD_AUTO_KEY',
            'USER_NAME',
        ],
        'binds' => [],
        'sql' => "
WITH target_wo AS (
    SELECT wo.WOO_AUTO_KEY, wo.SI_NUMBER AS WO_NUMBER
    FROM QCTL.WO_OPERATION wo
    WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
),
wip_keys AS (
    SELECT DISTINCT
        t.WO_NUMBER,
        wip.WIP_AUTO_KEY
    FROM QCTL.WIP_AUDIT_LOG wip
    JOIN target_wo t
      ON t.WOO_AUTO_KEY = wip.WOO_AUTO_KEY
)
SELECT
    'VIEW_BILLING_GROUPS_AUDIT' AS source_table,
    t.WO_NUMBER,
    TO_CHAR(v.STAMPTIME, 'YYYY-MM-DD HH24:MI:SS') AS stamp_date,
    v.SOURCE_FIELD,
    v.DESCR,
    v.ORIG_VALUE,
    v.NEW_VALUE,
    CAST(NULL AS NUMBER) AS amount,
    CAST(NULL AS NUMBER) AS qty,
    CAST(NULL AS NUMBER) AS wob_auto_key,
    CAST(NULL AS NUMBER) AS wot_auto_key,
    CAST(NULL AS NUMBER) AS wqh_auto_key,
    CAST(NULL AS NUMBER) AS wqd_auto_key,
    v.USER_NAME
FROM QCTL.VIEW_BILLING_GROUPS_AUDIT v
JOIN target_wo t
  ON t.WOO_AUTO_KEY = v.WOO_AUTO_KEY
UNION ALL
SELECT
    'COST_LOG' AS source_table,
    t.WO_NUMBER,
    TO_CHAR(c.ENTRY_DATETIME, 'YYYY-MM-DD HH24:MI:SS') AS stamp_date,
    c.OPERATIONAL_TYPE AS source_field,
    c.DESCRIPTION AS descr,
    CAST(NULL AS VARCHAR2(255)) AS orig_value,
    CAST(NULL AS VARCHAR2(255)) AS new_value,
    c.TOTAL_AMOUNT AS amount,
    c.QTY_COST AS qty,
    c.WOB_AUTO_KEY,
    c.WOT_AUTO_KEY,
    c.WQH_AUTO_KEY,
    c.WQD_AUTO_KEY,
    CAST(NULL AS VARCHAR2(20)) AS user_name
FROM QCTL.COST_LOG c
JOIN target_wo t
  ON t.WOO_AUTO_KEY = c.WOO_AUTO_KEY
UNION ALL
SELECT
    'COST_AUDIT_LOG' AS source_table,
    wk.WO_NUMBER,
    TO_CHAR(cog.TRAN_DATE, 'YYYY-MM-DD HH24:MI:SS') AS stamp_date,
    CAST(NULL AS VARCHAR2(40)) AS source_field,
    cog.TRAN_DESC AS descr,
    CAST(NULL AS VARCHAR2(255)) AS orig_value,
    CAST(NULL AS VARCHAR2(255)) AS new_value,
    cog.EXT_COST AS amount,
    cog.QTY AS qty,
    CAST(NULL AS NUMBER) AS wob_auto_key,
    CAST(NULL AS NUMBER) AS wot_auto_key,
    cog.WQH_AUTO_KEY,
    cog.WQD_AUTO_KEY,
    CAST(NULL AS VARCHAR2(20)) AS user_name
FROM QCTL.COST_AUDIT_LOG cog
JOIN wip_keys wk
  ON wk.WIP_AUTO_KEY = cog.WIP_AUTO_KEY
UNION ALL
SELECT
    'COST_CHANGE_LOG' AS source_table,
    wk.WO_NUMBER,
    TO_CHAR(chl.TRAN_DATE, 'YYYY-MM-DD HH24:MI:SS') AS stamp_date,
    chl.CONTEXT AS source_field,
    CAST(NULL AS VARCHAR2(255)) AS descr,
    TO_CHAR(chl.EXT_COST_OLD) AS orig_value,
    TO_CHAR(chl.EXT_COST_NEW) AS new_value,
    chl.EXT_COST AS amount,
    chl.QTY_NEW AS qty,
    CAST(NULL AS NUMBER) AS wob_auto_key,
    CAST(NULL AS NUMBER) AS wot_auto_key,
    cog.WQH_AUTO_KEY,
    cog.WQD_AUTO_KEY,
    CAST(NULL AS VARCHAR2(20)) AS user_name
FROM QCTL.COST_CHANGE_LOG chl
JOIN QCTL.COST_AUDIT_LOG cog
  ON cog.COG_AUTO_KEY = chl.COG_AUTO_KEY
JOIN wip_keys wk
  ON wk.WIP_AUTO_KEY = cog.WIP_AUTO_KEY
ORDER BY 2, 3, 1",
    ];
}

function quantumManagerWoSpbQuotesQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-spb-quotes');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-spb-quotes',
        'filename_prefix' => 'quantum_manager_wo_spb_quotes',
        'columns' => [
            'TARGET_WO',
            'WQH_AUTO_KEY',
            'WQH_NUMBER',
            'QUOTE_VERSION',
            'QUOTE_SEQUENCE',
            'ENTRY_DATE',
            'COMPANY_REF_NUMBER',
            'COMPANY_NAME',
            'WOO_AUTO_KEY',
            'SI_NUMBER',
            'STATUS_CODE',
            'TOTAL_FOREIGN_PRICE',
        ],
        'binds' => [],
        'sql' => "
WITH target_wo AS (
    SELECT wo.SI_NUMBER
    FROM QCTL.WO_OPERATION wo
    WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
)
SELECT
    t.SI_NUMBER AS TARGET_WO,
    q.WQH_AUTO_KEY,
    q.WQH_NUMBER,
    q.QUOTE_VERSION,
    q.QUOTE_SEQUENCE,
    TO_CHAR(q.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
    q.COMPANY_REF_NUMBER,
    q.COMPANY_NAME,
    q.WOO_AUTO_KEY,
    q.SI_NUMBER,
    q.STATUS_CODE,
    q.TOTAL_FOREIGN_PRICE
FROM target_wo t,
     SPB_WO_QUOTES(NULL, NULL, NULL, NULL, t.SI_NUMBER, NULL, 'T', NULL) q
ORDER BY t.SI_NUMBER, q.ENTRY_DATE DESC NULLS LAST, q.WQH_NUMBER",
    ];
}

function quantumManagerLatestEstimatesQuery(array $params): array
{
    $limit = managerLimit($params);
    $mode = strtolower((string)($params['mode'] ?? 'latest-estimates'));
    $isRangeMode = $mode === 'wo-range-table';
    $binds = [
        ':limit_rows' => $limit,
    ];
    $rangeWhere = '';
    $positiveEstimateWhere = "
      AND (
          NVL(bom.BOM_PARTS_EXT, 0)
          + NVL(wo.PARTS_FLAT_PRICE, 0)
          + NVL(wo.LABOR_FLAT_PRICE, 0)
          + NVL(wo.MISC_FLAT_PRICE, 0)
      ) > 0";
    $orderBy = 'wo.ENTRY_DATE DESC NULLS LAST, wo.WOO_AUTO_KEY DESC';

    if ($isRangeMode) {
        $rangeFrom = managerWoRangeNumber($params['from'] ?? '');
        $rangeTo = managerWoRangeNumber($params['to'] ?? '');
        if ($rangeFrom === null || $rangeTo === null) {
            throw new RuntimeException('Missing --from and --to values for --mode=wo-range-table');
        }
        if ($rangeFrom > $rangeTo) {
            [$rangeFrom, $rangeTo] = [$rangeTo, $rangeFrom];
        }

        $binds[':range_from'] = $rangeFrom;
        $binds[':range_to'] = $rangeTo;
        $rangeWhere = "
      AND REGEXP_LIKE(wo.SI_NUMBER, '^W?[0-9]+$', 'i')
      AND TO_NUMBER(REGEXP_REPLACE(wo.SI_NUMBER, '[^0-9]', '')) BETWEEN :range_from AND :range_to";
        $positiveEstimateWhere = '';
        $orderBy = "TO_NUMBER(REGEXP_REPLACE(wo.SI_NUMBER, '[^0-9]', '')), wo.SI_NUMBER";
    }

    return [
        'name' => $isRangeMode ? 'wo-range-table' : 'latest-estimates',
        'filename_prefix' => $isRangeMode
            ? 'quantum_manager_wo_range_' . $binds[':range_from'] . '_' . $binds[':range_to']
            : 'quantum_manager_latest_estimates',
        'columns' => [
            'WO_NUMBER',
            'WO_STATUS',
            'TASK',
            'PN',
            'DESCRIPTION',
            'SERIAL_NUMBER',
            'CUSTOMER',
            'ENTRY_DATE',
            'INVOICE',
            'INVOICE_DATE',
            'SHIP_DATE',
            'AWB_NUMBER',
            'COMPLETED_DATE',
            'BOM_PARTS_EXT',
            'PARTS_FLAT_PRICE',
            'LABOR_FLAT_PRICE',
            'MISC_FLAT_PRICE',
            'CALC_ESTIMATE',
            'BOM_ROWS',
        ],
        'binds' => $binds,
        'sql' => "
WITH bom AS (
    SELECT
        wb.WOO_AUTO_KEY,
        COUNT(*) AS BOM_ROWS,
        SUM(NVL(wb.UNIT_PRICE, 0) * NVL(wb.QTY_NEEDED, 0)) AS BOM_PARTS_EXT
    FROM QCTL.WO_BOM wb
    GROUP BY wb.WOO_AUTO_KEY
),
first_task AS (
    SELECT
        wt.WOO_AUTO_KEY,
        MIN(COALESCE(wt.SQUAWK_DESC, wt.LONG_DESCR)) KEEP (DENSE_RANK FIRST ORDER BY wt.SEQUENCE, wt.WOT_AUTO_KEY) AS TASK
    FROM QCTL.WO_TASK wt
    WHERE COALESCE(wt.SQUAWK_DESC, wt.LONG_DESCR) IS NOT NULL
    GROUP BY wt.WOO_AUTO_KEY
),
serials AS (
    SELECT
        v.WOO_AUTO_KEY,
        MIN(v.SERIAL_NUMBER) AS SERIAL_NUMBER
    FROM QCTL.VIEW_SPS_WO_SERIAL_NUMS v
    WHERE v.SERIAL_NUMBER IS NOT NULL
    GROUP BY v.WOO_AUTO_KEY
),
invoice_header AS (
    SELECT
        ih.WOO_AUTO_KEY,
        LISTAGG(ih.INVC_NUMBER, ', ') WITHIN GROUP (ORDER BY ih.INVOICE_DATE, ih.INVC_NUMBER) AS INVOICE,
        MAX(ih.INVOICE_DATE) AS INVOICE_DATE,
        MAX(ih.POST_DATE) AS POST_DATE,
        MAX(ih.SHIP_DATE) AS SHIP_DATE,
        LISTAGG(COALESCE(ih.AIRWAY_BILL, ih.TRACKING_NUMBER), ', ') WITHIN GROUP (ORDER BY ih.INVOICE_DATE, ih.INVC_NUMBER) AS AWB_NUMBER
    FROM QCTL.INVC_HEADER ih
    WHERE ih.WOO_AUTO_KEY IS NOT NULL
    GROUP BY ih.WOO_AUTO_KEY
),
billing AS (
    SELECT
        v.WOO_AUTO_KEY,
        LISTAGG(v.INVC_NUMBER, ', ') WITHIN GROUP (ORDER BY v.POST_DATE, v.INVC_NUMBER) AS INVOICE,
        MAX(v.POST_DATE) AS POST_DATE
    FROM QCTL.VIEW_WO_BILLING v
    GROUP BY v.WOO_AUTO_KEY
),
stm_complete AS (
    SELECT
        wsc.WOO_AUTO_KEY,
        MAX(wsc.SHIP_DATE) AS SHIP_DATE
    FROM QCTL.WO_STM_COMPLETE wsc
    GROUP BY wsc.WOO_AUTO_KEY
),
quote_header AS (
    SELECT
        wqh.WOO_MASTER AS WOO_AUTO_KEY,
        MAX(wqh.SHIP_DATE) AS SHIP_DATE,
        LISTAGG(wqh.AIRWAY_BILL, ', ') WITHIN GROUP (ORDER BY wqh.SHIP_DATE, wqh.WQH_NUMBER) AS AIRWAY_BILL
    FROM QCTL.WO_QUOTE_HEADER wqh
    WHERE wqh.WOO_MASTER IS NOT NULL
    GROUP BY wqh.WOO_MASTER
),
tat_summary AS (
    SELECT
        v.WOO_AUTO_KEY,
        MAX(v.SHIP_DATE) AS SHIP_DATE,
        MAX(v.INVC_POST_DATE) AS INVC_POST_DATE
    FROM QCTL.VIEW_WO_SUMMARY_WITH_TAT v
    GROUP BY v.WOO_AUTO_KEY
)
SELECT *
FROM (
    SELECT
        wo.SI_NUMBER AS WO_NUMBER,
        wo.WO_DISP AS WO_STATUS,
        ft.TASK,
        pm.PN,
        pm.DESCRIPTION,
        COALESCE(s.SERIAL_NUMBER, mc.SERIAL_NUMBER) AS SERIAL_NUMBER,
        wo.BILL_NAME AS CUSTOMER,
        TO_CHAR(wo.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
        COALESCE(ih.INVOICE, bill.INVOICE) AS INVOICE,
        TO_CHAR(COALESCE(ih.INVOICE_DATE, bill.POST_DATE, tat.INVC_POST_DATE), 'YYYY-MM-DD HH24:MI:SS') AS INVOICE_DATE,
        TO_CHAR(COALESCE(ih.SHIP_DATE, sc.SHIP_DATE, qh.SHIP_DATE, tat.SHIP_DATE, wo.MSG_SHIP_TO_CUST_DATE), 'YYYY-MM-DD HH24:MI:SS') AS SHIP_DATE,
        COALESCE(ih.AWB_NUMBER, qh.AIRWAY_BILL) AS AWB_NUMBER,
        TO_CHAR(COALESCE(wo.CLOSE_DATE, wo.CAMP_CLOSED_DATE), 'YYYY-MM-DD HH24:MI:SS') AS COMPLETED_DATE,
        NVL(bom.BOM_PARTS_EXT, 0) AS BOM_PARTS_EXT,
        NVL(wo.PARTS_FLAT_PRICE, 0) AS PARTS_FLAT_PRICE,
        NVL(wo.LABOR_FLAT_PRICE, 0) AS LABOR_FLAT_PRICE,
        NVL(wo.MISC_FLAT_PRICE, 0) AS MISC_FLAT_PRICE,
        (
            NVL(bom.BOM_PARTS_EXT, 0)
            + NVL(wo.PARTS_FLAT_PRICE, 0)
            + NVL(wo.LABOR_FLAT_PRICE, 0)
            + NVL(wo.MISC_FLAT_PRICE, 0)
        ) AS CALC_ESTIMATE,
        NVL(bom.BOM_ROWS, 0) AS BOM_ROWS
    FROM QCTL.WO_OPERATION wo
    LEFT JOIN bom
      ON bom.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN QCTL.PARTS_MASTER pm
      ON pm.PNM_AUTO_KEY = wo.PNM_AUTO_KEY
    LEFT JOIN first_task ft
      ON ft.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN serials s
      ON s.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN QCTL.VIEW_SPB_WO_MAINCOMPONENT mc
      ON mc.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN invoice_header ih
      ON ih.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN billing bill
      ON bill.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN stm_complete sc
      ON sc.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN quote_header qh
      ON qh.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN tat_summary tat
      ON tat.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    WHERE wo.SI_NUMBER IS NOT NULL
{$rangeWhere}{$positiveEstimateWhere}
    ORDER BY {$orderBy}
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerLatestInvoicedWoQuery(array $params): array
{
    $limit = managerLimit($params);

    return [
        'name' => 'latest-invoiced-wo',
        'filename_prefix' => 'quantum_manager_latest_invoiced_wo',
        'columns' => [
            'WO_NUMBER',
            'WO_STATUS',
            'TASK',
            'PN',
            'DESCRIPTION',
            'SERIAL_NUMBER',
            'CUSTOMER',
            'ENTRY_DATE',
            'INVOICE',
            'INVOICE_DATE',
            'INVOICE_POST_DATE',
            'SHIP_DATE',
            'AWB_NUMBER',
            'COMPLETED_DATE',
            'INVOICE_TOTAL_PRICE',
            'BOM_PARTS_EXT',
            'PARTS_FLAT_PRICE',
            'LABOR_FLAT_PRICE',
            'MISC_FLAT_PRICE',
            'CALC_ESTIMATE',
            'BOM_ROWS',
        ],
        'binds' => [
            ':limit_rows' => $limit,
        ],
        'sql' => "
WITH latest_invoice AS (
    SELECT *
    FROM (
        SELECT
            COALESCE(ih.WOO_AUTO_KEY, wqh.WOO_MASTER) AS WOO_AUTO_KEY,
            ih.INVC_NUMBER,
            ih.INVOICE_DATE,
            ih.POST_DATE,
            ih.SHIP_DATE,
            COALESCE(ih.AIRWAY_BILL, ih.TRACKING_NUMBER) AS AWB_NUMBER,
            ih.TOTAL_PRICE,
            ROW_NUMBER() OVER (
                PARTITION BY COALESCE(ih.WOO_AUTO_KEY, wqh.WOO_MASTER)
                ORDER BY ih.INVOICE_DATE DESC NULLS LAST, ih.POST_DATE DESC NULLS LAST, ih.INH_AUTO_KEY DESC
            ) AS RN
        FROM QCTL.INVC_HEADER ih
        LEFT JOIN QCTL.WO_QUOTE_HEADER wqh
          ON wqh.WQH_AUTO_KEY = ih.WQH_AUTO_KEY
        WHERE COALESCE(ih.WOO_AUTO_KEY, wqh.WOO_MASTER) IS NOT NULL
          AND ih.INVC_NUMBER IS NOT NULL
    )
    WHERE RN = 1
),
bom AS (
    SELECT
        wb.WOO_AUTO_KEY,
        COUNT(*) AS BOM_ROWS,
        SUM(NVL(wb.UNIT_PRICE, 0) * NVL(wb.QTY_NEEDED, 0)) AS BOM_PARTS_EXT
    FROM QCTL.WO_BOM wb
    GROUP BY wb.WOO_AUTO_KEY
),
first_task AS (
    SELECT
        wt.WOO_AUTO_KEY,
        MIN(COALESCE(wt.SQUAWK_DESC, wt.LONG_DESCR)) KEEP (DENSE_RANK FIRST ORDER BY wt.SEQUENCE, wt.WOT_AUTO_KEY) AS TASK
    FROM QCTL.WO_TASK wt
    WHERE COALESCE(wt.SQUAWK_DESC, wt.LONG_DESCR) IS NOT NULL
    GROUP BY wt.WOO_AUTO_KEY
),
serials AS (
    SELECT
        v.WOO_AUTO_KEY,
        MIN(v.SERIAL_NUMBER) AS SERIAL_NUMBER
    FROM QCTL.VIEW_SPS_WO_SERIAL_NUMS v
    WHERE v.SERIAL_NUMBER IS NOT NULL
    GROUP BY v.WOO_AUTO_KEY
)
SELECT *
FROM (
    SELECT
        wo.SI_NUMBER AS WO_NUMBER,
        wo.WO_DISP AS WO_STATUS,
        ft.TASK,
        pm.PN,
        pm.DESCRIPTION,
        COALESCE(s.SERIAL_NUMBER, mc.SERIAL_NUMBER) AS SERIAL_NUMBER,
        wo.BILL_NAME AS CUSTOMER,
        TO_CHAR(wo.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
        li.INVC_NUMBER AS INVOICE,
        TO_CHAR(li.INVOICE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INVOICE_DATE,
        TO_CHAR(li.POST_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INVOICE_POST_DATE,
        TO_CHAR(li.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SHIP_DATE,
        li.AWB_NUMBER,
        TO_CHAR(COALESCE(wo.CLOSE_DATE, wo.CAMP_CLOSED_DATE), 'YYYY-MM-DD HH24:MI:SS') AS COMPLETED_DATE,
        li.TOTAL_PRICE AS INVOICE_TOTAL_PRICE,
        NVL(bom.BOM_PARTS_EXT, 0) AS BOM_PARTS_EXT,
        NVL(wo.PARTS_FLAT_PRICE, 0) AS PARTS_FLAT_PRICE,
        NVL(wo.LABOR_FLAT_PRICE, 0) AS LABOR_FLAT_PRICE,
        NVL(wo.MISC_FLAT_PRICE, 0) AS MISC_FLAT_PRICE,
        (
            NVL(bom.BOM_PARTS_EXT, 0)
            + NVL(wo.PARTS_FLAT_PRICE, 0)
            + NVL(wo.LABOR_FLAT_PRICE, 0)
            + NVL(wo.MISC_FLAT_PRICE, 0)
        ) AS CALC_ESTIMATE,
        NVL(bom.BOM_ROWS, 0) AS BOM_ROWS
    FROM latest_invoice li
    JOIN QCTL.WO_OPERATION wo
      ON wo.WOO_AUTO_KEY = li.WOO_AUTO_KEY
    LEFT JOIN bom
      ON bom.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN QCTL.PARTS_MASTER pm
      ON pm.PNM_AUTO_KEY = wo.PNM_AUTO_KEY
    LEFT JOIN first_task ft
      ON ft.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN serials s
      ON s.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN QCTL.VIEW_SPB_WO_MAINCOMPONENT mc
      ON mc.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    ORDER BY li.INVOICE_DATE DESC NULLS LAST, li.POST_DATE DESC NULLS LAST, wo.SI_NUMBER DESC
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerLatestBilledWoQuery(array $params): array
{
    $limit = managerLimit($params);

    return [
        'name' => 'latest-billed-wo',
        'filename_prefix' => 'quantum_manager_latest_billed_wo',
        'columns' => [
            'WO_NUMBER',
            'WO_STATUS',
            'TASK',
            'PN',
            'DESCRIPTION',
            'SERIAL_NUMBER',
            'CUSTOMER',
            'ENTRY_DATE',
            'INVOICE',
            'INVOICE_DATE',
            'SHIP_DATE',
            'AWB_NUMBER',
            'COMPLETED_DATE',
            'INVOICE_TOTAL_PRICE',
            'BOM_PARTS_EXT',
            'PARTS_FLAT_PRICE',
            'LABOR_FLAT_PRICE',
            'MISC_FLAT_PRICE',
            'CALC_ESTIMATE',
            'BOM_ROWS',
        ],
        'binds' => [
            ':limit_rows' => $limit,
        ],
        'sql' => "
WITH latest_billing AS (
    SELECT *
    FROM (
        SELECT
            v.WOO_AUTO_KEY,
            v.INVC_NUMBER,
            v.POST_DATE,
            v.TOTAL_REVENUE,
            ROW_NUMBER() OVER (
                PARTITION BY v.WOO_AUTO_KEY
                ORDER BY v.POST_DATE DESC NULLS LAST, v.INVC_NUMBER DESC
            ) AS RN
        FROM QCTL.VIEW_WO_BILLING v
        WHERE v.WOO_AUTO_KEY IS NOT NULL
          AND v.INVC_NUMBER IS NOT NULL
    )
    WHERE RN = 1
),
bom AS (
    SELECT
        wb.WOO_AUTO_KEY,
        COUNT(*) AS BOM_ROWS,
        SUM(NVL(wb.UNIT_PRICE, 0) * NVL(wb.QTY_NEEDED, 0)) AS BOM_PARTS_EXT
    FROM QCTL.WO_BOM wb
    GROUP BY wb.WOO_AUTO_KEY
),
first_task AS (
    SELECT
        wt.WOO_AUTO_KEY,
        MIN(COALESCE(wt.SQUAWK_DESC, wt.LONG_DESCR)) KEEP (DENSE_RANK FIRST ORDER BY wt.SEQUENCE, wt.WOT_AUTO_KEY) AS TASK
    FROM QCTL.WO_TASK wt
    WHERE COALESCE(wt.SQUAWK_DESC, wt.LONG_DESCR) IS NOT NULL
    GROUP BY wt.WOO_AUTO_KEY
),
serials AS (
    SELECT
        v.WOO_AUTO_KEY,
        MIN(v.SERIAL_NUMBER) AS SERIAL_NUMBER
    FROM QCTL.VIEW_SPS_WO_SERIAL_NUMS v
    WHERE v.SERIAL_NUMBER IS NOT NULL
    GROUP BY v.WOO_AUTO_KEY
),
ship_complete AS (
    SELECT
        wsc.WOO_AUTO_KEY,
        MAX(wsc.SHIP_DATE) AS SHIP_DATE
    FROM QCTL.WO_STM_COMPLETE wsc
    GROUP BY wsc.WOO_AUTO_KEY
)
SELECT *
FROM (
    SELECT
        wo.SI_NUMBER AS WO_NUMBER,
        wo.WO_DISP AS WO_STATUS,
        ft.TASK,
        pm.PN,
        pm.DESCRIPTION,
        COALESCE(s.SERIAL_NUMBER, mc.SERIAL_NUMBER) AS SERIAL_NUMBER,
        wo.BILL_NAME AS CUSTOMER,
        TO_CHAR(wo.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
        lb.INVC_NUMBER AS INVOICE,
        TO_CHAR(lb.POST_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INVOICE_DATE,
        TO_CHAR(sc.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SHIP_DATE,
        NULL AS AWB_NUMBER,
        TO_CHAR(COALESCE(wo.CLOSE_DATE, wo.CAMP_CLOSED_DATE), 'YYYY-MM-DD HH24:MI:SS') AS COMPLETED_DATE,
        lb.TOTAL_REVENUE AS INVOICE_TOTAL_PRICE,
        NVL(bom.BOM_PARTS_EXT, 0) AS BOM_PARTS_EXT,
        NVL(wo.PARTS_FLAT_PRICE, 0) AS PARTS_FLAT_PRICE,
        NVL(wo.LABOR_FLAT_PRICE, 0) AS LABOR_FLAT_PRICE,
        NVL(wo.MISC_FLAT_PRICE, 0) AS MISC_FLAT_PRICE,
        (
            NVL(bom.BOM_PARTS_EXT, 0)
            + NVL(wo.PARTS_FLAT_PRICE, 0)
            + NVL(wo.LABOR_FLAT_PRICE, 0)
            + NVL(wo.MISC_FLAT_PRICE, 0)
        ) AS CALC_ESTIMATE,
        NVL(bom.BOM_ROWS, 0) AS BOM_ROWS
    FROM latest_billing lb
    JOIN QCTL.WO_OPERATION wo
      ON wo.WOO_AUTO_KEY = lb.WOO_AUTO_KEY
    LEFT JOIN bom
      ON bom.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN QCTL.PARTS_MASTER pm
      ON pm.PNM_AUTO_KEY = wo.PNM_AUTO_KEY
    LEFT JOIN first_task ft
      ON ft.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN serials s
      ON s.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN QCTL.VIEW_SPB_WO_MAINCOMPONENT mc
      ON mc.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    LEFT JOIN ship_complete sc
      ON sc.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
    ORDER BY lb.POST_DATE DESC NULLS LAST, wo.SI_NUMBER DESC
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerInvoiceWoSamplesQuery(array $params): array
{
    $limit = managerLimit($params);

    return [
        'name' => 'invoice-wo-samples',
        'filename_prefix' => 'quantum_manager_invoice_wo_samples',
        'columns' => [
            'SOURCE',
            'WO_NUMBER',
            'WO_STATUS',
            'INVOICE',
            'INVOICE_DATE',
            'SHIP_DATE',
            'AWB_NUMBER',
            'COMPLETED_DATE',
            'CUSTOMER',
        ],
        'binds' => [
            ':limit_direct' => $limit,
            ':limit_quote' => $limit,
            ':limit_billing' => $limit,
        ],
        'sql' => "
SELECT *
FROM (
    SELECT *
    FROM (
        SELECT
            'INVC_HEADER.WOO_AUTO_KEY' AS SOURCE,
            wo.SI_NUMBER AS WO_NUMBER,
            wo.WO_DISP AS WO_STATUS,
            ih.INVC_NUMBER AS INVOICE,
            TO_CHAR(ih.INVOICE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INVOICE_DATE,
            TO_CHAR(ih.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SHIP_DATE,
            COALESCE(ih.AIRWAY_BILL, ih.TRACKING_NUMBER) AS AWB_NUMBER,
            TO_CHAR(COALESCE(wo.CLOSE_DATE, wo.CAMP_CLOSED_DATE), 'YYYY-MM-DD HH24:MI:SS') AS COMPLETED_DATE,
            wo.BILL_NAME AS CUSTOMER
        FROM QCTL.INVC_HEADER ih
        JOIN QCTL.WO_OPERATION wo
          ON wo.WOO_AUTO_KEY = ih.WOO_AUTO_KEY
        WHERE ih.INVC_NUMBER IS NOT NULL
        ORDER BY ih.INVOICE_DATE DESC NULLS LAST, ih.POST_DATE DESC NULLS LAST, ih.INH_AUTO_KEY DESC
    )
    FETCH FIRST :limit_direct ROWS ONLY
)
UNION ALL
SELECT *
FROM (
    SELECT *
    FROM (
        SELECT
            'INVC_HEADER.WQH_AUTO_KEY -> WO_QUOTE_HEADER.WOO_MASTER' AS SOURCE,
            wo.SI_NUMBER AS WO_NUMBER,
            wo.WO_DISP AS WO_STATUS,
            ih.INVC_NUMBER AS INVOICE,
            TO_CHAR(ih.INVOICE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INVOICE_DATE,
            TO_CHAR(ih.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SHIP_DATE,
            COALESCE(ih.AIRWAY_BILL, ih.TRACKING_NUMBER) AS AWB_NUMBER,
            TO_CHAR(COALESCE(wo.CLOSE_DATE, wo.CAMP_CLOSED_DATE), 'YYYY-MM-DD HH24:MI:SS') AS COMPLETED_DATE,
            wo.BILL_NAME AS CUSTOMER
        FROM QCTL.INVC_HEADER ih
        JOIN QCTL.WO_QUOTE_HEADER wqh
          ON wqh.WQH_AUTO_KEY = ih.WQH_AUTO_KEY
        JOIN QCTL.WO_OPERATION wo
          ON wo.WOO_AUTO_KEY = wqh.WOO_MASTER
        WHERE ih.INVC_NUMBER IS NOT NULL
        ORDER BY ih.INVOICE_DATE DESC NULLS LAST, ih.POST_DATE DESC NULLS LAST, ih.INH_AUTO_KEY DESC
    )
    FETCH FIRST :limit_quote ROWS ONLY
)
UNION ALL
SELECT *
FROM (
    SELECT *
    FROM (
        SELECT
            'VIEW_WO_BILLING.WOO_AUTO_KEY' AS SOURCE,
            wo.SI_NUMBER AS WO_NUMBER,
            wo.WO_DISP AS WO_STATUS,
            v.INVC_NUMBER AS INVOICE,
            TO_CHAR(v.POST_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INVOICE_DATE,
            NULL AS SHIP_DATE,
            NULL AS AWB_NUMBER,
            TO_CHAR(COALESCE(wo.CLOSE_DATE, wo.CAMP_CLOSED_DATE), 'YYYY-MM-DD HH24:MI:SS') AS COMPLETED_DATE,
            wo.BILL_NAME AS CUSTOMER
        FROM QCTL.VIEW_WO_BILLING v
        JOIN QCTL.WO_OPERATION wo
          ON wo.WOO_AUTO_KEY = v.WOO_AUTO_KEY
        WHERE v.INVC_NUMBER IS NOT NULL
        ORDER BY v.POST_DATE DESC NULLS LAST, v.INVC_NUMBER DESC
    )
    FETCH FIRST :limit_billing ROWS ONLY
)
",
    ];
}

function quantumManagerInvoiceHeaderSamplesQuery(array $params): array
{
    $limit = managerLimit($params);

    return [
        'name' => 'invoice-header-samples',
        'filename_prefix' => 'quantum_manager_invoice_header_samples',
        'columns' => [
            'INVOICE',
            'INVOICE_DATE',
            'POST_DATE',
            'SHIP_DATE',
            'AWB_NUMBER',
            'TOTAL_PRICE',
            'WOO_AUTO_KEY',
            'WQH_AUTO_KEY',
            'SVC_AUTO_KEY',
            'CMP_AUTO_KEY',
            'BILL_NAME',
            'SHIP_NAME',
            'COMPANY_PO_NUMBER',
            'PROFORMA_NUMBER',
        ],
        'binds' => [
            ':limit_rows' => $limit,
        ],
        'sql' => "
SELECT *
FROM (
    SELECT
        ih.INVC_NUMBER AS INVOICE,
        TO_CHAR(ih.INVOICE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INVOICE_DATE,
        TO_CHAR(ih.POST_DATE, 'YYYY-MM-DD HH24:MI:SS') AS POST_DATE,
        TO_CHAR(ih.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SHIP_DATE,
        COALESCE(ih.AIRWAY_BILL, ih.TRACKING_NUMBER) AS AWB_NUMBER,
        ih.TOTAL_PRICE,
        ih.WOO_AUTO_KEY,
        ih.WQH_AUTO_KEY,
        ih.SVC_AUTO_KEY,
        ih.CMP_AUTO_KEY,
        ih.BILL_NAME,
        ih.SHIP_NAME,
        ih.COMPANY_PO_NUMBER,
        ih.PROFORMA_NUMBER
    FROM QCTL.INVC_HEADER ih
    WHERE ih.INVC_NUMBER IS NOT NULL
    ORDER BY ih.INVOICE_DATE DESC NULLS LAST, ih.POST_DATE DESC NULLS LAST, ih.INH_AUTO_KEY DESC
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerInvoicePoWoSamplesQuery(array $params): array
{
    $limit = managerLimit($params);

    return [
        'name' => 'invoice-po-wo-samples',
        'filename_prefix' => 'quantum_manager_invoice_po_wo_samples',
        'columns' => [
            'INVOICE',
            'INVOICE_DATE',
            'SHIP_DATE',
            'WO_NUMBER',
            'WO_STATUS',
            'COMPANY_PO_NUMBER',
            'WO_COMPANY_REF_NUMBER',
            'CUSTOMER',
            'INVOICE_TOTAL_PRICE',
            'COMPLETED_DATE',
        ],
        'binds' => [
            ':limit_rows' => $limit,
        ],
        'sql' => "
SELECT *
FROM (
    SELECT
        ih.INVC_NUMBER AS INVOICE,
        TO_CHAR(ih.INVOICE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INVOICE_DATE,
        TO_CHAR(ih.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS SHIP_DATE,
        wo.SI_NUMBER AS WO_NUMBER,
        wo.WO_DISP AS WO_STATUS,
        ih.COMPANY_PO_NUMBER,
        wo.COMPANY_REF_NUMBER AS WO_COMPANY_REF_NUMBER,
        wo.BILL_NAME AS CUSTOMER,
        ih.TOTAL_PRICE AS INVOICE_TOTAL_PRICE,
        TO_CHAR(COALESCE(wo.CLOSE_DATE, wo.CAMP_CLOSED_DATE), 'YYYY-MM-DD HH24:MI:SS') AS COMPLETED_DATE
    FROM QCTL.INVC_HEADER ih
    JOIN QCTL.WO_OPERATION wo
      ON REGEXP_REPLACE(UPPER(ih.COMPANY_PO_NUMBER), '[^A-Z0-9]', '') =
         REGEXP_REPLACE(UPPER(wo.COMPANY_REF_NUMBER), '[^A-Z0-9]', '')
    WHERE ih.INVC_NUMBER IS NOT NULL
      AND ih.COMPANY_PO_NUMBER IS NOT NULL
      AND wo.COMPANY_REF_NUMBER IS NOT NULL
    ORDER BY ih.INVOICE_DATE DESC NULLS LAST, ih.INH_AUTO_KEY DESC
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerWoManagerColumnsQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-manager-columns');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-manager-columns',
        'filename_prefix' => 'quantum_manager_wo_manager_columns',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'WO_STATUS',
            'TASK',
            'INVOICE',
            'INVOICE_DATE',
            'SHIP_DATE',
            'AWB_NUMBER',
            'COMPLETED_DATE',
            'INVOICE_SOURCE',
            'INVOICE_DATE_SOURCE',
            'SHIP_DATE_SOURCE',
            'AWB_SOURCE',
            'COMPLETED_DATE_SOURCE',
            'RAW_INVC_HEADER_DATE',
            'RAW_INVC_HEADER_SHIP_DATE',
            'RAW_INVC_HEADER_AWB',
            'RAW_BILLING_POST_DATE',
            'RAW_WSC_SHIP_DATE',
            'RAW_WQH_SHIP_DATE',
            'RAW_WQH_AWB',
            'RAW_TAT_SHIP_DATE',
            'RAW_TAT_INVC_POST_DATE',
            'RAW_WO_CLOSE_DATE',
            'RAW_WO_CAMP_CLOSED_DATE',
        ],
        'binds' => [],
        'sql' => "
WITH first_task AS (
    SELECT
        wt.WOO_AUTO_KEY,
        MIN(COALESCE(wt.SQUAWK_DESC, wt.LONG_DESCR)) KEEP (DENSE_RANK FIRST ORDER BY wt.SEQUENCE, wt.WOT_AUTO_KEY) AS TASK
    FROM QCTL.WO_TASK wt
    WHERE COALESCE(wt.SQUAWK_DESC, wt.LONG_DESCR) IS NOT NULL
    GROUP BY wt.WOO_AUTO_KEY
),
invoice_header AS (
    SELECT
        ih.WOO_AUTO_KEY,
        LISTAGG(ih.INVC_NUMBER, ', ') WITHIN GROUP (ORDER BY ih.INVOICE_DATE, ih.INVC_NUMBER) AS INVOICE,
        MAX(ih.INVOICE_DATE) AS INVOICE_DATE,
        MAX(ih.POST_DATE) AS POST_DATE,
        MAX(ih.SHIP_DATE) AS SHIP_DATE,
        LISTAGG(COALESCE(ih.AIRWAY_BILL, ih.TRACKING_NUMBER), ', ') WITHIN GROUP (ORDER BY ih.INVOICE_DATE, ih.INVC_NUMBER) AS AWB_NUMBER
    FROM QCTL.INVC_HEADER ih
    WHERE ih.WOO_AUTO_KEY IS NOT NULL
    GROUP BY ih.WOO_AUTO_KEY
),
billing AS (
    SELECT
        v.WOO_AUTO_KEY,
        LISTAGG(v.INVC_NUMBER, ', ') WITHIN GROUP (ORDER BY v.POST_DATE, v.INVC_NUMBER) AS INVOICE,
        MAX(v.POST_DATE) AS POST_DATE
    FROM QCTL.VIEW_WO_BILLING v
    GROUP BY v.WOO_AUTO_KEY
),
stm_complete AS (
    SELECT
        wsc.WOO_AUTO_KEY,
        MAX(wsc.SHIP_DATE) AS SHIP_DATE
    FROM QCTL.WO_STM_COMPLETE wsc
    GROUP BY wsc.WOO_AUTO_KEY
),
quote_header AS (
    SELECT
        wqh.WOO_MASTER AS WOO_AUTO_KEY,
        MAX(wqh.SHIP_DATE) AS SHIP_DATE,
        LISTAGG(wqh.AIRWAY_BILL, ', ') WITHIN GROUP (ORDER BY wqh.SHIP_DATE, wqh.WQH_NUMBER) AS AIRWAY_BILL
    FROM QCTL.WO_QUOTE_HEADER wqh
    WHERE wqh.WOO_MASTER IS NOT NULL
    GROUP BY wqh.WOO_MASTER
),
tat_summary AS (
    SELECT
        v.WOO_AUTO_KEY,
        MAX(v.SHIP_DATE) AS SHIP_DATE,
        MAX(v.INVC_POST_DATE) AS INVC_POST_DATE
    FROM QCTL.VIEW_WO_SUMMARY_WITH_TAT v
    GROUP BY v.WOO_AUTO_KEY
)
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    wo.WOO_AUTO_KEY,
    wo.WO_DISP AS WO_STATUS,
    ft.TASK,
    COALESCE(ih.INVOICE, bill.INVOICE) AS INVOICE,
    TO_CHAR(COALESCE(ih.INVOICE_DATE, bill.POST_DATE, tat.INVC_POST_DATE), 'YYYY-MM-DD HH24:MI:SS') AS INVOICE_DATE,
    TO_CHAR(COALESCE(ih.SHIP_DATE, sc.SHIP_DATE, qh.SHIP_DATE, tat.SHIP_DATE, wo.MSG_SHIP_TO_CUST_DATE), 'YYYY-MM-DD HH24:MI:SS') AS SHIP_DATE,
    COALESCE(ih.AWB_NUMBER, qh.AIRWAY_BILL) AS AWB_NUMBER,
    TO_CHAR(COALESCE(wo.CLOSE_DATE, wo.CAMP_CLOSED_DATE), 'YYYY-MM-DD HH24:MI:SS') AS COMPLETED_DATE,
    CASE
        WHEN ih.INVOICE IS NOT NULL THEN 'INVC_HEADER.WOO_AUTO_KEY'
        WHEN bill.INVOICE IS NOT NULL THEN 'VIEW_WO_BILLING.WOO_AUTO_KEY'
        ELSE NULL
    END AS INVOICE_SOURCE,
    CASE
        WHEN ih.INVOICE_DATE IS NOT NULL THEN 'INVC_HEADER.INVOICE_DATE'
        WHEN bill.POST_DATE IS NOT NULL THEN 'VIEW_WO_BILLING.POST_DATE'
        WHEN tat.INVC_POST_DATE IS NOT NULL THEN 'VIEW_WO_SUMMARY_WITH_TAT.INVC_POST_DATE'
        ELSE NULL
    END AS INVOICE_DATE_SOURCE,
    CASE
        WHEN ih.SHIP_DATE IS NOT NULL THEN 'INVC_HEADER.SHIP_DATE'
        WHEN sc.SHIP_DATE IS NOT NULL THEN 'WO_STM_COMPLETE.SHIP_DATE'
        WHEN qh.SHIP_DATE IS NOT NULL THEN 'WO_QUOTE_HEADER.SHIP_DATE'
        WHEN tat.SHIP_DATE IS NOT NULL THEN 'VIEW_WO_SUMMARY_WITH_TAT.SHIP_DATE'
        WHEN wo.MSG_SHIP_TO_CUST_DATE IS NOT NULL THEN 'WO_OPERATION.MSG_SHIP_TO_CUST_DATE'
        ELSE NULL
    END AS SHIP_DATE_SOURCE,
    CASE
        WHEN ih.AWB_NUMBER IS NOT NULL THEN 'INVC_HEADER.AIRWAY_BILL/TRACKING_NUMBER'
        WHEN qh.AIRWAY_BILL IS NOT NULL THEN 'WO_QUOTE_HEADER.AIRWAY_BILL'
        ELSE NULL
    END AS AWB_SOURCE,
    CASE
        WHEN wo.CLOSE_DATE IS NOT NULL THEN 'WO_OPERATION.CLOSE_DATE'
        WHEN wo.CAMP_CLOSED_DATE IS NOT NULL THEN 'WO_OPERATION.CAMP_CLOSED_DATE'
        ELSE NULL
    END AS COMPLETED_DATE_SOURCE,
    TO_CHAR(ih.INVOICE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RAW_INVC_HEADER_DATE,
    TO_CHAR(ih.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RAW_INVC_HEADER_SHIP_DATE,
    ih.AWB_NUMBER AS RAW_INVC_HEADER_AWB,
    TO_CHAR(bill.POST_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RAW_BILLING_POST_DATE,
    TO_CHAR(sc.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RAW_WSC_SHIP_DATE,
    TO_CHAR(qh.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RAW_WQH_SHIP_DATE,
    qh.AIRWAY_BILL AS RAW_WQH_AWB,
    TO_CHAR(tat.SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RAW_TAT_SHIP_DATE,
    TO_CHAR(tat.INVC_POST_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RAW_TAT_INVC_POST_DATE,
    TO_CHAR(wo.CLOSE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RAW_WO_CLOSE_DATE,
    TO_CHAR(wo.CAMP_CLOSED_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RAW_WO_CAMP_CLOSED_DATE
FROM QCTL.WO_OPERATION wo
LEFT JOIN first_task ft
  ON ft.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
LEFT JOIN invoice_header ih
  ON ih.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
LEFT JOIN billing bill
  ON bill.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
LEFT JOIN stm_complete sc
  ON sc.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
LEFT JOIN quote_header qh
  ON qh.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
LEFT JOIN tat_summary tat
  ON tat.WOO_AUTO_KEY = wo.WOO_AUTO_KEY
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER",
    ];
}

function quantumManagerWoCustomFieldsQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-custom-fields');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-custom-fields',
        'filename_prefix' => 'quantum_manager_wo_custom_fields',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'QUOTE_STATUS',
            'QUOTE_STATUS_DATE',
            'MSG_QUOTE_SENT_DATE',
            'MSG_QUOTE_APPR_DATE',
            'MSG_QUOTE_REJECT_DATE',
            'MSG_EST_SHIP_DATE',
            'MSG_WO_INVOICE_SENT_DATE',
            'MSG_WO_INVOICE_REC_DATE',
            'MSG_INVOICE_ACK_REC_DATE',
            'MSG_QUOTE_SENT_CNT',
            'MSG_QUOTE_APPR_CNT',
            'MSG_QUOTE_REJECT_CNT',
            'WO_UDF_001',
            'WO_UDF_002',
            'WO_UDF_003',
            'WO_UDF_004',
            'WO_UDF_005',
            'WO_UDF_006',
            'WO_UDF_007',
            'WO_UDF_008',
            'WO_UDF_009',
            'WO_UDF_010',
            'WO_UDF_011',
            'WO_UDF_012',
            'WO_UDF_013',
            'WO_UDF_014',
            'WO_UDF_015',
            'WO_UDF_016',
            'WO_UDF_017',
            'WO_UDF_018',
            'SDF_WOO_001',
            'SDF_WOO_002',
            'SDF_WOO_003',
            'SDF_WOO_004',
            'SDF_WOO_005',
            'SDF_WOO_006',
            'SDF_WOO_007',
            'SDF_WOO_008',
            'SDF_WOO_009',
            'SDF_WOO_010',
            'SDF_WOO_012',
            'SDF_WOO_013',
            'SDF_WOO_014',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    wo.WOO_AUTO_KEY,
    wo.QUOTE_STATUS,
    TO_CHAR(wo.QUOTE_STATUS_DATE, 'YYYY-MM-DD HH24:MI:SS') AS QUOTE_STATUS_DATE,
    TO_CHAR(wo.MSG_QUOTE_SENT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_SENT_DATE,
    TO_CHAR(wo.MSG_QUOTE_APPR_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_APPR_DATE,
    TO_CHAR(wo.MSG_QUOTE_REJECT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_REJECT_DATE,
    TO_CHAR(wo.MSG_EST_SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_EST_SHIP_DATE,
    TO_CHAR(wo.MSG_WO_INVOICE_SENT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_WO_INVOICE_SENT_DATE,
    TO_CHAR(wo.MSG_WO_INVOICE_REC_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_WO_INVOICE_REC_DATE,
    TO_CHAR(wo.MSG_INVOICE_ACK_REC_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_INVOICE_ACK_REC_DATE,
    wo.MSG_QUOTE_SENT_CNT,
    wo.MSG_QUOTE_APPR_CNT,
    wo.MSG_QUOTE_REJECT_CNT,
    wo.WO_UDF_001,
    wo.WO_UDF_002,
    wo.WO_UDF_003,
    wo.WO_UDF_004,
    wo.WO_UDF_005,
    wo.WO_UDF_006,
    wo.WO_UDF_007,
    wo.WO_UDF_008,
    wo.WO_UDF_009,
    wo.WO_UDF_010,
    wo.WO_UDF_011,
    wo.WO_UDF_012,
    wo.WO_UDF_013,
    wo.WO_UDF_014,
    wo.WO_UDF_015,
    wo.WO_UDF_016,
    wo.WO_UDF_017,
    wo.WO_UDF_018,
    wo.SDF_WOO_001,
    wo.SDF_WOO_002,
    wo.SDF_WOO_003,
    wo.SDF_WOO_004,
    wo.SDF_WOO_005,
    wo.SDF_WOO_006,
    wo.SDF_WOO_007,
    wo.SDF_WOO_008,
    wo.SDF_WOO_009,
    wo.SDF_WOO_010,
    wo.SDF_WOO_012,
    wo.SDF_WOO_013,
    wo.SDF_WOO_014
FROM QCTL.WO_OPERATION wo
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER",
    ];
}

function quantumManagerWoDateFieldsQuery(array $params): array
{
    $woNumbers = normalizeManagerWoList($params);
    if ($woNumbers === []) {
        throw new RuntimeException('Missing --wos or --wo value for --mode=wo-date-fields');
    }

    $woList = implode(', ', array_map(static fn(string $wo): string => quoteManagerSqlString($wo), $woNumbers));

    return [
        'name' => 'wo-date-fields',
        'filename_prefix' => 'quantum_manager_wo_date_fields',
        'columns' => [
            'WO_NUMBER',
            'WOO_AUTO_KEY',
            'ENTRY_DATE',
            'DUE_DATE',
            'CLOSE_DATE',
            'BASELINE_COMPL_DATE',
            'MANUAL_ECD',
            'WO_START',
            'QUOTE_STATUS_DATE',
            'AC_DEPARTURE_DATE',
            'LAST_STATUS_CHG',
            'PRINT_DATE_PT',
            'RELEASE_DATE',
            'CONTRACT_REL_DATE',
            'CUSTOMER_DATE',
            'AC_SCHED_IN_DATE',
            'AC_ACT_IN_DATE',
            'INVT_DRAW_DATE',
            'AC_SCHED_OUT_DATE',
            'MSG_RFQ_REC_DATE',
            'MSG_SHIP_FROM_CUST_DATE',
            'MSG_COMPONENT_REC_DATE',
            'MSG_QUOTE_SENT_DATE',
            'MSG_QUOTE_APPR_DATE',
            'MSG_QUOTE_REJECT_DATE',
            'MSG_SHIP_TO_CUST_DATE',
            'PLAN_DUE_DATE',
            'PLAN_START_DATE',
            'CLOSE_DATE_ORIG',
            'RMI_START_TIME',
            'RMI_STOP_TIME',
            'CAMP_CLOSED_DATE',
            'MSG_WO_INVOICE_SENT_DATE',
            'MSG_COMPONENT_REC_CUST_DATE',
            'MSG_EST_SHIP_DATE',
            'MSG_WO_INVOICE_REC_DATE',
            'MSG_ACK_SENT_DATE',
            'MSG_INVOICE_ACK_REC_DATE',
        ],
        'binds' => [],
        'sql' => "
SELECT
    wo.SI_NUMBER AS WO_NUMBER,
    wo.WOO_AUTO_KEY,
    TO_CHAR(wo.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
    TO_CHAR(wo.DUE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS DUE_DATE,
    TO_CHAR(wo.CLOSE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS CLOSE_DATE,
    TO_CHAR(wo.BASELINE_COMPL_DATE, 'YYYY-MM-DD HH24:MI:SS') AS BASELINE_COMPL_DATE,
    TO_CHAR(wo.MANUAL_ECD, 'YYYY-MM-DD HH24:MI:SS') AS MANUAL_ECD,
    TO_CHAR(wo.WO_START, 'YYYY-MM-DD HH24:MI:SS') AS WO_START,
    TO_CHAR(wo.QUOTE_STATUS_DATE, 'YYYY-MM-DD HH24:MI:SS') AS QUOTE_STATUS_DATE,
    TO_CHAR(wo.AC_DEPARTURE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS AC_DEPARTURE_DATE,
    TO_CHAR(wo.LAST_STATUS_CHG, 'YYYY-MM-DD HH24:MI:SS') AS LAST_STATUS_CHG,
    TO_CHAR(wo.PRINT_DATE_PT, 'YYYY-MM-DD HH24:MI:SS') AS PRINT_DATE_PT,
    TO_CHAR(wo.RELEASE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS RELEASE_DATE,
    TO_CHAR(wo.CONTRACT_REL_DATE, 'YYYY-MM-DD HH24:MI:SS') AS CONTRACT_REL_DATE,
    TO_CHAR(wo.CUSTOMER_DATE, 'YYYY-MM-DD HH24:MI:SS') AS CUSTOMER_DATE,
    TO_CHAR(wo.AC_SCHED_IN_DATE, 'YYYY-MM-DD HH24:MI:SS') AS AC_SCHED_IN_DATE,
    TO_CHAR(wo.AC_ACT_IN_DATE, 'YYYY-MM-DD HH24:MI:SS') AS AC_ACT_IN_DATE,
    TO_CHAR(wo.INVT_DRAW_DATE, 'YYYY-MM-DD HH24:MI:SS') AS INVT_DRAW_DATE,
    TO_CHAR(wo.AC_SCHED_OUT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS AC_SCHED_OUT_DATE,
    TO_CHAR(wo.MSG_RFQ_REC_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_RFQ_REC_DATE,
    TO_CHAR(wo.MSG_SHIP_FROM_CUST_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_SHIP_FROM_CUST_DATE,
    TO_CHAR(wo.MSG_COMPONENT_REC_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_COMPONENT_REC_DATE,
    TO_CHAR(wo.MSG_QUOTE_SENT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_SENT_DATE,
    TO_CHAR(wo.MSG_QUOTE_APPR_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_APPR_DATE,
    TO_CHAR(wo.MSG_QUOTE_REJECT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_QUOTE_REJECT_DATE,
    TO_CHAR(wo.MSG_SHIP_TO_CUST_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_SHIP_TO_CUST_DATE,
    TO_CHAR(wo.PLAN_DUE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS PLAN_DUE_DATE,
    TO_CHAR(wo.PLAN_START_DATE, 'YYYY-MM-DD HH24:MI:SS') AS PLAN_START_DATE,
    TO_CHAR(wo.CLOSE_DATE_ORIG, 'YYYY-MM-DD HH24:MI:SS') AS CLOSE_DATE_ORIG,
    TO_CHAR(wo.RMI_START_TIME, 'YYYY-MM-DD HH24:MI:SS') AS RMI_START_TIME,
    TO_CHAR(wo.RMI_STOP_TIME, 'YYYY-MM-DD HH24:MI:SS') AS RMI_STOP_TIME,
    TO_CHAR(wo.CAMP_CLOSED_DATE, 'YYYY-MM-DD HH24:MI:SS') AS CAMP_CLOSED_DATE,
    TO_CHAR(wo.MSG_WO_INVOICE_SENT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_WO_INVOICE_SENT_DATE,
    TO_CHAR(wo.MSG_COMPONENT_REC_CUST_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_COMPONENT_REC_CUST_DATE,
    TO_CHAR(wo.MSG_EST_SHIP_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_EST_SHIP_DATE,
    TO_CHAR(wo.MSG_WO_INVOICE_REC_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_WO_INVOICE_REC_DATE,
    TO_CHAR(wo.MSG_ACK_SENT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_ACK_SENT_DATE,
    TO_CHAR(wo.MSG_INVOICE_ACK_REC_DATE, 'YYYY-MM-DD HH24:MI:SS') AS MSG_INVOICE_ACK_REC_DATE
FROM QCTL.WO_OPERATION wo
WHERE UPPER(wo.SI_NUMBER) IN ({$woList})
ORDER BY wo.SI_NUMBER",
    ];
}

function normalizeManagerWoList(array $params): array
{
    $values = [];

    if (!empty($params['wos'])) {
        $values = preg_split('/[,\s]+/', (string)$params['wos'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
    } elseif (!empty($params['wo_number'])) {
        $values = [(string)$params['wo_number']];
    }

    $woNumbers = [];
    foreach ($values as $value) {
        $woNumber = normalizeManagerWoNumber($value);
        if ($woNumber !== '') {
            $woNumbers[] = $woNumber;
        }
    }

    return array_values(array_unique($woNumbers));
}

function quoteManagerSqlString(string $value): string
{
    return "'" . str_replace("'", "''", $value) . "'";
}

function quantumManagerWoSearchQuery(array $params): array
{
    $ref = trim((string)($params['ref'] ?? ''));
    $pn = strtoupper(trim((string)($params['pn'] ?? '')));
    $limit = managerLimit($params);

    if ($ref === '' && $pn === '') {
        throw new RuntimeException('Missing --ref or --pn value for --mode=wo-search');
    }

    $where = [];
    $binds = [
        ':limit_rows' => $limit,
    ];

    if ($ref !== '') {
        $where[] = "(
            UPPER(wo.SI_NUMBER) LIKE UPPER(:ref_like)
            OR UPPER(wo.COMPANY_REF_NUMBER) LIKE UPPER(:ref_like)
            OR UPPER(wo.BILL_NAME) LIKE UPPER(:ref_like)
            OR UPPER(wo.SHIP_NAME) LIKE UPPER(:ref_like)
        )";
        $binds[':ref_like'] = '%' . $ref . '%';
    }

    if ($pn !== '') {
        $where[] = "UPPER(pm.PN) = :pn";
        $binds[':pn'] = $pn;
    }

    return [
        'name' => 'wo-search',
        'filename_prefix' => 'quantum_manager_wo_search',
        'columns' => [
            'WOO_AUTO_KEY',
            'WO_NUMBER',
            'WO_STATUS',
            'COMPANY_REF_NUMBER',
            'PN',
            'DESCRIPTION',
            'BILL_NAME',
            'SHIP_NAME',
            'ENTRY_DATE',
            'CLOSE_DATE',
            'QUOTE_STATUS',
            'QUOTE_STATUS_DATE',
            'EST_TOTAL_COST',
            'EI_TOTAL_COST',
        ],
        'binds' => $binds,
        'sql' => "
SELECT *
FROM (
    SELECT
        wo.WOO_AUTO_KEY,
        wo.SI_NUMBER AS WO_NUMBER,
        wo.WO_DISP AS WO_STATUS,
        wo.COMPANY_REF_NUMBER,
        pm.PN,
        pm.DESCRIPTION,
        wo.BILL_NAME,
        wo.SHIP_NAME,
        TO_CHAR(wo.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
        TO_CHAR(wo.CLOSE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS CLOSE_DATE,
        wo.QUOTE_STATUS,
        TO_CHAR(wo.QUOTE_STATUS_DATE, 'YYYY-MM-DD HH24:MI:SS') AS QUOTE_STATUS_DATE,
        (
            NVL(wo.EST_PARTS_COST, 0)
            + NVL(wo.EST_LABOR_COST, 0)
            + NVL(wo.EST_VO_COST, 0)
            + NVL(wo.EST_FO_COST, 0)
            + NVL(wo.EST_OSV_COST, 0)
        ) AS EST_TOTAL_COST,
        (
            NVL(wo.EI_TOT_PARTS_COST, 0)
            + NVL(wo.EI_TOT_LABOR_COST, 0)
            + NVL(wo.EI_TOT_VO_COST, 0)
            + NVL(wo.EI_TOT_FO_COST, 0)
            + NVL(wo.EI_TOT_OSV_COST, 0)
        ) AS EI_TOTAL_COST
    FROM QCTL.WO_OPERATION wo
    LEFT JOIN QCTL.PARTS_MASTER pm
      ON pm.PNM_AUTO_KEY = wo.PNM_AUTO_KEY
    WHERE " . implode("\n      AND ", $where) . "
    ORDER BY wo.ENTRY_DATE DESC NULLS LAST, wo.SI_NUMBER
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerPartSearchQuery(array $params): array
{
    $pn = strtoupper(trim((string)($params['pn'] ?? '')));
    $limit = managerLimit($params);

    if ($pn === '') {
        throw new RuntimeException('Missing --pn value for --mode=part-search');
    }

    return [
        'name' => 'part-search',
        'filename_prefix' => 'quantum_manager_part_search',
        'columns' => [
            'PNM_AUTO_KEY',
            'PN',
            'PN_STRIPPED',
            'PN_UPPER',
            'DESCRIPTION',
            'DESCRIPTION_UPPER',
        ],
        'binds' => [
            ':pn' => $pn,
            ':pn_like' => '%' . $pn . '%',
            ':limit_rows' => $limit,
        ],
        'sql' => "
SELECT *
FROM (
    SELECT
        pm.PNM_AUTO_KEY,
        pm.PN,
        pm.PN_STRIPPED,
        pm.PN_UPPER,
        pm.DESCRIPTION,
        pm.DESCRIPTION_UPPER
    FROM QCTL.PARTS_MASTER pm
    WHERE UPPER(pm.PN) = :pn
       OR UPPER(pm.PN_STRIPPED) = REPLACE(:pn, '-', '')
       OR UPPER(pm.PN_UPPER) = :pn
       OR UPPER(pm.PN) LIKE :pn_like
    ORDER BY
        CASE
            WHEN UPPER(pm.PN) = :pn THEN 1
            WHEN UPPER(pm.PN_STRIPPED) = REPLACE(:pn, '-', '') THEN 2
            WHEN UPPER(pm.PN_UPPER) = :pn THEN 3
            ELSE 4
        END,
        pm.PN
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerRoSearchQuery(array $params): array
{
    $ref = trim((string)($params['ref'] ?? ''));
    $limit = managerLimit($params);

    if ($ref === '') {
        throw new RuntimeException('Missing --ref value for --mode=ro-search');
    }

    return [
        'name' => 'ro-search',
        'filename_prefix' => 'quantum_manager_ro_search',
        'columns' => [
            'ROH_AUTO_KEY',
            'RO_NUMBER',
            'VENDOR_NAME',
            'ENTRY_DATE',
            'OUT_DATE',
            'OPEN_FLAG',
            'LAST_MODIFIED',
        ],
        'binds' => [
            ':ref_like' => '%' . $ref . '%',
            ':limit_rows' => $limit,
        ],
        'sql' => "
SELECT *
FROM (
    SELECT
        rh.ROH_AUTO_KEY,
        rh.RO_NUMBER,
        rh.VENDOR_NAME,
        TO_CHAR(rh.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
        TO_CHAR(rh.OUT_DATE, 'YYYY-MM-DD HH24:MI:SS') AS OUT_DATE,
        rh.OPEN_FLAG,
        TO_CHAR(rh.LAST_MODIFIED, 'YYYY-MM-DD HH24:MI:SS') AS LAST_MODIFIED
    FROM QCTL.RO_HEADER rh
    WHERE UPPER(rh.RO_NUMBER) LIKE UPPER(:ref_like)
       OR UPPER(rh.VENDOR_NAME) LIKE UPPER(:ref_like)
    ORDER BY rh.ENTRY_DATE DESC NULLS LAST, rh.RO_NUMBER
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function quantumManagerEstimateSearchQuery(array $params): array
{
    $amount = trim((string)($params['amount'] ?? ''));
    $date = normalizeManagerDateParam((string)($params['date'] ?? ''));
    $limit = managerLimit($params);

    if ($amount === '' && $date === '') {
        throw new RuntimeException('Missing --amount or --date value for --mode=estimate-search');
    }

    $where = [];
    $binds = [
        ':limit_rows' => $limit,
    ];

    $estimateTotalExpr = "(
        NVL(wo.EST_PARTS_COST, 0)
        + NVL(wo.EST_LABOR_COST, 0)
        + NVL(wo.EST_VO_COST, 0)
        + NVL(wo.EST_FO_COST, 0)
        + NVL(wo.EST_OSV_COST, 0)
    )";

    $eiTotalExpr = "(
        NVL(wo.EI_TOT_PARTS_COST, 0)
        + NVL(wo.EI_TOT_LABOR_COST, 0)
        + NVL(wo.EI_TOT_VO_COST, 0)
        + NVL(wo.EI_TOT_FO_COST, 0)
        + NVL(wo.EI_TOT_OSV_COST, 0)
    )";

    if ($amount !== '') {
        $numericAmount = (float)str_replace([',', '$', ' '], '', $amount);
        $where[] = "({$estimateTotalExpr} BETWEEN :amount_min AND :amount_max OR {$eiTotalExpr} BETWEEN :amount_min AND :amount_max)";
        $binds[':amount_min'] = $numericAmount - 0.01;
        $binds[':amount_max'] = $numericAmount + 0.01;
    }

    if ($date !== '') {
        $where[] = "(
            TRUNC(wo.QUOTE_STATUS_DATE) = TO_DATE(:search_date, 'YYYY-MM-DD')
            OR TRUNC(wo.ENTRY_DATE) = TO_DATE(:search_date, 'YYYY-MM-DD')
            OR TRUNC(wo.CLOSE_DATE) = TO_DATE(:search_date, 'YYYY-MM-DD')
        )";
        $binds[':search_date'] = $date;
    }

    return [
        'name' => 'estimate-search',
        'filename_prefix' => 'quantum_manager_estimate_search',
        'columns' => [
            'WOO_AUTO_KEY',
            'WO_NUMBER',
            'WO_STATUS',
            'COMPANY_REF_NUMBER',
            'PN',
            'DESCRIPTION',
            'BILL_NAME',
            'ENTRY_DATE',
            'CLOSE_DATE',
            'QUOTE_STATUS',
            'QUOTE_STATUS_DATE',
            'EST_TOTAL_COST',
            'EI_TOTAL_COST',
        ],
        'binds' => $binds,
        'sql' => "
SELECT *
FROM (
    SELECT
        wo.WOO_AUTO_KEY,
        wo.SI_NUMBER AS WO_NUMBER,
        wo.WO_DISP AS WO_STATUS,
        wo.COMPANY_REF_NUMBER,
        pm.PN,
        pm.DESCRIPTION,
        wo.BILL_NAME,
        TO_CHAR(wo.ENTRY_DATE, 'YYYY-MM-DD HH24:MI:SS') AS ENTRY_DATE,
        TO_CHAR(wo.CLOSE_DATE, 'YYYY-MM-DD HH24:MI:SS') AS CLOSE_DATE,
        wo.QUOTE_STATUS,
        TO_CHAR(wo.QUOTE_STATUS_DATE, 'YYYY-MM-DD HH24:MI:SS') AS QUOTE_STATUS_DATE,
        {$estimateTotalExpr} AS EST_TOTAL_COST,
        {$eiTotalExpr} AS EI_TOTAL_COST
    FROM QCTL.WO_OPERATION wo
    LEFT JOIN QCTL.PARTS_MASTER pm
      ON pm.PNM_AUTO_KEY = wo.PNM_AUTO_KEY
    WHERE " . implode("\n      AND ", $where) . "
    ORDER BY wo.ENTRY_DATE DESC NULLS LAST, wo.SI_NUMBER
)
FETCH FIRST :limit_rows ROWS ONLY",
    ];
}

function normalizeManagerDateParam(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        throw new RuntimeException('Invalid --date value. Use YYYY-MM-DD or dd/mmm/yyyy');
    }

    return date('Y-m-d', $timestamp);
}

function managerLimit(array $params): int
{
    return max(1, min(50000, (int)($params['limit'] ?? 5000)));
}

function managerWoRangeNumber(mixed $value): ?int
{
    $digits = preg_replace('/[^0-9]/', '', strtoupper(trim((string)$value)));
    if ($digits === '') {
        return null;
    }

    return (int)$digits;
}

function normalizeManagerWoNumber(string $value): string
{
    $value = strtoupper(trim($value));

    if (preg_match('/^[0-9]+$/', $value)) {
        return 'W' . $value;
    }

    return $value;
}
