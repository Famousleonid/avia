<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\ManualPartGroup;
use App\Models\ManualPartGroupOption;
use App\Models\ManualServiceBulletin;
use App\Models\RmReport;
use App\Models\Workorder;
use App\Services\WorkorderAssemblyModificationService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RmReportController extends Controller
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
     * @return Application|Factory|View
     */
        public function create($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;
        
        // Сохраняем workorder_id в сессии для использования при удалении
        session(['current_workorder_id' => $id]);
        
        // Получаем существующие записи R&M для этого workorder
        $rm_reports = RmReport::where('manual_id', $manual_id)->templatesFirst()->get();

        return view('admin.rm_reports.create', compact('current_wo', 'rm_reports'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate(['workorder_id' => ['required', 'integer', 'exists:workorders,id']]);
        $workorder = Workorder::findOrFail($request->integer('workorder_id'));
        $validated = $this->validateRecord($request, $workorder);

        // Создаем запись в rm_reports
        $rmReport = RmReport::create([
            'manual_id' => $workorder->unit->manual_id,
            'is_admin_template' => $validated['is_admin_template'],
            'manual_service_bulletin_id' => $validated['manual_service_bulletin_id'] ?? null,
            'source_assy_option_id' => $validated['source_assy_option_id'] ?? null,
            'target_assy_option_id' => $validated['target_assy_option_id'] ?? null,
            'part_description' => $validated['part_description'],
            'mod_repair' => $validated['mod_repair'],
            'description' => $validated['mod_repair_description'],
            'ident_method' => $validated['ident_method'] ?? null,
        ]);
        $rmReport->loadMissing(['serviceBulletin', 'sourceAssyOption', 'targetAssyOption']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'R&M Record created successfully',
                'data' => [
                    'id' => $rmReport->id,
                    'is_admin_template' => $rmReport->is_admin_template,
                    'part_description' => $rmReport->part_description,
                    'mod_repair' => $rmReport->mod_repair,
                    'description' => $rmReport->description,
                    'ident_method' => $rmReport->ident_method,
                    'manual_service_bulletin_id' => $rmReport->manual_service_bulletin_id,
                    'source_assy_option_id' => $rmReport->source_assy_option_id,
                    'target_assy_option_id' => $rmReport->target_assy_option_id,
                    'changes_assembly_scope' => $rmReport->changesAssemblyScope(),
                    'service_bulletin_label' => $this->serviceBulletinLabel($rmReport->serviceBulletin),
                    'source_assy_label' => $this->assyOptionLabel($rmReport->sourceAssyOption),
                    'target_assy_label' => $this->assyOptionLabel($rmReport->targetAssyOption),
                    'source_assy_part_number' => $rmReport->sourceAssyOption?->part_number,
                    'target_assy_part_number' => $rmReport->targetAssyOption?->part_number,
                ]
            ]);
        }

        return redirect()->route('rm_reports.show', $validated['workorder_id'])
            ->with('success', 'R&M Record created successfully');
    }
    public function wo_store(Request $request)
{
    //
}

    /**
     * Return partial HTML for Repair & Modification Record tab (embedded in TDR show).
     *
     * @param int $workorder_id
     * @return Application|Factory|View
     */
    public function partial($workorder_id)
    {
        $current_wo = Workorder::findOrFail($workorder_id);
        $manual_id = $current_wo->unit->manual_id;

        $rm_reports = RmReport::where('manual_id', $manual_id)->templatesFirst()
            ->with(['serviceBulletin', 'sourceAssyOption.group', 'targetAssyOption.group'])
            ->get();
        [$assyOptions, $serviceBulletins] = $this->recordFormOptions((int) $manual_id);

        return view('admin.rm_reports.partial', compact('current_wo', 'rm_reports', 'assyOptions', 'serviceBulletins'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function show($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;

        $rm_reports = RmReport::where('manual_id', $manual_id)->templatesFirst()
            ->with(['serviceBulletin', 'sourceAssyOption.group', 'targetAssyOption.group'])
            ->get();
        [$assyOptions, $serviceBulletins] = $this->recordFormOptions((int) $manual_id);

        // Если это AJAX запрос, возвращаем JSON
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $rm_reports
            ]);
        }

        return view('admin.rm_reports.show-page', compact('current_wo', 'rm_reports', 'assyOptions', 'serviceBulletins'));
    }
