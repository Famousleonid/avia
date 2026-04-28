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
use Illuminate\Validation\ValidationException;

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

    /**
     * Return partial HTML for Log Card tab: grouped rows with variant selection like Create Log Card form.
     */
    public function partial($workorder_id)
    {
        $current_wo = Workorder::findOrFail($workorder_id);
        $codes = Code::all();

        $log_card = LogCard::where('workorder_id', $current_wo->id)->first();

        $ctx = $this->prepareGroupedLogCardComponents($current_wo);

        $componentData = [];
        if ($log_card && $log_card->component_data) {
            $componentData = is_array($log_card->component_data)
                ? $log_card->component_data
                : json_decode($log_card->component_data, true);
        }

        [$presetByIplGroup, $separateQueue] = $this->splitLogCardComponentPresets(is_array($componentData) ? $componentData : []);

        $tabMetaGroupMap = [];
        $groupKeysOrdered = [];
        foreach ($ctx['groupedComponents'] as $groupIndex => $group) {
            $k = (string) $groupIndex;
            $tabMetaGroupMap[$k] = $group['ipl_group'];
            $groupKeysOrdered[] = $k;
        }

        $tabMeta = [
            'workorder_id' => (int) $current_wo->id,
            'log_card_id' => $log_card ? (int) $log_card->id : null,
            'has_saved_log_card' => (bool) ($log_card && ! empty($componentData)),
            'group_map' => $tabMetaGroupMap,
            'group_keys_ordered' => $groupKeysOrdered,
        ];

        return view(
            'admin.log_card.partial',
            array_merge(compact(
                'current_wo',
                'log_card',
                'codes',
                'presetByIplGroup',
                'separateQueue',
                'tabMeta'
            ), $ctx)
        );
    }

    /**
     * @return array{0: array<string, array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function splitLogCardComponentPresets(array $componentData): array
    {
        $presetByIplGroup = [];
        $separateQueue = [];

        foreach ($componentData as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (isset($row['ipl_group']) && $row['ipl_group'] !== '' && $row['ipl_group'] !== null) {
                $presetByIplGroup[$row['ipl_group']] = $row;

                continue;
            }
            $separateQueue[] = $row;
        }

        return [$presetByIplGroup, $separateQueue];
    }

    /**
     * Grouped log-card components (same rules as create form): IPL suffix groups + separate rows for units_assy &gt; 1.
     *
     * @return array{
     *   groupedComponents: \Illuminate\Support\Collection,
     *   separateComponents: \Illuminate\Support\Collection,
     *   components: \Illuminate\Database\Eloquent\Collection,
     *   tdrs: \Illuminate\Database\Eloquent\Collection,
     *   code: ?\App\Models\Code,
     *   necessary: ?\App\Models\Necessary
     * }
     */
    private function prepareGroupedLogCardComponents(Workorder $current_wo): array
    {
        $manual_id = $current_wo->unit->manual_id;

        $necessary = Necessary::where('name', 'Order New')->first();
        $code = Code::where('name', 'Missing')->first();

        $components = Component::where('manual_id', $manual_id)
            ->where('log_card', 1)
            ->orderBy('ipl_num', 'asc')
            ->get();

        $tdrs = Tdr::where('workorder_id', $current_wo->id)->with(['codes', 'necessaries'])->get();

        $groupedComponents = $components->groupBy(function ($component) {
            if (preg_match('/^(\d+-\d+)/', $component->ipl_num, $matches)) {
                return $matches[1];
            }

            return $component->ipl_num;
        })->map(function ($group, $baseIplKey) use ($tdrs, $code, $necessary) {
            $filteredGroup = $group->filter(function ($component) {
                return ($component->units_assy ?? 1) == 1;
            });

            return [
                'ipl_group' => $baseIplKey,
                'group_key' => $baseIplKey,
                'components' => $filteredGroup->sortBy('ipl_num')->map(function ($component) use ($tdrs, $code, $necessary) {
                    $tdr = $tdrs->where('component_id', $component->id)->first();
                    $reasonForRemove = '';
                    if ($tdr) {
                        if ($tdr->codes && $code && $tdr->codes->id === $code->id) {
                            $reasonForRemove = 'Missing';
                        }
                        if ($tdr->necessaries && $necessary && $tdr->necessaries->id === $necessary->id && $tdr->codes) {
                            $reasonForRemove = $tdr->codes->name;
                        }
                    }

                    return [
                        'component' => $component,
                        'reason_for_remove' => $reasonForRemove,
                    ];
                }),
                'count' => $filteredGroup->count(),
                'has_multiple' => $filteredGroup->count() > 1,
            ];
        })->filter(function ($group) {
            return $group['count'] > 0;
        });

        $groupedComponents = $groupedComponents->sortBy(function ($group, $key) {
            if (preg_match('/^(\d+)-(\d+)$/', (string) $key, $matches)) {
                return (int) $matches[1] * 1000 + (int) $matches[2];
            }

            return $key;
        });

        $separateComponents = collect();

        foreach ($components as $component) {
            $units_assy = $component->units_assy ?? 1;

            if ($units_assy > 1) {
                $tdr = $tdrs->where('component_id', $component->id)->first();
                $reasonForRemove = '';
                if ($tdr) {
                    if ($tdr->codes && $code && $tdr->codes->id === $code->id) {
                        $reasonForRemove = 'Missing';
                    }
                    if ($tdr->necessaries && $necessary && $tdr->necessaries->id === $necessary->id && $tdr->codes) {
                        $reasonForRemove = $tdr->codes->name;
                    }
                }

                for ($i = 1; $i <= $units_assy; $i++) {
                    $separateComponents->push([
                        'component' => $component,
                        'reason_for_remove' => $reasonForRemove,
                        'units_assy' => $units_assy,
                        'unit_index' => $i,
                        'is_multiple_units' => true,
                        'group_key' => 'separate',
                        'ipl_group' => 'separate',
                    ]);
                }
            }
        }

        return [
            'groupedComponents' => $groupedComponents,
            'separateComponents' => $separateComponents,
            'components' => $components,
            'tdrs' => $tdrs,
            'code' => $code,
            'necessary' => $necessary,
        ];
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
        $codes = Code::all();
        $ctx = $this->prepareGroupedLogCardComponents($current_wo);

        return view('admin.log_card.create', array_merge(
            compact('current_wo', 'codes'),
            $ctx
        ));
    }

    /**
     * Определяет причину удаления компонента на основе TDR данных
     *
     * @param Tdr|null $tdr
     * @param Code|null $code
     * @param Necessary|null $necessary
     * @return string
     */
    private function getReasonForRemove($tdr, $code, $necessary)
    {
        if (!$tdr) {
            return '';
        }

        // Проверяем codes (Missing)
        if ($tdr->codes && $code && $tdr->codes->id === $code->id) {
            return 'Missing';
        }

        // Проверяем necessary (Order New)
        if ($tdr->necessaries && $necessary && $tdr->necessaries->id === $necessary->id) {
            // Если necessary = "Order New", то берем значение из codes
            if ($tdr->codes) {
                return $tdr->codes->name;
            }
        }

        return '';
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
        $this->validateLogCardComponentData($request);

        $workorder_id = $request->input('workorder_id');
        if (LogCard::where('workorder_id', $workorder_id)->exists()) {
            $message = __('Log Card for this workorder already exists.');
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->back()->withErrors(['workorder_id' => $message])->withInput();
        }

        $componentData = $request->input('component_data'); // это уже JSON-строка

//        dd($componentData);

        $logCard = \App\Models\LogCard::create([
            'workorder_id'    => $workorder_id,
            'component_data' => $componentData,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Log Card created successfully!', 'log_card_id' => $logCard->id]);
        }

        return redirect()->route('log_card.show', $workorder_id)
                ->with('success', 'Log Card created successfully!');
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

        // Проверяем конкретно компоненты 937, 940 и 981
        $comp937 = Component::find(937);
        $comp940 = Component::find(940);
        $comp981 = Component::find(981);

        // Проверяем, есть ли эти компоненты в полученной выборке
        $found937 = $components->where('id', 937)->first();
        $found940 = $components->where('id', 940)->first();
        $found981 = $components->where('id', 981)->first();

        // Загружаем коды для dropdown
        $codes = Code::all();

        // Группируем компоненты по базовому номеру из ipl_num (без буквенных суффиксов)
        $groupedComponents = $components->groupBy(function ($component) {
            // Извлекаем базовый номер из ipl_num (например, "1-120" из "1-120A")
            if (preg_match('/^(\d+-\d+)/', $component->ipl_num, $matches)) {
                return $matches[1];
            }
            return $component->ipl_num;
        })->map(function ($group, $baseIplKey) use ($tdrs, $componentData) {
            // Фильтруем компоненты - оставляем только те, у которых units_assy = 1
            $filteredGroup = $group->filter(function ($component) {
                return ($component->units_assy ?? 1) == 1;
            });

            return [
                'ipl_group' => $baseIplKey,
                'group_key' => $baseIplKey,
                'components' => $filteredGroup->sortBy('ipl_num')->map(function ($component) use ($tdrs, $componentData) {
                    // Ищем существующие данные для компонента
                    // Пробуем найти по числовому ID
                    $existingData = collect($componentData)->firstWhere('component_id', $component->id);

                    // Если не найдено, пробуем найти по строковому ID
                    if (!$existingData) {
                        $existingData = collect($componentData)->firstWhere('component_id', (string)$component->id);
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
        $this->validateLogCardComponentData($request);

        $log_card = \App\Models\LogCard::findOrFail($id);
        $log_card->workorder_id = $request->input('workorder_id');
        $log_card->component_data = $request->input('component_data');
        $log_card->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Log Card updated successfully!']);
        }

        return redirect()->route('log_card.show', $log_card->workorder_id)
                ->with('success', 'Log Card updated successfully!');
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

    /**
     * Разрешает сохранить Log Card с любым непустым подмножеством позиций (минимум одна).
     *
     * @throws ValidationException
     */
    private function validateLogCardComponentData(Request $request): void
    {
        $raw = $request->input('component_data');
        if (!is_string($raw) || $raw === '') {
            throw ValidationException::withMessages([
                'component_data' => [__('Заполните component_data.')],
            ]);
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'component_data' => [__('Недопустимый JSON в component_data.')],
            ]);
        }
        if (!is_array($decoded) || count($decoded) < 1) {
            throw ValidationException::withMessages([
                'component_data' => [__('Добавьте в Log Card хотя бы одну позицию (выберите компонент).')],
            ]);
        }
        foreach ($decoded as $row) {
            if (!is_array($row) || (!isset($row['component_id']) || $row['component_id'] === '' || $row['component_id'] === null)) {
                throw ValidationException::withMessages([
                    'component_data' => [__('Каждая строка должна содержать component_id.')],
                ]);
            }
        }
    }

}
