<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Services\ProcessAccessDecision;
use App\Services\ProcessAccessGuard;
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
        $manual = $manualProcess->manual;
        abort_unless($manual, 404);
        $manualProcess->load(['process.process_name', 'lockedBy']);
        $decision = $this->guard()->canUpdateManualProcess(request()->user(), $manualProcess);
        if (! $decision->allowed) {
            return $this->denyDecision(request(), $decision, route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']));
        }

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
        $manual = $manualProcess->manual;
        abort_unless($manual, 404);

        $process = Process::findOrFail($manualProcess->processes_id);
        $manualProcess->setRelation('process', $process);
        $decision = $this->guard()->canUpdateManualProcess($request->user(), $manualProcess);
        if (! $decision->allowed) {
            return $this->denyDecision($request, $decision, route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']));
        }

        $manualId = $manualProcess->manual_id;

        // Проверка, существует ли такой процесс с другим manual_id
        $validated = $request->validate([
            'process' => ['required', 'string', 'max:255'],
            'process_comment' => ['nullable', 'string', 'max:2000'],
        ]);
        $processComment = trim((string) ($validated['process_comment'] ?? ''));

        $existingProcess = Process::where('process', $validated['process'])
            ->where('process_names_id', $process->process_names_id)
            ->where('id', '!=', $process->id)
            ->first();

        if ($existingProcess) {
            // Если существует, создаем новый процесс и обновляем processes_id в manual_processes
            $newProcess = Process::create([
                'process_names_id' => $process->process_names_id,
                'process' => $validated['process']
            ]);

            $manualProcess->processes_id = $newProcess->id;
        } else {
            // Если не существует, обновляем существующий процесс
            $process->process = $validated['process'];
            $process->save();
        }
        $manualProcess->process_comment = $processComment !== '' ? $processComment : null;
        $manualProcess->save();

        $redirectTo = $request->input('return_to');
        if ($redirectTo) {
            return redirect($redirectTo)->with('success', 'Process updated successfully');
        }
        return redirect()->route('processes.edit', ['id' => $manualId])
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
        $manualProcess = ManualProcess::findOrFail($id);
        $manual = $manualProcess->manual;
        abort_unless($manual, 404);

        $manualProcess->load('process');
        $decision = $this->guard()->canDeleteManualProcess(request()->user(), $manualProcess);
        if (! $decision->allowed) {
            return $this->denyDecision(request(), $decision, route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes']));
        }

        $manualId = $manualProcess->manual_id;
        $manualProcess->delete();

        $redirectTo = request()->input('return_to');
        if ($redirectTo) {
            return redirect($redirectTo)->with('success', 'Process deleted successfully');
        }
        return redirect()->route('processes.edit', ['id' => $manualId])
            ->with('success', 'Process deleted successfully');
    }

    private function guard(): ProcessAccessGuard
    {
        return app(ProcessAccessGuard::class);
    }

    private function denyDecision(Request $request, ProcessAccessDecision $decision, string $fallbackUrl)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $decision->message,
                'reason' => $decision->reason,
                'contacts' => $decision->contacts,
            ], 403);
        }

        $returnTo = (string) $request->input('return_to', '');

        return redirect($returnTo !== '' ? $returnTo : $fallbackUrl)
            ->with('error', $decision->message);
    }
}
