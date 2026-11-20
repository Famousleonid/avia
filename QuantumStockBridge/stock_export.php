<?php
// Independent stock bridge. Quantum is SELECT-only; existing RO/WO sync is not invoked.
date_default_timezone_set('America/Toronto');
$options = defined('STOCK_STREAM') ? ['stream' => true] : getopt('', ['output:', 'send']);
function stockEvent(array $event): void {
    echo json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . "\n";
    if (ob_get_level()) { ob_flush(); } flush();
}
function stockHttp(string $url, ?string $body = null): array {
    $token = getenv('AVIA_SYNC_API_TOKEN');
    if (!$token || parse_url($url, PHP_URL_SCHEME) !== 'https') { throw new RuntimeException('Stock API requires HTTPS and a token'); }
    $curl = curl_init($url);
    $settings = [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token]];
    if ($body !== null) { $settings[CURLOPT_POST] = true; $settings[CURLOPT_POSTFIELDS] = $body; }
    curl_setopt_array($curl, $settings);
    $result = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
    if ($result === false || $status !== 200) { throw new RuntimeException('Stock API HTTP ' . $status); }
    return json_decode($result, true, 512, JSON_THROW_ON_ERROR);
}
try {
    $url = getenv('AVIA_STOCK_API_URL');
    if (!$url && preg_match('~/ro-sync/?$~', (string) getenv('AVIA_SYNC_API_URL'))) {
        $url = preg_replace('~/ro-sync/?$~', '/stock', getenv('AVIA_SYNC_API_URL'));
    }
    if (!isset($options['stream']) && !isset($options['send']) && !isset($options['output'])) { throw new RuntimeException('Use --send or --output'); }
    if (isset($options['stream'])) stockEvent(['type'=>'progress','stage'=>'Connecting to Quantum','count'=>0]);
    $exportedAt = gmdate('c');
    $conn = @oci_connect(getenv('ORACLE_USER'), getenv('ORACLE_PASS'), getenv('ORACLE_DSN') ?: 'MAXQPROD', 'AL32UTF8');
    if (!$conn) { throw new RuntimeException('Quantum connection failed'); }
    // One SELECT gives quantities and allocations the same Oracle statement snapshot.
    $sql = file_get_contents(__DIR__.'/stock_query.sql');
    $statement = oci_parse($conn, $sql);
    if (!@oci_execute($statement, OCI_NO_AUTO_COMMIT)) { throw new RuntimeException('Quantum stock SELECT failed'); }
    $rows = [];
    while ($row = oci_fetch_assoc($statement)) {
        $id=(int)$row['PNM_AUTO_KEY'];
        if (!isset($rows[$id])) {
        $rows[$id] = ['source_id' => $id, 'part_number' => trim((string) $row['PN']),
            'description' => trim((string) $row['DESCRIPTION']), 'on_hand' => (float) $row['QTY_OH'],
            'reserved' => (float) $row['QTY_RESERVED'], 'available' => (float) $row['QTY_AVAILABLE'],
            'reservations'=>[], 'unassigned_reserved'=>0];
        if (isset($options['stream']) && count($rows) % 500 === 0) stockEvent(['type'=>'progress','stage'=>'Reading Quantum','count'=>count($rows)]);
        }
        if ((float)($row['ALLOCATED_QTY']??0)>0) {
            if (!empty($row['WO_NUMBER'])) $rows[$id]['reservations'][]=['wo'=>trim($row['WO_NUMBER']),'quantity'=>(float)$row['ALLOCATED_QTY']];
            else $rows[$id]['unassigned_reserved']+=(float)$row['ALLOCATED_QTY'];
        }
    }
    if (oci_error($statement)) { throw new RuntimeException('Incomplete Quantum stock fetch refused'); }
    oci_free_statement($statement); oci_close($conn);
    if (!$rows) { throw new RuntimeException('Empty stock export refused'); }
    $payload = json_encode(['version' => 2, 'exported_at' => $exportedAt, 'row_count' => count($rows), 'rows' => array_values($rows)], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    if (isset($options['stream'])) { stockEvent(['type'=>'snapshot','payload'=>json_decode($payload,true)]); exit(0); }
    if (isset($options['output']) && file_put_contents($options['output'], $payload, LOCK_EX) === false) { throw new RuntimeException('Cannot write export'); }
    if (isset($options['send'])) {
        $receipt = stockHttp((string) $url, $payload);
        if (($receipt['success'] ?? false) !== true || (int) ($receipt['rows'] ?? -1) !== count($rows)) {
            throw new RuntimeException('Stock API did not confirm the complete snapshot');
        }
    }
    echo count($rows) . " Quantum stock rows exported\n";
} catch (Throwable $e) {
    if (isset($options['stream'])) { stockEvent(['type'=>'error','message'=>'Quantum stock export failed; previous snapshot preserved.']); }
    else { fwrite(STDERR, $e->getMessage() . "\n"); }
    exit(1);
}
