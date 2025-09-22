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

        // Автоматическая загрузка из Manual CSV файлов
        if ($manual) {
            $csvFiles = $manual->getMedia('csv_files');
            \Log::info('Available CSV files in manual', [
                'count' => $csvFiles->count(),
                'files' => $csvFiles->map(function($file) {
                    return [
                        'name' => $file->name,
                        'process_type' => $file->getCustomProperty('process_type'),
                        'path' => $file->getPath()
                    ];
                })->toArray()
            ]);

            // Загружаем NDT компоненты
            $ndtCsvMedia = $manual->getMedia('csv_files')->first(function ($media) {
                return $media->getCustomProperty('process_type') === 'ndt';
            });

            \Log::info('NDT CSV media found', [
                'found' => $ndtCsvMedia ? true : false,
                'path' => $ndtCsvMedia ? $ndtCsvMedia->getPath() : null
            ]);

            if ($ndtCsvMedia) {
                $ndtCadCsv->ndt_components = self::loadComponentsFromCsv($ndtCsvMedia->getPath(), 'ndt');
            } else {
                // Если NDT файл не найден, попробуем загрузить первый доступный CSV
                $firstCsv = $manual->getMedia('csv_files')->first();
                if ($firstCsv) {
                    \Log::info('Loading first available CSV as NDT', ['file' => $firstCsv->name]);
                    $ndtCadCsv->ndt_components = self::loadComponentsFromCsv($firstCsv->getPath(), 'ndt');
                }
            }

            // Загружаем CAD компоненты
            $cadCsvMedia = $manual->getMedia('csv_files')->first(function ($media) {
                return $media->getCustomProperty('process_type') === 'cad';
            });

            \Log::info('CAD CSV media found', [
                'found' => $cadCsvMedia ? true : false,
                'path' => $cadCsvMedia ? $cadCsvMedia->getPath() : null
            ]);

            if ($cadCsvMedia) {
                $ndtCadCsv->cad_components = self::loadComponentsFromCsv($cadCsvMedia->getPath(), 'cad');
            } else {
                // Если CAD файл не найден, попробуем загрузить второй доступный CSV
                $secondCsv = $manual->getMedia('csv_files')->skip(1)->first();
                if ($secondCsv) {
                    \Log::info('Loading second available CSV as CAD', ['file' => $secondCsv->name]);
                    $ndtCadCsv->cad_components = self::loadComponentsFromCsv($secondCsv->getPath(), 'cad');
                }
            }

            // Загружаем Stress компоненты
            $stressCsvMedia = $manual->getMedia('csv_files')->first(function ($media) {
                return $media->getCustomProperty('process_type') === 'stress';
            });

            \Log::info('Stress CSV media found', [
                'found' => $stressCsvMedia ? true : false,
                'path' => $stressCsvMedia ? $stressCsvMedia->getPath() : null
            ]);

            if ($stressCsvMedia) {
                $ndtCadCsv->stress_components = self::loadComponentsFromCsv($stressCsvMedia->getPath(), 'stress');
            } else {
                // Если Stress файл не найден, попробуем загрузить третий доступный CSV
                // но только если это не paint файл
                $thirdCsv = $manual->getMedia('csv_files')->skip(2)->first();
                if ($thirdCsv && $thirdCsv->getCustomProperty('process_type') !== 'paint') {
                    \Log::info('Loading third available CSV as Stress', ['file' => $thirdCsv->name]);
                    $ndtCadCsv->stress_components = self::loadComponentsFromCsv($thirdCsv->getPath(), 'stress');
                } else {
                    \Log::info('No suitable CSV found for Stress, leaving empty');
                }
            }

            // Загружаем Paint компоненты
            $paintCsvMedia = $manual->getMedia('csv_files')->first(function ($media) {
                return $media->getCustomProperty('process_type') === 'paint';
            });

            \Log::info('Paint CSV media found', [
                'found' => $paintCsvMedia ? true : false,
                'path' => $paintCsvMedia ? $paintCsvMedia->getPath() : null
            ]);

            if ($paintCsvMedia) {
                $ndtCadCsv->paint_components = self::loadComponentsFromCsv($paintCsvMedia->getPath(), 'paint');
            } else {
                // Если Paint файл не найден, попробуем загрузить четвертый доступный CSV
                $fourthCsv = $manual->getMedia('csv_files')->skip(3)->first();
                if ($fourthCsv) {
                    \Log::info('Loading fourth available CSV as Paint', ['file' => $fourthCsv->name]);
                    $ndtCadCsv->paint_components = self::loadComponentsFromCsv($fourthCsv->getPath(), 'paint');
                }
            }
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
            $csvFiles = $manual->getMedia('csv_files');
            \Log::info('Available CSV files in manual', [
                'count' => $csvFiles->count(),
                'files' => $csvFiles->map(function($file) {
                    return [
                        'name' => $file->name,
                        'process_type' => $file->getCustomProperty('process_type'),
                        'path' => $file->getPath()
                    ];
                })->toArray()
            ]);

            // Загружаем NDT компоненты
            $ndtCsvMedia = $manual->getMedia('csv_files')->first(function ($media) {
                return $media->getCustomProperty('process_type') === 'ndt';
            });

            if ($ndtCsvMedia) {
                \Log::info('Loading NDT components from CSV', ['file' => $ndtCsvMedia->name]);
                $ndtCadCsv->ndt_components = self::loadComponentsFromCsv($ndtCsvMedia->getPath(), 'ndt');
            }

            // Загружаем CAD компоненты
            $cadCsvMedia = $manual->getMedia('csv_files')->first(function ($media) {
                return $media->getCustomProperty('process_type') === 'cad';
            });

            if ($cadCsvMedia) {
                \Log::info('Loading CAD components from CSV', ['file' => $cadCsvMedia->name]);
                $ndtCadCsv->cad_components = self::loadComponentsFromCsv($cadCsvMedia->getPath(), 'cad');
            }

            // Загружаем Stress компоненты
            $stressCsvMedia = $manual->getMedia('csv_files')->first(function ($media) {
                return $media->getCustomProperty('process_type') === 'stress';
            });

            if ($stressCsvMedia) {
                \Log::info('Loading Stress components from CSV', ['file' => $stressCsvMedia->name]);
                $ndtCadCsv->stress_components = self::loadComponentsFromCsv($stressCsvMedia->getPath(), 'stress');
            } else {
                // Если Stress файл не найден, попробуем загрузить третий доступный CSV (по аналогии с createForWorkorder)
                // но только если это не paint файл
                $thirdCsv = $manual->getMedia('csv_files')->skip(2)->first();
                if ($thirdCsv && $thirdCsv->getCustomProperty('process_type') !== 'paint') {
                    \Log::info('Stress CSV not found; loading third available CSV as Stress', ['file' => $thirdCsv->name]);
                    $ndtCadCsv->stress_components = self::loadComponentsFromCsv($thirdCsv->getPath(), 'stress');
                } else {
                    \Log::warning('No Stress CSV and no suitable third CSV available for fallback');
                }
            }

            // Загружаем Paint компоненты
            $paintCsvMedia = $manual->getMedia('csv_files')->first(function ($media) {
                return $media->getCustomProperty('process_type') === 'paint';
            });

            if ($paintCsvMedia) {
                \Log::info('Loading Paint components from CSV', ['file' => $paintCsvMedia->name]);
                $ndtCadCsv->paint_components = self::loadComponentsFromCsv($paintCsvMedia->getPath(), 'paint');
            } else {
                // Если Paint файл не найден, попробуем загрузить четвертый доступный CSV
                $fourthCsv = $manual->getMedia('csv_files')->skip(3)->first();
                if ($fourthCsv) {
                    \Log::info('Paint CSV not found; loading fourth available CSV as Paint', ['file' => $fourthCsv->name]);
                    $ndtCadCsv->paint_components = self::loadComponentsFromCsv($fourthCsv->getPath(), 'paint');
                } else {
                    \Log::warning('No Paint CSV and no fourth CSV available for fallback');
                }
            }
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
            $csv->setHeaderOffset(0);
            $records = iterator_to_array($csv->getRecords());

            $components = [];
            foreach ($records as $row) {
                // Попробуем разные варианты названий колонок
                $itemNo = $row['ITEM   No.'] ?? $row['ITEM No.'] ?? $row['ITEM'] ?? $row['item_no'] ?? '';
                $partNo = $row['PART No.'] ?? $row['PART'] ?? $row['part_no'] ?? '';
                $description = $row['DESCRIPTION'] ?? $row['description'] ?? '';
                $process = $row['PROCESS No.'] ?? $row['PROCESS'] ?? $row['process'] ?? '1';
                $qty = $row['QTY'] ?? $row['qty'] ?? '1';

                if (!empty($itemNo)) {
                    $components[] = [
                        'ipl_num' => $itemNo,
                        'part_number' => $partNo,
                        'description' => $description,
                        'process' => $process,
                        'qty' => (int)$qty,
                    ];
                }
            }

            \Log::info("Loaded {$type} components from CSV", [
                'file' => $csvPath,
                'count' => count($components)
            ]);

            return $components;
        } catch (\Exception $e) {
            \Log::error("Error loading {$type} components from CSV: " . $e->getMessage(), [
                'file' => $csvPath,
                'error' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}



