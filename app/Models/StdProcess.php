<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\WorkorderStdProcessItemsService;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StdProcess extends Model
{
    use LogsActivity;

    public const STD_NDT = 'ndt';

    public const STD_CAD = 'cad';

    public const STD_STRESS = 'stress';

    public const STD_PAINT = 'paint';

    protected $table = 'std_processes';

    protected $fillable = [
        'manual_id',
        'component_id',
        'std',
        'process',
        'qty',
        'eff_code',
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('std_process')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function manual(): BelongsTo
    {
        return $this->belongsTo(Manual::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public static function validStdValues(): array
    {
        return [self::STD_NDT, self::STD_CAD, self::STD_STRESS, self::STD_PAINT];
    }

    public static function assertValidStd(string $std): void
    {
        if (! in_array($std, self::validStdValues(), true)) {
            throw new \InvalidArgumentException("Invalid std type: {$std}");
        }
    }

    public static function iplSortKey(?string $ipl): array
    {
        $value = self::sortComparableIplValue($ipl);

        if (! preg_match('/^(\d+)([A-Za-z]*)-(\d+)\s*([A-Za-z][A-Za-z0-9]*)?$/', $value, $matches)) {
            return [1, 0, '', 0, strtoupper($value)];
        }

        return [
            0,
            (int) $matches[1],
            strtoupper($matches[2] ?? ''),
            (int) $matches[3],
            strtoupper($matches[4] ?? ''),
        ];
    }

    public static function compareIplValues(?string $left, ?string $right): int
    {
        return self::iplSortKey($left) <=> self::iplSortKey($right);
    }

    protected static function sortComparableIplValue(?string $ipl): string
    {
        $value = trim((string) ($ipl ?? ''));
        if ($value === '') {
            return '';
        }

        $lines = preg_split('/\R+/', $value) ?: [];

        return trim((string) ($lines[0] ?? $value));
    }

    /**
     * Legacy numeric rank. Prefer compareIplValues()/iplSortKey() for new sorting.
     */
    public static function iplNumSortRank(?string $ipl): int
    {
        $key = self::iplSortKey($ipl);

        if (($key[0] ?? 1) !== 0) {
            return PHP_INT_MAX;
        }

        $sectionSuffix = (string) ($key[2] ?? '');
        $itemSuffix = (string) ($key[4] ?? '');

        return ((int) $key[1]) * 1_000_000_000
            + ($sectionSuffix !== '' ? (ord($sectionSuffix[0]) - 64) : 0) * 10_000_000
            + ((int) $key[3]) * 1_000
            + ($itemSuffix !== '' ? (ord($itemSuffix[0]) - 64) : 0);
    }

    /**
     * Values for the Process field from the manual Processes tab.
     *
     * @return array<int, string>
     */
    public static function processPicklistValuesForManual(int $manualId, string $std): array
    {
        return array_values(array_unique(array_map(
            static fn (array $option): string => (string) $option['value'],
            self::processPicklistOptionsForManual($manualId, $std)
        )));
    }

    /**
     * Options for the Process field from the manual Processes tab.
     *
     * @return array<int, array{value:string,label:string}>
     */
    public static function processPicklistOptionsForManual(int $manualId, string $std): array
    {
        self::assertValidStd($std);
        $mps = ManualProcess::query()
            ->where('manual_id', $manualId)
            ->with(['process.process_name'])
            ->get();

        $matchesStd = static function (?ProcessName $pn) use ($std): bool {
            if (! $pn) {
                return false;
            }
            $name = (string) ($pn->name ?? '');
            $sheet = trim((string) ($pn->process_sheet_name ?? ''));

            return match ($std) {
                self::STD_NDT => $sheet === 'NDT'
                    || in_array($name, [
                        'NDT-1', 'NDT-2', 'NDT-3', 'NDT-4',
                        'NDT-5', 'NDT-6', 'NDT-7', 'NDT-8',
                        'Eddy Current Test', 'BNI',
                    ], true),
                self::STD_CAD => $name === 'Cad plate',
                self::STD_STRESS => in_array($name, ['Bake (Stress relief)', 'Stress Relief'], true),
                self::STD_PAINT => trim($name) === 'Paint'
                    || $name === 'STD Paint List'
                    || Str::upper($sheet) === 'PAINT APPLICATION',
                default => false,
            };
        };

        $values = [];
        foreach ($mps as $mp) {
            $proc = $mp->process;
            if (! $proc || ! $matchesStd($proc->process_name)) {
                continue;
            }
            $val = trim((string) ($proc->process ?? ''));
            if ($std === self::STD_NDT) {
                $ndtNumber = self::ndtProcessNumber($proc->process_name);
                if ($ndtNumber === null) {
                    continue;
                }
                $label = trim($proc->process_name->name.' '.$val);
                $values[$ndtNumber] = [
                    'value' => $ndtNumber,
                    'label' => $label !== '' ? $label : $proc->process_name->name,
                ];
            } elseif ($val !== '') {
                $values[$val] = [
                    'value' => $val,
                    'label' => $val,
                ];
            }
        }

        if ($std === self::STD_NDT) {
            uksort($values, static fn (string $a, string $b): int => ((int) $a) <=> ((int) $b));
        }

        return array_values($values);
    }

    protected static function ndtProcessNumber(ProcessName $processName): ?string
    {
        if (preg_match('/^NDT-(\d+)\b/i', trim((string) $processName->name), $m)) {
            return (string) ((int) $m[1]);
        }

        return null;
    }

    public static function normalizeCsvProcessForManual(int $manualId, string $std, mixed $process, int $rowNumber): string
    {
        self::assertValidStd($std);

        $allowed = self::processPicklistValuesForManual($manualId, $std);
        if ($allowed === []) {
            throw ValidationException::withMessages([
                'csv' => sprintf('CSV row %d cannot be imported: no %s processes are linked to this manual.', $rowNumber, strtoupper($std)),
            ]);
        }

        if ($std === self::STD_NDT) {
            $numbers = self::extractNdtCsvProcessNumbers($process);
            if ($numbers === []) {
                throw ValidationException::withMessages([
                    'csv' => sprintf('CSV row %d has no valid NDT process number.', $rowNumber),
                ]);
            }

            $invalid = array_values(array_diff($numbers, $allowed));
            if ($invalid !== []) {
                throw ValidationException::withMessages([
                    'csv' => sprintf(
                        'CSV row %d uses NDT process %s, which is not linked to this manual.',
                        $rowNumber,
                        implode(' / ', $invalid)
                    ),
                ]);
            }

            $allowedOrder = array_flip($allowed);
            usort($numbers, static fn (string $a, string $b): int => ($allowedOrder[$a] ?? 9999) <=> ($allowedOrder[$b] ?? 9999));

            return implode(' / ', array_values(array_unique($numbers)));
        }

        $value = trim((string) ($process ?? ''));
        if ($value === '' || ! in_array($value, $allowed, true)) {
            throw ValidationException::withMessages([
                'csv' => sprintf(
                    'CSV row %d uses process "%s", which is not linked to this manual.',
                    $rowNumber,
                    $value !== '' ? $value : '(empty)'
                ),
            ]);
        }

        return $value;
    }

    /**
     * @return array<int, string>
     */
    protected static function extractNdtCsvProcessNumbers(mixed $process): array
    {
        $value = trim((string) ($process ?? ''));
        if ($value === '') {
            return [];
        }

        if (preg_match_all('/\bNDT\s*-\s*(\d+)\b/i', $value, $matches) && ! empty($matches[1])) {
            return array_values(array_unique(array_map(
                static fn (string $number): string => (string) ((int) $number),
                $matches[1]
            )));
        }

        $tokens = preg_split('/[\/,;]+/', $value) ?: [];
        $numbers = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }
            if (! preg_match('/^\d+$/', $token)) {
                return [];
            }
            $numbers[] = (string) ((int) $token);
        }

        return array_values(array_unique($numbers));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function loadComponentRowsFromCsv(string $csvPath, string $std): array
    {
        self::assertValidStd($std);

        try {
            $csv = Reader::createFromPath($csvPath, 'r');
            $csv->setDelimiter(self::detectCsvDelimiter($csvPath));
            $csv->setHeaderOffset(0);
            $records = iterator_to_array($csv->getRecords());

            $components = [];
            foreach ($records as $row) {
                $row = self::normalizeCsvRowKeys($row);

                $itemNo = self::csvPickFirstNonEmptyString($row, [
                    'item no.', 'item', 'item no', 'item_no', 'ipl', 'ipl num',
                ]) ?? '';

                $partNo = self::csvPickFirstNonEmptyString($row, [
                    'part no.', 'part', 'part_no', 'pn',
                ]) ?? '';

                $description = self::csvPickFirstNonEmptyString($row, [
                    'description', 'desc', 'name',
                ]) ?? '';

                $process = self::csvPickFirstNonEmptyString($row, [
                    'process no.', 'process', 'proc.', 'proc',
                ]) ?? '1';

                $qtyStr = self::csvPickFirstNonEmptyString($row, ['qty', 'quantity']);
                $qty = $qtyStr !== null ? (int) $qtyStr : 1;

                $manual = self::csvPickFirstNonEmptyString($row, [
                    'manual',
                    'cmm no.', 'cmm no', 'cmmno', 'cmm', 'cmm number',
                    'smm no.', 'smm no',
                ]);

                $effCodeRaw = self::csvPickFirstNonEmptyString($row, [
                    'eff code', 'eff_code', 'effcode',
                ]) ?? '';

                if ($itemNo === '') {
                    continue;
                }

                $component = [
                    'ipl_num' => $itemNo,
                    'part_number' => $partNo,
                    'description' => $description,
                    'process' => $process,
                    'qty' => $qty,
                ];

                if ($manual !== null && $manual !== '') {
                    $component['manual'] = $manual;
                }
                if ($effCodeRaw !== '') {
                    $norm = self::normalizeEffCodeForStorage($effCodeRaw);
                    if ($norm !== null) {
                        $component['eff_code'] = $norm;
                    }
                }

                $components[] = $component;
            }

            return $components;
        } catch (\Throwable $e) {
            \Log::error('Error loading STD rows from CSV: '.$e->getMessage(), [
                'file' => $csvPath,
                'std' => $std,
            ]);

            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    public static function validateStdCsvFormat(string $csvPath): array
    {
        try {
            $csv = Reader::createFromPath($csvPath, 'r');
            $csv->setDelimiter(self::detectCsvDelimiter($csvPath));
            $csv->setHeaderOffset(0);
            $headers = array_map(
                static function ($header): string {
                    $header = (string) $header;
                    $header = preg_replace('/^\x{FEFF}/u', '', $header);
                    $header = str_replace("\xEF\xBB\xBF", '', $header);
                    $header = trim($header);
                    $header = preg_replace('/^[\-\x{2013}\x{2014}]+/u', '', $header);
                    return preg_replace('/\s+/', ' ', $header);
                },
                $csv->getHeader()
            );
            $normalizedHeaders = array_map(
                static fn (string $header): string => strtolower($header),
                $headers
            );

            $required = [
                'item no. / IPL' => ['item no.', 'item', 'item no', 'item_no', 'ipl', 'ipl num'],
                'part no.' => ['part no.', 'part', 'part_no', 'pn'],
                'description' => ['description', 'desc', 'name'],
                'process no.' => ['process no.', 'process', 'proc.', 'proc'],
            ];

            $errors = [];
            foreach ($required as $label => $aliases) {
                if (count(array_intersect($normalizedHeaders, $aliases)) === 0) {
                    $errors[] = 'Missing required column: '.$label;
                }
            }

            if ($errors !== []) {
                return $errors;
            }

            $records = iterator_to_array($csv->getRecords());
            if ($records === []) {
                return ['CSV file has no data rows.'];
            }

            foreach ($records as $offset => $row) {
                $row = self::normalizeCsvRowKeys($row);
                $line = (int) $offset + 2;
                $itemNo = self::csvPickFirstNonEmptyString($row, ['item no.', 'item', 'item no', 'item_no', 'ipl', 'ipl num']);
                $partNo = self::csvPickFirstNonEmptyString($row, ['part no.', 'part', 'part_no', 'pn']);
                $description = self::csvPickFirstNonEmptyString($row, ['description', 'desc', 'name']);
                $process = self::csvPickFirstNonEmptyString($row, ['process no.', 'process', 'proc.', 'proc']);
                $qty = self::csvPickFirstNonEmptyString($row, ['qty', 'quantity']);

                if ($itemNo === null) {
                    $errors[] = "Line {$line}: item no. / IPL is required.";
                }
                if ($partNo === null) {
                    $errors[] = "Line {$line}: part no. is required.";
                }
                if ($description === null) {
                    $errors[] = "Line {$line}: description is required.";
                }
                if ($process === null) {
                    $errors[] = "Line {$line}: process no. is required.";
                }
                if ($qty !== null && (! ctype_digit($qty) || (int) $qty < 1)) {
                    $errors[] = "Line {$line}: qty must be a positive integer.";
                }
            }

            return array_slice($errors, 0, 10);
        } catch (\Throwable $e) {
            return ['CSV format error: '.$e->getMessage()];
        }
    }

    /**
     * Ключ IPL + Part № для проверки дубликата строки STD (совпадает с duplicateKeyForClient на клиенте).
     */
    public static function duplicateKeyForClient(?string $ipl, ?string $partNumber): string
    {
        return trim((string) ($ipl ?? ''))."\n".trim((string) ($partNumber ?? ''));
    }

    /**
     * Уже есть строка с тем же типом STD и той же деталью (IPL + Part №) для данного мануала.
     */
    public static function rowExistsForComponentStd(int $manualId, int $componentId, string $std): bool
    {
        self::assertValidStd($std);

        return self::query()
            ->where('manual_id', $manualId)
            ->where('component_id', $componentId)
            ->where('std', $std)
            ->exists();
    }

    /**
     * Compare parsed STD CSV rows against Parts. Components are the source of truth.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function reviewComponentRowsAgainstParts(int $manualId, array $rows): array
    {
        $componentsByIpl = Component::query()
            ->where('manual_id', $manualId)
            ->get(['id', 'ipl_num', 'part_number', 'name'])
            ->keyBy(fn (Component $component): string => self::normalizeCompareValue($component->ipl_num));

        $conflicts = [];
        foreach ($rows as $index => $row) {
            $ipl = trim((string) ($row['ipl_num'] ?? ''));
            if ($ipl === '') {
                continue;
            }

            $component = $componentsByIpl->get(self::normalizeCompareValue($ipl));
            if (! $component) {
                $conflicts[] = [
                    'index' => $index,
                    'row_number' => $index + 2,
                    'type' => 'missing_ipl',
                    'ipl_num' => $ipl,
                    'csv_part_number' => (string) ($row['part_number'] ?? ''),
                    'csv_description' => (string) ($row['description'] ?? ''),
                ];
                continue;
            }

            $csvDescription = trim((string) ($row['description'] ?? ''));
            $componentName = trim((string) ($component->name ?? ''));
            if ($csvDescription !== '' && self::normalizeCompareValue($csvDescription) !== self::normalizeCompareValue($componentName)) {
                $conflicts[] = [
                    'index' => $index,
                    'row_number' => $index + 2,
                    'type' => 'name_mismatch',
                    'ipl_num' => $ipl,
                    'csv_part_number' => (string) ($row['part_number'] ?? ''),
                    'csv_description' => $csvDescription,
                    'component_id' => (int) $component->id,
                    'component_part_number' => (string) ($component->part_number ?? ''),
                    'component_name' => $componentName,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Replace all rows for (manual_id, std) with parsed CSV-style component rows.
     * Rows are matched to Parts by IPL; CSV part names never overwrite existing Components.
     *
     * @param  array<int, array{ipl_num:string,part_number?:string,description?:string,process?:mixed,qty?:int,eff_code?:string|null}>  $rows
     * @param  array<int|string, string>  $resolutions
     */
    public static function replaceFromComponentRows(int $manualId, string $std, array $rows, array $resolutions = []): void
    {
        self::assertValidStd($std);

        DB::transaction(function () use ($manualId, $std, $rows, $resolutions) {
            $flagColumn = self::componentFlagColumnForStd($std);
            $componentsByKey = Component::query()
                ->where('manual_id', $manualId)
                ->get(['id', 'ipl_num', 'part_number', 'name', 'units_assy'])
                ->keyBy(fn (Component $component): string => self::normalizeCompareValue($component->ipl_num));

            $importRows = [];
            foreach ($rows as $index => $row) {
                $ipl = trim((string) ($row['ipl_num'] ?? ''));
                if ($ipl === '') {
                    continue;
                }

                $component = $componentsByKey->get(self::normalizeCompareValue($ipl));
                if (! $component) {
                    $action = (string) ($resolutions[(string) $index] ?? $resolutions[$index] ?? '');
                    if ($action === 'skip') {
                        continue;
                    }
                    if ($action !== 'add_component') {
                        throw ValidationException::withMessages([
                            'csv' => sprintf('CSV row %d uses IPL "%s", which does not exist in Parts.', $index + 2, $ipl),
                        ]);
                    }

                    $component = Component::query()->create([
                        'manual_id' => $manualId,
                        'ipl_num' => $ipl,
                        'part_number' => trim((string) ($row['part_number'] ?? '')),
                        'name' => trim((string) ($row['description'] ?? '')),
                        'units_assy' => max(1, (int) ($row['qty'] ?? 1)),
                    ]);
                    $componentsByKey->put(self::normalizeCompareValue($ipl), $component);
                } else {
                    $csvDescription = trim((string) ($row['description'] ?? ''));
                    $componentName = trim((string) ($component->name ?? ''));
                    if ($csvDescription !== '' && self::normalizeCompareValue($csvDescription) !== self::normalizeCompareValue($componentName)) {
                        $action = (string) ($resolutions[(string) $index] ?? $resolutions[$index] ?? '');
                        if ($action === 'skip') {
                            continue;
                        }
                        if ($action === 'overwrite_component') {
                            $component->update([
                                'part_number' => trim((string) ($row['part_number'] ?? $component->part_number ?? '')),
                                'name' => $csvDescription,
                                'units_assy' => max(1, (int) ($row['qty'] ?? $component->units_assy ?? 1)),
                            ]);
                            $component->refresh();
                        } elseif ($action !== 'use_component') {
                            throw ValidationException::withMessages([
                                'csv' => sprintf('CSV row %d has a different part name for IPL "%s".', $index + 2, $ipl),
                            ]);
                        }
                    }
                }

                $process = self::normalizeCsvProcessForManual($manualId, $std, $row['process'] ?? null, $index + 2);
                $importRows[] = [$row, $component, $process];
            }

            self::query()
                ->where('manual_id', $manualId)
                ->where('std', $std)
                ->get()
                ->each
                ->delete();

            Component::withoutEvents(function () use ($manualId, $flagColumn): void {
                Component::query()
                    ->where('manual_id', $manualId)
                    ->update([$flagColumn => false]);
            });

            $matchedComponentIds = array_map(
                static fn (array $pair): int => (int) $pair[1]->id,
                $importRows
            );
            if ($matchedComponentIds !== []) {
                Component::withoutEvents(function () use ($matchedComponentIds, $flagColumn): void {
                    Component::query()
                        ->whereIn('id', array_values(array_unique($matchedComponentIds)))
                        ->update([$flagColumn => true]);
                });
            }

            foreach ($importRows as [$row, $component, $process]) {
                $effVal = $row['eff_code'] ?? null;
                self::query()->create([
                    'manual_id' => $manualId,
                    'component_id' => $component->id,
                    'std' => $std,
                    'process' => $process,
                    'qty' => (int) ($row['qty'] ?? 1),
                    'eff_code' => self::normalizeEffCodeForStorage($effVal !== null ? (string) $effVal : null),
                ]);
            }
        });
    }

    /**
     * Коды EFF в одном формате: через запятую, пробелы после запятой допустимы; токены не нормализуют по регистру при хранении.
     *
     * @return array<int, string>
     */
    public static function effCodeTokens(?string $value): array
    {
        if ($value === null) {
            return [];
        }
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(
            preg_split('/[\s,;\/]+/', $value) ?: [],
            static fn (string $t): bool => trim($t) !== ''
        ));
    }

    public static function normalizeEffCodeForStorage(?string $value): ?string
    {
        $tokens = self::effCodeTokens($value);
        if ($tokens === []) {
            return null;
        }

        return implode(', ', $tokens);
    }

    /**
     * Empty WO unit EFF keeps all STD rows in the snapshot.
     * Empty STD row EFF means the row is universal for any filled unit EFF.
     * Иначе: пересечение токенов (сравнение без учёта регистра), формат «A, B, ав» и т.д.
     */
    public static function stdRowEffMatchesUnit(?string $rowEff, string $unitEff): bool
    {
        $unitTokens = self::effCodeTokens($unitEff);
        if ($unitTokens === []) {
            return true;
        }

        $rowTokens = self::effCodeTokens($rowEff);
        if ($rowTokens === []) {
            return true;
        }

        foreach ($rowTokens as $rt) {
            foreach ($unitTokens as $ut) {
                if (strcasecmp($rt, $ut) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Snapshot for all manual rows without EFF filtering. Kept for compatibility.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function snapshotComponentsForManual(int $manualId, string $std): array
    {
        self::assertValidStd($std);

        $records = self::query()
            ->where('manual_id', $manualId)
            ->where('std', $std)
            ->with('component.manual:id,number')
            ->orderBy('id')
            ->get();

        return self::sortRowsForSnapshot(self::recordsToSnapshotRows($records));
    }

    /**
     * Снимок STD для workorder с учётом EFF Code юнита (вкладка Components).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function snapshotComponentsForWorkorder(Workorder $workorder, string $std): array
    {
        self::assertValidStd($std);

        return app(WorkorderStdProcessItemsService::class)->snapshotRowsForWorkorder($workorder, $std);
    }

    /**
     * Build STD rows from Manual Parts component flags.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function snapshotFlaggedComponentsForWorkorder(Workorder $workorder, string $std, string $unitEff): array
    {
        $manual = $workorder->unit->manuals ?? null;
        if (! $manual) {
            return [];
        }

        $flagColumn = self::componentFlagColumnForStd($std);
        $process = self::defaultProcessForFlagSnapshot((int) $manual->id, $std);

        $components = Component::query()
            ->where('manual_id', $manual->id)
            ->where($flagColumn, true)
            ->orderBy('id')
            ->get();

        $branchResolver = app(\App\Services\ManualIplBranchRuleResolver::class);
        $rows = [];
        foreach ($components as $component) {
            if (! $branchResolver->allowsComponentForUnit($workorder->unit, (string) ($component->ipl_num ?? ''), (int) $manual->id)) {
                continue;
            }

            if (! self::stdRowEffMatchesUnit($component->eff_code, $unitEff)) {
                continue;
            }

            $qty = (int) ($component->units_assy ?? 1);
            $rows[] = [
                'ipl_num' => (string) ($component->ipl_num ?? ''),
                'part_number' => (string) ($component->part_number ?? ''),
                'description' => (string) ($component->name ?? ''),
                'process' => $process,
                'qty' => $qty > 0 ? $qty : 1,
                'manual' => (string) ($component->manual?->number ?? $manual->number ?? ''),
                'eff_code' => self::normalizeEffCodeForStorage($component->eff_code) ?? '',
            ];
        }

        return self::sortRowsForSnapshot($rows);
    }

    public static function componentFlagColumnForStd(string $std): string
    {
        return match ($std) {
            self::STD_NDT => 'ndt_list',
            self::STD_CAD => 'cad_list',
            self::STD_STRESS => 'stress_relief_list',
            self::STD_PAINT => 'paint_list',
            default => throw new \InvalidArgumentException("Invalid std type: {$std}"),
        };
    }

    protected static function defaultProcessForFlagSnapshot(int $manualId, string $std): string
    {
        if ($std === self::STD_PAINT) {
            $paintProcess = ManualProcess::query()
                ->where('manual_id', $manualId)
                ->whereHas('process.process_name', function ($query) {
                    $query->where('id', 25)
                        ->orWhere('name', 'Paint')
                        ->orWhere('process_sheet_name', 'PAINT APPLICATION');
                })
                ->with('process:id,process')
                ->first();

            if ($paintProcess?->process) {
                return (string) $paintProcess->process->process;
            }
        }

        $values = self::processPicklistValuesForManual($manualId, $std);

        return (string) ($values[0] ?? '1');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, self>  $records
     * @return array<int, array<string, mixed>>
     */
    protected static function recordsToSnapshotRows($records): array
    {
        $rows = [];
        foreach ($records as $r) {
            $rows[] = self::recordToSnapshotRow($r);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function recordToSnapshotRow(self $r): array
    {
        $row = [
            'ipl_num' => $r->component?->ipl_num ?? '',
            'part_number' => $r->component?->part_number ?? '',
            'description' => $r->component?->name ?? '',
            'process' => (string) $r->process,
            'qty' => (int) $r->qty,
        ];
        $sourceManual = trim((string) ($r->component?->manual?->number ?? ''));
        if ($sourceManual !== '') {
            $row['manual'] = $sourceManual;
        }
        $row['eff_code'] = self::normalizeEffCodeForStorage($r->eff_code) ?? '';

        return $row;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function sortRowsForSnapshot(array $rows): array
    {
        usort($rows, function ($a, $b) {
            $manualA = $a['manual'] ?? '';
            $manualB = $b['manual'] ?? '';
            $manualCompare = strnatcasecmp((string) $manualA, (string) $manualB);
            if ($manualCompare !== 0) {
                return $manualCompare;
            }

            $iplCompare = self::compareIplValues((string) ($a['ipl_num'] ?? ''), (string) ($b['ipl_num'] ?? ''));
            if ($iplCompare !== 0) {
                return $iplCompare;
            }

            return strnatcasecmp((string) ($a['part_number'] ?? ''), (string) ($b['part_number'] ?? ''));
        });

        return $rows;
    }

    public static function syncFromComponentFlagsForManual(Manual $manual): void
    {
        foreach (self::validStdValues() as $std) {
            self::syncFromComponentFlagsForManualStd($manual, $std);
        }
    }

    public static function syncFromComponentFlagsForManualWhenCountsDiffer(Manual $manual): void
    {
        foreach (self::validStdValues() as $std) {
            self::syncFromComponentFlagsForManualStd($manual, $std);
        }
    }

    public static function syncFromComponentFlagsForManualStd(Manual $manual, string $std): void
    {
        self::assertValidStd($std);

        DB::transaction(function () use ($manual, $std): void {
            $flagColumn = self::componentFlagColumnForStd($std);
            $flaggedComponents = Component::query()
                ->where('manual_id', $manual->id)
                ->where($flagColumn, true)
                ->orderBy('id')
                ->get();

            $flaggedKeys = $flaggedComponents
                ->mapWithKeys(fn (Component $component): array => [
                    (int) $component->id => true,
                ]);

            self::query()
                ->where('manual_id', $manual->id)
                ->where('std', $std)
                ->whereNotNull('component_id')
                ->with('component:id,manual_id')
                ->get()
                ->each(function (self $row) use ($flaggedKeys, $manual): void {
                    if (! $row->component) {
                        $row->delete();

                        return;
                    }

                    if ((int) $row->component->manual_id === (int) $manual->id
                        && ! $flaggedKeys->has((int) $row->component_id)) {
                        $row->delete();
                    }
                });

            foreach ($flaggedComponents as $component) {
                $ipl = trim((string) ($component->ipl_num ?? ''));
                if ($ipl === '') {
                    continue;
                }

                $row = self::query()->firstOrNew(
                    [
                        'manual_id' => $manual->id,
                        'component_id' => $component->id,
                        'std' => $std,
                    ]
                );

                if (! $row->exists || trim((string) $row->process) === '') {
                    $row->process = self::defaultProcessForFlagSnapshot((int) $manual->id, $std);
                }

                $row->qty = max(1, (int) ($component->units_assy ?? 1));
                $row->eff_code = self::normalizeEffCodeForStorage($component->eff_code);
                $row->save();
            }
        });
    }

    protected static function normalizeCompareValue(?string $value): string
    {
        return Str::of((string) ($value ?? ''))
            ->squish()
            ->lower()
            ->toString();
    }

    private static function detectCsvDelimiter(string $csvPath): string
    {
        $fh = fopen($csvPath, 'rb');
        if (! $fh) {
            return ',';
        }
        $line = fgets($fh);
        fclose($fh);
        if ($line === false || $line === '') {
            return ',';
        }
        $line = preg_replace('/^\x{FEFF}/u', '', $line);
        $line = str_replace("\xEF\xBB\xBF", '', $line);
        $semi = substr_count($line, ';');
        $comma = substr_count($line, ',');

        return $semi > $comma ? ';' : ',';
    }

    /**
     * @param  array<string|int, mixed>  $row
     * @return array<string, mixed>
     */
    private static function normalizeCsvRowKeys(array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            $k = (string) $key;
            $k = preg_replace('/^\x{FEFF}/u', '', $k);
            $k = str_replace("\xEF\xBB\xBF", '', $k);
            $k = trim($k);
            $k = preg_replace('/^[\-\x{2013}\x{2014}]+/u', '', $k);
            $k = trim($k);
            $k = preg_replace('/\s+/', ' ', $k);
            $out[strtolower($k)] = $value;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $norm
     * @param  array<int, string>  $candidateLogicalKeysHint
     */
    private static function csvPickFirstNonEmptyString(array $norm, array $candidateLogicalKeysHint): ?string
    {
        foreach ($candidateLogicalKeysHint as $logical) {
            $lk = strtolower(preg_replace('/\s+/', ' ', trim((string) $logical)));
            $lk = preg_replace('/^[\-\x{2013}\x{2014}]+/u', '', $lk);
            $lk = trim(preg_replace('/\s+/', ' ', $lk));

            if (! array_key_exists($lk, $norm)) {
                continue;
            }
            $v = $norm[$lk];
            if ($v === null || $v === '') {
                continue;
            }

            return trim((string) $v);
        }

        return null;
    }

}
