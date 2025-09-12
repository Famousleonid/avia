<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NdtCadCsv;
use App\Models\Workorder;
use App\Models\Component;
use App\Models\Process;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class NdtCadCsvController extends Controller
{
    /**
     * Показать форму управления компонентами для workorder
     */
    public function index(Workorder $workorder): View
    {
        $ndtCadCsv = $workorder->ndtCadCsv;

        // Отладочная информация
        $manual = $workorder->unit->manuals;
        \Log::info('Debug NDT/CAD CSV loading', [
            'workorder_id' => $workorder->id,
            'has_manual' => $manual ? true : false,
            'manual_id' => $manual ? $manual->id : null,
            'has_ndtCadCsv' => $ndtCadCsv ? true : false
        ]);

        if ($manual) {
            $csvFiles = $manual->getMedia('csv_files');
            \Log::info('CSV files in manual', [
                'count' => $csvFiles->count(),
                'files' => $csvFiles->map(function($file) {
                    return [
                        'name' => $file->name,
                        'process_type' => $file->getCustomProperty('process_type'),
                        'path' => $file->getPath()
                    ];
                })->toArray()
            ]);
        }

        // Если записи нет или она пустая, создаем/обновляем с автоматической загрузкой из Manual CSV
        $shouldAutoLoad = false;
        if (!$ndtCadCsv) {
            $shouldAutoLoad = true;
            \Log::info('No NdtCadCsv record found, will create with auto-loading');
        } else {
            $ndtEmpty = empty($ndtCadCsv->ndt_components) || (is_array($ndtCadCsv->ndt_components) && count($ndtCadCsv->ndt_components) == 0);
            $cadEmpty = empty($ndtCadCsv->cad_components) || (is_array($ndtCadCsv->cad_components) && count($ndtCadCsv->cad_components) == 0);
            
            \Log::info('Checking if NdtCadCsv needs auto-loading', [
                'has_ndtCadCsv' => true,
                'ndt_empty' => $ndtEmpty,
                'ndt_components' => $ndtCadCsv->ndt_components,
                'ndt_count' => is_array($ndtCadCsv->ndt_components) ? count($ndtCadCsv->ndt_components) : 'not array',
                'cad_empty' => $cadEmpty,
                'cad_components' => $ndtCadCsv->cad_components,
                'cad_count' => is_array($ndtCadCsv->cad_components) ? count($ndtCadCsv->cad_components) : 'not array',
                'should_auto_load' => $ndtEmpty && $cadEmpty
            ]);
            
            if ($ndtEmpty && $cadEmpty) {
                $shouldAutoLoad = true;
                \Log::info('NdtCadCsv is empty, will auto-load from manual');
            }
        }
        
        if ($shouldAutoLoad) {
            if (!$ndtCadCsv) {
                \Log::info('Creating new NdtCadCsv with auto-loading');
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder->id);
            } else {
                \Log::info('Updating existing empty NdtCadCsv with auto-loading');
                $ndtCadCsv = NdtCadCsv::loadComponentsFromManual($workorder->id, $ndtCadCsv);
            }
        } else {
            \Log::info('NdtCadCsv already has data, skipping auto-loading');
        }

        return view('admin.ndt-cad-csv.index', compact('workorder', 'ndtCadCsv'));
    }

    /**
     * Обновить NDT компоненты
     */
    public function updateNdtComponents(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'components' => 'required|array',
            'components.*.ipl_num' => 'required|string',
            'components.*.part_number' => 'required|string',
            'components.*.description' => 'required|string',
            'components.*.process' => 'required|string',
            'components.*.qty' => 'required|integer|min:1',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            $ndtCadCsv = NdtCadCsv::create([
                'workorder_id' => $workorder->id,
                'ndt_components' => [],
                'cad_components' => []
            ]);
        }

        $ndtCadCsv->ndt_components = $request->components;
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
            'message' => 'NDT компоненты успешно обновлены'
        ]);
    }

    /**
     * Обновить CAD компоненты
     */
    public function updateCadComponents(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'components' => 'required|array',
            'components.*.ipl_num' => 'required|string',
            'components.*.part_number' => 'required|string',
            'components.*.description' => 'required|string',
            'components.*.process' => 'required|string',
            'components.*.qty' => 'required|integer|min:1',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            $ndtCadCsv = NdtCadCsv::create([
                'workorder_id' => $workorder->id,
                'ndt_components' => [],
                'cad_components' => []
            ]);
        }

        $ndtCadCsv->cad_components = $request->components;
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
            'message' => 'CAD компоненты успешно обновлены'
        ]);
    }

    /**
     * Добавить NDT компонент
     */
    public function addNdtComponent(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'component_id' => 'required|integer|exists:components,id',
            'ipl_num' => 'required|string',
            'part_number' => 'required|string',
            'description' => 'required|string',
            'process' => 'required|string',
            'qty' => 'required|integer|min:1',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            $ndtCadCsv = NdtCadCsv::create([
                'workorder_id' => $workorder->id,
                'ndt_components' => [],
                'cad_components' => []
            ]);
        }

        $component = [
            'component_id' => $request->component_id,
            'ipl_num' => $request->ipl_num,
            'part_number' => $request->part_number,
            'description' => $request->description,
            'process' => $request->process,
            'qty' => $request->qty,
        ];

        $ndtCadCsv->addNdtComponent($component);
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
            'message' => 'NDT компонент успешно добавлен'
        ]);
    }

    /**
     * Добавить CAD компонент
     */
    public function addCadComponent(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'component_id' => 'required|integer|exists:components,id',
            'ipl_num' => 'required|string',
            'part_number' => 'required|string',
            'description' => 'required|string',
            'process' => 'required|string',
            'qty' => 'required|integer|min:1',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            $ndtCadCsv = NdtCadCsv::create([
                'workorder_id' => $workorder->id,
                'ndt_components' => [],
                'cad_components' => []
            ]);
        }

        $component = [
            'component_id' => $request->component_id,
            'ipl_num' => $request->ipl_num,
            'part_number' => $request->part_number,
            'description' => $request->description,
            'process' => $request->process,
            'qty' => $request->qty,
        ];

        $ndtCadCsv->addCadComponent($component);
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
            'message' => 'CAD компонент успешно добавлен'
        ]);
    }

    /**
     * Удалить NDT компонент
     */
    public function removeNdtComponent(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'index' => 'required|integer|min:0',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            return response()->json([
                'success' => false,
                'message' => 'Запись не найдена'
            ], 404);
        }

        // Логирование для отладки
        \Log::info('Удаление NDT компонента', [
            'workorder_id' => $workorder->id,
            'index' => $request->index,
            'components_before' => $ndtCadCsv->ndt_components,
            'count_before' => count($ndtCadCsv->ndt_components ?? [])
        ]);

        $ndtCadCsv->removeNdtComponent($request->index);
        $ndtCadCsv->save();

        \Log::info('NDT компонент удален', [
            'components_after' => $ndtCadCsv->ndt_components,
            'count_after' => count($ndtCadCsv->ndt_components ?? [])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'NDT компонент успешно удален'
        ]);
    }

    /**
     * Удалить CAD компонент
     */
    public function removeCadComponent(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'index' => 'required|integer|min:0',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            return response()->json([
                'success' => false,
                'message' => 'Запись не найдена'
            ], 404);
        }

        // Логирование для отладки
        \Log::info('Удаление CAD компонента', [
            'workorder_id' => $workorder->id,
            'index' => $request->index,
            'components_before' => $ndtCadCsv->cad_components,
            'count_before' => count($ndtCadCsv->cad_components ?? [])
        ]);

        $ndtCadCsv->removeCadComponent($request->index);
        $ndtCadCsv->save();

        \Log::info('CAD компонент удален', [
            'components_after' => $ndtCadCsv->cad_components,
            'count_after' => count($ndtCadCsv->cad_components ?? [])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'CAD компонент успешно удален'
        ]);
    }

    /**
     * Импортировать компоненты из CSV файла
     */
    public function importFromCsv(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:ndt,cad',
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        try {
            $csvFile = $request->file('csv_file');
            $csvPath = $csvFile->getPathname();

            $csv = \League\Csv\Reader::createFromPath($csvPath, 'r');
            $csv->setHeaderOffset(0);

            $headers = $csv->getHeader();
            $records = iterator_to_array($csv->getRecords());

            $components = [];
            foreach ($records as $row) {
                if (!empty($row['ITEM   No.'])) {
                    $components[] = [
                        'ipl_num' => $row['ITEM   No.'],
                        'part_number' => $row['PART No.'] ?? '',
                        'description' => $row['DESCRIPTION'] ?? '',
                        'process' => $row['PROCESS No.'] ?? '1',
                        'qty' => (int)($row['QTY'] ?? 1),
                    ];
                }
            }

            $ndtCadCsv = $workorder->ndtCadCsv;

            if (!$ndtCadCsv) {
                $ndtCadCsv = NdtCadCsv::create([
                    'workorder_id' => $workorder->id,
                    'ndt_components' => [],
                    'cad_components' => []
                ]);
            }

            if ($request->type === 'ndt') {
                $ndtCadCsv->ndt_components = $components;
            } else {
                $ndtCadCsv->cad_components = $components;
            }

            $ndtCadCsv->save();

            return response()->json([
                'success' => true,
                'message' => 'Компоненты успешно импортированы из CSV',
                'count' => count($components)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при импорте CSV: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Перезагрузить компоненты из Manual CSV
     */
    public function reloadFromManual(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:ndt,cad',
        ]);

        try {
            $ndtCadCsv = $workorder->ndtCadCsv;
            
            if (!$ndtCadCsv) {
                return response()->json([
                    'success' => false,
                    'message' => 'Запись NdtCadCsv не найдена'
                ], 404);
            }

            $manual = $workorder->unit->manuals;
            
            if (!$manual) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual не найден для данного workorder'
                ], 404);
            }

            $type = $request->input('type');
            $csvMedia = $manual->getMedia('csv_files')->first(function ($media) use ($type) {
                return $media->getCustomProperty('process_type') === $type;
            });

            if (!$csvMedia) {
                return response()->json([
                    'success' => false,
                    'message' => "CSV файл с process_type '{$type}' не найден в Manual"
                ], 404);
            }

            // Загружаем компоненты из CSV
            $components = NdtCadCsv::loadComponentsFromCsv($csvMedia->getPath(), $type);
            
            // Обновляем соответствующие компоненты
            if ($type === 'ndt') {
                $ndtCadCsv->ndt_components = $components;
            } else {
                $ndtCadCsv->cad_components = $components;
            }
            
            $ndtCadCsv->save();

            return response()->json([
                'success' => true,
                'message' => strtoupper($type) . " компоненты успешно перезагружены из Manual",
                'count' => count($components)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при перезагрузке: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить компоненты для дропдауна
     */
    public function getComponents(Request $request, Workorder $workorder): JsonResponse
    {
        try {
            $manual = $workorder->unit->manuals;
            
            if (!$manual) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual не найден для данного workorder'
                ], 404);
            }

            $components = Component::where('manual_id', $manual->id)
                ->select('id', 'name', 'ipl_num', 'part_number', 'units_assy')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'components' => $components
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении компонентов: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить CAD процессы для дропдауна
     */
    public function getCadProcesses(Request $request, Workorder $workorder): JsonResponse
    {
        try {
            $manual = $workorder->unit->manuals;
            
            if (!$manual) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual не найден для данного workorder'
                ], 404);
            }

            // Получаем CAD процессы для данного мануала
            $cadProcesses = Process::whereHas('manuals', function($query) use ($manual) {
                $query->where('manual_id', $manual->id);
            })
            ->whereHas('process_name', function($query) {
                $query->where('name', 'Cad plate');
            })
            ->select('id', 'process')
            ->orderBy('process')
            ->get();

            return response()->json([
                'success' => true,
                'processes' => $cadProcesses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении CAD процессов: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Принудительно загрузить компоненты из Manual CSV
     */
    public function forceLoadFromManual(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:ndt,cad',
        ]);

        try {
            $ndtCadCsv = $workorder->ndtCadCsv;
            
            if (!$ndtCadCsv) {
                // Создаем новую запись если не существует
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder->id);
            }

            $manual = $workorder->unit->manuals;
            
            if (!$manual) {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual не найден для данного workorder'
                ], 404);
            }

            $type = $request->input('type');
            $csvMedia = $manual->getMedia('csv_files')->first(function ($media) use ($type) {
                return $media->getCustomProperty('process_type') === $type;
            });

            if (!$csvMedia) {
                return response()->json([
                    'success' => false,
                    'message' => "CSV файл с process_type '{$type}' не найден в Manual"
                ], 404);
            }

            // Загружаем компоненты из CSV
            $components = NdtCadCsv::loadComponentsFromCsv($csvMedia->getPath(), $type);
            
            // Обновляем соответствующие компоненты
            if ($type === 'ndt') {
                $ndtCadCsv->ndt_components = $components;
            } else {
                $ndtCadCsv->cad_components = $components;
            }
            
            $ndtCadCsv->save();

            return response()->json([
                'success' => true,
                'message' => strtoupper($type) . " компоненты успешно загружены из Manual",
                'count' => count($components)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при принудительной загрузке: ' . $e->getMessage()
            ], 500);
        }
    }
}
