<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NdtCadCsv extends Model
{
    // TODO(ndt_cad_csv): move the remaining STD CSV parsing helpers to a dedicated
    // importer, then drop this legacy model/table completely.
    protected $table = 'ndt_cad_csv';

    protected $fillable = [
        'workorder_id',
        'ndt_components',
        'cad_components',
        'stress_components',
        'paint_components',
    ];

    protected $casts = [
        'ndt_components' => 'array',
        'cad_components' => 'array',
        'stress_components' => 'array',
        'paint_components' => 'array',
    ];

    /**
     * Связь с Workorder (один-к-одному)
     */
    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    /**
     * Получить NDT компоненты в структурированном виде
     */
    public function getNdtComponentsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Получить CAD компоненты в структурированном виде
     */
    public function getCadComponentsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Получить Stress компоненты в структурированном виде
     */
    public function getStressComponentsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Получить Paint компоненты в структурированном виде
     */
    public function getPaintComponentsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Установить NDT компоненты
     */
    public function setNdtComponentsAttribute($value)
    {
        $this->attributes['ndt_components'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Установить CAD компоненты
     */
    public function setCadComponentsAttribute($value)
    {
        $this->attributes['cad_components'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Установить Stress компоненты
     */
    public function setStressComponentsAttribute($value)
    {
        $this->attributes['stress_components'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Установить Paint компоненты
     */
    public function setPaintComponentsAttribute($value)
    {
        $this->attributes['paint_components'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Добавить NDT компонент
     */
    public function addNdtComponent(array $component): void
    {
        $components = $this->ndt_components;
        $components[] = $component;
        $this->ndt_components = $components;
    }

    /**
     * Добавить CAD компонент
     */
    public function addCadComponent(array $component): void
    {
        $components = $this->cad_components;
        $components[] = $component;
        $this->cad_components = $components;
    }

    /**
     * Добавить Stress компонент
     */
    public function addStressComponent(array $component): void
    {
        $components = $this->stress_components;
        $components[] = $component;
        $this->stress_components = $components;
    }

    /**
     * Добавить Paint компонент
     */
    public function addPaintComponent(array $component): void
    {
        $components = $this->paint_components;
        $components[] = $component;
        $this->paint_components = $components;
    }

    /**
     * Удалить NDT компонент по индексу
     */
    public function removeNdtComponent(int $index): void
    {
        $components = $this->ndt_components;
        if (isset($components[$index])) {
            $removedComponent = $components[$index];
            unset($components[$index]);
            $this->ndt_components = array_values($components);
        } else {
            \Log::warning('NDT компонент не найден по индексу', [
                'index' => $index,
                'available_indices' => array_keys($components ?? [])
            ]);
        }
    }

    /**
     * Удалить CAD компонент по индексу
     */
    public function removeCadComponent(int $index): void
    {
        $components = $this->cad_components;
        if (isset($components[$index])) {
            $removedComponent = $components[$index];
            unset($components[$index]);
            $this->cad_components = array_values($components);
        } else {
            \Log::warning('CAD компонент не найден по индексу', [
                'index' => $index,
                'available_indices' => array_keys($components ?? [])
            ]);
        }
    }

    /**
     * Удалить Stress компонент по индексу
     */
    public function removeStressComponent(int $index): void
    {
        $components = $this->stress_components;
        if (isset($components[$index])) {
            $removedComponent = $components[$index];
            unset($components[$index]);
            $this->stress_components = array_values($components);
        } else {
            \Log::warning('Stress компонент не найден по индексу', [
                'index' => $index,
                'available_indices' => array_keys($components ?? [])
            ]);
        }
    }

    /**
     * Удалить Paint компонент по индексу
     */
    public function removePaintComponent(int $index): void
    {
        $components = $this->paint_components;
        if (isset($components[$index])) {
            $removedComponent = $components[$index];
            unset($components[$index]);
            $this->paint_components = array_values($components);
        } else {
            \Log::warning('Paint компонент не найден по индексу', [
                'index' => $index,
                'available_indices' => array_keys($components ?? [])
            ]);
        }
    }

    /**
     * Обновить NDT компонент по индексу
     */
    public function updateNdtComponent(int $index, array $component): void
    {
        $components = $this->ndt_components;
        if (isset($components[$index])) {
            $components[$index] = $component;
            $this->ndt_components = $components;
        }
    }

    /**
     * Обновить CAD компонент по индексу
     */
    public function updateCadComponent(int $index, array $component): void
    {
        $components = $this->cad_components;
        if (isset($components[$index])) {
            $components[$index] = $component;
            $this->cad_components = $components;
        }
    }

    /**
     * Обновить Stress компонент по индексу
     */
    public function updateStressComponent(int $index, array $component): void
    {
        $components = $this->stress_components;
        if (isset($components[$index])) {
            $components[$index] = $component;
            $this->stress_components = $components;
        }
    }

    /**
     * Обновить Paint компонент по индексу
     */
    public function updatePaintComponent(int $index, array $component): void
    {
        $components = $this->paint_components;
        if (isset($components[$index])) {
            $components[$index] = $component;
            $this->paint_components = $components;
        }
    }


    public static function loadComponentsFromCsv($csvPath, $type)
    {
        try {
            $csv = \League\Csv\Reader::createFromPath($csvPath, 'r');
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

                if ($itemNo !== '') {
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
                        $norm = StdProcess::normalizeEffCodeForStorage($effCodeRaw);
                        if ($norm !== null) {
                            $component['eff_code'] = $norm;
                        }
                    }

                    $components[] = $component;
                }
            }

            return $components;
        } catch (\Exception $e) {
            \Log::error("Error loading {$type} components from CSV: ".$e->getMessage(), [
                'file' => $csvPath,
                'error' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Validate STD CSV headers and required row values before import.
     *
     * @return array<int, string>
     */
    public static function validateStdCsvFormat(string $csvPath): array
    {
        try {
            $csv = \League\Csv\Reader::createFromPath($csvPath, 'r');
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
     * Приводит ключи строки CSV к нижнему регистру, один пробел между словами, убирает BOM и ведущий «-» у заголовка.
     *
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
     * @param  array<string, mixed>  $norm  ключи уже нормализованы (строка поиска — lowercase, пробелы схлопнуты)
     * @param  array<int, string>  $candidateLogicalKeys
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



