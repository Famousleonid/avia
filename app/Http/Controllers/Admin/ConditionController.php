<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use Illuminate\Http\Request;

class ConditionController extends Controller
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'unit' => 'required|boolean',
        ]);

        // Явное преобразование в integer (1 или 0)
        $validated['unit'] = $validated['unit'] ? 1 : 0;
        
        // Если name пустое, автоматически создаем имя "note 1", "note 2" и т.д.
        if (empty($validated['name'])) {
            // Находим последний номер note для unit conditions
            $lastNote = Condition::where('unit', 1)
                ->where('name', 'like', 'note %')
                ->orderByRaw('CAST(SUBSTRING(name, 6) AS UNSIGNED) DESC')
                ->first();
            
            if ($lastNote) {
                // Извлекаем номер из последнего note
                preg_match('/note\s+(\d+)/i', $lastNote->name, $matches);
                $nextNumber = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
            } else {
                $nextNumber = 1;
            }
            
            $validated['name'] = 'note ' . $nextNumber;
        }

        Condition::create($validated);

        // Если это AJAX запрос, возвращаем JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Condition added successfully.')
            ]);
        }

        return redirect()->route('tdrs.inspection.unit', ['workorder_id' => $request->workorder_id])
            ->with('success', 'Condition added successfully');
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'unit' => 'nullable|boolean',
        ]);

        $condition = Condition::findOrFail($id);

        // Проверяем, что это не системное условие, которое нельзя редактировать
        if ($condition->name === 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST') {
            return response()->json([
                'success' => false,
                'message' => __('This condition cannot be edited.')
            ], 403);
        }

        // Если name пустое, автоматически создаем имя "note 1", "note 2" и т.д.
        if (empty($validated['name'])) {
            // Проверяем, было ли у condition имя в формате "note X"
            $wasNoteCondition = preg_match('/^note\s+\d+$/i', $condition->name);
            
            if ($wasNoteCondition) {
                // Если это был note condition, сохраняем старое имя
                // (пользователь мог случайно очистить поле)
                $condition->name = $condition->name;
            } else {
                // Если это был обычный condition, создаем новое имя "note X"
                $lastNote = Condition::where('unit', 1)
                    ->where('name', 'like', 'note %')
                    ->where('id', '!=', $id) // Исключаем текущий condition
                    ->orderByRaw('CAST(SUBSTRING(name, 6) AS UNSIGNED) DESC')
                    ->first();
                
                if ($lastNote) {
                    // Извлекаем номер из последнего note
                    preg_match('/note\s+(\d+)/i', $lastNote->name, $matches);
                    $nextNumber = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
                } else {
                    $nextNumber = 1;
                }
                
                $condition->name = 'note ' . $nextNumber;
            }
        } else {
            $condition->name = $validated['name'];
        }
        
        if (isset($validated['unit'])) {
            $condition->unit = $validated['unit'] ? 1 : 0;
        }
        $condition->save();

        return response()->json([
            'success' => true,
            'message' => __('Condition updated successfully.')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $condition = Condition::findOrFail($id);

        // Проверяем, что это не системное условие, которое нельзя удалять
        if ($condition->name === 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST') {
            return response()->json([
                'success' => false,
                'message' => __('This condition cannot be deleted.')
            ], 403);
        }

        // Проверяем, используется ли condition в TDR записях
        $tdrCount = \App\Models\Tdr::where('conditions_id', $id)->count();
        if ($tdrCount > 0) {
            return response()->json([
                'success' => false,
                'message' => __('Cannot delete condition. It is used in ') . $tdrCount . __(' TDR record(s).')
            ], 400);
        }

        $condition->delete();

        return response()->json([
            'success' => true,
            'message' => __('Condition deleted successfully.')
        ]);
    }
}
