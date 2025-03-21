<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use Illuminate\Http\Request;

class ManualProcessController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

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
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        $manualProcess = ManualProcess::findOrFail($id);
        $process = Process::findOrFail($manualProcess->processes_id);
        $manualId = $manualProcess->manual_id;
        $processNames = ProcessName::where('id',$process->process_names_id)->first();



        return view('admin.manual_processes.edit', compact('manualProcess',
            'process','processNames','manualId'));
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
        $manualProcess = ManualProcess::findOrFail($id);
        $process = Process::findOrFail($manualProcess->processes_id);

        $manualId = $manualProcess->manual_id;

        // Проверка, существует ли такой процесс с другим manual_id
        $existingProcess = Process::where('process', $request->input('process'))
            ->where('process_names_id', $process->process_names_id)
            ->where('id', '!=', $process->id)
            ->first();

        if ($existingProcess) {
            // Если существует, создаем новый процесс и обновляем processes_id в manual_processes
            $newProcess = Process::create([
                'process_names_id' => $process->process_names_id,
                'process' => $request->input('process')
            ]);

            $manualProcess->processes_id = $newProcess->id;
            $manualProcess->save();
        } else {
            // Если не существует, обновляем существующий процесс
            $process->process = $request->input('process');
            $process->save();
        }

        return redirect()->route('admin.processes.edit',['process' => $manualId])
            ->with('success', 'Process updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
//        dd($id);

        $manualProcess = ManualProcess::findOrFail($id);
        $manualId = $manualProcess->manual_id;
//        dd($manualProcess, $manualId);
        $manualProcess->delete();

        return redirect()->route('admin.processes.edit',['process' => $manualId])
            ->with('success', 'Process deleted successfully');
    }
}
