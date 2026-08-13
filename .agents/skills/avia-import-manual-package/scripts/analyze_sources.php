<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;

$projectRoot = dirname(__DIR__, 4);
require $projectRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

/** @return never */
function fail(string $message): void
{
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
}

function clean(mixed $value): string
{
    return trim(preg_replace('/\s+/u', ' ', (string) ($value ?? '')) ?? '');
}

function normalizeDash(string $value): string
{
    return str_replace(["\u{2010}", "\u{2011}", "\u{2012}", "\u{2013}", "\u{2014}", "\u{2212}"], '-', $value);
}

function normalizePart(mixed $value): string
{
    return strtoupper(preg_replace('/\s+/u', '', normalizeDash(clean($value))) ?? '');
}

/** @return list<string> */
function expandPartNumbers(mixed $value): array
{
    $value = normalizePart($value);
    if ($value === '') {
        return [];
    }
    if (! str_contains($value, '/')) {
        return [$value];
    }
    $pieces = explode('/', $value);
    $first = array_shift($pieces);
    $expanded = [$first];
    $dash = strrpos($first, '-');
    $prefix = $dash === false ? '' : substr($first, 0, $dash + 1);
    foreach ($pieces as $piece) {
        $piece = ltrim($piece, '-');
        $expanded[] = $prefix !== '' ? $prefix.$piece : $piece;
    }

    return array_values(array_unique($expanded));
}

function partNumberCompatible(mixed $left, mixed $right): bool
{
    $leftValues = expandPartNumbers($left);
    $rightValues = expandPartNumbers($right);
    foreach ($leftValues as $a) {
        foreach ($rightValues as $b) {
            if ($a === $b || (str_ends_with($a, '-') && str_starts_with($b, $a)) || (str_ends_with($b, '-') && str_starts_with($a, $b))) {
                return true;
            }
        }
    }

    return false;
}

function partIdentity(mixed $value): string
{
    return preg_replace('/[^A-Z0-9]/', '', normalizePart($value)) ?? '';
}

function normalizeName(mixed $value): string
{
    $value = strtoupper(clean($value));
    $value = preg_replace('/\bASSY\b/u', 'ASSEMBLY', $value) ?? $value;
    $value = preg_replace('/\bTQ\b/u', 'TORQUE', $value) ?? $value;
    $value = preg_replace('/\bUPR\b/u', 'UPPER', $value) ?? $value;
    $value = preg_replace('/\bLWR\b/u', 'LOWER', $value) ?? $value;
    $value = preg_replace('/\bATT\b/u', 'ATTACH', $value) ?? $value;
    $value = preg_replace('/\bNLG\b/u', '', $value) ?? $value;

    return trim(preg_replace('/[^A-Z0-9]+/', ' ', $value) ?? '');
}

function nameSimilarity(mixed $left, mixed $right): float
{
    $a = array_values(array_unique(array_filter(explode(' ', normalizeName($left)))));
    $b = array_values(array_unique(array_filter(explode(' ', normalizeName($right)))));
    if ($a === [] || $b === []) {
        return 0.0;
    }
    $intersection = count(array_intersect($a, $b));
    $union = count(array_unique(array_merge($a, $b)));

    return $union > 0 ? $intersection / $union : 0.0;
}

/** @return list<string> */
function splitLines(mixed $value): array
{
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return [];
    }

    return array_values(array_filter(array_map(
        static fn (string $line): string => trim(normalizeDash($line)),
        preg_split('/\R/u', $value) ?: []
    ), static fn (string $line): bool => $line !== ''));
}

function manualFromValue(mixed $value): ?string
{
    return preg_match('/\b(\d{2}-\d{2}-\d{2})\b/', (string) ($value ?? ''), $matches)
        ? $matches[1]
        : null;
}

function sheetKey(string $value): string
{
    return strtoupper(clean($value));
}

function normalizeIpl(mixed $value): string
{
    $value = strtoupper(normalizeDash(clean($value)));
    if (! preg_match('/^(\d+[A-Z]?)-(\d+)([A-Z]?)$/i', $value, $matches)) {
        return $value;
    }

    return strtoupper($matches[1]).'-'.(string) ((int) $matches[2]).strtoupper($matches[3]);
}

