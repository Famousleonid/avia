<?php

declare(strict_types=1);

use App\Models\Component;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../../../vendor/autoload.php';

$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$file = __DIR__ . '/candidates.csv';
$handle = fopen($file, 'rb');
$header = fgetcsv($handle);
$candidates = [];

while (($values = fgetcsv($handle)) !== false) {
    $row = array_combine($header, $values);
    $candidates[$row['ipl_num']][] = $row;
}

$components = Component::query()
    ->where('manual_id', 42)
    ->orderBy('ipl_num')
    ->get(['ipl_num', 'part_number', 'name']);

$exact = 0;
$iplFoundDifferent = [];
$missing = [];

foreach ($components as $component) {
    $matches = $candidates[$component->ipl_num] ?? [];
    if ($matches === []) {
        $missing[] = $component;
        continue;
    }

    $samePart = array_filter($matches, fn (array $row) => $row['part_number'] === $component->part_number);
    if ($samePart !== []) {
        $exact++;
        continue;
    }

    $iplFoundDifferent[] = [
        'current' => $component,
        'source' => $matches,
    ];
}

printf("existing: %d\n", $components->count());
printf("exact IPL + P/N: %d\n", $exact);
printf("IPL found, P/N differs: %d\n", count($iplFoundDifferent));
printf("IPL not detected: %d\n\n", count($missing));

foreach ($iplFoundDifferent as $entry) {
    printf("DIFFERENT %s | %s | %s\n", $entry['current']->ipl_num, $entry['current']->part_number, $entry['current']->name);
    foreach ($entry['source'] as $source) {
        printf("  SOURCE %s | %s | %s | %s\n", $source['part_number'], $source['name_ocr'], $source['source_page'], $source['source_y']);
    }
}

foreach ($missing as $component) {
    printf("UNDETECTED %s | %s | %s\n", $component->ipl_num, $component->part_number, $component->name);
}
