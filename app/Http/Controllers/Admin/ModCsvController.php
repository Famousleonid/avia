<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\ModCsv;
use App\Models\Unit;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ModCsvController extends Controller
{
    /**
     * Показать форму управления компонентами для workorder
     */
    public function show(int $workorderId): View
    {
        $workorder = Workorder::findOrFail($workorderId);

        $manual_id=$workorder->unit->manual_id;

        // Извлекаем компоненты, связанные с manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        // Получаем или создаем запись ModCsv с автоматической загрузкой из CSV
        $modCsv = ModCsv::getOrCreateForWorkorder($workorderId);





        return view('admin.mod_csv.show', compact('workorder', 'modCsv',
            'manual_id', 'components'));
    }

    /**
     * Обновить NDT компоненты
     */
    public function updateNdtComponents(Request $request, int $workorderId): JsonResponse
    {
        $request->validate([
            'components' => 'required|array',
            'components.*.ipl_num' => 'required|string',
            'components.*.part_number' => 'nullable|string',
            'components.*.description' => 'nullable|string',
            'components.*.qty' => 'nullable|integer|min:1',
            'components.*.process' => 'nullable|string',
        ]);

        $workorder = Workorder::findOrFail($workorderId);

        $modCsv = $workorder->modCsv ?? ModCsv::create([
            'workorder_id' => $workorderId,
            'ndt_components' => [],
            'cad_components' => []
        ]);

        $modCsv->ndt_components = $request->components;
        $modCsv->save();

        return response()->json([
            'success' => true,
            'message' => 'NDT компоненты успешно обновлены',
            'components' => $modCsv->ndt_components
        ]);
    }

    /**
     * Обновить CAD компоненты
     */
    public function updateCadComponents(Request $request, int $workorderId): JsonResponse
    {
        $request->validate([
            'components' => 'required|array',
            'components.*.ipl_num' => 'required|string',
            'components.*.part_number' => 'nullable|string',
            'components.*.description' => 'nullable|string',
            'components.*.qty' => 'nullable|integer|min:1',
            'components.*.process' => 'nullable|string',
        ]);

        $workorder = Workorder::findOrFail($workorderId);

        $modCsv = $workorder->modCsv ?? ModCsv::create([
            'workorder_id' => $workorderId,
            'ndt_components' => [],
            'cad_components' => []
        ]);

        $modCsv->cad_components = $request->components;
        $modCsv->save();

        return response()->json([
            'success' => true,
            'message' => 'CAD компоненты успешно обновлены',
            'components' => $modCsv->cad_components
        ]);
    }

    /**
     * Добавить NDT компонент
     */
    public function addNdtComponent(Request $request, int $workorderId): JsonResponse
    {
        $request->validate([
            'ipl_num' => 'required|string',
            'part_number' => 'nullable|string',
            'description' => 'nullable|string',
            'qty' => 'nullable|integer|min:1',
            'process' => 'nullable|string',
        ]);

        $workorder = Workorder::findOrFail($workorderId);

        $modCsv = $workorder->modCsv ?? ModCsv::create([
            'workorder_id' => $workorderId,
            'ndt_components' => [],
            'cad_components' => []
        ]);

        $component = $request->only(['ipl_num', 'part_number', 'description', 'qty', 'process']);
        $modCsv->addNdtComponent($component);
        $modCsv->save();

        return response()->json([
            'success' => true,
            'message' => 'NDT компонент успешно добавлен',
            'components' => $modCsv->ndt_components
        ]);
    }

    /**
     * Добавить CAD компонент
     */
    public function addCadComponent(Request $request, int $workorderId): JsonResponse
    {
        $request->validate([
            'ipl_num' => 'required|string',
            'part_number' => 'nullable|string',
            'description' => 'nullable|string',
            'qty' => 'nullable|integer|min:1',
            'process' => 'nullable|string',
        ]);

        $workorder = Workorder::findOrFail($workorderId);

        $modCsv = $workorder->modCsv ?? ModCsv::create([
            'workorder_id' => $workorderId,
            'ndt_components' => [],
            'cad_components' => []
        ]);

        $component = $request->only(['ipl_num', 'part_number', 'description', 'qty', 'process']);
        $modCsv->addCadComponent($component);
        $modCsv->save();

        return response()->json([
            'success' => true,
            'message' => 'CAD компонент успешно добавлен',
            'components' => $modCsv->cad_components
        ]);
    }

    /**
     * Удалить NDT компонент
     */
    public function removeNdtComponent(Request $request, int $workorderId): JsonResponse
    {
        $request->validate([
            'ipl_num' => 'required|string',
            'part_number' => 'required|string'
        ]);

        $workorder = Workorder::findOrFail($workorderId);
        $modCsv = $workorder->modCsv;

        if (!$modCsv) {
            return response()->json([
                'success' => false,
                'message' => 'Запись ModCsv не найдена'
            ], 404);
        }

        $modCsv->removeNdtComponentByIplNum($request->ipl_num, $request->part_number);
        $modCsv->save();

        return response()->json([
            'success' => true,
            'message' => 'NDT компонент успешно удален',
            'components' => $modCsv->ndt_components
        ]);
    }

    /**
     * Удалить CAD компонент
     */
    public function removeCadComponent(Request $request, int $workorderId): JsonResponse
    {
        $request->validate([
            'ipl_num' => 'required|string',
            'part_number' => 'required|string'
        ]);

        $workorder = Workorder::findOrFail($workorderId);
        $modCsv = $workorder->modCsv;

        if (!$modCsv) {
            return response()->json([
                'success' => false,
                'message' => 'Запись ModCsv не найдена'
            ], 404);
        }

        $modCsv->removeCadComponentByIplNum($request->ipl_num, $request->part_number);
        $modCsv->save();

        return response()->json([
            'success' => true,
            'message' => 'CAD компонент успешно удален',
            'components' => $modCsv->cad_components
        ]);
    }

    /**
     * Получить список компонентов для выбора из manual workorder
     */
    public function getComponents(Request $request, int $workorderId): JsonResponse
    {
        try {
            $workorder = Workorder::with('unit')->findOrFail($workorderId);

            if (!$workorder->unit || !$workorder->unit->manual_id) {
                return response()->json([
                    'success' => true,
                    'components' => [],
                    'message' => 'No manual found for this workorder'
                ]);
            }

            $manualId = $workorder->unit->manual_id;
            $query = Component::query()->where('manual_id', $manualId);

            if ($request->filled('search')) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('ipl_num', 'like', "%{$search}%")
                      ->orWhere('part_number', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            }

            $rows = $query->select('ipl_num', 'part_number', 'name', 'units_assy')
                ->orderByRaw("CAST(SUBSTRING_INDEX(ipl_num, '-', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(ipl_num, '-', -1) AS UNSIGNED)")
                ->get();

            $components = $rows->map(function ($row) {
                return [
                    'ipl_num' => $row->ipl_num,
                    'part_number' => $row->part_number,
                    'name' => $row->name,
                    'units_assy' => $row->units_assy ?? 1,
                    'process' => '',
                    'qty' => 1,
                ];
            })->all();

            return response()->json([
                'success' => true,
                'components' => $components,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getComponents:', [
                'workorder_id' => $workorderId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'components' => [],
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить CAD процессы для мануала
     */
    public function getCadProcesses(Request $request, int $workorderId): JsonResponse
    {
        $workorder = Workorder::with('unit')->findOrFail($workorderId);

        if (!$workorder->unit || !$workorder->unit->manual_id) {
            return response()->json([
                'success' => true,
                'processes' => [],
                'message' => 'No manual found for this workorder'
            ]);
        }

        $manualId = $workorder->unit->manual_id;

        // Получаем CAD процессы для данного мануала через промежуточную таблицу manual_processes
        $processes = \App\Models\Process::whereHas('manuals', function($query) use ($manualId) {
            $query->where('manual_id', $manualId);
        })
            ->where('process_names_id', 19) // ID для 'Cad plate'
            ->with('process_name') // Загружаем связанную таблицу process_names
            ->select('id', 'process_names_id', 'process')
            ->get();

        // Если не найдено процессов с process_names_id = 19, берем все процессы для мануала
        if ($processes->isEmpty()) {
            $processes = \App\Models\Process::whereHas('manuals', function($query) use ($manualId) {
                $query->where('manual_id', $manualId);
            })
                ->with('process_name')
                ->select('id', 'process_names_id', 'process')
                ->get();
        }

        // Преобразуем данные: используем поле process из таблицы processes и name из process_names
        $processes = $processes->map(function($process) {
            return [
                'id' => $process->id,
                'name' => $process->process, // Используем поле process из таблицы processes
                'process_name' => $process->process_name ? $process->process_name->name : 'Unknown'
            ];
        });

        \Log::info('CAD Processes query', [
            'manual_id' => $manualId,
            'processes_count' => $processes->count(),
            'processes' => $processes->toArray()
        ]);

        return response()->json([
            'success' => true,
            'processes' => $processes
        ]);
    }

    /**
     * Импортировать компоненты из CSV файла
     */
    public function importFromCsv(Request $request, int $workorderId): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:ndt,cad',
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);

        $workorder = Workorder::findOrFail($workorderId);

        $modCsv = $workorder->modCsv ?? ModCsv::create([
            'workorder_id' => $workorderId,
            'ndt_components' => [],
            'cad_components' => []
        ]);

        try {
            $csvPath = $request->file('csv_file')->getPathname();
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
                    'name' => $row['DESCRIPTION'] ?? '',
                    'qty' => (int)($row['QTY'] ?? 1),
                    'process_name' => $row['PROCESS No.'] ?? '1',
                ];

                $components[] = $component;
            }

            if ($request->type === 'ndt') {
                $modCsv->ndt_components = $components;
            } else {
                $modCsv->cad_components = $components;
            }

            $modCsv->save();

            return response()->json([
                'success' => true,
                'message' => 'Компоненты успешно импортированы из CSV',
                'components' => $request->type === 'ndt' ? $modCsv->ndt_components : $modCsv->cad_components
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при импорте CSV: ' . $e->getMessage()
            ], 500);
        }
    }
}