/** @return list<array<string,string>> */
function readCsv(string $path): array
{
    $handle = fopen($path, 'rb');
    if (! $handle) {
        fail("Cannot read CSV: {$path}");
    }
    $header = fgetcsv($handle);
    if (! is_array($header)) {
        fclose($handle);
        fail("CSV is empty: {$path}");
    }
    $header = array_map(static function ($value): string {
        $value = clean($value);
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }, $header);
    $required = ['ipl_num', 'part_number', 'name'];
    $missing = array_values(array_diff($required, $header));
    if ($missing !== []) {
        fclose($handle);
        fail('CSV '.basename($path).' is missing headers: '.implode(', ', $missing));
    }
    $rows = [];
    while (($values = fgetcsv($handle)) !== false) {
        if (count(array_filter($values, static fn ($v): bool => clean($v) !== '')) === 0) {
            continue;
        }
        $values = array_pad($values, count($header), '');
        $rows[] = array_combine($header, array_slice(array_map('clean', $values), 0, count($header)));
    }
    fclose($handle);

    return $rows;
}

function hasPartsCsvHeader(string $path): bool
{
    $handle = @fopen($path, 'rb');
    if (! $handle) {
        return false;
    }
    $header = fgetcsv($handle);
    fclose($handle);
    if (! is_array($header)) {
        return false;
    }
    $header = array_map(static function ($value): string {
        $value = clean($value);
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }, $header);

    return array_diff(['ipl_num', 'part_number', 'name'], $header) === [];
}

/** @param list<array<string,mixed>> $rows */
function writeCsv(string $path, array $header, array $rows): void
{
    $handle = fopen($path, 'wb');
    if (! $handle) {
        fail("Cannot write CSV: {$path}");
    }
    fputcsv($handle, $header);
    foreach ($rows as $row) {
        fputcsv($handle, array_map(static fn (string $key): mixed => $row[$key] ?? '', $header));
    }
    fclose($handle);
}

/** @return list<string> */
function globFiles(string $directory, array $extensions): array
{
    $files = [];
    foreach (scandir($directory) ?: [] as $name) {
        $path = $directory.DIRECTORY_SEPARATOR.$name;
        if (is_file($path) && in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $extensions, true)) {
            $files[] = $path;
        }
    }
    natcasesort($files);

    return array_values($files);
}

$options = getopt('', ['source-dir:', 'manual-number:', 'output-dir:', 'parts-csv::', 'target-json::']);
$sourceDir = isset($options['source-dir']) ? realpath((string) $options['source-dir']) : false;
$manualNumber = clean($options['manual-number'] ?? '');
$outputDir = isset($options['output-dir']) ? (string) $options['output-dir'] : '';
if ($sourceDir === false || ! is_dir($sourceDir)) {
    fail('A valid --source-dir is required.');
}
if (! preg_match('/^(\d{2}-\d{2}-\d{2})(?:\s+[A-Za-z0-9][A-Za-z0-9 .&()\/_-]*)?$/', $manualNumber, $manualMatches)) {
    fail('--manual-number must start with NN-NN-NN and may include the manual variant name.');
}
$workbookManualNumber = $manualMatches[1];
if ($outputDir === '') {
    fail('--output-dir is required.');
}
if (! is_dir($outputDir) && ! mkdir($outputDir, 0777, true) && ! is_dir($outputDir)) {
    fail("Cannot create output directory: {$outputDir}");
}
$outputDir = realpath($outputDir) ?: $outputDir;

$pdfFigures = [];
foreach (globFiles($sourceDir, ['pdf']) as $pdf) {
    if (preg_match('/^(\d+[A-Za-z]?)\.pdf$/i', basename($pdf), $matches)) {
        $pdfFigures[strtoupper(ltrim($matches[1], '0') ?: '0')] = basename($pdf);
    }
}
if ($pdfFigures === []) {
    fail('No numbered FIG PDFs were found.');
}

$workbookCandidates = [];
foreach (globFiles($sourceDir, ['xls', 'xlsx']) as $path) {
    if (str_starts_with(basename($path), '~$')) {
        continue;
    }
    try {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheetNames = $reader->listWorksheetNames($path);
        $keys = array_map('sheetKey', $sheetNames);
        $hasSb = in_array('SB', $keys, true);
        $processCount = count(array_filter($keys, static fn (string $key): bool =>
            $key === 'LLP' || $key === 'CAD' || $key === 'PAINT' || str_starts_with($key, 'PAINT (') || str_starts_with($key, 'NDT') || str_starts_with($key, 'PRL')
        ));
        if ($hasSb && $processCount >= 3) {
            $workbookCandidates[] = ['path' => $path, 'sheets' => $sheetNames];
        }
    } catch (Throwable $e) {
        // Ignore unrelated/unreadable spreadsheets; ambiguity is handled below.
    }
}
if (count($workbookCandidates) !== 1) {
    fail('Expected exactly one standard process workbook; found '.count($workbookCandidates).'.');
}
$workbookPath = $workbookCandidates[0]['path'];

