<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */

    public function index()
    {
        // Получаем все manuals
        $manuals = Manual::all();

        // Получаем все процессы с их process_names и manual_processes
        $processes = Process::with(['process_name', 'manuals'])->get();

        // Группируем процессы по manual_id
        $groupedProcesses = [];
        foreach ($processes as $process) {
            foreach ($process->manuals as $manual) {
                $groupedProcesses[$manual->id][] = [
                    'process_name' => $process->process_name->name,
                    'process' => $process->process,
                ];
            }
        }

        return view('admin.processes.index', compact('manuals', 'groupedProcesses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create(Request $request, $manualId = null)
    {
        // Получаем manual_id из параметра маршрута или из query string (для обратной совместимости)
        if (!$manualId) {
            $manualId = $request->route('manual_id') ?? $request->query('manual_id');
        }

        \Log::info('ProcessController::create - Request query parameters:', $request->query());
        \Log::info('ProcessController::create - Manual ID:', ['manual_id' => $manualId]);

        if (!$manualId) {
            abort(400, 'Manual ID is required');
        }

        $manual = Manual::findOrFail($manualId);
        $processNames = ProcessName::all();
        $processes = Process::all();

        return view('admin.processes.create', compact('manual','processNames','processes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        \Log::info('ProcessController::store - Request data:', $request->all());

        // Валидация с поддержкой двух сценариев: новый процесс или выбор существующего
        $validated = $request->validate([
            'process_names_id' => 'required|integer|exists:process_names,id',
            'manual_id' => 'required|integer|exists:manuals,id',
        ]);

        // Проверяем, какой сценарий используется
        if ($request->has('selected_process_id') && $request->selected_process_id) {
            // Сценарий 1: Выбор существующего процесса
            $validated['selected_process_id'] = $request->validate([
                'selected_process_id' => 'required|integer|exists:processes,id'
            ])['selected_process_id'];

            $processId = $validated['selected_process_id'];
        } else {
            // Сценарий 2: Создание нового процесса
            $validated['process'] = $request->validate([
                'process' => 'required|string|max:255'
            ])['process'];

            // Создаем новый процесс
            $process = Process::create([
                'process_names_id' => $validated['process_names_id'],
                'process' => $validated['process'],
            ]);

            $processId = $process->id;
        }

        try {
            // Добавляем запись в таблицу manual_processes
            DB::table('manual_processes')->insert([
                'manual_id' => $validated['manual_id'],
                'processes_id' => $processId,
            ]);

            // Загружаем процесс для возврата (независимо от того, новый он или существующий)
            $processToReturn = $process ?? Process::find($processId);

            // Если это AJAX-запрос или JSON запрос, возвращаем JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Process added successfully.',
                    'process' => $processToReturn
                ]);
            }

            // Проверяем, куда нужно вернуться
            if ($request->has('return_to') && $request->return_to) {
                return redirect($request->return_to)->with('success', 'Process added successfully.');
            }

            return redirect()->back()->with('success', 'Process added successfully.');
        } catch (\Exception $e) {
            \Log::error('ProcessController::store - Error: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error adding process: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error adding process: ' . $e->getMessage());
        }
    }
//

    public function getProcesses(Request $request)
    {
        $processNameId = $request->query('processNameId');
        $manualId = $request->query('manualId'); // Добавляем manualId в запрос

        // Получаем ID процессов, которые уже связаны с данным manual_id
        $existingProcessIds = DB::table('manual_processes')
            ->where('manual_id', $manualId)
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


//    public function getProcesses(Request $request)
//    {
//        $processNameId = $request->query('processNameId');
//        $processes = Process::where('process_names_id', $processNameId)->get();
//
//        return response()->json($processes)
//            ->header('Access-Control-Allow-Origin', '*')
//            ->header('Access-Control-Allow-Methods', 'GET');
//    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
//        dd($id);
        $manual = Manual::findorFail($id);
        $processNames = ProcessName::all();
        $processes = Process::all();
        $man_processes = ManualProcess::where('manual_id',$id)->get();

        return view('admin.processes.edit', compact('manual','processNames',
            'processes','man_processes'

        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        //;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
