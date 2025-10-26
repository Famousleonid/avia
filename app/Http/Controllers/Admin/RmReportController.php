<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\RmReport;
use App\Models\Workorder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
        $rm_reports = RmReport::where('manual_id', $manual_id)->get();

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
        $validated = $request->validate([
            'part_description' => 'required|string|max:255',
            'mod_repair' => 'required|in:Mod,Repair,SB',
            'mod_repair_description' => 'required|string|max:255',
            'ident_method' => 'nullable|string|max:255',
            'workorder_id' => 'required|exists:workorders,id',
        ]);

        // Получаем workorder для получения manual_id
        $workorder = Workorder::findOrFail($validated['workorder_id']);

        // Создаем запись в rm_reports
        $rmReport = RmReport::create([
            'manual_id' => $workorder->unit->manual_id,
            'part_description' => $validated['part_description'],
            'mod_repair' => $validated['mod_repair'],
            'description' => $validated['mod_repair_description'], // Поле в БД называется description
            'ident_method' => $validated['ident_method'],
        ]);

        // Проверяем, есть ли уже сохраненные данные в workorder
        if ($workorder->rm_report) {
            // Если есть данные, возвращаемся на страницу редактирования
            return redirect()->route('rm_reports.edit', $validated['workorder_id'])
                ->with('success', 'R&M Record created successfully');
        } else {
            // Если данных нет, возвращаемся на страницу создания
            return redirect()->route('rm_reports.create', $validated['workorder_id'])
                ->with('success', 'R&M Record created successfully');
        }
    }
    public function wo_store(Request $request)
{
    //
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

        $rm_reports = RmReport::where('manual_id', $manual_id)->get();

        // Если это AJAX запрос, возвращаем JSON
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $rm_reports
            ]);
        }

        return view('admin.rm_reports.show', compact('current_wo', 'rm_reports'));
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
        $rm_reports = RmReport::where('manual_id', $manual_id)->get();

        return view('admin.rm_reports.edit', compact('current_wo', 'rm_reports'));
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
        $selectedRecords = json_decode($request->selected_records, true);
        $workorder_id = $request->workorder_id;
        
        // Собираем технические заметки
        $technicalNotes = [];
        for ($i = 1; $i <= 7; $i++) {
            $noteKey = 'note' . $i;
            $technicalNotes[$noteKey] = $request->input($noteKey, '');
        }
        
        $dataToSave = [];
        
        // Добавляем R&M записи, если они выбраны
        if (!empty($selectedRecords)) {
            // Проверяем, является ли $selectedRecords массивом объектов или простым массивом ID
            if (is_array($selectedRecords) && isset($selectedRecords[0]) && is_array($selectedRecords[0])) {
                // Если это массив объектов, извлекаем только ID
                $recordIds = collect($selectedRecords)->pluck('id')->toArray();
            } else {
                // Если это простой массив ID
                $recordIds = $selectedRecords;
            }
            
            // Получаем выбранные записи R&M
            $rmRecords = RmReport::whereIn('id', $recordIds)->get();
            
            // Преобразуем в массив для JSON
            $rmData = $rmRecords->map(function($record) {
                return [
                    'id' => $record->id,
                    'created_at' => $record->created_at->toISOString()
                ];
            })->toArray();
            
            $dataToSave['rm_records'] = $rmData;
        }
        
        // Добавляем технические заметки
        $dataToSave['technical_notes'] = $technicalNotes;
        
        // Сохраняем в поле rm_report таблицы workorders
        $workorder = Workorder::findOrFail($workorder_id);
        $workorder->update([
            'rm_report' => json_encode($dataToSave)
        ]);
        
        $successMessage = '';
        if (!empty($selectedRecords)) {
            $successMessage .= count($selectedRecords) . ' R&M Record(s) and ';
        }
        $successMessage .= 'Technical Notes updated successfully';
        
        return response()->json([
            'success' => true,
            'message' => $successMessage
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

        if ($usedInOtherWorkorders->count() > 0) {
            $workorderNumbers = $usedInOtherWorkorders->pluck('number')->implode(', ');
            $errorMessage = "Cannot delete R&M Record. It is used in the following work orders: " . $workorderNumbers;
            
            if ($workorder_id) {
                // Проверяем, есть ли уже сохраненные данные в workorder
                $workorder = Workorder::find($workorder_id);
                if ($workorder && $workorder->rm_report) {
                    return redirect()->route('rm_reports.edit', $workorder_id)
                        ->with('error', $errorMessage);
                } else {
                    return redirect()->route('rm_reports.create', $workorder_id)
                        ->with('error', $errorMessage);
                }
            } else {
                return redirect()->back()->with('error', $errorMessage);
            }
        }

        $rmReport->delete();

        if ($workorder_id) {
            // Проверяем, есть ли уже сохраненные данные в workorder
            $workorder = Workorder::find($workorder_id);
            if ($workorder && $workorder->rm_report) {
                // Если есть данные, возвращаемся на страницу редактирования
                return redirect()->route('rm_reports.edit', $workorder_id)
                    ->with('success', 'R&M Record deleted successfully');
            } else {
                // Если данных нет, возвращаемся на страницу создания
                return redirect()->route('rm_reports.create', $workorder_id)
                    ->with('success', 'R&M Record deleted successfully');
            }
        } else {
            return redirect()->back()
                ->with('success', 'R&M Record deleted successfully');
        }
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

                    if ($usedInOtherWorkorders->count() > 0) {
                        $workorderNumbers = $usedInOtherWorkorders->pluck('number')->implode(', ');
                        $recordsInUse[] = "Record ID {$rmReport->id} ({$rmReport->part_description}) - used in: " . $workorderNumbers;
                    } else {
                        $recordsToDelete[] = $recordId;
                    }
                }
            }
            
            // Удаляем только те records, которые не используются
            if (!empty($recordsToDelete)) {
                RmReport::whereIn('id', $recordsToDelete)->delete();
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
    public function saveToWorkorder(Request $request)
    {
        $selectedRecords = json_decode($request->selected_records, true);
        $workorder_id = $request->workorder_id;
        
        // Собираем технические заметки
        $technicalNotes = [];
        for ($i = 1; $i <= 7; $i++) {
            $noteKey = 'note' . $i;
            $technicalNotes[$noteKey] = $request->input($noteKey, '');
        }
        
        $dataToSave = [];
        
        // Добавляем R&M записи, если они выбраны
        if (!empty($selectedRecords)) {
            // Проверяем, является ли $selectedRecords массивом объектов или простым массивом ID
            if (is_array($selectedRecords) && isset($selectedRecords[0]) && is_array($selectedRecords[0])) {
                // Если это массив объектов, извлекаем только ID
                $recordIds = collect($selectedRecords)->pluck('id')->toArray();
            } else {
                // Если это простой массив ID
                $recordIds = $selectedRecords;
            }
            
            // Получаем выбранные записи R&M
            $rmRecords = RmReport::whereIn('id', $recordIds)->get();
            
            // Преобразуем в массив для JSON
            $rmData = $rmRecords->map(function($record) {
                return [
                    'id' => $record->id,
                    'created_at' => $record->created_at->toISOString()
                ];
            })->toArray();
            
            $dataToSave['rm_records'] = $rmData;
        }
        
        // Добавляем технические заметки
        $dataToSave['technical_notes'] = $technicalNotes;
        
        // Сохраняем в поле rm_report таблицы workorders
        $workorder = Workorder::findOrFail($workorder_id);
        $workorder->update([
            'rm_report' => json_encode($dataToSave)
        ]);
        
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
    public function updateRecord(Request $request, $id)
    {
        $validated = $request->validate([
            'part_description' => 'required|string|max:255',
            'mod_repair' => 'required|in:Mod,Repair,SB',
            'mod_repair_description' => 'required|string|max:255',
            'ident_method' => 'nullable|string|max:255',
            'workorder_id' => 'required|exists:workorders,id',
        ]);

        $rmReport = RmReport::findOrFail($id);
        
        // Обновляем запись
        $rmReport->update([
            'part_description' => $validated['part_description'],
            'mod_repair' => $validated['mod_repair'],
            'description' => $validated['mod_repair_description'], // Поле в БД называется description
            'ident_method' => $validated['ident_method'],
        ]);

        // Проверяем, есть ли уже сохраненные данные в workorder
        $workorder = Workorder::findOrFail($validated['workorder_id']);
        if ($workorder->rm_report) {
            // Если есть данные, возвращаемся на страницу редактирования
            return redirect()->route('rm_reports.edit', $validated['workorder_id'])
                ->with('success', 'R&M Record updated successfully');
        } else {
            // Если данных нет, возвращаемся на страницу создания
            return redirect()->route('rm_reports.create', $validated['workorder_id'])
                ->with('success', 'R&M Record updated successfully');
        }
    }
}