$partsRows = [];
$partsSource = null;
if (isset($options['parts-csv']) && clean($options['parts-csv']) !== '') {
    $partsSource = realpath((string) $options['parts-csv']);
    if ($partsSource === false) {
        fail('--parts-csv was not found.');
    }
    $partsRows = readCsv($partsSource);
} else {
    $numberedCsvs = array_values(array_filter(
        globFiles($sourceDir, ['csv']),
        static fn (string $path): bool => (bool) preg_match('/^\d+[A-Za-z]?\.csv$/i', basename($path))
    ));
    if ($numberedCsvs !== []) {
        foreach ($numberedCsvs as $csv) {
            $partsRows = array_merge($partsRows, readCsv($csv));
        }
        $partsSource = 'numbered FIG CSVs';
    } else {
        $masterCandidates = [];
        foreach (globFiles($sourceDir, ['csv']) as $csv) {
            if (hasPartsCsvHeader($csv)) {
                $masterCandidates[] = $csv;
            }
        }
        if (count($masterCandidates) !== 1) {
            fail('No numbered FIG CSVs and no unique master Parts CSV were found. Extract PDFs first.');
        }
        $partsSource = $masterCandidates[0];
        $partsRows = readCsv($masterCandidates[0]);
    }
}

$issues = [];
$parts = [];
$partsByIpl = [];
$partsByPn = [];
foreach ($partsRows as $index => $row) {
    $part = [
        'source_row' => $index + 2,
        'ipl_num' => normalizeIpl($row['ipl_num'] ?? ''),
        'part_number' => normalizeDash(clean($row['part_number'] ?? '')),
        'assy_part_number' => normalizeDash(clean($row['assy_part_number'] ?? '')),
        'name' => clean($row['name'] ?? ''),
        'assy_ipl_num' => normalizeDash(clean($row['assy_ipl_num'] ?? '')),
        'units_assy' => clean($row['units_assy'] ?? ''),
    ];
    if (! preg_match('/^\d+[A-Z]?-\d+[A-Z]?$/i', $part['ipl_num']) || $part['part_number'] === '' || $part['name'] === '') {
        $issues[] = ['type' => 'invalid_part_row', 'row' => $index + 2, 'data' => $part];
        continue;
    }
    if (isset($partsByIpl[$part['ipl_num']])) {
        $issues[] = ['type' => 'duplicate_ipl', 'ipl' => $part['ipl_num'], 'rows' => [$partsByIpl[$part['ipl_num']]['source_row'], $part['source_row']]];
        continue;
    }
    $fig = strtoupper(explode('-', $part['ipl_num'], 2)[0]);
    $pdfFig = preg_replace('/[A-Z]$/i', '', $fig) ?: $fig;
    if (! isset($pdfFigures[$fig]) && ! isset($pdfFigures[$pdfFig])) {
        $issues[] = ['type' => 'ipl_without_matching_pdf', 'ipl' => $part['ipl_num'], 'row' => $part['source_row']];
    }
    $parts[] = $part;
    $partsByIpl[$part['ipl_num']] = $part;
    $partsByPn[normalizePart($part['part_number'])][] = $part;
}

$target = null;
$targetComponents = [];
$targetBulletins = [];
if (isset($options['target-json']) && clean($options['target-json']) !== '') {
    $targetPath = realpath((string) $options['target-json']);
    if ($targetPath === false) {
        fail('--target-json was not found.');
    }
    $decoded = json_decode((string) file_get_contents($targetPath), true, 512, JSON_THROW_ON_ERROR);
    if (array_is_list($decoded)) {
        if (count($decoded) !== 1) {
            fail('--target-json must contain exactly one manual.');
        }
        $decoded = $decoded[0];
    }
    if (! is_array($decoded) || clean($decoded['number'] ?? '') !== $manualNumber) {
        fail('--target-json manual number does not match --manual-number.');
    }
    $target = $decoded;
    $targetComponents = is_array($decoded['components'] ?? null) ? $decoded['components'] : [];
    $targetBulletins = is_array($decoded['service_bulletins'] ?? null) ? $decoded['service_bulletins'] : [];
}

