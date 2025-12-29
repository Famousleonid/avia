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
            'tdrs.component.media',        // ✅ компонент у Tdr
            'tdrs.tdrProcesses.processName',
        ]);

        // Собираем компоненты для этого воркордера
        $components = $workorder->tdrs
            ->filter(fn($tdr) => $tdr->component)       // только Tdr с компонентом
            ->groupBy('component_id')                    // группируем по компоненту
            ->map(function ($group) {
                $first = $group->first();
                $component = $first->component;
                $component->processesForWorkorder = $group
                    ->flatMap->tdrProcesses           // собираем все tdrProcesses из группы
                    ->values();

                return $component;
            })
            ->values();

        return view('mobile.pages.process', compact('workorder', 'components'));

    }
}
