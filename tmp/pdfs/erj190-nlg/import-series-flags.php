<?php

declare(strict_types=1);

use App\Models\Component;
use App\Models\ManualServiceBulletin;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$apply = in_array('--apply', $argv, true);
$manualId = 42;
$analysisJson = (string) file_get_contents(__DIR__ . '/series-flags-analysis.json');
$analysisJson = preg_replace('/^\xEF\xBB\xBF/', '', $analysisJson) ?? $analysisJson;
$analysis = json_decode($analysisJson, true, 512, JSON_THROW_ON_ERROR);
$source = json_decode((string) file_get_contents(__DIR__ . '/series-sheet-values.json'), true, 512, JSON_THROW_ON_ERROR);

$flagTargets = [];
foreach ($analysis['flags'] as $flagReport) {
    $field = $flagReport['field'];
    foreach ($flagReport['matched'] as $component) {
        $flagTargets[$field][strtoupper((string) $component['ipl'])] = true;
    }
}
foreach ($analysis['bushing'] as $component) {
    $flagTargets['is_bush'][strtoupper((string) $component['ipl'])] = true;
}

$sbRows = [];
foreach ($source['sheets']['SB']['cells'] as $cell) {
    if (! preg_match('/^([A-Z]+)(\d+)$/', $cell['cell'], $match) || (int) $match[2] < 6) {
        continue;
    }
    $sbRows[(int) $match[2]][$match[1]] = trim((string) $cell['value']);
}
ksort($sbRows);
$bulletins = [];
foreach ($sbRows as $sourceRow => $row) {
    if (($row['B'] ?? '') === '') {
        continue;
    }
    $requirement = isset($row['L']) && strtoupper($row['L']) === 'X'
        ? ManualServiceBulletin::REQUIREMENT_MANDATORY
        : (isset($row['K']) && strtoupper($row['K']) === 'X'
            ? ManualServiceBulletin::REQUIREMENT_RECOMMENDED
            : ManualServiceBulletin::REQUIREMENT_OPTIONAL);
    $bulletins[] = [
        'sort_order' => $sourceRow - 5,
        'year_introduced' => $row['A'] ?? null,
        'ac_mfg_service_bulletin_no' => $row['B'],
        'oem_service_bulletin_no' => $row['C'] ?? null,
        'awd_no' => $row['D'] ?? null,
        'identification_method' => $row['E'] ?? null,
        'description' => $row['F'] ?? null,
        'default_requirement' => $requirement,
        'is_active' => true,
    ];
}

$summary = [
    'manual_id' => $manualId,
    'flag_targets' => array_map('count', $flagTargets),
    'service_bulletins_from_workbook' => count($bulletins),
    'mode' => $apply ? 'apply' : 'preview',
];

if (! $apply) {
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

$actor = User::query()->where('email', 'codex.admin@avia.local')->first();
if ($actor !== null) {
    Auth::login($actor);
}

$summary['flags_set'] = [];
$summary['service_bulletins_created'] = 0;
$summary['service_bulletins_updated'] = 0;

DB::transaction(function () use ($manualId, $flagTargets, $bulletins, &$summary): void {
    foreach ($flagTargets as $field => $ipls) {
        $components = Component::query()
            ->where('manual_id', $manualId)
            ->whereIn(DB::raw('UPPER(ipl_num)'), array_keys($ipls))
            ->get();
        $changed = 0;
        foreach ($components as $component) {
            if ((bool) $component->{$field}) {
                continue;
            }
            $component->{$field} = true;
            $component->save();
            $changed++;
        }
        $summary['flags_set'][$field] = $changed;
    }

    foreach ($bulletins as $attributes) {
        $bulletin = ManualServiceBulletin::query()
            ->where('manual_id', $manualId)
            ->where('ac_mfg_service_bulletin_no', $attributes['ac_mfg_service_bulletin_no'])
            ->first();
        if ($bulletin === null) {
            ManualServiceBulletin::create(['manual_id' => $manualId] + $attributes);
            $summary['service_bulletins_created']++;
            continue;
        }
        $bulletin->fill($attributes);
        if ($bulletin->isDirty()) {
            $bulletin->save();
            $summary['service_bulletins_updated']++;
        }
    }
});

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