$canonicalByIpl = $partsByIpl;
$candidateByPn = $partsByPn;
foreach ($targetComponents as $component) {
    $ipl = normalizeIpl($component['ipl_num'] ?? '');
    if ($ipl === '') {
        continue;
    }
    $candidate = [
        'source' => 'target',
        'id' => $component['id'] ?? null,
        'ipl_num' => $ipl,
        'part_number' => normalizeDash(clean($component['part_number'] ?? '')),
        'name' => clean($component['name'] ?? ''),
    ] + $component;
    if (! isset($canonicalByIpl[$ipl])) {
        $canonicalByIpl[$ipl] = $candidate;
    }
    $pnKey = normalizePart($candidate['part_number']);
    $candidateByPn[$pnKey] = array_values(array_filter(
        $candidateByPn[$pnKey] ?? [],
        static fn (array $row): bool => clean($row['ipl_num'] ?? '') !== $ipl
    ));
    $candidateByPn[$pnKey][] = $candidate;
}

$reader = IOFactory::createReaderForFile($workbookPath);
$reader->setReadDataOnly(true);
$workbook = $reader->load($workbookPath);
$sheets = [];
$manualOccurrences = [];
foreach ($workbook->getWorksheetIterator() as $worksheet) {
    $values = $worksheet->toArray(null, true, true, false);
    $sheets[sheetKey($worksheet->getTitle())] = ['name' => $worksheet->getTitle(), 'values' => $values];
    foreach ($values as $rowIndex => $row) {
        foreach ($row as $value) {
            if (($detected = manualFromValue($value)) !== null) {
                $manualOccurrences[] = ['sheet' => $worksheet->getTitle(), 'row' => $rowIndex + 1, 'manual' => $detected];
            }
        }
    }
}
$workbook->disconnectWorksheets();
unset($workbook);

if (count(array_filter($manualOccurrences, static fn (array $item): bool => $item['manual'] === $workbookManualNumber)) === 0) {
    $issues[] = ['type' => 'manual_number_not_found_in_workbook', 'manual' => $workbookManualNumber];
}

$flagNames = ['log_card', 'ndt_list', 'cad_list', 'paint_list', 'kit'];
$flagsByIpl = [];
foreach ($canonicalByIpl as $ipl => $part) {
    $flagsByIpl[$ipl] = array_fill_keys($flagNames, false);
}
$mappings = [];

$setFlag = static function (string $ipl, string $flag, array $detail) use (&$flagsByIpl, &$issues, &$mappings): void {
    $mappings[] = ['ipl_num' => $ipl, 'flag' => $flag] + $detail;
    if (! isset($flagsByIpl[$ipl])) {
        $issues[] = ['type' => 'ipl_not_in_component_sources', 'ipl' => $ipl, 'flag' => $flag] + $detail;
        return;
    }
    $flagsByIpl[$ipl][$flag] = true;
};

