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
            'redirect' => route('admin.tdrs.processes', ['workorder_id' => $tdr->workorder->id])
        ], 200);
    }

    public function processesForm(Request $request, $id)
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function show($id)
    {

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

        return view('admin.tdr-processes.processes',compact('current_tdr',
            'current_wo','tdrProcesses','proces'
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
            ->route('admin.tdrs.processes', ['workorder_id' => $workorder_id])
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
            return redirect()->route('admin.tdrs.processes', ['workorder_id' => $tdr->workorder->id])
                ->with('success', 'Process deleted successfully.');
        }

        // Если processes содержит только одно значение, удаляем всю запись
        if (count($processData) === 1) {
            $tdrProcess->delete();
            return redirect()->route('admin.tdrs.processes', ['workorder_id' => $tdr->workorder->id])
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
        return redirect()->route('admin.tdrs.processes', ['workorder_id' => $tdr->workorder->id])
            ->with('success', 'Process removed successfully.');
    }
}
