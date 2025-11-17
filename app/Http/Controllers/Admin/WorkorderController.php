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

class WorkorderController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Workorder::class, 'workorder');
    }


    public function index()
    {
        $workorders = Workorder::with(['main.task', 'unit.manuals', 'customer', 'instruction', 'user'])
            ->orderByDesc('number')
            ->get();

        $manuals = Manual::all();
        $units = Unit::with('manuals')->get();

        return view('admin.workorders.index', compact('workorders', 'units', 'manuals'));
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

        Workorder::create($request->all());

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

    public function update(Request $request, $id)
    {
        $request->validate([
            'unit_id' => 'required',
            'customer_id' => 'required',
            'instruction_id' => 'required',
        ]);

        $wo = Workorder::find($id);

        $request->merge([
            'part_missing' => $request->has('part_missing') ? 1 : 0,
            'external_damage' => $request->has('external_damage') ? 1 : 0,
            'received_disassembly' => $request->has('received_disassembly') ? 1 : 0,
            'disassembly_upon_arrival' => $request->has('disassembly_upon_arrival') ? 1 : 0,
            'nameplate_missing' => $request->has('nameplate_missing') ? 1 : 0,
            'preliminary_test_false' => $request->has('preliminary_test_false') ? 1 : 0,
            'extra_parts' => $request->has('extra_parts') ? 1 : 0,
        ]);

        $wo->update($request->all());

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
            Log::channel('avia')->error("Photo load failed [workorder $id]: {$e->getMessage()}");
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function downloadAllGrouped($id)
    {
        try {
            $workorder = Workorder::findOrFail($id);
            $groups = ['photos', 'damages', 'logs'];

            Log::channel('avia')->info("ZIP download started for workorder ID: $id");

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
}