$mapProcessSheets = static function (string $sheetPrefix, string $flag) use (
    $sheets, $workbookManualNumber, $canonicalByIpl, &$issues, $setFlag
): void {
    $matchingSheets = array_filter(array_keys($sheets), static fn (string $key): bool =>
        $key === $sheetPrefix || str_starts_with($key, $sheetPrefix.' (')
    );
    if ($matchingSheets === []) {
        $issues[] = ['type' => 'missing_workbook_sheet', 'sheet' => $sheetPrefix];
        return;
    }

    foreach ($matchingSheets as $sheetKeyName) {
        // In the standard workbook the first process block belongs to the unit
        // named in the sheet header; later manual-number rows delimit other units.
        $manualContext = $workbookManualNumber;
        $currentFigure = null;
        foreach ($sheets[$sheetKeyName]['values'] as $rowIndex => $row) {
            foreach ($row as $value) {
                if (($detected = manualFromValue($value)) !== null) {
                    $manualContext = $detected;
                }
            }
            if ($manualContext !== $workbookManualNumber) {
                continue;
            }
            $rawItems = splitLines($row[0] ?? '');
            $partNumbers = splitLines($row[2] ?? '');
            $description = clean($row[6] ?? ($row[8] ?? ''));
            if ($rawItems === [] || $partNumbers === []) {
                continue;
            }
            $ipls = [];
            foreach ($rawItems as $position => $rawItem) {
                $rawItem = strtoupper(normalizeDash(clean($rawItem)));
                if (preg_match('/^(\d+[A-Z]?)-(\d+[A-Z]?)$/i', $rawItem, $matches)) {
                    $currentFigure = strtoupper($matches[1]);
                    $ipls[] = normalizeIpl($currentFigure.'-'.strtoupper($matches[2]));
                    continue;
                }
                if (preg_match('/^\d+[A-Z]?$/i', $rawItem) && $currentFigure !== null) {
                    $ipls[] = normalizeIpl($currentFigure.'-'.$rawItem);
                    continue;
                }
                if (preg_match('/^\d+[A-Z]?$/i', $rawItem)) {
                    $workbookPn = count($partNumbers) === 1 ? $partNumbers[0] : ($partNumbers[$position] ?? null);
                    $matchesByItemAndPart = array_filter($canonicalByIpl, static function (array $candidate, string $ipl) use ($rawItem, $workbookPn): bool {
                        return str_ends_with(strtoupper($ipl), '-'.$rawItem) && partNumberCompatible($candidate['part_number'] ?? '', $workbookPn);
                    }, ARRAY_FILTER_USE_BOTH);
                    if (count($matchesByItemAndPart) === 1) {
                        $ipls[] = array_key_first($matchesByItemAndPart);
                        $currentFigure = explode('-', array_key_first($matchesByItemAndPart), 2)[0];
                    }
                }
            }
            if ($ipls === []) {
                continue;
            }
            if (count($partNumbers) !== 1 && count($partNumbers) !== count($ipls)) {
                $issues[] = ['type' => 'multiline_count_mismatch', 'sheet' => $sheets[$sheetKeyName]['name'], 'row' => $rowIndex + 1, 'ipls' => $ipls, 'part_numbers' => $partNumbers];
            }
            foreach ($ipls as $position => $ipl) {
                $workbookPn = count($partNumbers) === 1 ? $partNumbers[0] : ($partNumbers[$position] ?? null);
                $ipl = normalizeIpl($ipl);
                $detail = ['sheet' => $sheets[$sheetKeyName]['name'], 'row' => $rowIndex + 1, 'workbook_part_number' => $workbookPn, 'description' => $description];
                $targetIpls = [];
                if (isset($canonicalByIpl[$ipl]) && $workbookPn && partNumberCompatible($workbookPn, $canonicalByIpl[$ipl]['part_number'])) {
                    $targetIpls[] = $ipl;
                } elseif ($workbookPn) {
                    [$declaredFigure, $declaredItem] = array_pad(explode('-', $ipl, 2), 2, '');
                    preg_match('/^(\d+)/', $declaredItem, $declaredItemMatch);
                    $declaredBase = $declaredItemMatch[1] ?? '';
                    foreach ($canonicalByIpl as $candidateIpl => $candidate) {
                        [$candidateFigure, $candidateItem] = array_pad(explode('-', $candidateIpl, 2), 2, '');
                        preg_match('/^(\d+)/', $candidateItem, $candidateItemMatch);
                        if ($candidateFigure === $declaredFigure
                            && ($candidateItemMatch[1] ?? '') === $declaredBase
                            && partNumberCompatible($workbookPn, $candidate['part_number'] ?? '')) {
                            $targetIpls[] = $candidateIpl;
                        }
                    }
                }
                if ($targetIpls === []) {
                    if (isset($canonicalByIpl[$ipl])) {
                        $issues[] = ['type' => 'workbook_csv_part_mismatch', 'ipl' => $ipl, 'flag' => $flag, 'csv_part_number' => $canonicalByIpl[$ipl]['part_number']] + $detail;
                    } else {
                        $issues[] = ['type' => 'ipl_not_in_component_sources', 'ipl' => $ipl, 'flag' => $flag] + $detail;
                    }
                    continue;
                }
                foreach (array_values(array_unique($targetIpls)) as $targetIpl) {
                    $mappingDetail = $targetIpl === $ipl ? $detail : $detail + ['declared_ipl' => $ipl, 'remapped_by_part_number' => true];
                    $setFlag($targetIpl, $flag, $mappingDetail);
                }
            }
        }
    }
};

$mapProcessSheets('NDT', 'ndt_list');
$mapProcessSheets('CAD', 'cad_list');
$mapProcessSheets('PAINT', 'paint_list');

