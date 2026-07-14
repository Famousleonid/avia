<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkorderStdProcess;
use App\Services\ManualDateRepairOrderSync;
use App\Services\ProcessSequenceGuard;
use App\Services\ProcessSequenceNotifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WorkorderStdProcessController extends Controller
{
    public function updateDate(Request $request, WorkorderStdProcess $stdProcess)
    {
        $isAjax = $request->ajax()
            || $request->expectsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        $v = Validator::make($request->all(), [
            'date_start' => ['nullable', 'date'],
            'date_finish' => ['nullable', 'date'],
            'date_promise' => ['nullable', 'date'],
        ]);

        if ($v->fails()) {
            if ($isAjax) {
                return response()->json(['success' => false, 'errors' => $v->errors()], 422);
            }

            return back()->withErrors($v)->withInput();
        }

        $data = $v->validated();

        if (! array_key_exists('date_start', $data) && ! array_key_exists('date_finish', $data) && ! array_key_exists('date_promise', $data)) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'No date fields'], 422);
            }

            return back()->withErrors(['date' => 'No date fields'])->withInput();
        }

        $allowsManualDateEditing = $this->allowsManualDateEditing($stdProcess);

        if (! $allowsManualDateEditing
            && ! $this->userCanBypassProcessSequence()
            && ($errors = app(ProcessSequenceGuard::class)->validateStdDateUpdate($stdProcess, $data))) {
            if ($isAjax) {
                return response()->json(['success' => false, 'errors' => $errors], 422);
            }

            return back()->withErrors($errors)->withInput();
        }

        if (! $allowsManualDateEditing) {
            $currentStart = $stdProcess->date_start ? $stdProcess->date_start->format('Y-m-d') : null;
            $effectiveStart = array_key_exists('date_start', $data)
                ? ($data['date_start'] ?: null)
                : $currentStart;

            if (! empty($data['date_finish']) && ! $effectiveStart) {
                $errors = ['date_finish' => ['The start date must be filled in before setting the end date.']];

                if ($isAjax) {
                    return response()->json(['success' => false, 'errors' => $errors], 422);
                }

                return back()->withErrors($errors)->withInput();
            }

            if (! empty($data['date_finish']) && $effectiveStart && Carbon::parse($data['date_finish'])->lt(Carbon::parse($effectiveStart))) {
                $errors = ['date_finish' => ['The end date cannot be earlier than the start date.']];

                if ($isAjax) {
                    return response()->json(['success' => false, 'errors' => $errors], 422);
                }

                return back()->withErrors($errors)->withInput();
            }
        }

        $oldStart = $stdProcess->date_start ? $stdProcess->date_start->format('Y-m-d') : null;
        $oldFinish = $stdProcess->date_finish ? $stdProcess->date_finish->format('Y-m-d') : null;
        $authId = auth()->id();
        $editorName = $this->dateEditorName();
        $nextProcessForNotification = app(ProcessSequenceGuard::class)->nextAfterStdProcess($stdProcess);

        if (array_key_exists('date_start', $data)) {
            $nextStart = $data['date_start'] ?: null;
            $stdProcess->date_start = $nextStart;
            if ($oldStart !== $nextStart) {
                $stdProcess->date_start_user_id = $authId;
                $stdProcess->date_start_user = $editorName;
            }

            if ($nextStart === null && ! $allowsManualDateEditing) {
                $stdProcess->date_finish = null;
                if ($oldFinish !== null) {
                    $stdProcess->date_finish_user_id = $authId;
                    $stdProcess->date_finish_user = $editorName;
                }
            }
        }

        if (array_key_exists('date_finish', $data)) {
            $nextFinish = $data['date_finish'] ?: null;
            $stdProcess->date_finish = $nextFinish;
            if ($oldFinish !== $nextFinish) {
                $stdProcess->date_finish_user_id = $authId;
                $stdProcess->date_finish_user = $editorName;
            }
        }

        if (array_key_exists('date_promise', $data)) {
            $stdProcess->date_promise = $data['date_promise'] ?: null;
        }

        app(ManualDateRepairOrderSync::class)->sync($stdProcess, $allowsManualDateEditing);

        $stdProcess->user_id = $authId;
        $stdProcess->save();

        $newFinishForNotification = $stdProcess->date_finish ? $stdProcess->date_finish->format('Y-m-d') : null;
        if ($oldFinish === null && $newFinishForNotification !== null) {
            app(ProcessSequenceNotifier::class)->notifyReady($nextProcessForNotification, $stdProcess);
        }

        if ($isAjax) {
            $stdProcess->loadMissing(['dateStartUpdatedBy:id,name,selection_name_order', 'dateFinishUpdatedBy:id,name,selection_name_order']);

            return response()->json([
                'success' => true,
                'user' => auth()->user()->selection_name ?? 'system',
                'date_start' => $stdProcess->date_start ? $stdProcess->date_start->format('Y-m-d') : null,
                'date_finish' => $stdProcess->date_finish ? $stdProcess->date_finish->format('Y-m-d') : null,
                'date_promise' => $stdProcess->date_promise ? $stdProcess->date_promise->format('Y-m-d') : null,
                'repair_order' => $stdProcess->repair_order,
                'date_start_user' => $stdProcess->dateStartUpdatedBy?->selection_name ?: $stdProcess->date_start_user,
                'date_finish_user' => $stdProcess->dateFinishUpdatedBy?->selection_name ?: $stdProcess->date_finish_user,
            ], 200);
        }

        return back()->with('success', 'STD process dates updated');
    }

    public function updateRepairOrder(Request $request, WorkorderStdProcess $stdProcess)
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole('Admin|Manager'), 403);

        $request->validate([
            'repair_order' => 'nullable|string|max:255',
            'vendor_id' => 'nullable|integer|exists:vendors,id',
        ]);

        if ($request->has('repair_order')) {
            $stdProcess->repair_order = $request->repair_order;
        }
        if ($request->has('vendor_id')) {
            $stdProcess->vendor_id = $request->filled('vendor_id') ? (int) $request->input('vendor_id') : null;
        }
        $stdProcess->user_id = auth()->id();
        $stdProcess->save();
        $stdProcess->load('vendor:id,name');

        return response()->json([
            'success' => true,
            'user' => auth()->user()?->selection_name ?? 'system',
            'repair_order' => $stdProcess->repair_order,
            'vendor_id' => $stdProcess->vendor_id,
            'vendor_name' => $stdProcess->vendor?->name,
            'updated_at' => now()->format('d.m.Y H:i'),
        ]);
    }

    public function updateIgnoreRow(Request $request, WorkorderStdProcess $stdProcess)
    {
        abort_unless(auth()->check(), 403);

        $data = $request->validate([
            'ignore_row' => ['required', 'boolean'],
        ]);

        $stdProcess->ignore_row = (bool) $data['ignore_row'];
        app(ManualDateRepairOrderSync::class)->sync($stdProcess, $this->allowsManualDateEditing($stdProcess));
        $stdProcess->user_id = auth()->id();
        $stdProcess->save();
        $stdProcess->loadMissing(['dateStartUpdatedBy:id,name,selection_name_order', 'dateFinishUpdatedBy:id,name,selection_name_order']);

        $rowName = $stdProcess->processName()->value('name') ?? 'STD Process';
        $updatedAt = now()->format('d ') . Str::lower(now()->format('M')) . now()->format(' Y');

        return response()->json([
            'success' => true,
            'message' => $stdProcess->ignore_row
                ? "Row ignored ({$rowName}) {$updatedAt}"
                : "Row restored ({$rowName}) {$updatedAt}",
            'ignore_row' => (bool) $stdProcess->ignore_row,
            'user' => auth()->user()?->selection_name ?? 'system',
            'updated_at' => $updatedAt,
            'repair_order' => $stdProcess->repair_order,
            'date_start' => $stdProcess->date_start ? $stdProcess->date_start->format('Y-m-d') : null,
            'date_finish' => $stdProcess->date_finish ? $stdProcess->date_finish->format('Y-m-d') : null,
            'date_start_user' => $stdProcess->dateStartUpdatedBy?->selection_name ?: $stdProcess->date_start_user,
            'date_finish_user' => $stdProcess->dateFinishUpdatedBy?->selection_name ?: $stdProcess->date_finish_user,
        ]);
    }

    private function userCanBypassProcessSequence(): bool
    {
        return auth()->check() && auth()->user()?->isAdmin();
    }

    private function allowsManualDateEditing(WorkorderStdProcess $stdProcess): bool
    {
        $stdProcess->loadMissing('processName');

        return (bool) ($stdProcess->processName?->allowsManualDateEditing() ?? false);
    }

    private function dateEditorName(): string
    {
        return auth()->user()?->selection_name ?? 'system';
    }
}
