<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Instruction;
use App\Models\Main;
use App\Models\Manual;
use App\Models\Task;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ZipStream\ZipStream;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use Spatie\Activitylog\Models\Activity;

class WorkorderController extends Controller
{
    public function logs()
    {
        $activities = Activity::query()
            ->where('log_name', 'workorder')
            ->with(['causer', 'subject']) // subject = Workorder
            ->orderByDesc('created_at')
            ->paginate(50);

        // Мапы id → красивое имя
        $unitsMap        = Unit::pluck('part_number', 'id')->all();
        $customersMap    = Customer::pluck('name', 'id')->all();
        $instructionsMap = Instruction::pluck('name', 'id')->all();
        $usersMap        = User::pluck('name', 'id')->all();

        // Читабельные названия полей
        $fieldLabels = [
            'number'        => 'WO Number',
            'unit_id'       => 'Unit',
            'customer_id'   => 'Customer',
            'instruction_id'=> 'Instruction',
            'user_id'       => 'Technik',
            'approve_at'    => 'Approve date',
            'approve_name'  => 'Approved by',
            'description'   => 'Description',
        ];

        return view('admin.log.index', compact(
            'activities',
            'unitsMap',
            'customersMap',
            'instructionsMap',
            'usersMap',
            'fieldLabels'
        ));
    }

    public function logsForWorkorder(Workorder $workorder)
    {
        $activities = Activity::query()
            ->where('log_name', 'workorder')
            ->where('subject_type', Workorder::class)
            ->where('subject_id', $workorder->id)
            ->with(['causer'])
            ->orderByDesc('created_at')
            ->get();

        // Мапы для красивых имён
        $unitsMap        = Unit::pluck('part_number', 'id')->all();
        $customersMap    = Customer::pluck('name', 'id')->all();
        $instructionsMap = Instruction::pluck('name', 'id')->all();
        $usersMap        = User::pluck('name', 'id')->all();

        $fieldLabels = [
            'number'         => 'WO Number',
            'unit_id'        => 'Unit',
            'customer_id'    => 'Customer',
            'instruction_id' => 'Instruction',
            'user_id'        => 'Technik',
            'approve_at'     => 'Approve date',
            'approve_name'   => 'Approved by',
            'description'    => 'Description',
            'serial_number'  => 'Serial number',

            // кастомные поля для task deleted
            'task'           => 'Task',
            'assigned_user'  => 'Technik',
            'date_start'     => 'Start',
            'date_finish'    => 'Finish',
            'main_id'        => 'Main ID',
        ];

        $formatValue = function ($field, $value) use ($unitsMap, $customersMap, $instructionsMap, $usersMap) {
            if ($value === null) return null;

            return match ($field) {
                'unit_id'        => $unitsMap[$value]        ?? $value,
                'customer_id'    => $customersMap[$value]    ?? $value,
                'instruction_id' => $instructionsMap[$value] ?? $value,
                'user_id'        => $usersMap[$value]        ?? $value,
                default          => $value,
            };
        };

        $data = $activities->map(function (Activity $log) use ($fieldLabels, $formatValue) {

            // ✅ важно: properties приводим к массиву
            $props = $log->properties ? $log->properties->toArray() : [];

            // стандартная структура Spatie для updated
            $attributes = $props['attributes'] ?? [];
            $old        = $props['old'] ?? [];

            $changes = [];

            // 1) обычные изменения (update)
            foreach ($attributes as $field => $newValue) {
                $oldValue = $old[$field] ?? null;

                if ($oldValue === $newValue) continue;

                $changes[] = [
                    'field' => $field,
                    'label' => $fieldLabels[$field] ?? $field,
                    'old'   => $formatValue($field, $oldValue),
                    'new'   => $formatValue($field, $newValue),
                ];
            }

            // 2) кастомные логи (например "task deleted") — данные лежат НЕ в attributes/old
            if (empty($changes)) {

                // task deleted: props.task / props.dates / props.assigned_user / props.main_id
                if (!empty($props['task']) || !empty($props['dates']) || !empty($props['assigned_user'])) {

                    $taskText = trim(
                        ($props['task']['general'] ?? '') .
                        (($props['task']['general'] ?? null) ? ' → ' : '') .
                        ($props['task']['name'] ?? '')
                    );

                    if ($taskText !== '') {
                        $changes[] = [
                            'field' => 'task',
                            'label' => $fieldLabels['task'],
                            'old'   => null,
                            'new'   => $taskText,
                        ];
                    }

                    if (!empty($props['assigned_user'])) {
                        $changes[] = [
                            'field' => 'assigned_user',
                            'label' => $fieldLabels['assigned_user'],
                            'old'   => null,
                            'new'   => $props['assigned_user'],
                        ];
                    }

                    if (!empty($props['dates']['start'])) {
                        $changes[] = [
                            'field' => 'date_start',
                            'label' => $fieldLabels['date_start'],
                            'old'   => null,
                            'new'   => $props['dates']['start'],
                        ];
                    }

                    if (array_key_exists('finish', $props['dates'] ?? [])) {
                        $changes[] = [
                            'field' => 'date_finish',
                            'label' => $fieldLabels['date_finish'],
                            'old'   => null,
                            'new'   => $props['dates']['finish'] ?? null,
                        ];
                    }

                    if (!empty($props['main_id'])) {
                        $changes[] = [
                            'field' => 'main_id',
                            'label' => $fieldLabels['main_id'],
                            'old'   => null,
                            'new'   => $props['main_id'],
                        ];
                    }
                }
            }

            return [
                'id'          => $log->id,
                'created_at'  => optional($log->created_at)->format('d-M-y H:i'),
                'description' => $log->description,
                'event'       => $log->event,
                'log_name'    => $log->log_name,
                'causer_name' => optional($log->causer)->name,
                'changes'     => $changes,
            ];
        })->values();

        return response()->json($data);
    }

