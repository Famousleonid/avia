<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Instruction;
use App\Models\Manual;
use App\Models\Tdr;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkorderController extends Controller
{

    public function index()
    {
        $workorders = Workorder::all();
        $manuals = Manual::all();
        $units =Unit::with('manuals')->get();
        $tdrs=Tdr::all();

        return view('admin.workorders.index', compact('workorders','tdrs','units','manuals'));
    }


    public function create()
    {
        $customers = Customer::all();
        $units = Unit::with('manuals')->get();
        $instructions = Instruction::all();
        $manuals = Manual::all();
        $users = User::all();
        $currentUser = Auth::user();

        return view('admin.workorders.create', compact( 'customers', 'units', 'instructions','users','currentUser','manuals'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'number' => 'required ',
            'unit_id' => 'required',
            'customer_id' => 'required',
            'instruction_id' => 'required',
        ]);


        $number = Workorder::where('number', $request['number'])->first();
        if ($number) {
            return redirect()
                ->route('admin.workorders.create')
                ->with('error', 'Workorder number is already exists.');
        }



        Workorder::create($request->all());

        return redirect()->route('admin.workorders.index')->with('success', 'Workorder added');
    }

    public function destroy($id)
    {

        Workorder::destroy($id);

        return redirect()->route('admin-workorders.index')->with('success', 'Workorder deleted');
    }

    public function edit($id)
    {

        $current_wo = Workorder::find($id);
        $customers = Customer::all();
        $units = Unit::all();
        $instructions = Instruction::all();
        $user = Auth::user();

        return view('admin.workorders.edit', compact('user', 'customers', 'units', 'instructions', 'current_wo'));

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
            \Log::error('Update Inspect Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'unit_id' => 'required',
            'customer_id' => 'required',
            'instruction_id' => 'required',
        ]);

        $wo = Workorder::find($id);

        $wo->update(
            [
                'unit_id' => $request->unit_id,
                'customer_id' => $request->customer_id,
                'instruction_id' => $request->instruction_id,
                'serial_number' => $request->serial_number,
                'manual' => $request->manual,
                'description' => $request->description,
                'notes' => $request->notes
            ]);


        return redirect()->route('admin-workorders.index')->with('success', 'Workorder was edited successfully');
    }

}
