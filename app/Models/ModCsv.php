<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModCsv extends Model
{
    protected $table = 'mod_csv';
    
    protected $fillable = [
        'workorder_id',
        'ndt_components',
        'cad_components'
    ];

    protected $casts = [
        'ndt_components' => 'array',
        'cad_components' => 'array',
    ];

    /**
     * Связь с Workorder
     */
    public function workorder(): BelongsTo
    {
        return $this->belongsTo(Workorder::class);
    }

    /**
     * Получить NDT компоненты с дефолтным значением
     */
    public function getNdtComponentsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Получить CAD компоненты с дефолтным значением
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
        if (isset($components[$index])) {
            unset($components[$index]);
            $this->ndt_components = array_values($components); // Переиндексируем массив
        }
    }

    /**
     * Удалить CAD компонент по индексу
     */
    public function removeCadComponent(int $index): void
    {
        $components = $this->cad_components;
        if (isset($components[$index])) {
            unset($components[$index]);
            $this->cad_components = array_values($components); // Переиндексируем массив
        }
    }

    /**
     * Удалить NDT компонент по ipl_num и part_number
     */
    public function removeNdtComponentByIplNum(string $iplNum, string $partNumber): void
    {
        $components = $this->ndt_components;
        $filteredComponents = array_filter($components, function($component) use ($iplNum, $partNumber) {
            return !($component['ipl_num'] === $iplNum && $component['part_number'] === $partNumber);
        });
        $this->ndt_components = array_values($filteredComponents);
    }

    /**
     * Удалить CAD компонент по ipl_num и part_number
     */
    public function removeCadComponentByIplNum(string $iplNum, string $partNumber): void
    {
        $components = $this->cad_components;
        $filteredComponents = array_filter($components, function($component) use ($iplNum, $partNumber) {
            return !($component['ipl_num'] === $iplNum && $component['part_number'] === $partNumber);
        });
        $this->cad_components = array_values($filteredComponents);
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
     * Создать или получить ModCsv для workorder с автоматической загрузкой из CSV
     */
    public static function getOrCreateForWorkorder(int $workorderId): self
    {
        $workorder = Workorder::with('unit.manuals')->findOrFail($workorderId);
        
        // Проверяем, существует ли уже запись
        $modCsv = self::where('workorder_id', $workorderId)->first();
        
        if ($modCsv) {
            return $modCsv;
        }
        
        // Создаем новую запись
        $modCsv = self::create([
            'workorder_id' => $workorderId,
            'ndt_components' => [],
            'cad_components' => []
        ]);
        
        // Пытаемся загрузить данные из CSV файлов manual
        if ($workorder->unit && $workorder->unit->manuals) {
            $manual = $workorder->unit->manuals;
            
            // Загружаем NDT компоненты
            $ndtComponents = self::loadComponentsFromCsv($manual, 'ndt');
            if (!empty($ndtComponents)) {
                $modCsv->ndt_components = $ndtComponents;
                \Log::info('Loaded NDT components from CSV', [
                    'manual_id' => $manual->id,
                    'count' => count($ndtComponents)
                ]);
            } else {
                \Log::warning('No NDT CSV file found for manual', [
                    'manual_id' => $manual->id,
                    'manual_name' => $manual->name ?? 'Unknown'
                ]);
            }
            
            // Загружаем CAD компоненты
            $cadComponents = self::loadComponentsFromCsv($manual, 'cad');
            if (!empty($cadComponents)) {
                $modCsv->cad_components = $cadComponents;
                \Log::info('Loaded CAD components from CSV', [
                    'manual_id' => $manual->id,
                    'count' => count($cadComponents)
                ]);
            } else {
                \Log::warning('No CAD CSV file found for manual', [
                    'manual_id' => $manual->id,
                    'manual_name' => $manual->name ?? 'Unknown'
                ]);
            }
            
            $modCsv->save();
        }
        
        return $modCsv;
    }
    
    /**
     * Загрузить компоненты из CSV файла manual
     */
    private static function loadComponentsFromCsv($manual, string $type): array
    {
        try {
            // Получаем CSV-файл с нужным process_type
            $csvMedia = $manual->getMedia('csv_files')->first(function ($media) use ($type) {
                return $media->getCustomProperty('process_type') === $type;
            });
            
            if (!$csvMedia) {
                return [];
            }
            
            $csvPath = $csvMedia->getPath();
            $csv = \League\Csv\Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);
            
            $records = iterator_to_array($csv->getRecords());
            $components = [];
            
            foreach ($records as $row) {
                if (!isset($row['ITEM   No.'])) {
                    continue;
                }
                
                $component = [
                    'ipl_num' => $row['ITEM   No.'],
                    'part_number' => $row['PART No.'] ?? '',
                    'description' => $row['DESCRIPTION'] ?? '',
                    'process' => $row['PROCESS No.'] ?? '1',
                    'qty' => (int)($row['QTY'] ?? 1),
                ];
                
                $components[] = $component;
            }
            
            return $components;
            
        } catch (\Exception $e) {
            \Log::error('Error loading components from CSV', [
                'manual_id' => $manual->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
