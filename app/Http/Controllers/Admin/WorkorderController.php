<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Instruction;
use App\Models\Manual;
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

    public function __construct()
    {
        $this->middleware('can:workorders.viewAny')->only('index');
        $this->middleware('can:workorders.view')->only('show');
        $this->middleware('can:workorders.create')->only(['create', 'store']);
        $this->middleware('can:workorders.update')->only(['edit', 'update']);
        $this->middleware('can:workorders.delete')->only('destroy');
        $this->middleware('can:workorders.approve')->only('approve');
    }


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
            $props      = $log->properties ?? [];
            $attributes = $props['attributes'] ?? [];
            $old        = $props['old'] ?? [];

            $changes = [];

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

        $current = Workorder::find($id);

        if ($current->approve_at == NULL) {
            $current->approve_at = 1;
            $current->approve_at = now();
            $current->approve_name = auth()->user()->name;
            $current->save();
        } else {
            $current->approve_at = 0;
            $current->approve_at = NULL;
            $current->approve_name = NULL;
            $current->save();
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
            $collections = ['photos', 'damages', 'logs'];
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
            $groups = ['photos', 'damages', 'logs'];

          //  Log::channel('avia')->info("ZIP download started for workorder ID: $id");

            return new StreamedResponse(function () use ($workorder, $groups, $id) {
                $options = new ArchiveOptions();
                $options->setSendHttpHeaders(true);

                $zip = new ZipStream(null, $options);

                foreach ($groups as $group) {
                    foreach ($workorder->getMedia($group) as $media) {
                        $filePath = $media->getPath();

                        if (!file_exists($filePath)) {
                            Log::channel('avia')->error("File not found: $filePath");
                            continue;
                        }

                        $filename = Str::slug(pathinfo($media->file_name, PATHINFO_FILENAME)) . '.' .
                            pathinfo($media->file_name, PATHINFO_EXTENSION);

                        $relativePath = "$group/$filename";

                        $zip->addFileFromPath($relativePath, $filePath);
                        Log::channel('avia')->info("Added to zip: $relativePath");
                    }
                }

                $zip->finish();
                Log::channel('avia')->info("ZIP stream finished for workorder ID: $id");

            }, 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="workorder_' . $id . '_images.zip"',
            ]);
        } catch (\Throwable $e) {
            Log::channel('avia')->error("ZIP creation failed: " . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Получить список всех PDF файлов для workorder
     */
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
