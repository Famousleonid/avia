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

        // Отладочная информация
        \Log::info('Total components found: ' . $components->count());
        foreach ($components as $component) {
            \Log::info('Component: ' . $component->name . ', ipl_num: ' . $component->ipl_num . ', units_assy: ' . ($component->units_assy ?? 'null'));
        }

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
            // Фильтруем компоненты - оставляем только те, у которых units_assy = 1
            $filteredGroup = $group->filter(function ($component) {
                return ($component->units_assy ?? 1) == 1;
            });

            return [
                'ipl_group' => $group->keys()->first(), // Используем ключ группы (базовый номер)
                'group_key' => $group->keys()->first(), // Дублируем для удобства
                'components' => $filteredGroup->sortBy('ipl_num')->map(function ($component) use ($tdrs, $code, $necessary) {
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
                'count' => $filteredGroup->count(),
                'has_multiple' => $filteredGroup->count() > 1
            ];
        })->filter(function ($group) {
            // Убираем пустые группы
            return $group['count'] > 0;
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

        // Обрабатываем компоненты с units_assy > 1 - создаем отдельные строки
        $separateComponents = collect();

        // Сначала проверим все компоненты, включая те, что были исключены из группировки
        foreach ($components as $component) {
            $units_assy = $component->units_assy ?? 1;

            if ($units_assy > 1) {
                // Ищем TDR для данного компонента
                $tdr = $tdrs->where('component_id', $component->id)->first();
                $reasonForRemove = '';
                if ($tdr) {
                    // Проверяем codes (Missing)
                    if ($tdr->codes && $tdr->codes->id === $code->id) {
                        $reasonForRemove = 'Missing';
                    }
                    // Проверяем necessary (Order New)
                    if ($tdr->necessaries && $tdr->necessaries->id === $necessary->id) {
                        if ($tdr->codes) {
                            $reasonForRemove = $tdr->codes->name;
                        }
                    }
                }

                // Создаем отдельные строки для каждой единицы
                for ($i = 1; $i <= $units_assy; $i++) {
                    $separateComponents->push([
                        'component' => $component,
                        'reason_for_remove' => $reasonForRemove,
                        'units_assy' => $units_assy,
                        'unit_index' => $i,
                        'is_multiple_units' => true,
                        'group_key' => 'separate',
                        'ipl_group' => 'separate'
                    ]);
                }
            }
        }

        \Log::info('Separate components count: ' . $separateComponents->count());

        return view('admin.log_card.create', compact('current_wo', 'groupedComponents', 'separateComponents', 'components', 'tdrs', 'code', 'necessary','codes'));
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

        // Отладочная информация о сохраненных данных
        \Log::info('Saved component data:', $componentData);

        // Отладочная информация о полученных компонентах
        \Log::info('Components found with log_card=1: ' . $components->count());
        foreach ($components as $component) {
            \Log::info('Component ID: ' . $component->id . ', Name: ' . $component->name . ', IPL: ' . $component->ipl_num . ', Units: ' . ($component->units_assy ?? 1));
        }

        // Проверяем конкретно компоненты 937, 940 и 981
        $comp937 = Component::find(937);
        $comp940 = Component::find(940);
        $comp981 = Component::find(981);

        if ($comp937) {
            \Log::info('Component 937: log_card=' . ($comp937->log_card ?? 'null') . ', manual_id=' . ($comp937->manual_id ?? 'null') . ', units_assy=' . ($comp937->units_assy ?? 1));
        }
        if ($comp940) {
            \Log::info('Component 940: log_card=' . ($comp940->log_card ?? 'null') . ', manual_id=' . ($comp940->manual_id ?? 'null') . ', units_assy=' . ($comp940->units_assy ?? 1));
        }
        if ($comp981) {
            \Log::info('Component 981: log_card=' . ($comp981->log_card ?? 'null') . ', manual_id=' . ($comp981->manual_id ?? 'null') . ', units_assy=' . ($comp981->units_assy ?? 1));
        }

        // Проверяем, есть ли эти компоненты в полученной выборке
        $found937 = $components->where('id', 937)->first();
        $found940 = $components->where('id', 940)->first();
        $found981 = $components->where('id', 981)->first();

        \Log::info('Found in components query: 937=' . ($found937 ? 'YES' : 'NO') . ', 940=' . ($found940 ? 'YES' : 'NO') . ', 981=' . ($found981 ? 'YES' : 'NO'));

        // Проверяем группировку
        \Log::info('Components 937, 940, 981 should be in groups:');
        if ($found937) {
            \Log::info('Component 937: ipl_num=' . $found937->ipl_num . ', units_assy=' . ($found937->units_assy ?? 1));
        }
        if ($found940) {
            \Log::info('Component 940: ipl_num=' . $found940->ipl_num . ', units_assy=' . ($found940->units_assy ?? 1));
        }
        if ($found981) {
            \Log::info('Component 981: ipl_num=' . $found981->ipl_num . ', units_assy=' . ($found981->units_assy ?? 1));
        }

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
            // Фильтруем компоненты - оставляем только те, у которых units_assy = 1
            $filteredGroup = $group->filter(function ($component) {
                return ($component->units_assy ?? 1) == 1;
            });

            return [
                'ipl_group' => $group->keys()->first(), // Используем ключ группы (базовый номер)
                'group_key' => $group->keys()->first(), // Дублируем для удобства
                'components' => $filteredGroup->sortBy('ipl_num')->map(function ($component) use ($tdrs, $componentData) {
                    // Ищем существующие данные для компонента
                    // Пробуем найти по числовому ID
                    $existingData = collect($componentData)->firstWhere('component_id', $component->id);

                    // Если не найдено, пробуем найти по строковому ID
                    if (!$existingData) {
                        $existingData = collect($componentData)->firstWhere('component_id', (string)$component->id);
                    }

                    // DEBUG: Логируем поиск existing_data
                    if ($component->id == 937 || $component->id == 940) {
                        \Log::info('DEBUG: Looking for component ' . $component->id . ' in componentData');
                        \Log::info('DEBUG: componentData contains: ' . json_encode($componentData));
                        \Log::info('DEBUG: Found existing_data: ' . ($existingData ? json_encode($existingData) : 'NULL'));
                    }

                    return [
                        'component' => $component,
                        'existing_data' => $existingData
                    ];
                }),
                'count' => $filteredGroup->count(),
                'has_multiple' => $filteredGroup->count() > 1
            ];
        })->filter(function ($group) {
            // Убираем пустые группы
            return $group['count'] > 0;
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

        // Обрабатываем компоненты с units_assy > 1 - создаем отдельные строки
        $separateComponents = collect();

        // Сначала проверим все компоненты, включая те, что были исключены из группировки
        foreach ($components as $component) {
            $units_assy = $component->units_assy ?? 1;

            if ($units_assy > 1) {
                // Ищем все существующие данные для компонента
                $existingDataForComponent = collect($componentData)->where('component_id', $component->id);

                // Если не найдено по числовому ID, пробуем по строковому
                if ($existingDataForComponent->isEmpty()) {
                    $existingDataForComponent = collect($componentData)->where('component_id', (string)$component->id);
                }

//                // DEBUG: Логируем поиск для компонента 981
//                if ($component->id == 981) {
//                    \Log::info('DEBUG: Looking for component 981 in componentData');
//                    \Log::info('DEBUG: Found ' . $existingDataForComponent->count() . ' entries for component 981');
//                    foreach ($existingDataForComponent as $idx => $data) {
//                        \Log::info('DEBUG: Entry ' . $idx . ': ' . json_encode($data));
//                    }
//                }

//                // DEBUG: Логируем все отдельные компоненты
//                \Log::info('DEBUG: Processing separate component ' . $component->id . ' with units_assy=' . $units_assy);
//
                // Создаем отдельные строки для каждой единицы
                for ($i = 1; $i <= $units_assy; $i++) {
                    // Для каждой единицы ищем соответствующие данные
                    // Используем values() чтобы получить массив и взять по индексу
                    $existingDataArray = $existingDataForComponent->values()->toArray();
                    $existingData = isset($existingDataArray[$i - 1]) ? $existingDataArray[$i - 1] : null;

                    // DEBUG: Логируем данные для каждой единицы
                    if ($component->id == 981) {
                        \Log::info('DEBUG: Component 981 Unit ' . $i . ' - existing_data: ' . ($existingData ? json_encode($existingData) : 'NULL'));
                    }

                    $separateComponents->push([
                        'component' => $component,
                        'existing_data' => $existingData,
                        'units_assy' => $units_assy,
                        'unit_index' => $i,
                        'is_multiple_units' => true,
                        'group_key' => 'separate',
                        'ipl_group' => 'separate'
                    ]);
                }
            }
        }

        \Log::info('Separate components count (edit): ' . $separateComponents->count());

        // Отладочная информация о группированных компонентах
        \Log::info('Grouped components count: ' . $groupedComponents->count());
        foreach ($groupedComponents as $groupKey => $group) {
            \Log::info('Group ' . $groupKey . ': ' . $group['count'] . ' components');
            foreach ($group['components'] as $comp) {
                \Log::info('  - Component ID: ' . $comp['component']->id . ', Name: ' . $comp['component']->name . ', IPL: ' . $comp['component']->ipl_num);
            }
        }

        return view('admin.log_card.edit', compact('current_wo', 'groupedComponents', 'separateComponents', 'components', 'tdrs', 'log_card', 'componentData', 'codes'));
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
