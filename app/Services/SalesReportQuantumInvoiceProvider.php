<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Throwable;

class SalesReportQuantumInvoiceProvider
{
    /**
     * @param list<string> $woNumbers
     * @return array{available: bool, warning: ?string, items: array<string, array<string, mixed>>}
     */
    public function fetch(array $woNumbers, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $woNumbers = $this->normalizeWoNumbers($woNumbers);

        if ($woNumbers === []) {
            return ['available' => false, 'warning' => null, 'items' => []];
        }

        if (! function_exists('oci_connect')) {
            return [
                'available' => false,
                'warning' => 'Quantum OCI8 extension is not loaded; invoice amounts are unavailable.',
                'items' => [],
            ];
        }

        $user = env('QUANTUM_MANAGER_ORACLE_USER') ?: env('ORACLE_USER');
        $pass = env('QUANTUM_MANAGER_ORACLE_PASS') ?: env('ORACLE_PASS');
        $dsn = env('QUANTUM_MANAGER_ORACLE_DSN') ?: env('ORACLE_DSN') ?: 'MAXQPROD';

        if (! $user || ! $pass) {
            return [
                'available' => false,
                'warning' => 'Quantum credentials are not configured; invoice amounts are unavailable.',
                'items' => [],
            ];
        }

        $conn = @oci_connect($user, $pass, $dsn, 'AL32UTF8');
        if (! $conn) {
            $error = oci_error();

            return [
                'available' => false,
                'warning' => 'Quantum connection failed: ' . ($error['message'] ?? 'unknown error'),
                'items' => [],
            ];
        }

        try {
            $sql = $this->invoiceSql($woNumbers);
            $stid = oci_parse($conn, $sql);

            $dateFrom = $from?->format('Y-m-d') ?? '';
            $dateToNext = $to?->copy()->addDay()->format('Y-m-d') ?? '';

            oci_bind_by_name($stid, ':date_from', $dateFrom);
            oci_bind_by_name($stid, ':date_to_next', $dateToNext);

            if (! @oci_execute($stid, OCI_NO_AUTO_COMMIT)) {
                $error = oci_error($stid);

                return [
                    'available' => false,
                    'warning' => 'Quantum invoice query failed: ' . ($error['message'] ?? 'unknown error'),
                    'items' => [],
                ];
            }

            $items = [];
            while (($row = oci_fetch_assoc($stid)) !== false) {
                $woNumber = strtoupper((string) ($row['WO_NUMBER'] ?? ''));
                if ($woNumber === '') {
                    continue;
                }

                $items[$woNumber] = [
                    'amount' => isset($row['INVOICED_AMOUNT']) ? (float) $row['INVOICED_AMOUNT'] : null,
                    'invoice_date' => $this->parseQuantumDate($row['INVOICE_DATE'] ?? null),
                    'invoice_numbers' => (string) ($row['INVOICE_NUMBERS'] ?? ''),
                    'source' => (string) ($row['SOURCE'] ?? ''),
                ];
            }

            return ['available' => true, 'warning' => null, 'items' => $items];
        } catch (Throwable $e) {
            return [
                'available' => false,
                'warning' => 'Quantum invoice lookup failed: ' . $e->getMessage(),
                'items' => [],
            ];
        } finally {
            @oci_close($conn);
        }
    }

    /**
     * @param list<string> $woNumbers
     * @return list<string>
     */
    private function normalizeWoNumbers(array $woNumbers): array
    {
        $normalized = [];

        foreach ($woNumbers as $number) {
            $value = strtoupper(trim((string) $number));
            if ($value === '') {
                continue;
            }

            if (! str_starts_with($value, 'W')) {
                $value = 'W' . $value;
            }

            if (preg_match('/^W\d+$/', $value)) {
                $normalized[$value] = $value;
            }
        }

        return array_values($normalized);
    }