if (! isset($sheets['LLP'])) {
    $issues[] = ['type' => 'missing_workbook_sheet', 'sheet' => 'LLP'];
} else {
    foreach ($sheets['LLP']['values'] as $rowIndex => $row) {
        $description = clean($row[0] ?? '');
        $partCell = clean($row[1] ?? '');
        if ($description === '' || $partCell === '') {
            continue;
        }
        preg_match_all('/[A-Z0-9]+(?:-[A-Z0-9]*)+(?:\/-?[A-Z0-9]+)*/i', $partCell, $matches);
        $partNumbers = array_values(array_unique($matches[0] ?? []));
        if ($partNumbers === []) {
            continue;
        }
        $accepted = [];
        $candidatesSeen = [];
        foreach ($partNumbers as $workbookPn) {
            foreach ($canonicalByIpl as $candidate) {
                if (! partNumberCompatible($workbookPn, $candidate['part_number'] ?? '')) {
                    continue;
                }
                $score = nameSimilarity($description, $candidate['name']);
                $candidatesSeen[] = ['ipl_num' => $candidate['ipl_num'], 'part_number' => $candidate['part_number'], 'name' => $candidate['name'], 'score' => round($score, 3)];
                if ($score >= 0.34) {
                    $accepted[$candidate['ipl_num']] = true;
                    $setFlag($candidate['ipl_num'], 'log_card', ['sheet' => 'LLP', 'row' => $rowIndex + 1, 'workbook_part_number' => $workbookPn, 'description' => $description]);
                }
            }
        }
        if ($candidatesSeen === [] || $accepted === []) {
            $issues[] = [
                'type' => $candidatesSeen === [] ? 'llp_part_not_in_component_sources' : 'llp_name_not_matched',
                'sheet' => 'LLP', 'row' => $rowIndex + 1, 'description' => $description,
                'part_numbers' => $partNumbers, 'candidates' => $candidatesSeen,
            ];
        }
    }
}

$prlSheets = array_filter(array_keys($sheets), static fn (string $key): bool => str_starts_with($key, 'PRL'));
$matchingPrlSheets = array_values(array_filter($prlSheets, static function (string $key) use ($sheets, $workbookManualNumber): bool {
    foreach ($sheets[$key]['values'] as $row) {
        foreach ($row as $value) {
            if (manualFromValue($value) === $workbookManualNumber) {
                return true;
            }
        }
    }
    return false;
}));
if (count($matchingPrlSheets) !== 1) {
    $issues[] = ['type' => 'missing_workbook_sheet', 'sheet' => 'PRL'];
} else {
    $prlKey = $matchingPrlSheets[0];
    $manualContext = null;
    $currentFigure = null;
    foreach ($sheets[$prlKey]['values'] as $rowIndex => $row) {
        foreach ($row as $value) {
            if (($detected = manualFromValue($value)) !== null) {
                $manualContext = $detected;
            }
        }
        if ($manualContext !== $workbookManualNumber) {
            continue;
        }
        $figures = splitLines($row[0] ?? '');
        $items = splitLines($row[1] ?? '');
        $partNumbers = splitLines($row[3] ?? '');
        $description = clean($row[2] ?? '');
        if ($figures !== [] && preg_match('/^\d+[A-Z]?$/i', $figures[0])) {
            $currentFigure = strtoupper($figures[0]);
        }
        if ($currentFigure === null || $items === [] || $partNumbers === [] || strtoupper($items[0]) === 'ALT') {
            continue;
        }
        $ipls = [];
        foreach ($items as $position => $item) {
            $figure = count($figures) === 1 ? $figures[0] : ($figures[$position] ?? $currentFigure);
            $figure = clean($figure) !== '' ? $figure : $currentFigure;
            $ipls[] = normalizeDash($figure.'-'.$item);
        }
        if (count($partNumbers) !== 1 && count($partNumbers) !== count($ipls)) {
            $issues[] = ['type' => 'prl_multiline_count_mismatch', 'sheet' => $sheets[$prlKey]['name'], 'row' => $rowIndex + 1, 'ipls' => $ipls, 'part_numbers' => $partNumbers];
        }
        $isKit = strtoupper(clean($row[5] ?? '')) === 'KIT';
        foreach ($ipls as $position => $ipl) {
            $workbookPn = count($partNumbers) === 1 ? $partNumbers[0] : ($partNumbers[$position] ?? null);
            $detail = ['sheet' => $sheets[$prlKey]['name'], 'row' => $rowIndex + 1, 'workbook_part_number' => $workbookPn, 'description' => $description];
            if ($isKit) {
                $setFlag($ipl, 'kit', $detail);
            }
            if (isset($canonicalByIpl[$ipl]) && $workbookPn && ! partNumberCompatible($workbookPn, $canonicalByIpl[$ipl]['part_number'])) {
                $issues[] = ['type' => 'workbook_csv_part_mismatch', 'ipl' => $ipl, 'flag' => 'kit', 'csv_part_number' => $canonicalByIpl[$ipl]['part_number']] + $detail;
            }
        }
    }
}

