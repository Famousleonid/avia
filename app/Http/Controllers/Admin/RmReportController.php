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
            'mod_repair' => 'required|string|max:255',
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

        return redirect()->route('rm_reports.create', $validated['workorder_id'])
            ->with('success', 'R&M Record created successfully');
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
    return view('admin.rm_reports.rmRecordForm', compact('current_wo'));

}
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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

        $rmReport->delete();

        if ($workorder_id) {
            return redirect()->route('rm_reports.create', $workorder_id)
                ->with('success', 'R&M Record deleted successfully');
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
            RmReport::whereIn('id', $selectedRecords)->delete();
        }
        
        return redirect()->route('rm_reports.create', $workorder_id)
            ->with('success', count($selectedRecords) . ' R&M Record(s) deleted successfully');
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
            // Получаем выбранные записи R&M
            $rmRecords = RmReport::whereIn('id', $selectedRecords)->get();
            
            // Преобразуем в массив для JSON
            $rmData = $rmRecords->map(function($record) {
                return [
                    'id' => $record->id,
                    'part_description' => $record->part_description,
                    'mod_repair' => $record->mod_repair,
                    'description' => $record->description,
                    'ident_method' => $record->ident_method,
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
        
        return redirect()->route('rm_reports.create', $workorder_id)
            ->with('success', $successMessage);
    }
}