    public function index()
    {
        $workorders = Workorder::with(['main.task', 'unit.manuals', 'customer', 'instruction', 'user'])
            ->orderByDesc('number')
            ->get();

        foreach ($workorders as $wo) {
            dump($wo->number, $wo->isDone());
        }
        dd('END');


        $manuals = Manual::all();
        $units = Unit::with('manuals')->get();
        $customers = Customer::orderBy('name')->get();
        $users     = User::orderBy('name')->get();

        return view('admin.workorders.index', compact('workorders', 'units', 'manuals','customers','users'));
    }

    public function create()
    {
        $customers = Customer::all();
        $units = Unit::with('manuals')->get();
        $instructions = Instruction::all();
        $manuals = Manual::all();
        $users = User::all();
        $currentUser = Auth::user();

        return view('admin.workorders.create', compact('customers', 'units', 'instructions', 'users', 'currentUser', 'manuals'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'number' => 'required|unique:workorders,number',
            'unit_id' => 'required',
            'customer_id' => 'required',
            'instruction_id' => 'required',
        ]);

        $number = Workorder::where('number', $request['number'])->first();
        if ($number) {
            return redirect()
                ->route('workorders.create')
                ->with('error', 'Workorder number is already exists.');
        }

        $request->merge([
            'part_missing' => $request->has('part_missing') ? 1 : 0,
            'external_damage' => $request->has('external_damage') ? 1 : 0,
            'received_disassembly' => $request->has('received_disassembly') ? 1 : 0,
            'disassembly_upon_arrival' => $request->has('disassembly_upon_arrival') ? 1 : 0,
            'nameplate_missing' => $request->has('nameplate_missing') ? 1 : 0,
            'preliminary_test_false' => $request->has('preliminary_test_false') ? 1 : 0,
            'extra_parts' => $request->has('extra_parts') ? 1 : 0,
        ]);

        $workorder = Workorder::create($request->all());

        // Если description заполнено и unit->name пустое, обновляем unit->name
        if (!empty($request->description)) {
            $unit = Unit::find($workorder->unit_id);
            if ($unit && empty($unit->name)) {
                $unit->name = $request->description;
                $unit->save();
            }
        }

        return redirect()->route('workorders.index')->with('success', 'Workorder added');
    }

    public function destroy(Workorder $workorder)
    {

        $workorder->delete();

        return redirect()->route('workorders.index')->with('success', 'Workorder deleted');
    }

    public function edit(Workorder $workorder)
    {
        $current_wo = $workorder;
        $customers = Customer::all();
        $units = Unit::all();
        $instructions = Instruction::all();
        $manuals = Manual::all();
        $users = User::all();
        $open_at = Carbon::parse($current_wo->open_at)->format('Y-m-d');


        return view('admin.workorders.edit', compact('users', 'customers', 'units', 'instructions', 'current_wo', 'manuals', 'open_at'));

    }

    public function update(Request $request, Workorder $workorder)
    {
        $request->validate([
            'unit_id' => 'required',
            'customer_id' => 'required',
            'instruction_id' => 'required',
        ]);

        $request->merge([
            'part_missing' => $request->has('part_missing') ? 1 : 0,
            'external_damage' => $request->has('external_damage') ? 1 : 0,
            'received_disassembly' => $request->has('received_disassembly') ? 1 : 0,
            'disassembly_upon_arrival' => $request->has('disassembly_upon_arrival') ? 1 : 0,
            'nameplate_missing' => $request->has('nameplate_missing') ? 1 : 0,
            'preliminary_test_false' => $request->has('preliminary_test_false') ? 1 : 0,
            'extra_parts' => $request->has('extra_parts') ? 1 : 0,
        ]);

        $workorder->update($request->all());

        // Если description заполнено и unit->name пустое, обновляем unit->name
        if (!empty($request->description)) {
            // Используем unit_id из запроса, так как он мог измениться
            $unitId = $request->unit_id ?? $workorder->unit_id;
            $unit = Unit::find($unitId);
            if ($unit && empty($unit->name)) {
                $unit->name = $request->description;
                $unit->save();
            }
        }

        return redirect()->route('workorders.index')->with('success', 'Workorder was edited successfully');
    }

