<?php
// Read-only Oracle export. Without --send, writes only the requested local JSON file.
date_default_timezone_set('America/Toronto');
$options = getopt('', ['output:', 'send']);
$conn = oci_connect(getenv('ORACLE_USER'), getenv('ORACLE_PASS'), getenv('ORACLE_DSN') ?: 'MAXQPROD', 'AL32UTF8');
if (!$conn) { fwrite(STDERR, "Quantum connection failed\n"); exit(1); }
$sql = <<<'SQL'
SELECT wo.WOO_AUTO_KEY, wo.SI_NUMBER, pm.PN, pm.DESCRIPTION, wo.BILL_NAME,
       TO_CHAR(wo.ENTRY_DATE, 'YYYY-MM-DD') OPEN_DATE,
       TO_CHAR(wo.CLOSE_DATE, 'YYYY-MM-DD') CLOSE_DATE,
       wo.WO_DISP, wo.KIT_QTY, wo.WOO_PARENT
FROM QCTL.WO_OPERATION wo
LEFT JOIN QCTL.PARTS_MASTER pm ON pm.PNM_AUTO_KEY = wo.PNM_AUTO_KEY
ORDER BY wo.WOO_AUTO_KEY
SQL;
function historyQuery($conn, string $sql): array {
    $statement = oci_parse($conn, $sql);
    if (!oci_execute($statement, OCI_NO_AUTO_COMMIT)) { throw new RuntimeException('Quantum SELECT failed'); }
    $rows = [];
    while ($row = oci_fetch_assoc($statement)) { $rows[] = $row; }
    oci_free_statement($statement);
    return $rows;
}
try {
    $components = [];
    foreach (historyQuery($conn, 'SELECT WOO_AUTO_KEY, SERIAL_NUMBER, QTY FROM QCTL.VIEW_SPB_WO_MAINCOMPONENT') as $component) {
        $components[$component['WOO_AUTO_KEY']][] = $component;
    }
    $rows = [];
    foreach (historyQuery($conn, $sql) as $wo) {
        $main = $components[$wo['WOO_AUTO_KEY']] ?? [];
        $serials = array_values(array_filter(array_map(fn ($r) => trim((string) $r['SERIAL_NUMBER']), $main)));
        $quantity = $wo['KIT_QTY'] === null ? null : (float) $wo['KIT_QTY'];
        $mainQuantity = array_sum(array_map(fn ($r) => (float) $r['QTY'], $main));
        $review = $quantity === null || $quantity <= 0 || ($quantity > 1 && (
            $mainQuantity != $quantity || count($serials) !== count(array_unique($serials))
            || count($main) > 1
        ));
        $rows[] = [
            'source_id' => (int) $wo['WOO_AUTO_KEY'], 'wo_number' => trim((string) $wo['SI_NUMBER']),
            'part_number' => trim((string) $wo['PN']), 'description' => trim((string) $wo['DESCRIPTION']),
            'customer' => trim((string) $wo['BILL_NAME']),
            'opened_at' => $wo['OPEN_DATE'], 'closed_at' => $wo['CLOSE_DATE'],
            'status' => trim((string) $wo['WO_DISP']), 'quantity' => $quantity,
            'serials' => implode(', ', array_unique($serials)), 'quantity_review' => $review,
            'parent_id' => $wo['WOO_PARENT'] === null ? null : (int) $wo['WOO_PARENT'],
        ];
    }
    oci_close($conn);
    $payload = json_encode(['version' => 1, 'exported_at' => gmdate('c'), 'row_count' => count($rows), 'rows' => $rows], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    if (isset($options['output'])) {
        if (file_put_contents($options['output'], $payload, LOCK_EX) === false) { throw new RuntimeException('Cannot write export'); }
    }
    if (isset($options['send'])) {
        $url = getenv('AVIA_UNIT_HISTORY_API_URL');
        if (!$url && preg_match('~/ro-sync/?$~', (string) getenv('AVIA_SYNC_API_URL'))) {
            $url = preg_replace('~/ro-sync/?$~', '/unit-history', getenv('AVIA_SYNC_API_URL'));
        }
        $token = getenv('AVIA_SYNC_API_TOKEN');
        if (!$url || !$token || parse_url($url, PHP_URL_SCHEME) !== 'https') { throw new RuntimeException('Set HTTPS AVIA_UNIT_HISTORY_API_URL and AVIA_SYNC_API_TOKEN'); }
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
            CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 120]);
        $result = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($status !== 200 || $result === false) { throw new RuntimeException('History sync failed: HTTP ' . $status); }
    }
    if (!isset($options['output']) && !isset($options['send'])) { throw new RuntimeException('Use --output=path or --send'); }
    echo count($rows) . " Quantum WO rows exported\n";
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . "\n"); exit(1); }
