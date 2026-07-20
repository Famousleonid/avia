<?php

declare(strict_types=1);

use App\Models\Component;
use App\Models\ManualServiceBulletin;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$manualId = 42;
$source = json_decode((string) file_get_contents(__DIR__ . '/series-sheet-values.json'), true, 512, JSON_THROW_ON_ERROR);
$components = Component::query()->where('manual_id', $manualId)->get([
    'id', 'ipl_num', 'part_number', 'name', 'log_card', 'ndt_list', 'cad_list', 'paint_list', 'kit', 'is_bush',
]);
$componentByIpl = $components->keyBy(static fn (Component $component): string => strtoupper(trim((string) $component->ipl_num)));
$componentByPn = $components
    ->filter(static fn (Component $component): bool => trim((string) $component->part_number) !== '')
    ->groupBy(static fn (Component $component): string => strtoupper(trim((string) $component->part_number)));

$rowsForSheet = static function (string $sheet) use ($source): array {
    $rows = [];
    foreach ($source['sheets'][$sheet]['cells'] as $cell) {
        if (! preg_match('/^([A-Z]+)(\d+)$/', $cell['cell'], $match)) {
            continue;
        }
        $rows[(int) $match[2]][$match[1]] = $cell['value'];
    }
    ksort($rows);
    return $rows;
};
$ipls = static function (string $value): array {
    preg_match_all('/\b\d{1,2}-\d{1,3}[A-Z]?\b/i', $value, $matches);
    return array_values(array_unique(array_map('strtoupper', $matches[0])));
};
$componentSummary = static function (Component $component, string $field): array {
    return [
        'ipl' => $component->ipl_num,
        'pn' => $component->part_number,
        'name' => $component->name,
        'already_set' => (bool) $component->{$field},
    ];
};
$sectionRows = static function (string $sheet) use ($rowsForSheet): array {
    $currentCmm = null;
    $output = [];
    foreach ($rowsForSheet($sheet) as $rowNumber => $row) {
        foreach ($row as $value) {
            if (preg_match('/^\d{2}-\d{2}-\d{2}$/', trim((string) $value))) {
                $currentCmm = trim((string) $value);
            }
        }
        if ($currentCmm === '32-21-02') {
            $output[$rowNumber] = $row;
        }
    }
    return $output;
};
$report = [
    'component_count' => $components->count(),
    'existing_service_bulletins' => ManualServiceBulletin::query()->where('manual_id', $manualId)->count(),
    'flags' => [],
    'bushing' => [],
];

// LLP has no IPL column, so its exact listed part numbers apply to every matching manual component.
$llpMatched = [];
$llpUnmatched = [];
foreach (collect($source['sheets']['LLP']['candidates'])->pluck('value')->unique() as $partNumber) {
    $partNumber = strtoupper(trim((string) $partNumber));
    $matches = $componentByPn->get($partNumber);
    if ($matches === null) {
        $llpUnmatched[] = $partNumber;
        continue;
    }
    foreach ($matches as $component) {
        $llpMatched[$component->id] = $componentSummary($component, 'log_card');
    }
}
$report['flags']['LLP'] = [
    'field' => 'log_card',
    'matched' => array_values($llpMatched),
    'unmatched_pn' => $llpUnmatched,
];

foreach ([
    'NDT' => 'ndt_list',
    'NDT Feedback' => 'ndt_list',
    'CAD' => 'cad_list',
    'CAD AIRCO PLATING' => 'cad_list',
    'PAINT' => 'paint_list',
    'PAINT (2)' => 'paint_list',
] as $sheet => $field) {
    $matched = [];
    $unmatchedIpls = [];
    foreach ($sectionRows($sheet) as $row) {
        foreach ($ipls((string) ($row['A'] ?? '')) as $ipl) {
            $component = $componentByIpl->get($ipl);
            if ($component === null) {
                $unmatchedIpls[] = $ipl;
                continue;
            }
            $matched[$component->id] = $componentSummary($component, $field);
        }
    }
    $report['flags'][$sheet] = [
        'field' => $field,
        'matched' => array_values($matched),
        'unmatched_ipl' => array_values(array_unique($unmatchedIpls)),
    ];
}

// PRL identifies components by figure and item; only rows explicitly coded KIT are relevant.
$prlMatched = [];
$prlUnmatchedIpls = [];
foreach ($sectionRows('PRL') as $row) {
    if (strtoupper(trim((string) ($row['F'] ?? ''))) !== 'KIT') {
        continue;
    }
    $figure = trim((string) ($row['A'] ?? ''));
    $item = strtoupper(trim((string) ($row['B'] ?? '')));
    if (! preg_match('/^\d{1,2}$/', $figure) || ! preg_match('/^\d{1,3}[A-Z]?$/', $item)) {
        continue;
    }
    $ipl = $figure . '-' . $item;
    $component = $componentByIpl->get($ipl);
    if ($component === null) {
        $prlUnmatchedIpls[] = $ipl;
        continue;
    }
    $prlMatched[$component->id] = $componentSummary($component, 'kit');
}
$report['flags']['PRL'] = [
    'field' => 'kit',
    'matched' => array_values($prlMatched),
    'unmatched_ipl' => array_values(array_unique($prlUnmatchedIpls)),
];

foreach ($components as $component) {
    if (stripos((string) $component->name, 'bushing') !== false) {
        $report['bushing'][] = $componentSummary($component, 'is_bush');
    }
}

foreach ($report['flags'] as &$flagReport) {
    usort($flagReport['matched'], static fn (array $a, array $b): int => strnatcasecmp($a['ipl'], $b['ipl']));
}
unset($flagReport);
usort($report['bushing'], static fn (array $a, array $b): int => strnatcasecmp($a['ipl'], $b['ipl']));

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
