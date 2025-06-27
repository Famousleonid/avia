<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Code;
use App\Models\Component;
use App\Models\Manual;
use App\Models\Necessary;
use App\Models\Workorder;
use App\Models\Tdr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogCardController extends Controller
{
    const PROCESS_TYPE_LOG = 'log';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function logCardForm(Request $request, $id)
    {
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);
        // Получаем данные о manual, связанном с этим Workorder
        $manual = $current_wo->unit->manual_id;
        $manual_wo = $current_wo->unit->manuals;

        $builders = Builder::all();

        $manuals = Manual::where('id', $manual)
            ->with('builder')
            ->get();

// Получаем CSV-файл с process_type = 'log'
        $csvMedia = $manual_wo->getMedia('csv_files')->first(function ($media) {
            return $media->getCustomProperty('process_type') === self::PROCESS_TYPE_LOG;
        });

        return view('admin.log_card.logCardForm', compact('current_wo','manuals', 'builders'));

    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;

        $codes = Code::all();
        $necessaries = Necessary::all();

        $necessary = Necessary::where('name', 'Order New')->first();
        $code = Code::where('name', 'Missing')->first();


        $components = Component::where('manual_id', $manual_id)
            ->where('log_card', 1)
            ->orderBy('ipl_num', 'asc')
            ->get();

        // Получаем TDR записи для данного workorder
        $tdrs = Tdr::where('workorder_id', $id)->get();

        // Группируем компоненты по цифрам из ipl_num
        $groupedComponents = $components->groupBy(function ($component) {
            // Извлекаем только цифры из ipl_num
            preg_match('/\d+/', $component->ipl_num, $matches);
            return $matches[0] ?? $component->ipl_num;
        })->map(function ($group) use ($tdrs, $code, $necessary) {
            return [
                'ipl_group' => $group->first()->ipl_num,
                'components' => $group->map(function ($component) use ($tdrs, $code, $necessary) {
                    // Ищем TDR для данного компонента
                    $tdr = $tdrs->where('component_id', $component->id)->first();
            Log::info('TDR:'.$tdr);
                    // Определяем причину удаления
                    $reasonForRemove = '';
                    if ($tdr) {
                        if ($tdr->code === $code) {
                            $reasonForRemove = 'Missing';
                        } elseif ($tdr->necessary === $necessary) {
                            $reasonForRemove = 'Order New';
                        }
                    }

                    return [
                        'component' => $component,
                        'reason_for_remove' => $reasonForRemove
                    ];
                }),
                'count' => $group->count(),
                'has_multiple' => $group->count() > 1
            ];
        });

        return view('admin.log_card.create', compact('current_wo', 'groupedComponents', 'components', 'tdrs', 'code', 'necessary','codes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $current_wo = Workorder::findOrFail($id);

        return view('admin.log_card.show', compact('current_wo'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
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