$bulletins = [];
if (! isset($sheets['SB'])) {
    $issues[] = ['type' => 'missing_workbook_sheet', 'sheet' => 'SB'];
} else {
    $headerFound = false;
    foreach ($sheets['SB']['values'] as $rowIndex => $row) {
        $first = clean($row[0] ?? '');
        if (! $headerFound) {
            $headerFound = strcasecmp($first, 'Year Introduced') === 0;
            continue;
        }
        if (clean($row[1] ?? '') === '') {
            continue;
        }
        $requirement = null;
        if (strtoupper(clean($row[11] ?? '')) === 'X') {
            $requirement = 'mandatory';
        } elseif (strtoupper(clean($row[10] ?? '')) === 'X') {
            $requirement = 'recommended';
        } elseif (strtoupper(clean($row[9] ?? '')) === 'X') {
            $requirement = 'optional';
        }
        $bulletins[] = [
            'source_row' => $rowIndex + 1,
            'sort_order' => count($bulletins) + 1,
            'year_introduced' => clean($row[0] ?? ''),
            'ac_mfg_service_bulletin_no' => clean($row[1] ?? ''),
            'oem_service_bulletin_no' => clean($row[2] ?? ''),
            'awd_no' => clean($row[3] ?? ''),
            'identification_method' => clean($row[4] ?? ''),
            'description' => clean($row[5] ?? ''),
            'default_requirement' => $requirement,
        ];
    }
    if (! $headerFound) {
        $issues[] = ['type' => 'sb_header_not_found', 'sheet' => 'SB'];
    }
}

$flagCounts = [];
foreach ($flagNames as $flag) {
    $flagCounts[$flag] = count(array_filter($flagsByIpl, static fn (array $values): bool => $values[$flag]));
}

$targetComparison = null;
if ($target !== null) {
    $targetByIpl = [];
    foreach ($targetComponents as $component) {
        $targetByIpl[normalizeIpl($component['ipl_num'] ?? '')] = $component;
    }
    $exactExisting = [];
    $newIpls = [];
    $iplConflicts = [];
    foreach ($parts as $part) {
        $existing = $targetByIpl[$part['ipl_num']] ?? null;
        if ($existing === null) {
            $newIpls[] = $part['ipl_num'];
        } elseif (partIdentity($existing['part_number'] ?? '') === partIdentity($part['part_number'])) {
            $exactExisting[] = $part['ipl_num'];
        } else {
            $iplConflicts[] = [
                'ipl_num' => $part['ipl_num'],
                'source_part_number' => $part['part_number'],
                'source_name' => $part['name'],
                'target_id' => $existing['id'] ?? null,
                'target_part_number' => $existing['part_number'] ?? null,
                'target_name' => $existing['name'] ?? null,
            ];
        }
    }
    $existingOnly = array_values(array_filter(array_keys($targetByIpl), static fn (string $ipl): bool => ! isset($partsByIpl[$ipl])));
    $flagAdditions = [];
    foreach ($targetByIpl as $ipl => $existing) {
        foreach ($flagNames as $flag) {
            if (($flagsByIpl[$ipl][$flag] ?? false) && ! (bool) ($existing[$flag] ?? false)) {
                $flagAdditions[] = ['id' => $existing['id'] ?? null, 'ipl_num' => $ipl, 'flag' => $flag];
            }
        }
    }
    $targetSbByAc = [];
    foreach ($targetBulletins as $row) {
        $targetSbByAc[strtoupper(clean($row['ac_mfg_service_bulletin_no'] ?? ''))] = $row;
    }
    $sbComparison = [];
    foreach ($bulletins as $row) {
        $key = strtoupper(clean($row['ac_mfg_service_bulletin_no']));
        $existing = $targetSbByAc[$key] ?? null;
        if ($existing === null) {
            $sbComparison[] = ['status' => 'new', 'source' => $row];
            continue;
        }
        $different = [];
        foreach (['year_introduced', 'oem_service_bulletin_no', 'awd_no', 'identification_method', 'description', 'default_requirement'] as $field) {
            if (strtoupper(clean($row[$field] ?? '')) !== strtoupper(clean($existing[$field] ?? ''))) {
                $different[] = $field;
            }
        }
        $sbComparison[] = ['status' => $different === [] ? 'same' : 'different', 'source' => $row, 'target' => $existing, 'differences' => $different];
    }
    $targetComparison = [
        'manual_id' => $target['id'] ?? null,
        'component_count' => count($targetComponents),
        'service_bulletin_count' => count($targetBulletins),
        'exact_existing_count' => count($exactExisting),
        'new_count' => count($newIpls),
        'ipl_conflict_count' => count($iplConflicts),
        'existing_only_count' => count($existingOnly),
        'flag_addition_count' => count($flagAdditions),
        'exact_existing_ipls' => $exactExisting,
        'new_ipls' => $newIpls,
        'ipl_conflicts' => $iplConflicts,
        'existing_only_ipls' => $existingOnly,
        'flag_additions' => $flagAdditions,
        'service_bulletins' => $sbComparison,
    ];
}

