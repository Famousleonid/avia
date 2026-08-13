<?php

declare(strict_types=1);

use App\Models\Manual;
use Illuminate\Contracts\Console\Kernel;

$options = getopt('', ['manual-number:', 'app-root::', 'output::']);
$manualNumber = trim((string) ($options['manual-number'] ?? ''));
if (! preg_match('/^\d{2}-\d{2}-\d{2}(?:\s+[A-Za-z0-9][A-Za-z0-9 .&()\/_-]*)?$/', $manualNumber)) {
    fwrite(STDERR, '--manual-number must start with NN-NN-NN and may include the manual variant name.'.PHP_EOL);
    exit(1);
}

$appRoot = isset($options['app-root']) && trim((string) $options['app-root']) !== ''
    ? rtrim((string) $options['app-root'], '/\\')
    : dirname(__DIR__, 4);

require $appRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
$app = require $appRoot.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';
$app->make(Kernel::class)->bootstrap();

$manuals = Manual::query()
    ->where('number', $manualNumber)
    ->with([
        'components' => static fn ($query) => $query->orderBy('ipl_num')->select([
            'id', 'manual_id', 'ipl_num', 'part_number', 'name', 'assy_part_number',
            'assy_ipl_num', 'units_assy', 'log_card', 'ndt_list', 'cad_list',
            'paint_list', 'kit',
        ]),
        'serviceBulletins' => static fn ($query) => $query->orderBy('sort_order')->select([
            'id', 'manual_id', 'sort_order', 'year_introduced',
            'ac_mfg_service_bulletin_no', 'oem_service_bulletin_no', 'awd_no',
            'identification_method', 'description', 'default_requirement', 'is_active',
        ]),
    ])
    ->get(['id', 'number']);

if ($manuals->count() !== 1) {
    fwrite(STDERR, "Expected one manual {$manualNumber}; found {$manuals->count()}.".PHP_EOL);
    exit(2);
}

$manual = $manuals->first();
$json = json_encode([
    'id' => $manual->id,
    'number' => $manual->number,
    'components' => $manual->components->values()->toArray(),
    'service_bulletins' => $manual->serviceBulletins->values()->toArray(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

if (isset($options['output']) && trim((string) $options['output']) !== '') {
    $output = (string) $options['output'];
    $directory = dirname($output);
    if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
        fwrite(STDERR, "Cannot create output directory: {$directory}".PHP_EOL);
        exit(3);
    }
    file_put_contents($output, $json);
    echo json_encode(['output' => $output, 'manual_id' => $manual->id, 'components' => $manual->components->count(), 'service_bulletins' => $manual->serviceBulletins->count()], JSON_UNESCAPED_SLASHES).PHP_EOL;
} else {
    echo $json;
}
