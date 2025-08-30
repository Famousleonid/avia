<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Code;
use App\Models\Component;
use App\Models\LogCard;
use App\Models\Manual;
use App\Models\Necessary;
use App\Models\Workorder;
use App\Models\Tdr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
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

        $components = Component::where('manual_id', $manual)->get();

        $log_card = LogCard::where('workorder_id', $current_wo->id)->first();

        // Получаем массив из JSON
        $componentData = [];
        if ($log_card && $log_card->component_data) {
            $componentData = is_array($log_card->component_data)
                ? $log_card->component_data
                : json_decode($log_card->component_data, true);
        }

        $log_count= count($componentData);

        // Разделяем на две части
        $componentData_1 = [];
        $componentData_2 = [];

        if ($log_count > 9) {
            $componentData_1 = array_slice($componentData, 0, 12); // первые 11 элементов
            $componentData_2 = array_slice($componentData, 12);    // оставшиеся элементы
        }
        $log_count_1= count($componentData_1);
        $log_count_2= count($componentData_2);
//// Получаем CSV-файл с process_type = 'log'
//        $csvMedia = $manual_wo->getMedia('csv_files')->first(function ($media) {
//            return $media->getCustomProperty('process_type') === self::PROCESS_TYPE_LOG;
//        });
        // Загружаем коды для отображения названий
        $codes = Code::all();

        if ($log_count > 9) {

            return view('admin.log_card.logCardForm2', compact('current_wo','manuals', 'builders',  'log_card',
                'components' ,'componentData_1',
                'componentData_2', 'log_count_1', 'log_count_2', 'codes'
            ));

        }else {
            return view('admin.log_card.logCardForm', compact('current_wo','manuals', 'builders', 'componentData', 'log_card', 'components' ,'log_count', 'codes'));

        }



    }
    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
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
            ->where('log_card', 1)  // Возвращаем условие
            ->orderBy('ipl_num', 'asc')
            ->get();

        // Получаем TDR записи для данного workorder с загруженными отношениями
        $tdrs = Tdr::where('workorder_id', $id)->with(['codes', 'necessaries'])->get();

        // Группируем компоненты по базовому номеру из ipl_num (без буквенных суффиксов)
        $groupedComponents = $components->groupBy(function ($component) {
            // Извлекаем базовый номер из ipl_num (например, "1-120" из "1-120A")
            if (preg_match('/^(\d+-\d+)/', $component->ipl_num, $matches)) {
                return $matches[1];
            }
            return $component->ipl_num;
        })->map(function ($group) use ($tdrs, $code, $necessary) {
            return [
                'ipl_group' => $group->keys()->first(), // Используем ключ группы (базовый номер)
                'group_key' => $group->keys()->first(), // Дублируем для удобства
                'components' => $group->sortBy('ipl_num')->map(function ($component) use ($tdrs, $code, $necessary) {
                    // Ищем TDR для данного компонента
                    $tdr = $tdrs->where('component_id', $component->id)->first();
                    Log::info('TDR:'.$tdr);
                    // Определяем причину удаления
                    $reasonForRemove = '';
                    if ($tdr) {
                        // Проверяем codes (Missing)
                        if ($tdr->codes && $tdr->codes->id === $code->id) {
                            Log::info('Code: ' . $tdr->codes->name);
                            $reasonForRemove = 'Missing';
                        }

                        // Проверяем necessary (Order New)
                        if ($tdr->necessaries && $tdr->necessaries->id === $necessary->id) {
                            Log::info('Necessary: ' . $tdr->necessaries->name);

                            // Если necessary = "Order New", то берем значение из codes
                            if ($tdr->codes) {
                                Log::info('Code: ' . $tdr->codes->name);
                                $reasonForRemove = $tdr->codes->name;
                            }
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

        // Сортируем группы по базовым номерам ipl_num
        $groupedComponents = $groupedComponents->sortBy(function ($group, $key) {
            // Функция для правильной сортировки номеров вида "1-120", "1-130", "2-100"
            if (preg_match('/^(\d+)-(\d+)$/', $key, $matches)) {
                $first = (int)$matches[1];
                $second = (int)$matches[2];
                // Создаем числовое значение для сортировки (например, 1-120 = 1120, 1-130 = 1130)
                return $first * 1000 + $second;
            }
            return $key;
        });

        return view('admin.log_card.create', compact('current_wo', 'groupedComponents', 'components', 'tdrs', 'code', 'necessary','codes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
//        dd($request);
        $request->validate([
            'workorder_id' => 'required|integer|exists:workorders,id',
            'component_data' => 'required|string',
        ]);

        $workorder_id = $request->input('workorder_id');
        $componentData = $request->input('component_data'); // это уже JSON-строка

//        dd($componentData);

        \App\Models\LogCard::create([
            'workorder_id'    => $workorder_id,
            'component_data' => $componentData,
        ]);

        return redirect()->route('log_card.show', $workorder_id)
            ->with('success', 'Log Card успешно создан!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function show($id)
    {
        $current_wo = Workorder::findOrFail($id);
        $manual_id = $current_wo->unit->manual_id;
        $components = Component::where('manual_id', $manual_id)->get();

        $log_card = LogCard::where('workorder_id', $current_wo->id)->first();

        // Получаем массив из JSON
        $componentData = [];
        if ($log_card && $log_card->component_data) {
            $componentData = is_array($log_card->component_data)
                ? $log_card->component_data
                : json_decode($log_card->component_data, true);
        }

        // Загружаем коды для отображения названий
        $codes = Code::all();

        return view('admin.log_card.show', compact('current_wo', 'componentData', 'log_card', 'components', 'codes'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $log_card = LogCard::findOrFail($id);
        $current_wo = Workorder::findOrFail($log_card->workorder_id);
        $manual_id = $current_wo->unit->manual_id;

//        $components = Component::where('manual_id', $manual_id)->get();
        $components = Component::where('manual_id', $manual_id)
            ->where('log_card', 1)
            ->orderBy('ipl_num', 'asc')
            ->get();

        $tdrs = Tdr::where('workorder_id', $current_wo->id)->with(['codes', 'necessaries'])->get();
        $componentData = json_decode($log_card->component_data, true);

        // Загружаем коды для dropdown
        $codes = Code::all();

        // Группируем компоненты по базовому номеру из ipl_num (без буквенных суффиксов)
        $groupedComponents = $components->groupBy(function ($component) {
            // Извлекаем базовый номер из ipl_num (например, "1-120" из "1-120A")
            if (preg_match('/^(\d+-\d+)/', $component->ipl_num, $matches)) {
                return $matches[1];
            }
            return $component->ipl_num;
        })->map(function ($group) use ($tdrs, $componentData) {
            // Находим существующие данные для группы
            $groupExistingData = null;
            foreach ($group as $component) {
                $existingData = collect($componentData)->firstWhere('component_id', $component->id);
                if ($existingData) {
                    $groupExistingData = $existingData;
                    break;
                }
            }

            return [
                'ipl_group' => $group->keys()->first(), // Используем ключ группы (базовый номер)
                'group_key' => $group->keys()->first(), // Дублируем для удобства
                'existing_data' => $groupExistingData, // Данные для всей группы
                'components' => $group->sortBy('ipl_num')->map(function ($component) use ($tdrs, $componentData) {
                    // Ищем существующие данные для компонента
                    $existingData = collect($componentData)->firstWhere('component_id', $component->id);

                    return [
                        'component' => $component,
                        'existing_data' => $existingData
                    ];
                }),
                'count' => $group->count(),
                'has_multiple' => $group->count() > 1
            ];
        });

        // Сортируем группы по базовым номерам ipl_num
        $groupedComponents = $groupedComponents->sortBy(function ($group, $key) {
            // Функция для правильной сортировки номеров вида "1-120", "1-130", "2-100"
            if (preg_match('/^(\d+)-(\d+)$/', $key, $matches)) {
                $first = (int)$matches[1];
                $second = (int)$matches[2];
                // Создаем числовое значение для сортировки (например, 1-120 = 1120, 1-130 = 1130)
                return $first * 1000 + $second;
            }
            return $key;
        });

        return view('admin.log_card.edit', compact('current_wo', 'groupedComponents', 'components', 'tdrs', 'log_card', 'componentData', 'codes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'workorder_id' => 'required|integer|exists:workorders,id',
            'component_data' => 'required|string',
        ]);

        $log_card = \App\Models\LogCard::findOrFail($id);
        $log_card->workorder_id = $request->input('workorder_id');
        $log_card->component_data = $request->input('component_data');
        $log_card->save();

        return redirect()->route('log_card.show', $log_card->workorder_id)
            ->with('success', 'Log Card успешно обновлён!');
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
