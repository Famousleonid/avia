<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NdtCadCsv;
use App\Models\StdProcess;
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
        if ($manual) {
            $csvFiles = $manual->getMedia('csv_files');
        }

        // Если записи нет или она пустая, создаем/обновляем с автоматической загрузкой из Manual CSV
        $shouldAutoLoad = false;
        if (!$ndtCadCsv) {
            $shouldAutoLoad = true;
        } else {
            $ndtEmpty = empty($ndtCadCsv->ndt_components) || (is_array($ndtCadCsv->ndt_components) && count($ndtCadCsv->ndt_components) == 0);
            $cadEmpty = empty($ndtCadCsv->cad_components) || (is_array($ndtCadCsv->cad_components) && count($ndtCadCsv->cad_components) == 0);
            $stressEmpty = empty($ndtCadCsv->stress_components) || (is_array($ndtCadCsv->stress_components) && count($ndtCadCsv->stress_components) == 0);
            $paintEmpty = empty($ndtCadCsv->paint_components) || (is_array($ndtCadCsv->paint_components) && count($ndtCadCsv->paint_components) == 0);

            if ($ndtEmpty && $cadEmpty && $stressEmpty && $paintEmpty) {
                $shouldAutoLoad = true;
            }
        }

        if ($shouldAutoLoad) {
            if (! $ndtCadCsv) {
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder->id);
            } else {
                $ndtCadCsv = NdtCadCsv::loadComponentsFromManual($workorder->id, $ndtCadCsv);
            }
        }

        return view('admin.ndt-cad-csv.index', compact('workorder', 'ndtCadCsv'));
    }

    /**
     * Partial для встраивания в TDR show (без layout)
     */
    public function partial(Workorder $workorder): View
    {
        $ndtCadCsv = $workorder->ndtCadCsv;
        $shouldAutoLoad = false;
        if (!$ndtCadCsv) {
            $shouldAutoLoad = true;
        } else {
            $ndtEmpty = empty($ndtCadCsv->ndt_components) || (is_array($ndtCadCsv->ndt_components) && count($ndtCadCsv->ndt_components) == 0);
            $cadEmpty = empty($ndtCadCsv->cad_components) || (is_array($ndtCadCsv->cad_components) && count($ndtCadCsv->cad_components) == 0);
            $stressEmpty = empty($ndtCadCsv->stress_components) || (is_array($ndtCadCsv->stress_components) && count($ndtCadCsv->stress_components) == 0);
            $paintEmpty = empty($ndtCadCsv->paint_components) || (is_array($ndtCadCsv->paint_components) && count($ndtCadCsv->paint_components) == 0);
            if ($ndtEmpty && $cadEmpty && $stressEmpty && $paintEmpty) {
                $shouldAutoLoad = true;
            }
        }
        if ($shouldAutoLoad) {
            if (! $ndtCadCsv) {
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder->id);
            } else {
                $ndtCadCsv = NdtCadCsv::loadComponentsFromManual($workorder->id, $ndtCadCsv);
            }
        }
        return view('admin.ndt-cad-csv.partial', compact('workorder', 'ndtCadCsv'));
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
                'message' => 'NDT components updated successfully'
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
                'cad_components' => [],
                'stress_components' => []
            ]);
        }

        $ndtCadCsv->cad_components = $request->components;
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'CAD components updated successfully'
        ]);
    }

    /**
     * Обновить Stress компоненты
     */
    public function updateStressComponents(Request $request, Workorder $workorder): JsonResponse
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
                'cad_components' => [],
                'stress_components' => []
            ]);
        }

        $ndtCadCsv->stress_components = $request->components;
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'Stress components updated successfully'
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
            'eff_code' => 'nullable|string|max:255',
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
            'eff_code' => StdProcess::normalizeEffCodeForStorage($request->input('eff_code')) ?? '',
        ];

        $ndtCadCsv->addNdtComponent($component);
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'NDT component added successfully'
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
            'eff_code' => 'nullable|string|max:255',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            $ndtCadCsv = NdtCadCsv::create([
                'workorder_id' => $workorder->id,
                'ndt_components' => [],
                'cad_components' => [],
                'stress_components' => []
            ]);
        }

        $component = [
            'component_id' => $request->component_id,
            'ipl_num' => $request->ipl_num,
            'part_number' => $request->part_number,
            'description' => $request->description,
            'process' => $request->process,
            'qty' => $request->qty,
            'eff_code' => StdProcess::normalizeEffCodeForStorage($request->input('eff_code')) ?? '',
        ];

        $ndtCadCsv->addCadComponent($component);
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'CAD component added successfully'
        ]);
    }

    /**
     * Добавить Stress компонент
     */
    public function addStressComponent(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'component_id' => 'required|integer|exists:components,id',
            'ipl_num' => 'required|string',
            'part_number' => 'required|string',
            'description' => 'required|string',
            'process' => 'required|string',
            'qty' => 'required|integer|min:1',
            'eff_code' => 'nullable|string|max:255',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            $ndtCadCsv = NdtCadCsv::create([
                'workorder_id' => $workorder->id,
                'ndt_components' => [],
                'cad_components' => [],
                'stress_components' => []
            ]);
        }

        $component = [
            'component_id' => $request->component_id,
            'ipl_num' => $request->ipl_num,
            'part_number' => $request->part_number,
            'description' => $request->description,
            'process' => $request->process,
            'qty' => $request->qty,
            'eff_code' => StdProcess::normalizeEffCodeForStorage($request->input('eff_code')) ?? '',
        ];

        $ndtCadCsv->addStressComponent($component);
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'Stress component added successfully'
        ]);
    }

    /**
     * Добавить Paint компонент
     */
    public function addPaintComponent(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'component_id' => 'required|integer|exists:components,id',
            'ipl_num' => 'required|string',
            'part_number' => 'required|string',
            'description' => 'required|string',
            'process' => 'required|string',
            'qty' => 'required|integer|min:1',
            'eff_code' => 'nullable|string|max:255',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            $ndtCadCsv = NdtCadCsv::create([
                'workorder_id' => $workorder->id,
                'ndt_components' => [],
                'cad_components' => [],
                'stress_components' => [],
                'paint_components' => []
            ]);
        }

        $component = [
            'component_id' => $request->component_id,
            'ipl_num' => $request->ipl_num,
            'part_number' => $request->part_number,
            'description' => $request->description,
            'process' => $request->process,
            'qty' => $request->qty,
            'eff_code' => StdProcess::normalizeEffCodeForStorage($request->input('eff_code')) ?? '',
        ];

        $ndtCadCsv->addPaintComponent($component);
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'Paint component added successfully'
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
                'message' => 'Record not found'
            ], 404);
        }

        $ndtCadCsv->removeNdtComponent($request->index);
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'NDT component deleted successfully'
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
                'message' => 'Record not found'
            ], 404);
        }

        $ndtCadCsv->removeCadComponent($request->index);
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'CAD component deleted successfully'
        ]);
    }

    /**
     * Удалить Stress компонент
     */
    public function removeStressComponent(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'index' => 'required|integer|min:0',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found'
            ], 404);
        }

        $ndtCadCsv->removeStressComponent($request->index);
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'Stress component deleted successfully'
        ]);
    }

    /**
     * Удалить Paint компонент
     */
    public function removePaintComponent(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'index' => 'required|integer|min:0',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found'
            ], 404);
        }

        $ndtCadCsv->removePaintComponent($request->index);
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'Paint component deleted successfully'
        ]);
    }

    /**
     * Импорт CSV на workorder отключён — используйте вкладку STD Processes у мануала.
     */
    public function importFromCsv(Request $request, Workorder $workorder): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'CSV import for workorder is disabled. Upload CSV in CMM -> STD Processes tab.',
        ], 422);
    }

    /**
     * Полная замена снимка выбранного типа из таблицы std_processes (и при необходимости — из прикреплённого CSV в мануал).
     */
    public function reloadFromManual(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:ndt,cad,stress,paint',
        ]);

        try {
            $ndtCadCsv = $workorder->ndtCadCsv;

            if (! $ndtCadCsv) {
                return response()->json([
                    'success' => false,
            'message' => 'NdtCadCsv record not found',
                ], 404);
            }

            $manual = $workorder->unit->manuals;

            if (! $manual) {
                return response()->json([
                    'success' => false,
            'message' => 'Manual was not found for this workorder',
                ], 404);
            }

            $type = $request->input('type');
            StdProcess::syncFromMediaIfEmpty($manual, $type);
            $components = StdProcess::snapshotComponentsForWorkorder($workorder, $type);

            if (count($components) === 0) {
                return response()->json([
                    'success' => false,
            'message' => 'There is no STD data for this type in the manual. Fill STD Processes or upload CSV in CMM.',
                ], 404);
            }

            if ($type === 'ndt') {
                $ndtCadCsv->ndt_components = $components;
            } elseif ($type === 'cad') {
                $ndtCadCsv->cad_components = $components;
            } elseif ($type === 'paint') {
                $ndtCadCsv->paint_components = $components;
            } else {
                $ndtCadCsv->stress_components = $components;
            }

            $ndtCadCsv->save();

            return response()->json([
                'success' => true,
                'message' => strtoupper($type).' components were replaced from the STD manual',
                'count' => count($components),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
            'message' => 'Reload error: '.$e->getMessage(),
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
                'message' => 'Manual was not found for this workorder'
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
                'message' => 'Error while loading components: ' . $e->getMessage()
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
                'message' => 'Manual was not found for this workorder'
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
                'message' => 'Error while loading CAD processes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить Stress процессы для дропдауна
     */
    public function getStressProcesses(Request $request, Workorder $workorder): JsonResponse
    {
        try {
            $manual = $workorder->unit->manuals;

            if (!$manual) {
                return response()->json([
                    'success' => false,
                'message' => 'Manual was not found for this workorder'
                ], 404);
            }

            // Получаем Stress процессы для данного мануала (process_names_id = 3)
            $stressProcesses = Process::whereHas('manuals', function($query) use ($manual) {
                $query->where('manual_id', $manual->id);
            })
            ->whereHas('process_name', function($query) {
                $query->where('id', 3); // Bake (Stress Realive)
            })
            ->select('id', 'process')
            ->orderBy('process')
            ->get();

            return response()->json([
                'success' => true,
                'processes' => $stressProcesses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error while loading Stress processes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить Paint процессы для дропдауна
     */
    public function getPaintProcesses(Request $request, Workorder $workorder): JsonResponse
    {
        try {
            $manual = $workorder->unit->manuals;

            if (!$manual) {
                return response()->json([
                    'success' => false,
                'message' => 'Manual was not found for this workorder'
                ], 404);
            }

            // Получаем Paint процессы для данного мануала (process_names_id = 25)
            $paintProcesses = Process::whereHas('manuals', function($query) use ($manual) {
                $query->where('manual_id', $manual->id);
            })
            ->whereHas('process_name', function($query) {
                $query->where('id', 25); // Paint
            })
            ->select('id', 'process')
            ->orderBy('process')
            ->get();

            return response()->json([
                'success' => true,
                'processes' => $paintProcesses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error while loading Paint processes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Редактировать NDT компонент
     */
    public function editNdtComponent(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'index' => 'required|integer|min:0',
            'part_number' => 'required|string',
            'description' => 'required|string',
            'process' => 'required|string',
            'qty' => 'required|integer|min:1',
            'eff_code' => 'nullable|string|max:255',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found'
            ], 404);
        }

        $components = $ndtCadCsv->ndt_components ?? [];

        if (!isset($components[$request->index])) {
            return response()->json([
                'success' => false,
                'message' => 'Component not found'
            ], 404);
        }

        // Обновляем только редактируемые поля, сохраняя остальные
        $components[$request->index]['part_number'] = $request->part_number;
        $components[$request->index]['description'] = $request->description;
        $components[$request->index]['process'] = $request->process;
        $components[$request->index]['qty'] = $request->qty;
        $components[$request->index]['eff_code'] = StdProcess::normalizeEffCodeForStorage($request->input('eff_code')) ?? '';

        $ndtCadCsv->ndt_components = $components;
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'NDT component updated successfully'
        ]);
    }

    /**
     * Редактировать CAD компонент
     */
    public function editCadComponent(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'index' => 'required|integer|min:0',
            'part_number' => 'required|string',
            'description' => 'required|string',
            'process' => 'required|string',
            'qty' => 'required|integer|min:1',
            'eff_code' => 'nullable|string|max:255',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found'
            ], 404);
        }

        $components = $ndtCadCsv->cad_components ?? [];

        if (!isset($components[$request->index])) {
            return response()->json([
                'success' => false,
                'message' => 'Component not found'
            ], 404);
        }

        // Обновляем только редактируемые поля, сохраняя остальные
        $components[$request->index]['part_number'] = $request->part_number;
        $components[$request->index]['description'] = $request->description;
        $components[$request->index]['process'] = $request->process;
        $components[$request->index]['qty'] = $request->qty;
        $components[$request->index]['eff_code'] = StdProcess::normalizeEffCodeForStorage($request->input('eff_code')) ?? '';

        $ndtCadCsv->cad_components = $components;
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'CAD component updated successfully'
        ]);
    }

    /**
     * Редактировать Stress компонент
     */
    public function editStressComponent(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'index' => 'required|integer|min:0',
            'part_number' => 'required|string',
            'description' => 'required|string',
            'process' => 'required|string',
            'qty' => 'required|integer|min:1',
            'eff_code' => 'nullable|string|max:255',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found'
            ], 404);
        }

        $components = $ndtCadCsv->stress_components ?? [];

        if (!isset($components[$request->index])) {
            return response()->json([
                'success' => false,
                'message' => 'Component not found'
            ], 404);
        }

        // Обновляем только редактируемые поля, сохраняя остальные
        $components[$request->index]['part_number'] = $request->part_number;
        $components[$request->index]['description'] = $request->description;
        $components[$request->index]['process'] = $request->process;
        $components[$request->index]['qty'] = $request->qty;
        $components[$request->index]['eff_code'] = StdProcess::normalizeEffCodeForStorage($request->input('eff_code')) ?? '';

        $ndtCadCsv->stress_components = $components;
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'Stress component updated successfully'
        ]);
    }

    /**
     * Редактировать Paint компонент
     */
    public function editPaintComponent(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'index' => 'required|integer|min:0',
            'part_number' => 'required|string',
            'description' => 'required|string',
            'process' => 'required|string',
            'qty' => 'required|integer|min:1',
            'eff_code' => 'nullable|string|max:255',
        ]);

        $ndtCadCsv = $workorder->ndtCadCsv;

        if (!$ndtCadCsv) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found'
            ], 404);
        }

        $components = $ndtCadCsv->paint_components;
        if (!isset($components[$request->index])) {
            return response()->json([
                'success' => false,
                'message' => 'Component not found'
            ], 404);
        }

        // Обновляем только редактируемые поля, сохраняя остальные
        $components[$request->index]['part_number'] = $request->part_number;
        $components[$request->index]['description'] = $request->description;
        $components[$request->index]['process'] = $request->process;
        $components[$request->index]['qty'] = $request->qty;
        $components[$request->index]['eff_code'] = StdProcess::normalizeEffCodeForStorage($request->input('eff_code')) ?? '';

        $ndtCadCsv->paint_components = $components;
        $ndtCadCsv->save();

        return response()->json([
            'success' => true,
                'message' => 'Paint component updated successfully'
        ]);
    }

    /**
     * Как reloadFromManual, но создаёт NdtCadCsv при отсутствии (все типы — из STD; затем при необходимости уже существующая запись дублирует логику одного типа).
     */
    public function forceLoadFromManual(Request $request, Workorder $workorder): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:ndt,cad,stress,paint',
        ]);

        try {
            $ndtCadCsv = $workorder->ndtCadCsv;

            if (! $ndtCadCsv) {
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder->id);
                $type = $request->input('type');
                $field = match ($type) {
                    'ndt' => 'ndt_components',
                    'cad' => 'cad_components',
                    'paint' => 'paint_components',
                    default => 'stress_components',
                };
                $snapshot = $ndtCadCsv->$field ?? [];

                return response()->json([
                    'success' => true,
                'message' => 'NdtCadCsv was created and filled from the STD manual',
                    'count' => is_array($snapshot) ? count($snapshot) : 0,
                ]);
            }

            return $this->reloadFromManual($request, $workorder);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Forced load error: '.$e->getMessage(),
            ], 500);
        }
    }
}
