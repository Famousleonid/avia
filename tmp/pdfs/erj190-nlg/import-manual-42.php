<?php

declare(strict_types=1);

use App\Models\Component;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$apply = in_array('--apply', $argv, true);
$manualId = 42;

$manualOverrides = [
    '1-5F' => ['part_number' => '190-70745-609', 'name' => 'NLG SHOCK STRUT EQUIPPED'],
    '1-5G' => ['part_number' => '190-70745-611', 'name' => 'NLG SHOCK STRUT EQUIPPED'],
    '1-5H' => ['part_number' => '190-70745-613', 'name' => 'NLG SHOCK STRUT EQUIPPED'],
    '1-5J' => ['part_number' => '190-70745-407', 'name' => 'NLG SHOCK STRUT EQUIPPED'],
    '1-5K' => ['part_number' => '190-70745-409', 'name' => 'NLG SHOCK STRUT EQUIPPED'],
    '1-30' => ['part_number' => 'NAS1197-416L', 'name' => 'WASHER, FLAT-ALUMINUM'],
    '1-30A' => ['part_number' => 'NAS1197-416', 'name' => 'WASHER, FLAT-ALUMINUM'],
    '1-780J' => ['part_number' => '190-70453-409', 'name' => 'NLG SHOCK STRUT, ASSY'],
    '10-270' => ['part_number' => '190-70506-001', 'name' => 'SCREW, SET'],
    '10-310A' => ['part_number' => '170-70476-901', 'name' => 'RACK'],
    '10-240' => ['part_number' => 'MS15001-1', 'name' => 'FITTING, LUBRICATION'],
    '4-220' => ['part_number' => 'AN3H3A', 'name' => 'BOLT'],
    '5-330A' => ['part_number' => '190-70545-001', 'name' => 'SUPPORT, UPPER LIGHT'],
    '7-70' => ['part_number' => '80-019-01', 'name' => 'SENSOR, PROXIMITY'],
];
$discardedIpls = ['8-8', '9-9', '9-3E', '10-319'];
$forcedCandidatePns = [
    '1-1' => '190-70450-403',
    '1-5B' => '190-70745-601',
    '10-10' => '170-70505-001',
];

$handle = fopen(__DIR__ . '/candidates-300.csv', 'rb');
$header = fgetcsv($handle);
$source = [];
while (($values = fgetcsv($handle)) !== false) {
    $row = array_combine($header, $values);
    $ipl = trim($row['ipl_num']);
    if (in_array($ipl, $discardedIpls, true)) {
        continue;
    }
    if (isset($forcedCandidatePns[$ipl]) && $row['part_number'] !== $forcedCandidatePns[$ipl]) {
        continue;
    }
    if (! isset($source[$ipl])) {
        $source[$ipl] = $row;
    }
}
foreach ($manualOverrides as $ipl => $override) {
    $source[$ipl] = array_merge($source[$ipl] ?? ['ipl_num' => $ipl], $override);
}

$knownNames = [];
foreach (Component::query()->whereNotNull('part_number')->get(['part_number', 'name']) as $component) {
    $pn = strtoupper(trim((string) $component->part_number));
    if ($pn !== '' && trim((string) $component->name) !== '') {
        $knownNames['pn:' . $pn][] = trim((string) $component->name);
    }
}

$resolveName = static function (string $partNumber, string $ocrName) use ($knownNames): string {
    $exact = $knownNames['pn:' . $partNumber] ?? [];
    if ($exact !== []) {
        $counts = array_count_values($exact);
        arsort($counts);
        return (string) array_key_first($counts);
    }

    $near = [];
    foreach ($knownNames as $key => $names) {
        $candidate = substr($key, 3);
        if (abs(strlen($candidate) - strlen($partNumber)) <= 1 && levenshtein($candidate, $partNumber) <= 1) {
            $near[] = $names[0];
        }
    }
    if (count(array_unique($near)) === 1) {
        return $near[0];
    }

    $name = trim((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $ocrName));
    $name = preg_replace('/\s+/', ' ', $name) ?: '';
    return trim($name, " .,*");
};

$existing = Component::query()->where('manual_id', $manualId)->get()->keyBy('ipl_num');
$toCreate = [];
foreach ($source as $ipl => $row) {
    if ($existing->has($ipl)) {
        continue;
    }
    $partNumber = strtoupper(trim((string) ($row['part_number'] ?? '')));
    $name = $row['name'] ?? $resolveName($partNumber, (string) ($row['name_ocr'] ?? ''));
    if ($partNumber === '' || $name === '') {
        throw new RuntimeException("Incomplete source row for {$ipl}.");
    }
    $toCreate[] = compact('ipl', 'partNumber', 'name');
}

$changes = [
    '8-155B' => ['part_number' => '190-70469-005', 'name' => 'TORQUE LINK, UPPER'],
    '10-91A' => ['part_number' => '170-70747-301'],
];

printf("Existing rows preserved: %d\n", $existing->count());
printf("New source IPL rows: %d\n", count($toCreate));
printf("Confirmed corrections: %d\n", count($changes));

if (! $apply) {
    exit(0);
}

$actor = User::query()->where('email', 'codex.admin@avia.local')->first();
if ($actor) {
    Auth::login($actor);
}

DB::transaction(function () use ($toCreate, $changes, $existing, $manualId): void {
    foreach ($toCreate as $row) {
        Component::create([
            'manual_id' => $manualId,
            'ipl_num' => $row['ipl'],
            'part_number' => $row['partNumber'],
            'name' => $row['name'],
        ]);
    }
    foreach ($changes as $ipl => $attributes) {
        $component = $existing->get($ipl);
        if (! $component) {
            throw new RuntimeException("Existing IPL {$ipl} was not found.");
        }
        $component->fill($attributes);
        $component->save();
    }
});

printf("Created: %d\n", count($toCreate));
foreach ($changes as $ipl => $attributes) {
    printf("Corrected: %s -> %s\n", $ipl, json_encode($attributes, JSON_UNESCAPED_SLASHES));
}
