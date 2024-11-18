<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Instruction;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkorderController extends Controller
{
    public function create()
    {
        $customers = Customer::orderBy('name', 'asc')->get();
        $units = Unit::all();
        $instructions = Instruction::all();
        $current_user = Auth::user();
        $users = User::all();

        return view('workorder.create', compact('current_user', 'customers', 'units', 'instructions', 'users'));
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
                ->route('workorder.create')
                ->with('error', 'Workorder number is already exists.');
        }

        Workorder::create($request->all());

        return redirect()->route('cabinet.index')->with('success', 'Workorder added');
    }

    public function destroy($id)
    {

        Workorder::destroy($id);

        return redirect()->route('cabinet.workorders')->with('success', 'Workorder deleted');
    }

    public function edit($id)
    {

        $current_wo = Workorder::find($id);
        $customers = Customer::all();
        $units = Unit::all();
        $instructions = Instruction::all();
        // $current_user = Auth::user();
        $users = User::all();

        return view('workorder.edit', compact('customers', 'units', 'instructions', 'current_wo', 'users'));

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
                'user_id' => $request->user_id,
                'customer_id' => $request->customer_id,
                'instruction_id' => $request->instruction_id,
                'serial_number' => $request->serial_number,
                'manual' => $request->manual,
                'description' => $request->description,
                'notes' => $request->notes,
                'place' => $request->place,
                'created_at' => $request->created_at,
            ]);

        return redirect()->route('cabinet.index')->with('success', 'Workorder was edited successfully');
    }

}
