<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StdProcess extends Model
{
    public const STD_NDT = 'ndt';

    public const STD_CAD = 'cad';

    public const STD_STRESS = 'stress';

    public const STD_PAINT = 'paint';

    protected $table = 'std_processes';

    protected $fillable = [
        'manual_id',
        'std',
        'ipl_num',
        'part_number',
        'description',
        'process',
        'qty',
        'manual',
        'eff_code',
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    public function manual(): BelongsTo
    {
        return $this->belongsTo(Manual::class);
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

    /**
     * Числовой ключ для сортировки IPL в «натуральном» порядке (1-10, 1-20, 1-20A, …), как у Parts на manuals.show.
     */
    public static function iplNumSortRank(?string $ipl): int
    {
        $ipl = (string) ($ipl ?? '');
        if (! preg_match('/^(\d+)-(\d+)([A-Za-z]?)$/', $ipl, $m)) {
            return PHP_INT_MAX;
        }

        $section = (int) $m[1];
        $number = (int) $m[2];
        $suffix = strtoupper($m[3] ?? '');
        $suffixVal = $suffix === '' ? 0 : (ord($suffix) - 64);

        return $section * 1_000_000 + $number * 100 + $suffixVal;
    }

    /**
     * Значения поля Process (процедура) с вкладки Processes руководства для добавления строки STD CAD / Stress / Paint.
     *
     * @return array<int, string>
     */
    public static function processPicklistValuesForManual(int $manualId, string $std): array
    {
        self::assertValidStd($std);
        if ($std === self::STD_NDT) {
            return [];
        }

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
            if ($val !== '') {
                $values[$val] = true;
            }
        }

        return array_keys($values);
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
    public static function rowExistsForManualStdPart(int $manualId, string $std, ?string $ipl, ?string $partNumber): bool
    {
        self::assertValidStd($std);
        $ipl = trim((string) ($ipl ?? ''));
        $pn = trim((string) ($partNumber ?? ''));

        $q = self::query()
            ->where('manual_id', $manualId)
            ->where('std', $std)
            ->where('ipl_num', $ipl);

        if ($pn === '') {
            $q->where(function ($sub) {
                $sub->whereNull('part_number')->orWhere('part_number', '');
            });
        } else {
            $q->where('part_number', $pn);
        }

        return $q->exists();
    }

    /**
     * Replace all rows for (manual_id, std) with parsed CSV-style component rows.
     *
     * @param  array<int, array{ipl_num:string,part_number?:string,description?:string,process?:mixed,qty?:int,manual?:string,eff_code?:string|null}>  $rows
     */
    public static function replaceFromComponentRows(int $manualId, string $std, array $rows): void
    {
        self::assertValidStd($std);

        DB::transaction(function () use ($manualId, $std, $rows) {
            self::query()->where('manual_id', $manualId)->where('std', $std)->delete();

            foreach ($rows as $row) {
                if (empty($row['ipl_num'])) {
                    continue;
                }
                $manualVal = $row['manual'] ?? null;
                $effVal = $row['eff_code'] ?? null;
                self::query()->create([
                    'manual_id' => $manualId,
                    'std' => $std,
                    'ipl_num' => (string) $row['ipl_num'],
                    'part_number' => (string) ($row['part_number'] ?? ''),
                    'description' => isset($row['description']) ? (string) $row['description'] : null,
                    'process' => (string) ($row['process'] ?? '1'),
                    'qty' => (int) ($row['qty'] ?? 1),
                    'manual' => $manualVal !== null && $manualVal !== '' ? (string) $manualVal : null,
                    'eff_code' => self::normalizeEffCodeForStorage($effVal !== null ? (string) $effVal : null),
                ]);
            }
        });
    }

    public static function replaceFromCsvPath(int $manualId, string $std, string $csvPath): void
    {
        $rows = NdtCadCsv::loadComponentsFromCsv($csvPath, $std);
        self::replaceFromComponentRows($manualId, $std, $rows);
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
            array_map('trim', explode(',', $value)),
            static fn (string $t): bool => $t !== ''
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
     * Пустой EFF у юнита WO — в снимок попадают все строки STD.
     * Пустой EFF у строки STD — строка универсальная (любой юнит с заполненным EFF).
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
     * Снимок для всех строк мануала (без фильтра EFF) — превью в админке не используется; для совместимости.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function snapshotComponentsForManual(int $manualId, string $std): array
    {
        self::assertValidStd($std);

        $records = self::query()
            ->where('manual_id', $manualId)
            ->where('std', $std)
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

        $manual = $workorder->unit->manuals ?? null;
        if (! $manual) {
            return [];
        }

        $unitEff = trim((string) ($workorder->unit->eff_code ?? ''));

        $records = self::query()
            ->where('manual_id', $manual->id)
            ->where('std', $std)
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($records as $r) {
            if (! self::stdRowEffMatchesUnit($r->eff_code, $unitEff)) {
                continue;
            }
            $rows[] = self::recordToSnapshotRow($r);
        }

        return self::sortRowsForSnapshot($rows);
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
            'ipl_num' => $r->ipl_num,
            'part_number' => $r->part_number ?? '',
            'description' => $r->description ?? '',
            'process' => (string) $r->process,
            'qty' => (int) $r->qty,
        ];
        if ($r->manual !== null && $r->manual !== '') {
            $row['manual'] = $r->manual;
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

            $aParts = explode('-', (string) ($a['ipl_num'] ?? ''));
            $bParts = explode('-', (string) ($b['ipl_num'] ?? ''));
            $aFirst = (int) ($aParts[0] ?? 0);
            $bFirst = (int) ($bParts[0] ?? 0);
            if ($aFirst !== $bFirst) {
                return $aFirst - $bFirst;
            }
            $aSecond = (int) ($aParts[1] ?? 0);
            $bSecond = (int) ($bParts[1] ?? 0);

            return $aSecond - $bSecond;
        });

        return $rows;
    }

    /**
     * Re-read every attached STD CSV from media and replace std_processes rows (per type).
     * Types without a readable CSV file are left unchanged.
     */
    public static function reimportAllTypesFromMedia(Manual $manual): void
    {
        foreach (self::validStdValues() as $std) {
            $media = self::findCsvMediaForStd($manual, $std);
            if ($media && is_readable($media->getPath())) {
                self::replaceFromCsvPath($manual->id, $std, $media->getPath());
            }
        }
    }

    /**
     * If there are no DB rows for this manual+std but a csv_files media exists, import from file.
     */
    public static function syncFromMediaIfEmpty(Manual $manual, string $std): bool
    {
        self::assertValidStd($std);

        if (self::query()->where('manual_id', $manual->id)->where('std', $std)->exists()) {
            return true;
        }

        $media = self::findCsvMediaForStd($manual, $std);
        if (! $media || ! is_readable($media->getPath())) {
            return false;
        }

        try {
            self::replaceFromCsvPath($manual->id, $std, $media->getPath());
        } catch (\Throwable $e) {
            \Log::error('StdProcess::syncFromMediaIfEmpty failed', [
                'manual_id' => $manual->id,
                'std' => $std,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * For STD admin tab: fill any empty std bucket from attached CSV.
     */
    public static function syncEmptyTypesFromMedia(Manual $manual): void
    {
        foreach (self::validStdValues() as $std) {
            self::syncFromMediaIfEmpty($manual, $std);
        }
    }

    public static function findCsvMediaForStd(Manual $manual, string $std): ?\Spatie\MediaLibrary\MediaCollections\Models\Media
    {
        $collection = $manual->getMedia('csv_files');

        $byType = $collection->first(function ($media) use ($std) {
            return $media->getCustomProperty('process_type') === $std;
        });
        if ($byType) {
            return $byType;
        }

        $fileNameMatch = function ($media) use ($std) {
            $fileName = strtolower($media->file_name ?? '');

            return match ($std) {
                self::STD_CAD => str_contains($fileName, 'cad_std') || str_contains($fileName, 'cad'),
                self::STD_NDT => str_contains($fileName, 'ndt_std') || str_contains($fileName, 'ndt'),
                self::STD_STRESS => str_contains($fileName, 'stress') || str_contains($fileName, 'stress_relief'),
                self::STD_PAINT => str_contains($fileName, 'paint'),
                default => false,
            };
        };

        return $collection->first($fileNameMatch);
    }

    public static function deleteForManualAndStd(int $manualId, string $std): void
    {
        self::assertValidStd($std);
        self::query()->where('manual_id', $manualId)->where('std', $std)->delete();
    }
}
