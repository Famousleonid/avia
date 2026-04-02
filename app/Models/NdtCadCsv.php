<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NdtCadCsv extends Model
{
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
        \Log::info('NdtCadCsv::removeNdtComponent', [
            'index' => $index,
            'components_before' => $components,
            'count_before' => count($components ?? [])
        ]);

        if (isset($components[$index])) {
            $removedComponent = $components[$index];
            unset($components[$index]);
            $this->ndt_components = array_values($components);

            \Log::info('NDT компонент удален из модели', [
                'removed_component' => $removedComponent,
                'components_after' => $this->ndt_components,
                'count_after' => count($this->ndt_components ?? [])
            ]);
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
        \Log::info('NdtCadCsv::removeCadComponent', [
            'index' => $index,
            'components_before' => $components,
            'count_before' => count($components ?? [])
        ]);

        if (isset($components[$index])) {
            $removedComponent = $components[$index];
            unset($components[$index]);
            $this->cad_components = array_values($components);

            \Log::info('CAD компонент удален из модели', [
                'removed_component' => $removedComponent,
                'components_after' => $this->cad_components,
                'count_after' => count($this->cad_components ?? [])
            ]);
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
        \Log::info('NdtCadCsv::removeStressComponent', [
            'index' => $index,
            'components_before' => $components,
            'count_before' => count($components ?? [])
        ]);

        if (isset($components[$index])) {
            $removedComponent = $components[$index];
            unset($components[$index]);
            $this->stress_components = array_values($components);

            \Log::info('Stress компонент удален из модели', [
                'removed_component' => $removedComponent,
                'components_after' => $this->stress_components,
                'count_after' => count($this->stress_components ?? [])
            ]);
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
        \Log::info('NdtCadCsv::removePaintComponent', [
            'index' => $index,
            'components_before' => $components,
            'count_before' => count($components ?? [])
        ]);

        if (isset($components[$index])) {
            $removedComponent = $components[$index];
            unset($components[$index]);
            $this->paint_components = array_values($components);

            \Log::info('Paint компонент удален из модели', [
                'removed_component' => $removedComponent,
                'components_after' => $this->paint_components,
                'count_after' => count($this->paint_components ?? [])
            ]);
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

    /**
     * Создать NdtCadCsv для workorder с автоматической загрузкой из Manual CSV
     */
    public static function createForWorkorder($workorderId)
    {
        $workorder = Workorder::findOrFail($workorderId);
        $manual = $workorder->unit->manuals;

        \Log::info('Creating NdtCadCsv for workorder', [
            'workorder_id' => $workorderId,
            'has_manual' => $manual ? true : false,
            'manual_id' => $manual ? $manual->id : null
        ]);

        $ndtCadCsv = new self([
            'workorder_id' => $workorderId,
            'ndt_components' => [],
            'cad_components' => [],
            'stress_components' => [],
            'paint_components' => []
        ]);

        if ($manual) {
            $ndtCadCsv->ndt_components = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);
            $ndtCadCsv->cad_components = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_CAD);
            $ndtCadCsv->stress_components = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_STRESS);
            $ndtCadCsv->paint_components = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_PAINT);
        } else {
            \Log::warning('No manual found for workorder', ['workorder_id' => $workorderId]);
        }

        $ndtCadCsv->save();

        \Log::info('NdtCadCsv created', [
            'ndt_components_count' => count($ndtCadCsv->ndt_components),
            'cad_components_count' => count($ndtCadCsv->cad_components),
            'stress_components_count' => count($ndtCadCsv->stress_components),
            'paint_components_count' => count($ndtCadCsv->paint_components)
        ]);

        return $ndtCadCsv;
    }

    /**
     * Загрузить компоненты из Manual CSV в существующую запись
     */
    public static function loadComponentsFromManual($workorderId, $ndtCadCsv)
    {
        $workorder = Workorder::findOrFail($workorderId);
        $manual = $workorder->unit->manuals;

        \Log::info('Loading components from manual for existing NdtCadCsv', [
            'workorder_id' => $workorderId,
            'has_manual' => $manual ? true : false,
            'manual_id' => $manual ? $manual->id : null
        ]);

        if ($manual) {
            $ndtCadCsv->ndt_components = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_NDT);
            $ndtCadCsv->cad_components = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_CAD);
            $ndtCadCsv->stress_components = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_STRESS);
            $ndtCadCsv->paint_components = StdProcess::snapshotComponentsForWorkorder($workorder, StdProcess::STD_PAINT);
        }

        $ndtCadCsv->save();

        \Log::info('NdtCadCsv updated with components', [
            'ndt_components_count' => count($ndtCadCsv->ndt_components),
            'cad_components_count' => count($ndtCadCsv->cad_components),
            'stress_components_count' => count($ndtCadCsv->stress_components),
            'paint_components_count' => count($ndtCadCsv->paint_components)
        ]);

        return $ndtCadCsv;
    }

    /**
     * Загрузить компоненты из CSV файла
     */
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

            \Log::info("Loaded {$type} components from CSV", [
                'file' => $csvPath,
                'count' => count($components),
            ]);

            return $components;
        } catch (\Exception $e) {
            \Log::error("Error loading {$type} components from CSV: ".$e->getMessage(), [
                'file' => $csvPath,
                'error' => $e->getTraceAsString(),
            ]);

            return [];
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



