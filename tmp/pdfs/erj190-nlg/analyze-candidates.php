<?php

declare(strict_types=1);

use App\Models\Component;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$handle = fopen(__DIR__ . '/candidates-300.csv', 'rb');
$header = fgetcsv($handle);
$rows = [];
while (($values = fgetcsv($handle)) !== false) {
    $rows[] = array_combine($header, $values);
}

$known = [];
foreach (Component::query()->whereNotNull('part_number')->get(['part_number', 'name']) as $component) {
    $pn = strtoupper(trim((string) $component->part_number));
    if ($pn === '') {
        continue;
    }
    $known['pn:' . $pn][] = $component->name;
}

$exact = 0;
$unknown = [];
foreach ($rows as $row) {
    if (isset($known['pn:' . $row['part_number']])) {
        $exact++;
        continue;
    }
    $unknown[] = $row;
}

printf("candidate rows: %d\n", count($rows));
printf("exact P/N known elsewhere: %d\n", $exact);
printf("P/N not known elsewhere: %d\n\n", count($unknown));

foreach ($unknown as $row) {
    $matches = [];
    foreach ($known as $knownKey => $names) {
        $knownPn = substr($knownKey, 3);
        if (abs(strlen($knownPn) - strlen($row['part_number'])) > 2) {
            continue;
        }
        $distance = levenshtein($row['part_number'], $knownPn);
        if ($distance <= 2) {
            $matches[] = [$distance, $knownPn, $names[0]];
        }
    }
    usort($matches, fn (array $a, array $b) => $a[0] <=> $b[0] ?: strcmp($a[1], $b[1]));
    $near = array_slice($matches, 0, 3);
    printf("%s | %s | %s | %s\n", $row['ipl_num'], $row['part_number'], $row['name_ocr'], $row['source_page']);
    foreach ($near as [$distance, $pn, $name]) {
        printf("  near %d: %s | %s\n", $distance, $pn, $name);
    }
}