public function rmRecordForm(Request $request, $id)
{
    $current_wo = Workorder::findOrFail($id);
    // Получаем данные о manual, связанном с этим Workorder
    $manual = $current_wo->unit->manual_id;
    $manual_wo = $current_wo->unit->manuals;

    $builders = Builder::all();
    
    // Получаем сохраненные данные R&M
    $savedData = null;
    $technicalNotes = [];
    $rmRecords = collect(); // Инициализируем как пустую коллекцию
    
    if ($current_wo->rm_report) {
        $savedData = json_decode($current_wo->rm_report, true);
        
        if ($savedData) {
            // Получаем технические заметки
            if (isset($savedData['technical_notes'])) {
                $technicalNotes = $savedData['technical_notes'];
            }
            
            // Получаем R&M записи
            if (isset($savedData['rm_records']) && !empty($savedData['rm_records'])) {
                $recordIds = collect($savedData['rm_records'])->pluck('id')->toArray();
                $rmRecords = RmReport::whereIn('id', $recordIds)->get();
            }
        }
    }
    
    return view('admin.rm_reports.rmRecordForm', compact('current_wo', 'technicalNotes', 'rmRecords'));

}
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;
        
        // Сохраняем workorder_id в сессии для использования при удалении
        session(['current_workorder_id' => $id]);
        
        // Получаем существующие записи R&M для этого workorder
        $rm_reports = RmReport::where('manual_id', $manual_id)->templatesFirst()->get();

        return view('admin.rm_reports.edit', compact('current_wo', 'rm_reports'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id, WorkorderAssemblyModificationService $modificationService)
    {
        $request->validate([
            'workorder_id' => ['required', 'integer', 'exists:workorders,id'],
            'selected_records' => ['nullable', 'json'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string'],
        ]);
        $selectedRecords = json_decode((string) $request->selected_records, true);
        $workorder_id = (int) $request->workorder_id;
        
        // Собираем технические заметки (новый формат: notes[])
        $technicalNotes = $request->input('notes', []);
        // Обратная совместимость: если notes[] нет, пробуем старые поля note1..note7
        if (empty($technicalNotes)) {
            for ($i = 1; $i <= 7; $i++) {
                $noteValue = $request->input('note' . $i, '');
                if ($noteValue !== '') {
                    $technicalNotes[] = $noteValue;
                }
            }
        }
        
        $workorder = Workorder::findOrFail($workorder_id);
        $recordIds = $this->selectedRecordIds($selectedRecords);
        $rmRecords = $this->selectedRecordsForWorkorder($workorder, $recordIds);
        $dataToSave = [];
        if ($rmRecords->isNotEmpty()) {
            $dataToSave['rm_records'] = $this->recordSnapshots($rmRecords);
        }
        
        // Добавляем технические заметки
        $dataToSave['technical_notes'] = $technicalNotes;
        
        $modification = DB::transaction(function () use ($workorder, $dataToSave, $rmRecords, $modificationService): array {
            $workorder->update(['rm_report' => json_encode($dataToSave)]);

            return $modificationService->sync($workorder->fresh(['unit', 'scopeComponent', 'modifiedScopePartGroupOption']), $rmRecords);
        });
        
        $successMessage = '';
        if (!empty($selectedRecords)) {
            $successMessage .= count($selectedRecords) . ' R&M Record(s) and ';
        }
        $successMessage .= 'Technical Notes updated successfully';
        
        return response()->json([
            'success' => true,
            'message' => $successMessage,
            'modification' => $modification,
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
        $rmReport = RmReport::findOrFail($id);
        abort_if($rmReport->is_admin_template && ! auth()->user()?->roleIs('Admin'), 403, 'Only Admin can delete protected R&M templates.');
        
        // Получаем workorder_id из текущей сессии или из параметра запроса
        $workorder_id = session('current_workorder_id') ?? request('workorder_id');
        
        // Если workorder_id не найден, попробуем получить его из связанных данных
        if (!$workorder_id) {
            // Попробуем найти workorder через manual
            $workorder = Workorder::whereHas('unit', function($query) use ($rmReport) {
                $query->where('manual_id', $rmReport->manual_id);
            })->first();
            
            $workorder_id = $workorder ? $workorder->id : null;
        }

        // Проверяем, используется ли этот R&M record в других workorders
        $usedInWorkorders = Workorder::whereNotNull('rm_report')
            ->where('rm_report', '!=', '')
            ->get()
            ->filter(function($workorder) use ($rmReport) {
                $rmData = json_decode($workorder->rm_report, true);
                if ($rmData && isset($rmData['rm_records'])) {
                    return collect($rmData['rm_records'])->contains('id', $rmReport->id);
                }
                return false;
            });

        // Если record используется в других workorders (не в текущем)
        $usedInOtherWorkorders = $usedInWorkorders->filter(function($workorder) use ($workorder_id) {
            return $workorder->id != $workorder_id;
        });

        if ($rmReport->changesAssemblyScope() && $usedInWorkorders->isNotEmpty()) {
            $workorderNumbers = $usedInWorkorders->pluck('number')->implode(', ');
            $errorMessage = 'Cannot delete an active assembly conversion. Deselect it first in Workorder(s): '.$workorderNumbers;

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 422);
            }

            $redirectTo = $workorder_id ? route('rm_reports.show', $workorder_id) : url()->previous();

            return redirect($redirectTo)->with('error', $errorMessage);
        }

        if ($usedInOtherWorkorders->count() > 0) {
            $workorderNumbers = $usedInOtherWorkorders->pluck('number')->implode(', ');
            $errorMessage = "Cannot delete R&M Record. It is used in the following work orders: " . $workorderNumbers;

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 422);
            }
            $redirectTo = $workorder_id ? route('rm_reports.show', $workorder_id) : url()->previous();
            return redirect($redirectTo)->with('error', $errorMessage);
        }

        $rmReport->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'R&M Record deleted successfully',
                'workorder_id' => $workorder_id
            ]);
        }

        if ($workorder_id) {
            return redirect()->route('rm_reports.show', $workorder_id)
                ->with('success', 'R&M Record deleted successfully');
        }
        return redirect()->back()->with('success', 'R&M Record deleted successfully');
    }
    
    /**
     * Remove multiple specified resources from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyMultiple(Request $request)
    {
        $selectedRecords = json_decode($request->selected_records, true);
        abort_if(! auth()->user()?->roleIs('Admin') && RmReport::whereIn('id', $selectedRecords ?: [])->where('is_admin_template', true)->exists(), 403);
        $workorder_id = $request->workorder_id;
        
        if (!empty($selectedRecords)) {
            // Проверяем каждый record на использование в других workorders
            $recordsToDelete = [];
            $recordsInUse = [];
            
            foreach ($selectedRecords as $recordId) {
                $rmReport = RmReport::find($recordId);
                if ($rmReport) {
                    // Проверяем, используется ли этот R&M record в других workorders
                    $usedInWorkorders = Workorder::whereNotNull('rm_report')
                        ->where('rm_report', '!=', '')
                        ->get()
                        ->filter(function($workorder) use ($rmReport) {
                            $rmData = json_decode($workorder->rm_report, true);
                            if ($rmData && isset($rmData['rm_records'])) {
                                return collect($rmData['rm_records'])->contains('id', $rmReport->id);
                            }
                            return false;
                        });

                    // Если record используется в других workorders (не в текущем)
                    $usedInOtherWorkorders = $usedInWorkorders->filter(function($workorder) use ($workorder_id) {
                        return $workorder->id != $workorder_id;
                    });

                    if ($rmReport->changesAssemblyScope() && $usedInWorkorders->isNotEmpty()) {
                        $workorderNumbers = $usedInWorkorders->pluck('number')->implode(', ');
                        $recordsInUse[] = "Record ID {$rmReport->id} ({$rmReport->part_description}) - active assembly conversion in: ".$workorderNumbers;
                    } elseif ($usedInOtherWorkorders->count() > 0) {
                        $workorderNumbers = $usedInOtherWorkorders->pluck('number')->implode(', ');
                        $recordsInUse[] = "Record ID {$rmReport->id} ({$rmReport->part_description}) - used in: " . $workorderNumbers;
                    } else {
                        $recordsToDelete[] = $recordId;
                    }
                }
            }
            
            // Удаляем только те records, которые не используются
            if (!empty($recordsToDelete)) {
                // Delete through model events so every removed record is audited.
                DB::transaction(fn () => RmReport::destroy($recordsToDelete));
            }
            
            // Формируем сообщение
            $successMessage = '';
            $errorMessage = '';
            
            if (!empty($recordsToDelete)) {
                $successMessage = count($recordsToDelete) . ' R&M Record(s) deleted successfully.';
            }
            
            if (!empty($recordsInUse)) {
                $errorMessage = 'Cannot delete the following records: ' . implode('; ', $recordsInUse);
            }
            
            // Определяем, на какую страницу возвращаться
            $workorder = Workorder::find($workorder_id);
            if ($workorder && $workorder->rm_report) {
                $redirectRoute = 'rm_reports.edit';
            } else {
                $redirectRoute = 'rm_reports.create';
            }
            
            $redirect = redirect()->route($redirectRoute, $workorder_id);
            
            if (!empty($successMessage)) {
                $redirect->with('success', $successMessage);
            }
            
            if (!empty($errorMessage)) {
                $redirect->with('error', $errorMessage);
            }
            
            return $redirect;
        }
        
        return redirect()->route('rm_reports.create', $workorder_id)
            ->with('error', 'No records selected for deletion');
    }
    
    /**
     * Save selected R&M records to workorder as JSON.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveToWorkorder(Request $request, WorkorderAssemblyModificationService $modificationService)
    {
        // Обрабатываем selected_records - может быть пустым массивом или не передан
        $selectedRecords = [];
        if ($request->has('selected_records') && !empty($request->selected_records)) {
            $decoded = json_decode($request->selected_records, true);
            if (is_array($decoded)) {
                $selectedRecords = $decoded;
            }
        }
        
        $workorder_id = (int) $request->workorder_id;
        
        // Собираем технические заметки (новый формат: notes[])
        $technicalNotes = $request->input('notes', []);
        // Обратная совместимость: если notes[] нет, пробуем старые поля note1..note7
        if (empty($technicalNotes)) {
            for ($i = 1; $i <= 7; $i++) {
                $noteValue = $request->input('note' . $i, '');
                if ($noteValue !== '') {
                    $technicalNotes[] = $noteValue;
                }
            }
        }
        
        $dataToSave = [];
        
        // Получаем workorder для проверки существующих данных
        $workorder = Workorder::findOrFail($workorder_id);
        
        // Добавляем R&M записи, если они выбраны
        if (!empty($selectedRecords)) {
            $recordIds = $this->selectedRecordIds($selectedRecords);
            $rmRecords = $this->selectedRecordsForWorkorder($workorder, $recordIds);
            $dataToSave['rm_records'] = $this->recordSnapshots($rmRecords);
        } else {
            // Если записи не выбраны, сохраняем существующие записи из workorder (если есть)
            if ($workorder->rm_report) {
                $existingData = json_decode($workorder->rm_report, true);
                if ($existingData && isset($existingData['rm_records']) && !empty($existingData['rm_records'])) {
                    $dataToSave['rm_records'] = $existingData['rm_records'];
                }
            }
        }
        
        // Добавляем технические заметки
        $dataToSave['technical_notes'] = $technicalNotes;
        
        $selectedSnapshotIds = collect($dataToSave['rm_records'] ?? [])->pluck('id')->map(fn ($recordId): int => (int) $recordId)->all();
        $rmRecords = $this->selectedRecordsForWorkorder($workorder, $selectedSnapshotIds);

        DB::transaction(function () use ($workorder, $dataToSave, $rmRecords, $modificationService): void {
            $workorder->update(['rm_report' => json_encode($dataToSave)]);
            $modificationService->sync($workorder->fresh(['unit', 'scopeComponent', 'modifiedScopePartGroupOption']), $rmRecords);
        });
        
        $successMessage = '';
        if (!empty($selectedRecords)) {
            $successMessage .= count($selectedRecords) . ' R&M Record(s) and ';
        }
        $successMessage .= 'Technical Notes saved to work order successfully';
        
        return redirect()->route('rm_reports.show', $workorder_id)
            ->with('success', $successMessage);
    }

    /**
     * Get a specific R&M record for editing.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecord($id)
    {
        $rmReport = RmReport::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $rmReport
        ]);
    }

    /**
     * Update a specific R&M record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRecord(Request $request, $id, WorkorderAssemblyModificationService $modificationService)
    {
        $rmReport = RmReport::findOrFail($id);
        $request->validate(['workorder_id' => ['required', 'integer', 'exists:workorders,id']]);
        $workorder = Workorder::findOrFail($request->integer('workorder_id'));
        $validated = $this->validateRecord($request, $workorder, $rmReport);

        $newDescription = (string) $validated['mod_repair_description'];
        $oldDescription = (string) ($rmReport->description ?? '');
        if ($newDescription !== $oldDescription && mb_strlen($newDescription) > 250) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Description may not be greater than 250 characters.',
                    'errors' => [
                        'mod_repair_description' => ['Description may not be greater than 250 characters.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['mod_repair_description' => 'Description may not be greater than 250 characters.'])
                ->withInput();
        }
        
        DB::transaction(function () use ($rmReport, $validated, $newDescription, $modificationService): void {
            $rmReport->update([
                'is_admin_template' => $validated['is_admin_template'],
                'manual_service_bulletin_id' => $validated['manual_service_bulletin_id'] ?? null,
                'source_assy_option_id' => $validated['source_assy_option_id'] ?? null,
                'target_assy_option_id' => $validated['target_assy_option_id'] ?? null,
                'part_description' => $validated['part_description'],
                'mod_repair' => $validated['mod_repair'],
                'description' => $newDescription,
                'ident_method' => $validated['ident_method'] ?? null,
            ]);

            // R&M rows are reusable manual templates. Refresh every Workorder
            // currently using this row so its Modified P/N and effective scope
            // cannot drift apart when the conversion is edited.
            $this->workordersUsingRecord($rmReport)->each(function (Workorder $usedWorkorder) use ($modificationService): void {
                $selectedIds = collect(data_get(json_decode((string) $usedWorkorder->rm_report, true), 'rm_records', []))
                    ->pluck('id')
                    ->map(fn ($recordId): int => (int) $recordId)
                    ->filter()
                    ->all();
                $records = $this->selectedRecordsForWorkorder($usedWorkorder, $selectedIds);

                $modificationService->sync(
                    $usedWorkorder->fresh(['unit', 'scopeComponent', 'modifiedScopePartGroupOption']),
                    $records
                );
            });
        });

        $rmReport->refresh()->loadMissing(['serviceBulletin', 'sourceAssyOption', 'targetAssyOption']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'R&M Record updated successfully',
                'data' => [
                    'id' => $rmReport->id,
                    'is_admin_template' => $rmReport->is_admin_template,
                    'part_description' => $rmReport->part_description,
                    'mod_repair' => $rmReport->mod_repair,
                    'description' => $rmReport->description,
                    'ident_method' => $rmReport->ident_method,
                    'manual_service_bulletin_id' => $rmReport->manual_service_bulletin_id,
                    'source_assy_option_id' => $rmReport->source_assy_option_id,
                    'target_assy_option_id' => $rmReport->target_assy_option_id,
                    'changes_assembly_scope' => $rmReport->changesAssemblyScope(),
                    'service_bulletin_label' => $this->serviceBulletinLabel($rmReport->serviceBulletin),
                    'source_assy_label' => $this->assyOptionLabel($rmReport->sourceAssyOption),
                    'target_assy_label' => $this->assyOptionLabel($rmReport->targetAssyOption),
                    'source_assy_part_number' => $rmReport->sourceAssyOption?->part_number,
                    'target_assy_part_number' => $rmReport->targetAssyOption?->part_number,
                ]
            ]);
        }

        return redirect()->route('rm_reports.show', $validated['workorder_id'])
            ->with('success', 'R&M Record updated successfully');
    }

    /** @return array<string, mixed> */
    private function validateRecord(Request $request, Workorder $workorder, ?RmReport $existing = null): array
    {
        abort_if($existing?->is_admin_template && ! auth()->user()?->roleIs('Admin'), 403, 'Only Admin can change protected R&M templates.');
        abort_if($request->has('is_admin_template') && ! auth()->user()?->roleIs('Admin') && $request->boolean('is_admin_template'), 403);
        $validated = $request->validate([
            'is_admin_template' => ['sometimes', 'boolean'],
            'part_description' => ['required', 'string', 'max:255'],
            'mod_repair' => ['required', 'in:Mod,Repair,SB'],
            'mod_repair_description' => $existing
                ? ['required', 'string']
                : ['required', 'string', 'max:250'],
            'ident_method' => ['nullable', 'string', 'max:255'],
            'workorder_id' => ['required', 'integer', 'exists:workorders,id'],
            'manual_service_bulletin_id' => ['nullable', 'integer', 'exists:manual_service_bulletins,id'],
            'source_assy_option_id' => ['nullable', 'integer', 'exists:manual_part_group_options,id'],
            'target_assy_option_id' => ['nullable', 'integer', 'exists:manual_part_group_options,id'],
        ]);

        $validated['is_admin_template'] = $request->has('is_admin_template')
            ? $request->boolean('is_admin_template') : (bool) ($existing?->is_admin_template ?? false);
        $manualId = (int) ($workorder->unit?->manual_id ?? 0);
        if ($existing && (int) $existing->manual_id !== $manualId) {
            throw ValidationException::withMessages([
                'workorder_id' => __('This R&M record does not belong to the Workorder manual.'),
            ]);
        }

        $mappingFields = [
            'manual_service_bulletin_id',
            'source_assy_option_id',
            'target_assy_option_id',
        ];
        $hasMappingValue = collect($mappingFields)->contains(
            fn (string $field): bool => (int) ($validated[$field] ?? 0) > 0
        );

        if (! $hasMappingValue) {
            $validated['manual_service_bulletin_id'] = null;
            $validated['source_assy_option_id'] = null;
            $validated['target_assy_option_id'] = null;

            return $validated;
        }

        if (($validated['mod_repair'] ?? null) !== 'SB') {
            throw ValidationException::withMessages([
                'mod_repair' => __('Assembly conversion can only be configured for an SB record.'),
            ]);
        }

        foreach ($mappingFields as $field) {
            if ((int) ($validated[$field] ?? 0) <= 0) {
                throw ValidationException::withMessages([
                    $field => __('Select the Service Bulletin, received ASSY, and modified ASSY.'),
                ]);
            }
        }

        $optionIds = [
            (int) $validated['source_assy_option_id'],
            (int) $validated['target_assy_option_id'],
        ];
        if ($optionIds[0] === $optionIds[1]) {
            throw ValidationException::withMessages([
                'target_assy_option_id' => __('Modified ASSY must be different from the received ASSY.'),
            ]);
        }

        $validOptions = ManualPartGroupOption::query()
            ->whereIn('id', $optionIds)
            ->whereHas('group', fn ($group) => $group
                ->where('manual_id', $manualId)
                ->where('type', ManualPartGroup::TYPE_ASSY))
            ->count();
        if ($validOptions !== 2) {
            throw ValidationException::withMessages([
                'source_assy_option_id' => __('Both assemblies must be active ASSY groups from this Workorder manual.'),
            ]);
        }

        $validBulletin = ManualServiceBulletin::query()
            ->whereKey((int) $validated['manual_service_bulletin_id'])
            ->where('manual_id', $manualId)
            ->exists();
        if (! $validBulletin) {
            throw ValidationException::withMessages([
                'manual_service_bulletin_id' => __('The Service Bulletin must belong to this Workorder manual.'),
            ]);
        }

        return $validated;
    }

    /** @return array{0:Collection<int, ManualPartGroupOption>,1:Collection<int, ManualServiceBulletin>} */
    private function recordFormOptions(int $manualId): array
    {
        $assyOptions = ManualPartGroupOption::query()
            ->whereHas('group', fn ($group) => $group
                ->where('manual_id', $manualId)
                ->where('type', ManualPartGroup::TYPE_ASSY))
            ->with(['group:id,manual_id,name,type', 'component:id,part_number,ipl_num,name'])
            ->orderBy('ipl_num')
            ->orderBy('part_number')
            ->get();
        $serviceBulletins = ManualServiceBulletin::query()
            ->where('manual_id', $manualId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return [$assyOptions, $serviceBulletins];
    }

    /** @return list<int> */
    private function selectedRecordIds(mixed $selectedRecords): array
    {
        $records = is_array($selectedRecords) ? $selectedRecords : [];

        return collect($records)
            ->map(fn ($record) => is_array($record) ? ($record['id'] ?? null) : $record)
            ->map(fn ($recordId): int => (int) $recordId)
            ->filter(fn (int $recordId): bool => $recordId > 0)
            ->unique()
            ->values()
            ->all();
    }

    /** @return Collection<int, RmReport> */
    private function selectedRecordsForWorkorder(Workorder $workorder, array $recordIds): Collection
    {
        if ($recordIds === []) {
            return collect();
        }

        $manualId = (int) ($workorder->unit?->manual_id ?? 0);
        $records = RmReport::query()
            ->whereIn('id', $recordIds)
            ->where('manual_id', $manualId)
            ->with(['serviceBulletin', 'sourceAssyOption.group', 'targetAssyOption.group'])
            ->get();

        if ($records->count() !== count($recordIds)) {
            throw ValidationException::withMessages([
                'selected_records' => __('Every selected R&M record must belong to this Workorder manual.'),
            ]);
        }

        return $records;
    }

    /** @param Collection<int, RmReport> $records */
    private function recordSnapshots(Collection $records): array
    {
        return $records->map(fn (RmReport $record): array => [
            'id' => (int) $record->id,
            'created_at' => $record->created_at->toISOString(),
            'manual_service_bulletin_id' => $record->manual_service_bulletin_id
                ? (int) $record->manual_service_bulletin_id
                : null,
            'source_assy_option_id' => $record->source_assy_option_id
                ? (int) $record->source_assy_option_id
                : null,
            'target_assy_option_id' => $record->target_assy_option_id
                ? (int) $record->target_assy_option_id
                : null,
        ])->values()->all();
    }

    /** @return Collection<int, Workorder> */
    private function workordersUsingRecord(RmReport $record): Collection
    {
        return Workorder::query()
            ->withoutGlobalScope('exclude_drafts')
            ->where(function ($query) use ($record): void {
                $query->where('modified_scope_rm_report_id', $record->id)
                    ->orWhereNotNull('rm_report');
            })
            ->get()
            ->filter(function (Workorder $workorder) use ($record): bool {
                if ((int) ($workorder->modified_scope_rm_report_id ?? 0) === (int) $record->id) {
                    return true;
                }

                return collect(data_get(json_decode((string) $workorder->rm_report, true), 'rm_records', []))
                    ->contains(fn ($item): bool => (int) data_get($item, 'id') === (int) $record->id);
            })
            ->values();
    }

    private function serviceBulletinLabel(?ManualServiceBulletin $bulletin): string
    {
        if (! $bulletin) {
            return '';
        }

        return collect([
            $bulletin->ac_mfg_service_bulletin_no,
            $bulletin->oem_service_bulletin_no,
            $bulletin->description,
        ])->map(fn ($value): string => trim((string) $value))
            ->first(fn (string $value): bool => $value !== '') ?? ('SB #'.$bulletin->id);
    }

    private function assyOptionLabel(?ManualPartGroupOption $option): string
    {
        if (! $option) {
            return '';
        }

        $partNumber = trim((string) $option->part_number);
        $ipl = trim((string) $option->ipl_num);

        return $ipl !== '' ? $partNumber.' · IPL '.$ipl : $partNumber;
    }
}
