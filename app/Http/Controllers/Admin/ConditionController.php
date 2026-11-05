<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return DB::transaction(function () use ($validated, $id) {
            $condition = Condition::active()->lockForUpdate()->findOrFail($id);
            $oldName = $condition->name;
            $oldUnit = $condition->unit;

            // Проверяем, что это не системное условие, которое нельзя редактировать
            if ($condition->name === Condition::NAME_PARTS_MISSING) {
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
            if ($condition->isDirty(['name', 'unit'])) {
                $replacement = new Condition(['name' => $condition->name, 'unit' => $condition->unit]);
                $replacement->replaces_id = $condition->id;
                $replacement->save();
                // Never rewrite the wording attached to existing inspections or legacy TDRs.
                $condition->name = $oldName;
                $condition->unit = $oldUnit;
                $condition->archived_at = now();
                $condition->save();
            }

            return response()->json([
                'success' => true,
                'message' => __('Condition updated. Existing inspections keep their original version.'),
                'condition' => $replacement ?? $condition,
            ]);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $condition = Condition::active()->lockForUpdate()->findOrFail($id);

            // Проверяем, что это не системное условие, которое нельзя удалять
            if ($condition->name === Condition::NAME_PARTS_MISSING) {
                return response()->json([
                    'success' => false,
                    'message' => __('This condition cannot be deleted.')
                ], 403);
            }

            $condition->archived_at = now();
            $condition->save();

            $workorderId = $request->query('workorder_id') ?? $request->input('workorder_id');
            $response = [
                'success' => true,
                'message' => __('Condition archived. Existing inspections are unchanged.')
            ];
            if ($workorderId) {
                $response['redirect'] = route('tdrs.show', ['id' => $workorderId]);
            }

            return response()->json($response);
        });
    }
}
