<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Services\ProcessAccessDecision;
use App\Services\ProcessAccessGuard;
use Illuminate\Http\Request;

class ManualProcessController extends Controller
{
    public function deletionImpact(Request $request, ManualProcess $manualProcess)
    {
        $manualProcess->load('process');
        $decision = $this->guard()->canDeleteManualProcess($request->user(), $manualProcess);
        if (!$decision->allowed) {
            return $this->denyDecision($request, $decision, route('manuals.show', $manualProcess->manual_id));
        }
        $duplicate = ManualProcess::where('manual_id', $manualProcess->manual_id)
            ->where('processes_id', $manualProcess->processes_id)->where('id', '!=', $manualProcess->id)->exists();
        $versionIds = $manualProcess->versions()->pluck('process_id')->push($manualProcess->processes_id)->unique();
        $templates = \App\Models\TravelerProcessTemplate::where('manual_id', $manualProcess->manual_id)
            ->whereHas('steps', fn ($q) => $q->whereIn('process_id', $versionIds))
            ->withCount(['steps as affected_steps' => fn ($q) => $q->whereIn('process_id', $versionIds)])
            ->get(['id']);
        $steps = $duplicate ? 0 : (int) $templates->sum('affected_steps');
        $count = $duplicate ? 0 : $templates->count();
        return response()->json([
            'manual_links_removed' => 1, 'affected_steps' => $steps, 'affected_templates' => $count,
            'blocked' => false,
            'message' => "Archive this process in this manual? {$steps} saved step(s) in {$count} template(s) will retain their specifications. "
                .'Other manuals and existing Travelers are unchanged. Only a system administrator can restore it.',
        ]);
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

        // Проверка, существует ли такой процесс с другим manual_id
        $validated = $request->validate([
            'process' => ['required', 'string', 'max:1000'],
            'process_comment' => ['nullable', 'string', 'max:2000'],
        ]);
        $processComment = trim((string) ($validated['process_comment'] ?? ''));

        $updatedManualProcess = \App\Services\ManualProcessVersions::edit($manualProcess, [
            'process' => $validated['process'], 'process_comment' => $processComment !== '' ? $processComment : null,
        ], $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Process version saved. Existing templates and Travelers keep their saved specification.',
            'manual_process' => [
                'id' => $updatedManualProcess->id,
                'process' => $updatedManualProcess->process?->process ?? '',
                'process_comment' => $updatedManualProcess->process_comment ?? '',
                'process_name' => $updatedManualProcess->process?->process_name?->name ?? '',
            ],
        ]);
    }

    public function updateRequirements(Request $request, ManualProcess $manualProcess)
    {
        $manualProcess->load('process');
        $decision = $this->guard()->canUpdateManualProcess($request->user(), $manualProcess);
        if (! $decision->allowed) {
            return $this->denyDecision($request, $decision, route('manuals.show', ['manual' => $manualProcess->manual_id, 'tab' => 'processes']));
        }

        $validated = $request->validate([
            'requires_fig' => ['required', 'boolean'],
            'requires_zone' => ['required', 'boolean'],
        ]);

        \App\Services\ManualProcessVersions::edit($manualProcess, [
            'requires_fig' => (bool) $validated['requires_fig'],
            'requires_zone' => (bool) $validated['requires_zone'],
        ], $request->user()->id);
        $manualProcess->refresh();

        return response()->json([
            'ok' => true,
            'requires_fig' => $manualProcess->requires_fig,
            'requires_zone' => $manualProcess->requires_zone,
        ]);
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
        \Illuminate\Support\Facades\DB::transaction(function () use ($manualProcess) {
            $link = ManualProcess::lockForUpdate()->findOrFail($manualProcess->id);
            \App\Services\ManualProcessVersions::record($link, request()->user()->id);
            $link->update(['archived_by' => request()->user()->id]);
            $link->delete();
        });

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'manual_process_id' => (int) $manualProcess->id,
                'message' => 'Process archived in this manual. Saved templates and Travelers are preserved.',
            ]);
        }

        $redirectTo = request()->input('return_to');
        if ($redirectTo) {
            return redirect($redirectTo)->with('success', 'Process archived in this manual. Saved templates and Travelers are preserved.');
        }
        return redirect()->route('manuals.show', ['manual' => $manualId, 'tab' => 'processes'])
            ->with('success', 'Process archived in this manual. Saved templates and Travelers are preserved.');
    }

    public function archive(Request $request, \App\Models\Manual $manual)
    {
        abort_unless($request->user()->isAdmin(), 403);
        return view('admin.manuals.process-archive', [
            'manual' => $manual,
            'links' => ManualProcess::onlyTrashed()->where('manual_id', $manual->id)
                ->with('process.process_name')->orderByDesc('deleted_at')->get(),
        ]);
    }

    public function restore(Request $request, int $id)
    {
        abort_unless($request->user()->isAdmin(), 403);
        \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $link = ManualProcess::onlyTrashed()->lockForUpdate()->findOrFail($id);
            $link->restore();
            $link->update(['archived_by' => null]);
        });
        return back()->with('success', 'Process restored.');
    }

    public function history(Request $request, int $id)
    {
        $link = ManualProcess::withTrashed()->with('process.process_name')->findOrFail($id);
        if ($link->trashed()) abort_unless($request->user()->isAdmin(), 403);
        else abort_unless($this->guard()->canManageManual($request->user(), $link->manual)->allowed, 403);
        return view('admin.manuals.process-history', [
            'link' => $link, 'versions' => $link->versions()->with(['process', 'createdBy'])->orderByDesc('version')->get(),
        ]);
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
