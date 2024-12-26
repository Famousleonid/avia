<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralTask;
use Illuminate\Http\Request;

class GeneralTaskController extends Controller
{
    public function index()
    {
        $general_tasks = GeneralTask::all();

        return view('admin.general_tasks.index',compact('general_tasks'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:250'
        ]);

        GeneralTask::create($validated);

        return redirect()->route('admin.general-tasks.index')->with('success', 'General task created successfully.');
    }


    public function update(Request $request, GeneralTask $generalTask)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:250'
        ]);

        $generalTask->update($validated);

        return redirect()->route('admin.general-tasks.index')->with('success', 'General task updated successfully.');
    }

    public function destroy(GeneralTask $generalTask)
    {
        $generalTask->delete();

        return redirect()->route('admin.general-tasks.index')->with('success', 'General task deleted successfully.');
    }
}
