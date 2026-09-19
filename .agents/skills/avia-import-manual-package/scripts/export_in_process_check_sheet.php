<?php

declare(strict_types=1);

require dirname(__DIR__, 4).'/vendor/autoload.php';

$options = getopt('', ['workbook:', 'manual-id:', 'manual-number:', 'output-prefix:', 'expected-hash:']);
foreach (['workbook', 'manual-id', 'manual-number', 'output-prefix'] as $required) {
    if (empty($options[$required])) {
        fwrite(STDERR, "Required: --workbook --manual-id --manual-number --output-prefix; optional --expected-hash (reviewed existing template).\n");
        exit(1);
    }
}
$prefix = $options['output-prefix'];
foreach (['_source.json', '_import.sql', '_verify.sql'] as $suffix) {
    if (file_exists($prefix.$suffix)) {
        fwrite(STDERR, "Refusing to overwrite $prefix$suffix; choose a new version.\n");
        exit(1);
    }
}
if (! is_dir(dirname($prefix))) {
    mkdir(dirname($prefix), 0777, true);
}
try {
    $content = (new App\Services\InProcessCheckSheetImporter())->extract($options['workbook']);
    file_put_contents($prefix.'_source.json', json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    if ($content['issues']) {
        throw new RuntimeException(implode("\n", $content['issues']));
    }
    $sql = (new App\Services\InProcessCheckSheetSql())->build($content, (int) $options['manual-id'], $options['manual-number'], $options['expected-hash'] ?? null);
    file_put_contents($prefix.'_import.sql', $sql['import']);
    file_put_contents($prefix.'_verify.sql', $sql['verify']);
    echo json_encode(['manual' => $options['manual-number'], 'manual_id' => (int) $options['manual-id'],
        'filled_tasks' => count(array_filter($content['rows'], fn ($row) => $row['task'] !== '')),
        'slots' => count($content['rows']), 'content_sha256' => $sql['content_sha256'], 'prefix' => $prefix], JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage().PHP_EOL);
    exit(1);
}
