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
    ];

    protected $casts = [
        'ndt_components' => 'array',
        'cad_components' => 'array',
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
            'cad_components' => []
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
        } else {
            \Log::warning('No manual found for workorder', ['workorder_id' => $workorderId]);
        }
        
        $ndtCadCsv->save();
        
        \Log::info('NdtCadCsv created', [
            'ndt_components_count' => count($ndtCadCsv->ndt_components),
            'cad_components_count' => count($ndtCadCsv->cad_components)
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
        }
        
        $ndtCadCsv->save();
        
        \Log::info('NdtCadCsv updated with components', [
            'ndt_components_count' => count($ndtCadCsv->ndt_components),
            'cad_components_count' => count($ndtCadCsv->cad_components)
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
