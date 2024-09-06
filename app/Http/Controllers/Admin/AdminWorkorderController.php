<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Instruction;
use App\Models\Unit;
use App\Models\Workorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWorkorderController extends Controller
{

    public function index()
    {
        $workorders = Workorder::all();

        return view('admin.workorder.index', compact('workorders'));
    }


    public function create()
    {
        $customers = Customer::all();
        $units = Unit::all();
        $instructions = Instruction::all();
        $user = Auth::user();

        return view('admin.workorder.create', compact('user', 'customers', 'units', 'instructions'));
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

        return redirect()->route('admin-workorders.index')->with('success', 'Workorder added');
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

        return view('admin.workorder.edit', compact('user', 'customers', 'units', 'instructions', 'current_wo'));

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
