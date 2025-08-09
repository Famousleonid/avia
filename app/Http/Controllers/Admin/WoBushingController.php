<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WoBushing;
use App\Models\Workorder;
use App\Models\Component;
use App\Models\Process;
use App\Models\ProcessName;
use Illuminate\Http\Request;

class WoBushingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;
        
        // Get all bushings (components where is_bush = 1) for this manual, grouped by bush_ipl_num
        $bushingsQuery = Component::where('manual_id', $manual_id)
            ->where('is_bush', 1)
            ->orderBy('bush_ipl_num', 'asc')
            ->orderBy('ipl_num', 'asc')
            ->get();
            
        // Group bushings by bush_ipl_num
        $bushings = $bushingsQuery->groupBy('bush_ipl_num');
        
        // Get processes for each process type for this manual
        $machiningProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Machining');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
            
        // NDT processes - показываем process_name (NDT-1, NDT-4 и т.д.)
        $ndtProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'LIKE', 'NDT%');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
            
        $passivationProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Passivation');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
            
        // CAD processes - все процессы для 'Cad plate'
        $cadProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Cad plate');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
            
        // Xylan processes - все процессы для 'Xylan coating'
        $xylanProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Xylan coating');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
        
        return view('admin.wo_bushings.create', compact(
            'current_wo',
            'bushings', 
            'machiningProcesses',
            'ndtProcesses',
            'passivationProcesses',
            'cadProcesses',
            'xylanProcesses'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'workorder_id' => 'required|exists:workorders,id',
            'group_bushings' => 'array',
        ]);

        $workorderId = $request->workorder_id;
        $groupBushingsData = $request->group_bushings ?? [];

        // Check if WoBushing already exists for this workorder
        $existingWoBushing = WoBushing::where('workorder_id', $workorderId)->first();
        
        if ($existingWoBushing) {
            return redirect()->route('wo_bushings.show', $workorderId)
                ->with('warning', 'Bushings data already exists for this Work Order. Please use Edit to modify.');
        }

        // Process group data and convert to individual component records
        $bushDataArray = [];
        foreach ($groupBushingsData as $groupKey => $groupData) {
            if (isset($groupData['components']) && is_array($groupData['components'])) {
                foreach ($groupData['components'] as $componentId) {
                    $bushDataArray[] = [
                        'bushing' => (int)$componentId,
                        'qty' => (int)($groupData['qty'] ?? 1),
                        'processes' => [
                            'machining' => $groupData['machining'] ? (int)$groupData['machining'] : null,
                            'ndt' => $groupData['ndt'] ? (int)$groupData['ndt'] : null,
                            'passivation' => $groupData['passivation'] ? (int)$groupData['passivation'] : null,
                            'cad' => $groupData['cad'] ? (int)$groupData['cad'] : null,
                            'xylan' => $groupData['xylan'] ? (int)$groupData['xylan'] : null,
                        ]
                    ];
                }
            }
        }

        if (empty($bushDataArray)) {
            return redirect()->back()
                ->with('error', 'Please select at least one component before submitting.')
                ->withInput();
        }

        WoBushing::create([
            'workorder_id' => $workorderId,
            'bush_data' => $bushDataArray,
        ]);

        return redirect()->route('wo_bushings.show', $workorderId)
            ->with('success', 'Bushings data created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;
        
        // Get all bushings (components where is_bush = 1) for this manual, grouped by bush_ipl_num
        $bushingsQuery = Component::where('manual_id', $manual_id)
            ->where('is_bush', 1)
            ->orderBy('bush_ipl_num', 'asc')
            ->orderBy('ipl_num', 'asc')
            ->get();
            
        // Group bushings by bush_ipl_num
        $bushings = $bushingsQuery->groupBy('bush_ipl_num');
        
        // Get processes for each process type for this manual
        $machiningProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Machining');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
            
        // NDT processes - показываем process_name (NDT-1, NDT-4 и т.д.)
        $ndtProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'LIKE', 'NDT%');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
            
        $passivationProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Passivation');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
            
        // CAD processes - все процессы для 'Cad plate'
        $cadProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Cad plate');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
            
        // Xylan processes - все процессы для 'Xylan coating'
        $xylanProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Xylan coating');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
        
        // Get existing WoBushing data if available
        $woBushing = WoBushing::where('workorder_id', $current_wo->id)->first();
        $bushData = [];
        if ($woBushing && $woBushing->bush_data) {
            $bushData = is_array($woBushing->bush_data) 
                ? $woBushing->bush_data 
                : json_decode($woBushing->bush_data, true);
        }
        
        return view('admin.wo_bushings.show', compact(
            'current_wo',
            'bushings', 
            'machiningProcesses',
            'ndtProcesses',
            'passivationProcesses',
            'cadProcesses',
            'xylanProcesses',
            'woBushing',
            'bushData'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $woBushing = WoBushing::findOrFail($id);
        $current_wo = $woBushing->workorder;
        $manual_id = $current_wo->unit->manual_id;
        
        // Get all bushings (components where is_bush = 1) for this manual, grouped by bush_ipl_num
        $bushingsQuery = Component::where('manual_id', $manual_id)
            ->where('is_bush', 1)
            ->orderBy('bush_ipl_num', 'asc')
            ->orderBy('ipl_num', 'asc')
            ->get();
            
        // Group bushings by bush_ipl_num
        $bushings = $bushingsQuery->groupBy('bush_ipl_num');
        
        // Get processes for each process type for this manual
        $machiningProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Machining');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
            
        // NDT processes - показываем process_name (NDT-1, NDT-4 и т.д.)
        $ndtProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'LIKE', 'NDT%');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
            
        $passivationProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Passivation');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
            
        // CAD processes - все процессы для 'Cad plate'
        $cadProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Cad plate');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
            
        // Xylan processes - все процессы для 'Xylan coating'
        $xylanProcesses = Process::whereHas('process_name', function($query) {
                $query->where('name', 'Xylan coating');
            })
            ->whereHas('manuals', function($query) use ($manual_id) {
                $query->where('manual_id', $manual_id);
            })
            ->with('process_name')
            ->get();
        
        // Get existing bush data
        $bushData = is_array($woBushing->bush_data) 
            ? $woBushing->bush_data 
            : json_decode($woBushing->bush_data, true);
        
        return view('admin.wo_bushings.edit', compact(
            'current_wo',
            'woBushing',
            'bushings', 
            'machiningProcesses',
            'ndtProcesses',
            'passivationProcesses',
            'cadProcesses',
            'xylanProcesses',
            'bushData'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'group_bushings' => 'array',
        ]);

        $woBushing = WoBushing::findOrFail($id);
        $groupBushingsData = $request->group_bushings ?? [];

        if (empty($groupBushingsData)) {
            return redirect()->back()
                ->with('error', 'Please select at least one group before submitting.')
                ->withInput();
        }

        // Process group data and convert to individual component records
        $bushDataArray = [];
        foreach ($groupBushingsData as $groupKey => $groupData) {
            if (isset($groupData['components']) && is_array($groupData['components'])) {
                foreach ($groupData['components'] as $componentId) {
                    $bushDataArray[] = [
                        'bushing' => (int)$componentId,
                        'qty' => (int)($groupData['qty'] ?? 1),
                        'processes' => [
                            'machining' => $groupData['machining'] ? (int)$groupData['machining'] : null,
                            'ndt' => $groupData['ndt'] ? (int)$groupData['ndt'] : null,
                            'passivation' => $groupData['passivation'] ? (int)$groupData['passivation'] : null,
                            'cad' => $groupData['cad'] ? (int)$groupData['cad'] : null,
                            'xylan' => $groupData['xylan'] ? (int)$groupData['xylan'] : null,
                        ]
                    ];
                }
            }
        }

        if (empty($bushDataArray)) {
            return redirect()->back()
                ->with('error', 'Please select at least one component in the selected groups.')
                ->withInput();
        }

        // Update record with processed component data
        $woBushing->update([
            'bush_data' => $bushDataArray
        ]);

        return redirect()->route('wo_bushings.show', $woBushing->workorder_id)
            ->with('success', 'Bushings data updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