    public function approve($id)
    {

        $current = Workorder::findOrFail($id);
        $user    = Auth::user();

        $waitingTask = Task::where('name', 'Waiting approve')->first();
        if (!$waitingTask) {
            return redirect()->back();
        }

        $waitingTaskId = $waitingTask->id;
        $generalTaskId = $waitingTask->general_task_id;

        $mainQuery = Main::where('workorder_id', $current->id)
            ->where('task_id', $waitingTaskId);

        if (is_null($current->approve_at)) {

            $current->approve_at = now();
            $current->approve_name = $user->name;
            $current->save();

            $main = $mainQuery->first();

            if (!$main) {
                $main = new Main();
                $main->workorder_id = $current->id;
                $main->task_id = $waitingTaskId;
                $main->general_task_id = $generalTaskId; // ← ключевая строка
            }

            $main->user_id = $user->id;
            $main->date_finish = $current->approve_at;
            $main->save();

        } else {

            $current->approve_at = null;
            $current->approve_name = null;
            $current->save();

            if ($main = $mainQuery->first()) {
                $main->date_finish = null;
                $main->user_id = null;
                $main->save();
            }
        }

        return redirect()->back();

    }

    public function updateInspect(Request $request, $id)
    {

        try {
            $workOrder = WorkOrder::findOrFail($id);

            $workOrder->part_missing = $request->has('part_missing');
            $workOrder->external_damage = $request->has('external_damage');
            $workOrder->received_disassembly = $request->has('received_disassembly');
            $workOrder->disassembly_upon_arrival = $request->has('disassembly_upon_arrival');
            $workOrder->nameplate_missing = $request->has('nameplate_missing');
            $workOrder->preliminary_test_false = $request->has('preliminary_test_false');
            $workOrder->extra_parts = $request->has('extra_parts');

            $workOrder->save();

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function photos($id)
    {
        try {
            $workorder = Workorder::findOrFail($id);
            $collections = ['photos', 'damages', 'logs', 'final'];
            $result = [];

            foreach ($collections as $col) {
                $result[$col] = $workorder->getMedia($col)->map(function ($media) use ($workorder, $col) {
                    return [
                        'id'    => $media->id,
                        'thumb' => route('image.show.thumb', [
                            'mediaId'   => $media->id,
                            'modelId'   => $workorder->id,
                            'mediaName' => $col,
                        ]),
                        'big'   => route('image.show.big', [
                            'mediaId'   => $media->id,
                            'modelId'   => $workorder->id,
                            'mediaName' => $col,
                        ]),
                    ];
                })->values()->toArray();
            }

            return response()->json($result);

        } catch (\Throwable $e) {
//            Log::channel('avia')->error("Photo load failed [workorder $id]: {$e->getMessage()}");
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function downloadAllGrouped($id)
    {
        try {
            $workorder = Workorder::findOrFail($id);
            $groups = ['photos', 'damages', 'logs','final'];

          //  Log::channel('avia')->info("ZIP download started for workorder ID: $id");

            return new StreamedResponse(function () use ($workorder, $groups, $id) {
                $options = new ArchiveOptions();
                $options->setSendHttpHeaders(true);

                $zip = new ZipStream(null, $options);

                foreach ($groups as $group) {
                    foreach ($workorder->getMedia($group) as $media) {
                        $filePath = $media->getPath();

                        if (!file_exists($filePath)) {
                           // Log::channel('avia')->error("File not found: $filePath");
                            continue;
                        }

                        $filename = Str::slug(pathinfo($media->file_name, PATHINFO_FILENAME)) . '.' .
                            pathinfo($media->file_name, PATHINFO_EXTENSION);

                        $relativePath = "$group/$filename";

                        $zip->addFileFromPath($relativePath, $filePath);
                      //  Log::channel('avia')->info("Added to zip: $relativePath");
                    }
                }

                $zip->finish();
              //  Log::channel('avia')->info("ZIP stream finished for workorder ID: $id");

            }, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="workorder_' . $id . '_images.zip"',
            ]);
        } catch (\Throwable $e) {
          //  Log::channel('avia')->error("ZIP creation failed: " . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function pdfs($id)
    {
        try {
            $workorder = Workorder::findOrFail($id);
            $pdfs = $workorder->getMedia('pdfs')->map(function ($media) use ($workorder) {
                $documentName = $media->getCustomProperty('document_name') ?: ($media->name ?? null);

                return [
                    'id' => $media->id,
                    'name' => $documentName ?: $media->file_name,
                    'file_name' => $media->file_name,
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                    'created_at' => $media->created_at->format('Y-m-d H:i:s'),
                    'url' => route('workorders.pdf.show', [
                        'workorderId' => $workorder->id,
                        'mediaId' => $media->id,
                    ]),
                    'download_url' => route('workorders.pdf.download', [
                        'workorderId' => $workorder->id,
                        'mediaId' => $media->id,
                    ]),
                ];
            })->values()->toArray();

            return response()->json([
                'success' => true,
                'pdfs' => $pdfs,
                'count' => count($pdfs),
            ]);
        } catch (\Throwable $e) {
            Log::error("PDF list failed for workorder $id: {$e->getMessage()}");
            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
