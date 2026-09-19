<?php

namespace App\Services;

use InvalidArgumentException;

/** Offline handoff only: this class never opens a database connection. */
class InProcessCheckSheetSql
{
    public function build(array $content, int $manualId, string $manualNumber, ?string $expectedHash = null): array
    {
        if ($manualId < 1 || trim($manualNumber) === '' || ! empty($content['issues'])
            || empty($content['rows']) || ($content['schema_version'] ?? null) !== 1
            || ! preg_match('/^[a-f0-9]{64}$/', $content['source_sha256'] ?? '')
            || ($expectedHash !== null && ! preg_match('/^[a-f0-9]{64}$/', $expectedHash))) {
            throw new InvalidArgumentException('Review source issues, manual identity and expected hash before SQL generation.');
        }
        $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $hash = hash('sha256', $json);
        $literal = fn (string $text) => "CONVERT(0x".bin2hex($text)." USING utf8mb4)";
        $number = $literal($manualNumber);
        $file = $literal($content['source_file']);
        $sheet = $literal($content['source_sheet']);
        $sourceHash = $content['source_sha256'];
        $payload = $literal($json);
        $old = $expectedHash ? " OR content_sha256 = '$expectedHash'" : '';
        // MySQL stores native JSON with normalized spacing/key order; MariaDB stores text.
        // Normalize BOTH operands through JSON_EXTRACT instead of hashing the DB text.
        $sameContent = "BINARY JSON_EXTRACT(content, '$') = BINARY JSON_EXTRACT($payload, '$')";
        $check = "EXISTS (SELECT 1 FROM manual_in_process_check_sheets WHERE manual_id = $manualId AND content_sha256 = '$hash' AND $sameContent AND source_sha256 = '$sourceHash' AND schema_version = 1)";
        $import = "-- IN PROCESS CHECK SHEET only. Run the schema migration first.\n"
            ."-- No Parts, flags, groups or WO history are changed. Unexpected templates are NOT overwritten.\n"
            ."SET NAMES utf8mb4;\nSTART TRANSACTION;\n"
            ."SET @ipcs_ready = (SELECT COUNT(*) = 1 FROM manuals WHERE id = $manualId AND BINARY number = BINARY $number AND deleted_at IS NULL)\n"
            ." AND NOT EXISTS (SELECT 1 FROM manual_in_process_check_sheets WHERE manual_id = $manualId AND NOT ((content_sha256 = '$hash' AND $sameContent)$old));\n"
            ."SELECT IF(@ipcs_ready, 'READY', 'BLOCKED: manual identity or existing template differs') AS preflight;\n"
            ."INSERT INTO manual_in_process_check_sheets (manual_id, schema_version, source_file, source_sheet, source_sha256, content_sha256, content, created_at, updated_at)\n"
            ."SELECT $manualId, 1, $file, $sheet, '$sourceHash', '$hash', $payload, NOW(), NOW() WHERE @ipcs_ready\n"
            ."ON DUPLICATE KEY UPDATE updated_at = IF(content_sha256 <> VALUES(content_sha256), VALUES(updated_at), updated_at), schema_version = VALUES(schema_version), source_file = VALUES(source_file), source_sheet = VALUES(source_sheet), source_sha256 = VALUES(source_sha256), content_sha256 = VALUES(content_sha256), content = VALUES(content);\n"
            ."SET @ipcs_pass = @ipcs_ready AND $check;\n"
            ."SET @ipcs_finish = IF(@ipcs_pass, 'COMMIT', 'ROLLBACK');\nPREPARE ipcs_finish FROM @ipcs_finish;\nEXECUTE ipcs_finish;\nDEALLOCATE PREPARE ipcs_finish;\n"
            ."SELECT IF(@ipcs_pass, 'SUCCESS', 'BLOCKED / ROLLED BACK') AS final_status;\n";
        $verify = "-- SELECT only: expected manual and exact reviewed template contents.\n"
            ."SELECT IF((SELECT COUNT(*) FROM manuals WHERE id = $manualId AND BINARY number = BINARY $number AND deleted_at IS NULL) = 1 AND $check, 'PASS', 'FAIL') AS check_sheet_verification;\n"
            ."SELECT manual_id, source_file, source_sheet, source_sha256, content_sha256, JSON_LENGTH(content, '$.rows') AS total_slots, updated_at FROM manual_in_process_check_sheets WHERE manual_id = $manualId;\n";
        return ['import' => $import, 'verify' => $verify, 'content_sha256' => $hash];
    }
}
