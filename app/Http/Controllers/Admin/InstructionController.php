<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instruction;
use Illuminate\Http\Request;

class InstructionController extends Controller
{

    public function index()
    {
        $instructions = Instruction::all();

        return View('index', compact('instructions'));
    }


    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        //
    }


    public function show($id)
    {
        //
    }


    public function edit($id)
    {
        //
    }


    public function update(Request $request, $id)
    {
        //
    }


    public function destroy($id)
    {
        $answer = Instruction::destroy($id);

        return redirect()->route('component.index')->with('success', 'Component deleted successful.');
    }
}
