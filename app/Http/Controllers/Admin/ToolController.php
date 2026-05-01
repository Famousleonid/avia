<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workorder;
use App\Models\WorkorderTool;
use App\Tools\WorkorderTools\WorkorderToolRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToolController extends Controller
{
    public function __construct(private WorkorderToolRegistry $registry)
    {
    }

    public function index(Request $request): View
    {
        $tools = $this->registry->all();
        $activeTool = (string) $request->get('tool', $tools[0]['key'] ?? '');
        $workorderId = $request->integer('workorder_id') ?: null;
        $prefillWorkorder = trim((string) $request->get('workorder', ''));
        $prefillUserName = trim((string) $request->get('user', Auth::user()?->name ?? ''));
        $currentManualNumber = null;

        if ($workorderId) {
            $workorder = Workorder::query()
                ->with('unit.manual:id,number')
                ->find($workorderId);

            $currentManualNumber = $workorder?->unit?->manual?->number;

            if ($currentManualNumber) {
                $tools = $this->registry->forManualNumber($currentManualNumber);
            }
        }

        if (empty($tools)) {
            $tools = [];
        } else {
            $selectedTool = collect($tools)->firstWhere('key', $activeTool);
            $activeTool = $selectedTool['key'] ?? $tools[0]['key'];
        }

        $savedByTool = [];
        if ($workorderId && !empty($tools)) {
            $savedByTool = WorkorderTool::query()
                ->where('workorder_id', $workorderId)
                ->whereIn('tool_key', collect($tools)->pluck('key')->all())
                ->get()
                ->keyBy('tool_key')
                ->map(fn (WorkorderTool $entry) => $entry->input_values ?? [])
                ->all();
        }

        $tools = array_map(function (array $tool) use ($savedByTool) {
            $savedValues = $savedByTool[$tool['key']] ?? [];

            $tool['inputs'] = array_map(function (array $input) use ($savedValues) {
                if (array_key_exists($input['key'], $savedValues) && $savedValues[$input['key']] !== null) {
                    $input['default'] = (string) $savedValues[$input['key']];
                }

                return $input;
            }, $tool['inputs']);

            return $tool;
        }, $tools);

        return view('admin.tools.index', [
            'tools' => $tools,
            'activeTool' => $activeTool,
            'workorderId' => $workorderId,
            'currentManualNumber' => $currentManualNumber,
            'prefillWorkorder' => $prefillWorkorder,
            'prefillUserName' => $prefillUserName,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workorder_id' => ['required', 'integer', 'exists:workorders,id'],
            'tool_key' => ['required', 'string'],
            'input_values' => ['required', 'array'],
        ]);

        $tool = $this->registry->find($validated['tool_key']);
        abort_if(!$tool, 422, 'Unknown tool key.');

        $allowedKeys = collect($tool['inputs'])->pluck('key')->all();
        $inputValues = [];

        foreach ($allowedKeys as $key) {
            $rawValue = $validated['input_values'][$key] ?? null;
            $inputValues[$key] = is_numeric($rawValue) ? (float) $rawValue : 0.0;
        }

        $entry = WorkorderTool::firstOrNew([
            'workorder_id' => (int) $validated['workorder_id'],
            'tool_key' => $validated['tool_key'],
        ]);

        if (!$entry->exists) {
            $entry->created_by = Auth::id();
        }

        $entry->input_values = $inputValues;
        $entry->updated_by = Auth::id();
        $entry->save();

        return response()->json([
            'saved' => true,
        ]);
    }
}
