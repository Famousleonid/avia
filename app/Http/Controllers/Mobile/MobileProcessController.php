<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Workorder;
use Illuminate\Http\Request;

class MobileProcessController extends Controller
{
    public function process(Workorder $workorder)
    {
        $workorder->load([
            'unit',
            'media',
            'tdrs.component.media',
            'tdrs.tdrProcesses.processName',
        ]);

        $components = $workorder->tdrs
            ->filter(fn ($tdr) => (bool) $tdr->component)
            ->groupBy('component_id')
            ->map(function ($group) {
                $component = $group->first()->component;

                $component->processesForWorkorder = $group
                    ->flatMap(fn ($tdr) => $tdr->tdrProcesses ?? collect())
                    ->values();

                return $component;
            })
            // ✅ ВАЖНО: фильтр по наличию процессов
            ->filter(fn ($component) => $component->processesForWorkorder->isNotEmpty())
            ->values();

        return view('mobile.pages.process', compact('workorder', 'components'));
    }
}
