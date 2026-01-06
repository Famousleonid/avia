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
            'name' => 'required|string|max:255',
            'unit' => 'required|boolean',
        ]);

        // Явное преобразование в integer (1 или 0)
        $validated['unit'] = $validated['unit'] ? 1 : 0;

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
            'name' => 'required|string|max:255',
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

        $condition->name = $validated['name'];
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
