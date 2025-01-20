<?php

namespace App\Http\Controllers\Cabinet;

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

class WorkorderController extends Controller
{

    public function index()
    {
        $workorders = Workorder::all();
        $manuals = Manual::all();
        $units = Unit::with('manuals')->get();

        return view('cabinet.workorders.index', compact('workorders', 'units', 'manuals'));
    }

    public function create()
    {
        $customers = Customer::all();
        $units = Unit::with('manuals')->get();
        $instructions = Instruction::all();
        $manuals = Manual::all();
        $users = User::all();
        $currentUser = Auth::user();

        return view('cabinet.workorders.create', compact('customers', 'units', 'instructions', 'users', 'currentUser', 'manuals'));
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
                ->route('admin.workorders.create')
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

        return redirect()->route('cabinet.workorders.index')->with('success', 'Workorder added');
    }

    public function destroy($id)
    {

        Workorder::destroy($id);

        return redirect()->route('cabinet.workorders.index')->with('success', 'Workorder deleted');
    }

    public function edit($id)
    {
        $current_wo = Workorder::find($id);
        $customers = Customer::all();
        $units = Unit::all();
        $instructions = Instruction::all();
        $manuals = Manual::all();
        $users = User::all();
        $open_at = Carbon::parse($current_wo->open_at)->format('Y-m-d');



        return view('cabinet.workorders.edit', compact('users', 'customers', 'units', 'instructions', 'current_wo', 'manuals','open_at'));

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

        return redirect()->route('cabinet.workorders.index')->with('success', 'Workorder was edited successfully');
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
}
