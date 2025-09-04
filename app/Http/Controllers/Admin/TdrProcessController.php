<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Vendor;
use App\Models\Workorder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use JetBrains\PhpStorm\NoReturn;

class TdrProcessController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        //

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
       //
    }



    public function createProcesses(Request $request, $tdrId)
    {
        // Находим запись Tdr по ID
        $current_tdr = Tdr::findOrFail($tdrId);

        // Получаем workorder_id из текущей записи Tdr
        $workorder_id = $current_tdr->workorder_id;

        // Находим связанный Workorder
        $current_wo = Workorder::find($workorder_id);

        // Если Workorder не найден, выбрасываем исключение
        if (!$current_wo) {
            abort(404, 'Workorder not found');
        }

        // Получаем manual_id из Workorder (если такая связь существует)
        $manual_id = $current_wo->unit->manual_id ?? null;

        // Получаем имена процессов
        $processNames = ProcessName::all();

        // Получаем процессы, связанные с manual_id
        $processes = Process::whereHas('manuals', function ($query) use ($manual_id) {
            $query->where('manual_id', $manual_id);
        })->get();

        // Получаем процессы, уже связанные с текущим Tdr
        $tdrProcesses = TdrProcess::where('tdrs_id', $tdrId)->get();

        // Передаем данные в представление
        return view('admin.tdr-processes.createProcesses', compact(
            'current_tdr',
            'current_wo',
            'processNames',
            'processes',
            'tdrProcesses',
            'manual_id'
        ));
    }

    public function getProcess($processNameId, Request $request)
    {
        // Получаем manual_id из запроса
        $manualId = $request->query('manual_id');

        // Фильтруем процессы по processNameId и manual_id
        $processes = Process::where('process_names_id', $processNameId)
            ->whereHas('manuals', function ($query) use ($manualId) {
                $query->where('manual_id', $manualId);
            })
            ->get();

        // Log or inspect the response data for debugging
        \Log::info($processes); // Log data to inspect it

        return response()->json($processes);
    }

    public function getProcesses(Request $request)
    {
        $processNameId = $request->query('processNameId');
        $manualId = $request->query('manualId');

        // Получаем процессы, которые уже связаны с данным manual_id
        $existingProcessIds = ManualProcess::where('manual_id', $manualId)
            ->pluck('processes_id')
            ->toArray();

        // Фильтруем процессы для выбора (исключаем существующие)
        $availableProcesses = Process::where('process_names_id', $processNameId)
            ->whereNotIn('id', $existingProcessIds)
            ->get();

        // Получаем существующие процессы
        $existingProcesses = Process::whereIn('id', $existingProcessIds)
            ->where('process_names_id', $processNameId)
            ->get();

        return response()->json([
            'existingProcesses' => $existingProcesses,
            'availableProcesses' => $availableProcesses,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'tdrs_id' => 'required|integer|exists:tdrs,id',
            'processes' => 'required|json'
        ]);

        $tdrId = $request->input('tdrs_id');
        $processesData = json_decode($request->input('processes'), true);

        // Если processesData пустой, возвращаем ошибку
        if (empty($processesData)) {
            return response()->json(['error' => 'No processes selected.'], 400);
        }

        // Находим запись Tdr
        $tdr = Tdr::find($tdrId);
        if (!$tdr) {
            return response()->json(['error' => 'TDR not found.'], 404);
        }

        // Сохраняем каждый процесс
        foreach ($processesData as $data) {
            TdrProcess::create([
                'tdrs_id' => $tdrId,
                'process_names_id' => $data['process_names_id'],
                'processes' => json_encode($data['processes']), // Сохраняем массив ID процессов
                'date_start' => now(), // Пример даты начала
                'date_finish' => now()->addDays(1), // Пример даты завершения
            ]);
        }

        // Возвращаем JSON-ответ с URL для перенаправления
        return response()->json([
            'message' => 'Processes saved successfully!',
            'redirect' => route('tdrs.processes', ['workorder_id' => $tdr->workorder->id])
        ], 200);
    }

    public function processesForm_old(Request $request, $id)
    {
//         dd($request,$id);

        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);

        // Получаем данные о manual_id, связанном с этим Workorder
        $manual_id = $current_wo->unit->manual_id;

        // Извлекаем компоненты, связанные с manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        $tdrs = Tdr::where('workorder_id',$current_wo->id)->pluck('component_id');;

        $processes_name_id = $request->input('process_name_id');
        $process_name = ProcessName::where('id',$processes_name_id)->first();

        $components = Component::where('manual_id', $manual_id)->get();

        $tdrs = Tdr::where('workorder_id',$current_wo->id)->pluck('component_id');;

        $manuals = Manual::where('id', $manual_id)->get();

        $tdr = Tdr::all();

        $processNames = ProcessName::all();
        // Получаем processes_id из таблицы manual_processes для данного manual_id
        $manualProcesses = ManualProcess::where('manual_id', $manual_id)->pluck('processes_id');

        if ($process_name->process_sheet_name == 'NDT') {
            $ndt1_name_id = ProcessName::where('name','NDT-1')->first()->id;
            $ndt4_name_id = ProcessName::where('name','NDT-4')->first()->id;
            $ndt6_name_id = ProcessName::where('name','Eddy Current Test')->first()->id;
            $ndt5_name_id = ProcessName::where('name','BNI')->first()->id;

            // Получаем NDT processes - ВСЕГДА включаем процессы для ndt_1 и ndt_4
            $ndt_processes = Process::whereIn('id', $manualProcesses)
                ->where(function ($query) use ($ndt1_name_id, $ndt4_name_id, $ndt5_name_id, $ndt6_name_id) {
                    $query->where('process_names_id', $ndt1_name_id)
                        ->orWhere('process_names_id', $ndt4_name_id)
                        ->orWhere('process_names_id', $ndt5_name_id)
                        ->orWhere('process_names_id', $ndt6_name_id);
                })
                ->get();
            $ndt_components = TdrProcess::whereIn('tdrs_id',$tdrs)
                ->where(function ($query) use ($ndt1_name_id, $ndt4_name_id, $ndt5_name_id, $ndt6_name_id) {
                    $query->where('process_names_id', $ndt1_name_id)
                        ->orWhere('process_names_id', $ndt4_name_id)
                        ->orWhere('process_names_id', $ndt5_name_id)
                        ->orWhere('process_names_id', $ndt6_name_id);
                })
                ->with('tdr')
                ->with('processName')
                ->get();
            return view('admin.tdr-processes.processesForm', compact('current_wo', 'components',
                'tdrs','manuals','ndt_processes','ndt1_name_id','ndt4_name_id','ndt5_name_id','ndt6_name_id',
                'ndt_components','process_name'
            ));
        } else {

            $process_components = Process::whereIn('id', $manualProcesses)
                ->where('process_names_id', $processes_name_id)
                               ->get();

            $process_tdr_components = TdrProcess::whereIn('tdrs_id',$tdrs)
                ->where('process_names_id', $processes_name_id)
                ->get();

            return view('admin.tdr-processes.processesForm', compact('current_wo', 'components',
                'tdrs','manuals','process_name','process_components','manual_id',
                'process_tdr_components'
            ));
        }


    }
    public function processesForm(Request $request, $id)
    {
        // Загрузка Workorder с необходимыми отношениями
        $current_wo = Workorder::findOrFail($id);

        // Получаем manual_id через отношения
        $manual_id = $current_wo->unit->manual_id;

        // Получаем ID процесса из запроса
        $processes_name_id = $request->input('process_name_id');
        $process_name = ProcessName::findOrFail($processes_name_id);

        // Получаем компоненты и TDRs
        $components = Component::where('manual_id', $manual_id)->get();
        $tdr_ids = Tdr::where('workorder_id', $current_wo->id)->pluck('id');

        // Получаем manual processes
        $manualProcesses = ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        // Обработка NDT формы
        if ($process_name->process_sheet_name == 'NDT') {
            // Получаем ID process names одним запросом
            $processNames = ProcessName::whereIn('name', [
                'NDT-1',
                'NDT-4',
                'Eddy Current Test',
                'BNI'
            ])->pluck('id', 'name');

            // Извлекаем ID по именам
            $ndt_ids = [
                'ndt1_name_id' => $processNames['NDT-1'],
                'ndt4_name_id' => $processNames['NDT-4'],
                'ndt6_name_id' => $processNames['Eddy Current Test'],
                'ndt5_name_id' => $processNames['BNI']
            ];

            // Получаем NDT processes - ВСЕГДА включаем процессы для ndt_1 и ndt_4
            $ndt_processes = Process::whereIn('id', $manualProcesses)
                ->where(function ($query) use ($ndt_ids) {
                    $query->whereIn('process_names_id', $ndt_ids)
                        // Всегда включаем процессы для NDT-1 и NDT-4, даже если они не связаны с текущим процессом
                        ->orWhere('process_names_id', $ndt_ids['ndt1_name_id'])
                        ->orWhere('process_names_id', $ndt_ids['ndt4_name_id']);
                })
                ->get();

            // Получаем NDT components
            $ndt_components = TdrProcess::whereIn('tdrs_id', $tdr_ids)
                ->whereIn('process_names_id', $ndt_ids)
                ->with(['tdr', 'processName'])
                ->get();

            return view('admin.tdr-processes.processesForm', array_merge([
                'current_wo' => $current_wo,
                'components' => $components,
                'tdrs' => $tdr_ids,
                'manuals' => Manual::where('id', $manual_id)->get(),
                'process_name' => $process_name,
                'ndt_processes' => $ndt_processes,
                'ndt_components' => $ndt_components
            ], $ndt_ids));
        }

        // Обработка обычных процессов
        $process_components = Process::whereIn('id', $manualProcesses)
            ->where('process_names_id', $processes_name_id)
            ->get();

        $process_tdr_components = TdrProcess::whereIn('tdrs_id', $tdr_ids)
            ->where('process_names_id', $processes_name_id)
            ->with(['tdr', 'processName'])
            ->get();

        return view('admin.tdr-processes.processesForm', [
            'current_wo' => $current_wo,
            'components' => $components,
            'tdrs' => $tdr_ids,
            'manuals' => Manual::where('id', $manual_id)->get(),
            'process_name' => $process_name,
            'process_components' => $process_components,
            'process_tdr_components' => $process_tdr_components,
            'manual_id' => $manual_id
        ]);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function show($id, Request $request)
    {
        // Загружаем процесс TDR со связанными данными (жадная загрузка)
        $current_tdrs_process = TdrProcess::with([
            'processName',                   // Название процесса
            'tdr.workorder.unit.manuals',    // Рабочий заказ -> агрегат ->
            // руководство
            'tdr.workorder'                 // Рабочий заказ
        ])->findOrFail($id);

        // Получаем vendor_id из запроса
        $vendorId = $request->input('vendor_id');
        $selectedVendor = null;
        if ($vendorId) {
            $selectedVendor = Vendor::find($vendorId);
        }

        // Получаем связанные данные через отношения
        $process_name = $current_tdrs_process->processName;
        $current_tdr = $current_tdrs_process->tdr;
        $current_wo = $current_tdr->workorder;
        $manual_id = $current_wo->unit->manual_id;

        // Получаем компоненты и процессы из руководства
        $components = Component::where('manual_id', $manual_id)->get();
        $manualProcesses = ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        // Базовые данные для представления
        $viewData = [
            'current_wo' => $current_wo,             // Текущий рабочий заказ
            'components' => $components,             // Компоненты
            'tdrs' => [$current_tdr->id],           // ID связанных TDR (массив для совместимости)
            'manuals' => Manual::where('id', $manual_id)->get(), // Руководства
            'process_name' => $process_name,         // Название процесса
            'manual_id' => $manual_id,              // ID руководства
            'selectedVendor' => $selectedVendor     // Выбранный поставщик
        ];

        // Обработка случая для NDT-форм
        if ($process_name->process_sheet_name == 'NDT') {
            // Получаем ID process names для NDT
            $ndt_ids = [
                'ndt1_name_id' => ProcessName::where('name', 'NDT-1')->value('id'),
                'ndt4_name_id' => ProcessName::where('name', 'NDT-4')->value('id'),
                'ndt6_name_id' => ProcessName::where('name', 'Eddy Current Test')->value('id'),
                'ndt5_name_id' => ProcessName::where('name', 'BNI')->value('id')
            ];

            // Получаем NDT processes - ВСЕГДА включаем процессы для ndt_1 и ndt_4
            $ndt_processes = Process::whereIn('id', $manualProcesses)
                ->where(function ($query) use ($ndt_ids) {
                    $query->whereIn('process_names_id', $ndt_ids)
                        // Всегда включаем процессы для NDT-1 и NDT-4, даже если они не связаны с текущим процессом
                        ->orWhere('process_names_id', $ndt_ids['ndt1_name_id'])
                        ->orWhere('process_names_id', $ndt_ids['ndt4_name_id']);
                })
                ->get();

            $viewData += [
                'ndt_processes' => $ndt_processes,

                'ndt_components' => TdrProcess::where('tdrs_id', $current_tdr->id)
                    ->where('process_names_id', $process_name->id)
                    ->with(['tdr', 'processName'])
                    ->get(),

                // Добавляем ID текущего процесса (а не всех NDT процессов)
                'current_ndt_id' => $process_name->id,

                // Оставляем остальные ID для возможного использования в шаблоне
                'ndt1_name_id' => $ndt_ids['ndt1_name_id'],
                'ndt4_name_id' => $ndt_ids['ndt4_name_id'],
                'ndt6_name_id' => $ndt_ids['ndt6_name_id'],
                'ndt5_name_id' => $ndt_ids['ndt5_name_id']
            ];

        } else {
            // Обработка обычных процессов
            $viewData += [
                'process_components' => Process::whereIn('id', $manualProcesses)
                    ->where('process_names_id', $process_name->id)
                    ->get(),
                'process_tdr_components' => TdrProcess::where('tdrs_id', $current_tdr->id)
                    ->where('process_names_id', $process_name->id)
                    ->with(['tdr', 'processName'])
                    ->get()
            ];
        }

        return view('admin.tdr-processes.processesForm', $viewData);
    }

    public function processes(Request $request, $tdrId)
    {
        // Находим запись Tdr по ID
        $current_tdr = Tdr::findOrFail($tdrId);

        // Получаем workorder_id из текущей записи Tdr
        $workorder_id = $current_tdr->workorder_id;

        // Находим связанный Workorder
        $current_wo = Workorder::find($workorder_id);

        $tdrProcesses = TdrProcess::all();
        $proces = Process::all();
        
        // Получаем всех поставщиков
        $vendors = Vendor::all();

        return view('admin.tdr-processes.processes',compact('current_tdr',
            'current_wo','tdrProcesses','proces','vendors'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        // Находим запись TdrProcess по ID
        $current_tdr_processes = TdrProcess::findOrFail($id);

        $tdr_id = $current_tdr_processes->tdrs_id;

        $current_tdr = Tdr::find($tdr_id);

        // Получаем workorder_id из текущей записи Tdr
        $workorder_id = $current_tdr->workorder_id;

        // Находим связанный Workorder
        $current_wo = Workorder::find($workorder_id);

        // Получаем имена процессов
        $processNames = ProcessName::all();

        // Получаем процессы, связанные с manual_id
        $manual_id = $current_wo->unit->manual_id ?? null;
        $processes = Process::whereHas('manualProcesses', function ($query) use ($manual_id) {
            $query->where('manual_id', $manual_id);
        })->get();

        return view('admin.tdr-processes.edit', compact(
            'current_tdr',
            'current_wo',
            'current_tdr_processes',
            'processNames',
            'processes'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Находим запись TdrProcess по ID
        $current_tdr_processes = TdrProcess::findOrFail($id);

        // Валидация данных
        $validated = $request->validate([
            'tdrs_id' => 'required|integer|exists:tdrs,id',
            'processes' => 'required|array',
            'processes.*.process_names_id' => 'required|integer|exists:process_names,id',
            'processes.*.process' => 'required|array',
            'processes.*.process.*' => 'integer|exists:processes,id',
        ]);

        // Извлекаем данные из запроса
        $processData = $validated['processes'][0]; // Берём первый элемент массива

        // Преобразуем все элементы массива process в целые числа
        $processesArray = array_map('intval', $processData['process']);

        // Формируем данные для обновления
        $dataToUpdate = [
            'tdrs_id' => $validated['tdrs_id'],
            'process_names_id' => $processData['process_names_id'],
            'processes' => json_encode($processesArray), // Преобразуем массив в JSON
        ];

        // Обновляем запись
        \Log::info('Before update:', $current_tdr_processes->toArray());
        $current_tdr_processes->update($dataToUpdate);
        \Log::info('After update:', $current_tdr_processes->fresh()->toArray());

        // Получаем workorder_id для редиректа
        $current_tdr = Tdr::find($validated['tdrs_id']);
        $workorder_id = $current_tdr->workorder_id;

        // Редирект с сообщением об успехе
        return redirect()
            ->route('tdrs.processes', ['workorder_id' => $workorder_id])
            ->with('success', 'TDR for Component updated successfully');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($tdr_process)
    {
        // Получаем tdrId из запроса
        $tdrId = request('tdrId');
        $processToRemove = request('process'); // Значение, которое нужно удалить

        // Находим запись Tdr
        $tdr = Tdr::find($tdrId);
        if (!$tdr) {
            return redirect()->back()->with('error', 'TDR not found.');
        }

        // Находим запись по ID
        $tdrProcess = TdrProcess::findOrFail($tdr_process);

        // Декодируем JSON-поле processes
        $processData = json_decode($tdrProcess->processes, true);

        // Если processes пустой или не является массивом, удаляем всю запись
        if (!is_array($processData)) {
            $tdrProcess->delete();
            return redirect()->route('tdrs.processes', ['workorder_id' => $tdr->workorder->id])
                ->with('success', 'Process deleted successfully.');
        }

        // Если processes содержит только одно значение, удаляем всю запись
        if (count($processData) === 1) {
            $tdrProcess->delete();
            return redirect()->route('tdrs.processes', ['workorder_id' => $tdr->workorder->id])
                ->with('success', 'Process deleted successfully.');
        }

        // Удаляем значение из массива (приводим типы к int для сравнения)
        $processData = array_filter($processData, function ($process) use ($processToRemove) {
            return (int)$process !== (int)$processToRemove;
        });

        // Обновляем поле processes
        $tdrProcess->processes = json_encode(array_values($processData)); // Переиндексируем массив
        $tdrProcess->save();

        // Перенаправляем с сообщением об успехе
        return redirect()->route('tdrs.processes', ['workorder_id' => $tdr->workorder->id])
            ->with('success', 'Process removed successfully.');
    }
}
