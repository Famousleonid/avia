<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workorder;
use App\Models\WorkorderTool;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToolController extends Controller
{
    private function tools(): array
    {
        return [
            [
                'key' => 'nlg-erj-170-sleeve-37',
                'label' => 'NLG ERJ-170 Sleeve 37',
                'manual_numbers' => ['32-21-01'],
                'image' => asset('img/tools/ERJ-170 bush 37.jpg'),
                'print_title' => 'Sleeve Print Sheet',
                'print_subtitle' => 'ERJ-170 / Tool 37',
                'inputs' => [
                    [
                        'key' => 'r_out',
                        'label' => 'R out',
                        'hint' => 'Outer radius from the reference drawing.',
                        'placeholder' => '',
                        'step' => '0.001',
                        'default' => '0.0',
                    ],
                    [
                        'key' => 'r_in',
                        'label' => 'R in',
                        'hint' => 'Inner radius from the reference drawing.',
                        'placeholder' => '',
                        'step' => '0.001',
                        'default' => '0.000',
                    ],
                    [
                        'key' => 'd',
                        'label' => 'D',
                        'hint' => 'Chord height from the drawing.',
                        'placeholder' => '',
                        'step' => '0.001',
                        'default' => '0.0',
                    ],
                ],
            ],
        ];
    }

    private function toolMap(): array
    {
        return collect($this->tools())->keyBy('key')->all();
    }

    public function index(Request $request): View
    {
        $tools = $this->tools();

        $activeTool = (string) $request->get('tool', $tools[0]['key']);
        $selectedTool = collect($tools)->firstWhere('key', $activeTool);
        $activeTool = $selectedTool['key'] ?? $tools[0]['key'];
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
                $tools = array_values(array_filter($tools, function (array $tool) use ($currentManualNumber) {
                    $manualNumbers = $tool['manual_numbers'] ?? [];

                    return in_array($currentManualNumber, $manualNumbers, true);
                }));
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
        $toolMap = $this->toolMap();

        $validated = $request->validate([
            'workorder_id' => ['required', 'integer', 'exists:workorders,id'],
            'tool_key' => ['required', 'string'],
            'input_values' => ['required', 'array'],
        ]);

        $tool = $toolMap[$validated['tool_key']] ?? null;
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