    /**
     * @param list<string> $woNumbers
     */
    private function invoiceSql(array $woNumbers): string
    {
        $targetRows = implode("\nUNION ALL\n", array_map(
            static fn (string $number): string => "    SELECT '" . str_replace("'", "''", $number) . "' AS wo_number FROM dual",
            $woNumbers
        ));

        return "
WITH target_wos AS (
{$targetRows}
),
base_wo AS (
    SELECT
        wo.WOO_AUTO_KEY,
        wo.SI_NUMBER AS WO_NUMBER,
        wo.COMPANY_REF_NUMBER
    FROM QCTL.WO_OPERATION wo
    JOIN target_wos target
      ON UPPER(wo.SI_NUMBER) = target.wo_number
),
billing_rows AS (
    SELECT
        base.WO_NUMBER,
        v.INVC_NUMBER AS INVOICE_NUMBER,
        v.POST_DATE AS INVOICE_DATE,
        v.TOTAL_REVENUE AS AMOUNT,
        'VIEW_WO_BILLING' AS SOURCE
    FROM base_wo base
    JOIN QCTL.VIEW_WO_BILLING v
      ON v.WOO_AUTO_KEY = base.WOO_AUTO_KEY
    WHERE v.INVC_NUMBER IS NOT NULL
      AND (:date_from IS NULL OR v.POST_DATE >= TO_DATE(:date_from, 'YYYY-MM-DD'))
      AND (:date_to_next IS NULL OR v.POST_DATE < TO_DATE(:date_to_next, 'YYYY-MM-DD'))
),
direct_invoice_rows AS (
    SELECT
        base.WO_NUMBER,
        ih.INVC_NUMBER AS INVOICE_NUMBER,
        COALESCE(ih.INVOICE_DATE, ih.POST_DATE) AS INVOICE_DATE,
        ih.TOTAL_PRICE AS AMOUNT,
        'INVC_HEADER.WOO_AUTO_KEY' AS SOURCE
    FROM base_wo base
    JOIN QCTL.INVC_HEADER ih
      ON ih.WOO_AUTO_KEY = base.WOO_AUTO_KEY
    WHERE ih.INVC_NUMBER IS NOT NULL
      AND (:date_from IS NULL OR COALESCE(ih.INVOICE_DATE, ih.POST_DATE) >= TO_DATE(:date_from, 'YYYY-MM-DD'))
      AND (:date_to_next IS NULL OR COALESCE(ih.INVOICE_DATE, ih.POST_DATE) < TO_DATE(:date_to_next, 'YYYY-MM-DD'))
),
po_invoice_rows AS (
    SELECT
        base.WO_NUMBER,
        ih.INVC_NUMBER AS INVOICE_NUMBER,
        COALESCE(ih.INVOICE_DATE, ih.POST_DATE) AS INVOICE_DATE,
        ih.TOTAL_PRICE AS AMOUNT,
        'INVC_HEADER.COMPANY_PO_NUMBER' AS SOURCE
    FROM base_wo base
    JOIN QCTL.INVC_HEADER ih
      ON REGEXP_REPLACE(UPPER(ih.COMPANY_PO_NUMBER), '[^A-Z0-9]', '') =
         REGEXP_REPLACE(UPPER(base.COMPANY_REF_NUMBER), '[^A-Z0-9]', '')
    WHERE ih.INVC_NUMBER IS NOT NULL
      AND ih.COMPANY_PO_NUMBER IS NOT NULL
      AND base.COMPANY_REF_NUMBER IS NOT NULL
      AND (:date_from IS NULL OR COALESCE(ih.INVOICE_DATE, ih.POST_DATE) >= TO_DATE(:date_from, 'YYYY-MM-DD'))
      AND (:date_to_next IS NULL OR COALESCE(ih.INVOICE_DATE, ih.POST_DATE) < TO_DATE(:date_to_next, 'YYYY-MM-DD'))
),
chosen_rows AS (
    SELECT * FROM billing_rows
    UNION ALL
    SELECT direct_rows.*
    FROM direct_invoice_rows direct_rows
    WHERE NOT EXISTS (
        SELECT 1 FROM billing_rows b WHERE b.WO_NUMBER = direct_rows.WO_NUMBER
    )
    UNION ALL
    SELECT po_rows.*
    FROM po_invoice_rows po_rows
    WHERE NOT EXISTS (
        SELECT 1 FROM billing_rows b WHERE b.WO_NUMBER = po_rows.WO_NUMBER
    )
      AND NOT EXISTS (
        SELECT 1 FROM direct_invoice_rows d WHERE d.WO_NUMBER = po_rows.WO_NUMBER
    )
)
SELECT
    WO_NUMBER,
    LISTAGG(INVOICE_NUMBER, ', ') WITHIN GROUP (ORDER BY INVOICE_DATE, INVOICE_NUMBER) AS INVOICE_NUMBERS,
    MAX(INVOICE_DATE) AS INVOICE_DATE,
    SUM(NVL(AMOUNT, 0)) AS INVOICED_AMOUNT,
    MIN(SOURCE) AS SOURCE
FROM chosen_rows
GROUP BY WO_NUMBER
ORDER BY WO_NUMBER";
    }

    private function parseQuantumDate(mixed $value): ?CarbonImmutable
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($raw);
        } catch (Throwable) {
            return null;
        }
    }
}