$basicHeader = ['ipl_num', 'part_number', 'assy_part_number', 'name', 'assy_ipl_num', 'units_assy'];
writeCsv($outputDir.DIRECTORY_SEPARATOR.'parts.csv', $basicHeader, $parts);
file_put_contents(
    $outputDir.DIRECTORY_SEPARATOR.'component_flags.json',
    json_encode(array_map(static fn (string $ipl, array $flags): array => ['ipl_num' => $ipl] + $flags, array_keys($flagsByIpl), $flagsByIpl), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

$sbHeader = ['Year Introduced', 'AC MFG Service Bulletin No.', 'OEM Service Bulletin No.', 'AWD No.', 'Identification Method', 'Description', 'Reserved 1', 'Reserved 2', 'Reserved 3', 'Optional', 'Recommended', 'Mandatory'];
$sbRows = array_map(static function (array $row): array {
    return [
        'Year Introduced' => $row['year_introduced'],
        'AC MFG Service Bulletin No.' => $row['ac_mfg_service_bulletin_no'],
        'OEM Service Bulletin No.' => $row['oem_service_bulletin_no'],
        'AWD No.' => $row['awd_no'],
        'Identification Method' => $row['identification_method'],
        'Description' => $row['description'],
        'Reserved 1' => '', 'Reserved 2' => '', 'Reserved 3' => '',
        'Optional' => $row['default_requirement'] === 'optional' ? 'X' : '',
        'Recommended' => $row['default_requirement'] === 'recommended' ? 'X' : '',
        'Mandatory' => $row['default_requirement'] === 'mandatory' ? 'X' : '',
    ];
}, $bulletins);
writeCsv($outputDir.DIRECTORY_SEPARATOR.'service_bulletins.csv', $sbHeader, $sbRows);

$issueCounts = [];
foreach ($issues as $issue) {
    $issueCounts[$issue['type']] = ($issueCounts[$issue['type']] ?? 0) + 1;
}
ksort($issueCounts);
$analysis = [
    'schema_version' => 1,
    'manual_number' => $manualNumber,
    'source_dir' => $sourceDir,
    'parts_source' => $partsSource,
    'workbook' => $workbookPath,
    'pdf_figures' => array_keys($pdfFigures),
    'counts' => [
        'parts' => count($parts),
        'service_bulletins' => count($bulletins),
        'flags' => $flagCounts,
        'issues' => count($issues),
    ],
    'requires_review' => $issues !== [],
    'issue_counts' => $issueCounts,
    'issues' => $issues,
    'mappings' => $mappings,
    'service_bulletins' => $bulletins,
    'target_comparison' => $targetComparison,
];
file_put_contents($outputDir.DIRECTORY_SEPARATOR.'analysis.json', json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

$report = [
    '# AVIA manual package audit', '',
    '- Manual: `'.$manualNumber.'`',
    '- Source: `'.$sourceDir.'`',
    '- Workbook: `'.basename($workbookPath).'`',
    '- Parts: '.count($parts),
    '- Service bulletins: '.count($bulletins),
    '- Flags: '.implode(', ', array_map(static fn (string $key, int $value): string => $key.'='.$value, array_keys($flagCounts), $flagCounts)),
    '- Unresolved source issues: '.count($issues), '',
    '## Issue counts', '',
];
if ($issueCounts === []) {
    $report[] = 'None.';
} else {
    foreach ($issueCounts as $type => $count) {
        $report[] = '- `'.$type.'`: '.$count;
    }
}
$report[] = '';
$report[] = 'See `analysis.json` for exact rows and values. No database changes were made.';
file_put_contents($outputDir.DIRECTORY_SEPARATOR.'report.md', implode(PHP_EOL, $report).PHP_EOL);

echo json_encode([
    'analysis' => $outputDir.DIRECTORY_SEPARATOR.'analysis.json',
    'parts' => count($parts),
    'service_bulletins' => count($bulletins),
    'flags' => $flagCounts,
    'issues' => count($issues),
    'requires_review' => $issues !== [],
], JSON_UNESCAPED_SLASHES).PHP_EOL;
