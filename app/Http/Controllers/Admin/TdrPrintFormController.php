<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Code;
use App\Models\Component;
use App\Models\Condition;
use App\Models\Customer;
use App\Models\ExtraProcess;
use App\Models\LogCard;
use App\Models\Manual;
use App\Models\ManualProcess;
use App\Models\Necessary;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Unit;
use App\Models\User;
use App\Models\Workorder;
use App\Models\NdtCadCsv;
use App\Services\WorkorderStdListProcessesService;
use App\Support\LogCardDestructionCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;

class TdrPrintFormController extends Controller
{
    private const DEFAULT_QTY = 1;
    private const DEFAULT_PROCESS = 1;
    private const PROCESS_TYPE_LOG = 'log';

    public function prlForm(Request $request, $id){
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);

        // Получаем данные о manual_id, связанном с этим Workorder
        $manual_id = $current_wo->unit->manual_id;

        // Извлекаем компоненты, связанные с manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        $builders = Builder::all();
        $codes = Code::all();
        $necessaries = Necessary::all();

        $necessary = Necessary::where('name', 'Order New')->first();
        $code = Code::where('name', 'Missing')->first();

        $manuals = Manual::where('id', $manual_id)
            ->with('builder')
            ->get();

        // Получаем TDR записи с непустым order_component_id
        $ordersPartsNew = Tdr::where('workorder_id', $current_wo->id)
            ->where('necessaries_id', $necessary->id)
            ->whereNotNull('order_component_id')
            ->with(['codes', 'orderComponent' => function($query) {
                // Добавляем manual_id в select и загружаем связь manual
                $query->select('id', 'name', 'part_number', 'ipl_num', 'assy_part_number', 'assy_ipl_num', 'manual_id')
                      ->with('manual:id,number'); // Загружаем только id и number из Manual
            }])
            ->get();

        // Получаем TDR записи без order_component_id
        $ordersParts = Tdr::where('workorder_id', $current_wo->id)
            ->where('necessaries_id', $necessary->id)
            ->whereNull('order_component_id')
            ->with(['codes', 'component' => function($query) {
                // Добавляем manual_id в select и загружаем связь manual
                $query->select('id', 'name', 'part_number', 'ipl_num', 'assy_part_number', 'assy_ipl_num', 'manual_id')
                      ->with('manual:id,number'); // Загружаем только id и number из Manual
            }])
            ->get();

        // Объединяем коллекции
        $ordersParts = $ordersPartsNew->concat($ordersParts);

        // Добавляем поле manual (номер manual) к каждой TDR записи
        $ordersParts = $ordersParts->map(function($tdr) {
            // Определяем, какой компонент использовать: orderComponent или component
            $component = $tdr->orderComponent ?? $tdr->component;

            // Получаем номер manual из связанного компонента
            if ($component && $component->manual) {
                $tdr->manual = $component->manual->number; // Номер manual (например, "32-11-12")
            } else {
                $tdr->manual = null; // Если manual нет, устанавливаем null
            }

            return $tdr;
        });

        // Сортируем TDR записи: сначала по manual (если есть), потом по IPL номерам компонентов
        $ordersParts = $ordersParts->sort(function($a, $b) {
            // Сначала сравниваем по manual
            $manualA = $a->manual ?? '';
            $manualB = $b->manual ?? '';
            $manualCompare = strnatcasecmp($manualA, $manualB);
            if ($manualCompare !== 0) {
                return $manualCompare;
            }

            // Если manual одинаковые или оба null, сравниваем по IPL номерам
            $componentA = $a->orderComponent ?? $a->component;
            $componentB = $b->orderComponent ?? $b->component;

            // Используем assy_ipl_num если есть, иначе ipl_num
            $iplA = ($componentA && isset($componentA->assy_ipl_num) && $componentA->assy_ipl_num !== null && $componentA->assy_ipl_num !== '')
                ? $componentA->assy_ipl_num
                : ($componentA->ipl_num ?? '');
            $iplB = ($componentB && isset($componentB->assy_ipl_num) && $componentB->assy_ipl_num !== null && $componentB->assy_ipl_num !== '')
                ? $componentB->assy_ipl_num
                : ($componentB->ipl_num ?? '');

            // Разбиваем IPL номер на части (например, "1-65" -> ["1", "65"])
            $aParts = explode('-', $iplA);
            $bParts = explode('-', $iplB);

            // Сравниваем первую часть (до -)
            $aFirst = (int)($aParts[0] ?? 0);
            $bFirst = (int)($bParts[0] ?? 0);

            if ($aFirst !== $bFirst) {
                return $aFirst - $bFirst;
            }

            // Если первая часть одинаковая, сравниваем вторую часть (после -)
            $aSecond = (int)($aParts[1] ?? 0);
            $bSecond = (int)($bParts[1] ?? 0);

            return $aSecond - $bSecond;
        })->values(); // values() переиндексирует коллекцию после сортировки

        // Собираем все уникальные номера manual из компонентов
        $uniqueManuals = $ordersParts->map(function($tdr) {
            return $tdr->manual ?? null;
        })->filter(function($manual) {
            return $manual !== null && $manual !== '';
        })->unique()->values()->toArray();

        // Определяем, есть ли несколько manual
        $hasMultipleManuals = count($uniqueManuals) > 1;

        // Разбиение на страницы теперь происходит на фронтенде через JavaScript
        // Передаём все компоненты без предварительного разбиения
        // Это позволяет управлять количеством строк на странице через Print Settings

        return view('admin.tdrs.prlForm', compact('current_wo', 'components','manuals', 'builders', 'codes','necessaries', 'ordersParts', 'uniqueManuals', 'hasMultipleManuals'));
    }

    public function ndtForm(Request $request, $id)
    {
        // Загрузка Workorder с необходимыми отношениями
        $current_wo = Workorder::findOrFail($id);

        // Получаем manual_id через отношения
        $manual_id = $current_wo->unit->manual_id;

        // Получаем компоненты для manual
        $components = Component::where('manual_id', $manual_id)->get();

        // Получаем TDR записи с непустым component_id
        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->whereNotNull('component_id')
            ->get(['id', 'component_id']); // Выбираем только нужные поля

        // Получаем ID process names одним запросом
        $processNames = ProcessName::whereIn('name', [
            'NDT-1',
            'NDT-4',
            'Eddy Current Test',
            'BNI'
        ])->pluck('id', 'name');

        // Проверяем, что все process names найдены
        if ($processNames->count() !== 4) {
            abort(500, 'Не все Process Names найдены');
        }

        // Извлекаем ID по именам
        $ndt1_name_id = $processNames['NDT-1'];
        $ndt4_name_id = $processNames['NDT-4'];
        $ndt6_name_id = $processNames['Eddy Current Test'];
        $ndt5_name_id = $processNames['BNI'];

        // Получаем manual processes
        $manualProcesses = ManualProcess::where('manual_id', $manual_id)
            ->pluck('processes_id');

        // Получаем form_number
        $form_number = ProcessName::where('process_sheet_name', 'NDT')
            ->value('form_number');

        // Получаем NDT processes
        $ndt_processes = Process::whereIn('id', $manualProcesses)
            ->whereIn('process_names_id', [
                $ndt1_name_id,
                $ndt4_name_id,
                $ndt5_name_id,
                $ndt6_name_id
            ])
            ->get();

        // Получаем NDT components
        $ndt_components = TdrProcess::whereIn('tdrs_id', $tdrs->pluck('id'))
            ->whereIn('process_names_id', [
                $ndt1_name_id,
                $ndt4_name_id,
                $ndt5_name_id,
                $ndt6_name_id
            ])
            ->with(['tdr', 'processName'])
            ->get();

        return view('admin.tdrs.ndtForm', [
            'current_wo' => $current_wo,
            'components' => $components,
            'tdrs' => $tdrs,
            'manuals' => Manual::where('id', $manual_id)->get(), // Оставлено для совместимости
            'ndt_processes' => $ndt_processes,
            'ndt1_name_id' => $ndt1_name_id,
            'ndt4_name_id' => $ndt4_name_id,
            'ndt5_name_id' => $ndt5_name_id,
            'ndt6_name_id' => $ndt6_name_id,
            'ndt_components' => $ndt_components,
            'form_number' => $form_number
        ]);
    }

    public function ndtStd($workorder_id)
    {
        $current_wo = Workorder::findOrFail($workorder_id);
        $manual = $current_wo->unit->manuals;

        // Получаем или создаем NdtCadCsv для данного workorder с автоматической загрузкой
        $ndtCadCsv = $current_wo->ndtCadCsv;
        if (!$ndtCadCsv) {
            $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder_id);
        }

        // Получаем ID process names для NDT
        $processNames = ProcessName::whereIn('name', [
            'NDT-1',
            'NDT-4',
            'Eddy Current Test',
            'BNI'
        ])->pluck('id', 'name');

        // Извлекаем ID по именам
        $ndt_ids = [
            'ndt1_name_id' => $processNames['NDT-1'] ?? null,
            'ndt4_name_id' => $processNames['NDT-4'] ?? null,
            'ndt6_name_id' => $processNames['Eddy Current Test'] ?? null,
            'ndt5_name_id' => $processNames['BNI'] ?? null
        ];

        // Получаем manual processes
        $manualProcesses = ManualProcess::where('manual_id', $manual->id)
            ->pluck('processes_id');

        // Получаем NDT processes
        $ndt_processes = Process::whereIn('id', $manualProcesses)
            ->whereIn('process_names_id', $ndt_ids)
            ->get();

        $ndtStdQtyMaps = $this->ndtStdExcludedAndTdrQtyByNormalizedIpl($workorder_id);
        $excludedQtyByIpl = $ndtStdQtyMaps['excluded'];
        $tdrItemsMap = $ndtStdQtyMaps['tdr'];
        $unitsAssyByIpl = $this->buildUnitsAssyByNormalizedIplMap($manual);

        // Фильтруем компоненты из NdtCadCsv с учетом remaining quantity
        $ndt_components = [];

        foreach ($ndtCadCsv->ndt_components as $component) {
            $iplNum = $component['ipl_num'] ?? '';
            if (empty($iplNum)) {
                continue; // Пропускаем компоненты без IPL номера
            }

            // Нормализуем IPL номер для сравнения
            $normalizedIpl = $this->normalizeIplNum($iplNum);

            // Получаем данные для расчета
            $csvQty = (int) ($component['qty'] ?? self::DEFAULT_QTY);
            $tdrQty = $tdrItemsMap[$normalizedIpl] ?? 0; // Сумма QTY из TDR для этого IPL (нормализованного) БЕЗ статусов Missing, Repair, Order New
            $excludedQty = $excludedQtyByIpl[$normalizedIpl] ?? 0; // Количество компонентов со статусами Missing, Repair, Order New

            $stdQty = max(1, $csvQty);
            $unitsAssy = $this->resolveNdtStdUnitsAssyForRow($component, $iplNum, $normalizedIpl, $unitsAssyByIpl);
            $baseQty = min($stdQty, $unitsAssy);
            $remaining = $baseQty - $excludedQty - $tdrQty;

            // Если результат <= 0, компонент скрывается
            if ($remaining <= 0) {
                continue;
            }

            // Если результат > 0, показываем с qty = remaining
            $displayQty = $remaining;

            // Преобразуем в объект для совместимости с существующим кодом
            $componentObj = new \stdClass();
            $componentObj->ipl_num = $iplNum;
            $componentObj->part_number = $component['part_number'] ?? '';
            $componentObj->name = $component['description'] ?? '';
            $componentObj->qty = $displayQty; // min(QTY из STD, units_assy) − excluded − tdr
            $componentObj->process_name = $component['process'] ?? '1';
            // Добавляем поле manual, если оно есть
            $componentObj->manual = $component['manual'] ?? null;
            $componentObj->eff_code = trim((string) ($component['eff_code'] ?? ''));

            $ndt_components[] = $componentObj;
        }

        // Сортируем NDT компоненты: сначала по manual (если есть), потом по ipl_num
        usort($ndt_components, function($a, $b) {
            // Сначала сравниваем по manual
            $manualA = $a->manual ?? '';
            $manualB = $b->manual ?? '';
            $manualCompare = strnatcasecmp($manualA, $manualB);
            if ($manualCompare !== 0) {
                return $manualCompare;
            }

            // Если manual одинаковые, сравниваем по ipl_num
            $aParts = explode('-', $a->ipl_num ?? '');
            $bParts = explode('-', $b->ipl_num ?? '');

            // Сравниваем первую часть (до -)
            $aFirst = (int)($aParts[0] ?? 0);
            $bFirst = (int)($bParts[0] ?? 0);

            if ($aFirst !== $bFirst) {
                return $aFirst - $bFirst;
            }

            // Если первая часть одинаковая, сравниваем вторую часть (после -)
            $aSecond = (int)($aParts[1] ?? 0);
            $bSecond = (int)($bParts[1] ?? 0);

            return $aSecond - $bSecond;
        });

        $form_number = 'NDT-STD';

        // Разбиение на страницы теперь происходит на фронтенде через JavaScript
        // Передаём все компоненты без предварительного разбиения

        return view('admin.tdrs.ndtFormStd', [
                'current_wo' => $current_wo,
                'manual' => $manual,
                'ndt_components' => $ndt_components,
                'ndt_processes' => $ndt_processes,
                'form_number' => $form_number,
                'manuals' => [$manual], // Для совместимости с существующим кодом
            ] + $ndt_ids); // Добавляем ID процессов NDT
    }

    public function cadStd(Request $request, $workorder_id)
    {
        try {
            // Получаем рабочий заказ и связанные данные
            $current_wo = Workorder::findOrFail($workorder_id);
            $manual = $current_wo->unit->manuals;

            if (!$manual) {
                throw new \RuntimeException('Manual not found for this workorder');
            }

            // Получаем или создаем NdtCadCsv для данного workorder с автоматической загрузкой
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder_id);
            }

            // Автозагрузка Stress компонентов из Manual CSV при их отсутствии
//            $stressEmpty = empty($ndtCadCsv->stress_components)
//                || (is_array($ndtCadCsv->stress_components) && count($ndtCadCsv->stress_components) === 0);

//            if ($stressEmpty) {
//                // \Log::info('Stress components are empty. Attempting auto-load from Manual CSV', [
//                    'workorder_id' => $workorder_id,
//                    'before_count' => is_array($ndtCadCsv->stress_components) ? count($ndtCadCsv->stress_components) : 0,
//                ]);
//                $ndtCadCsv = NdtCadCsv::loadComponentsFromManual($workorder_id, $ndtCadCsv);
//                // \Log::info('Auto-load completed', [
//                    'after_count' => is_array($ndtCadCsv->stress_components) ? count($ndtCadCsv->stress_components) : 0,
//                ]);
//            }

            // Получаем ID process names для CAD
            $processNames = ProcessName::whereIn('name', ['Cad plate'])->pluck('id', 'name');

            if (!isset($processNames['Cad plate'])) {
                throw new \RuntimeException('CAD process name not found');
            }

            // Извлекаем ID по именам
            $cad_ids = [
                'cad_name_id' => $processNames['Cad plate']
            ];

            // Получаем manual processes
            $manualProcesses = ManualProcess::where('manual_id', $manual->id)
                ->pluck('processes_id');

            // Получаем CAD processes
            $cad_processes = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $cad_ids)
                ->get();

            // Получаем существующие IPL номера
            $existingIplNums = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component')
                ->get()
                ->pluck('component.ipl_num')
                ->filter()
                ->unique()
                ->toArray();

            // Получаем все процессы для данного manual и process_name
            $validProcesses = Process::whereIn('id', $manualProcesses)
                ->where('process_names_id', $cad_ids['cad_name_id'])
                ->pluck('process')
                ->toArray();

            // Подготовка данных TDR и units_assy для расчёта остатков (CAD)
            $tdrItemsCad = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num')
                ->get()
                ->map(function ($tdr) {
                    return [
                        'ipl_num' => $tdr->component->ipl_num ?? null,
                        'qty' => (int)($tdr->qty ?? 0),
                    ];
                })
                ->filter(function ($item) {
                    return !empty($item['ipl_num']) && $item['qty'] > 0;
                })
                ->values()
                ->toArray();

            // Мапа units_assy по IPL из Components всех manuals (с нормализацией IPL)
            // Приоритет: компоненты из текущего manual
            // Это необходимо, т.к. в CSV и TDR могут быть компоненты из других manuals
            $unitsAssyByIplCad = [];

            // Получаем все компоненты, сортируя так, чтобы сначала шли компоненты из текущего manual
            $allComponentsCad = Component::select('ipl_num', 'units_assy', 'manual_id')
                ->orderByRaw("CASE WHEN manual_id = ? THEN 0 ELSE 1 END", [$manual->id])
                ->get();

            foreach ($allComponentsCad as $component) {
                if ($component->ipl_num) {
                    $normalizedIpl = $this->normalizeIplNum($component->ipl_num);
                    if (!empty($normalizedIpl)) {
                        // Добавляем только если еще нет в мапе
                        // Благодаря сортировке, компоненты из текущего manual будут добавлены первыми
                        if (!isset($unitsAssyByIplCad[$normalizedIpl])) {
                            $num = (int)($component->units_assy ?? 1);
                            $unitsAssy = $num > 0 ? $num : 1;
                            $unitsAssyByIplCad[$normalizedIpl] = $unitsAssy;
                        }
                    }
                }
            }

            // Получаем количество компонентов со статусами Missing, Repair, Order New
            // Создаем мапу excludedQtyByIplCad - количество исключенных компонентов по IPL
            $excludedQtyByIplCad = [];

            // Получаем ID для Missing, Repair, Order New
            // Missing - в таблице codes
            $missingCode = Code::where('name', 'Missing')->first();

            // Repair - в таблице necessaries (ID = 1)
            $repairNecessary = Necessary::find(1);
            if (!$repairNecessary || stripos($repairNecessary->name, 'repair') === false) {
                // Пробуем найти по имени
                $repairNecessary = Necessary::where('name', 'Repair')->first();
                if (!$repairNecessary) {
                    $repairNecessary = Necessary::where('name', 'REPAIR')->first();
                }
                if (!$repairNecessary) {
                    $repairNecessary = Necessary::where('name', 'repair')->first();
                }
            }

            // Order New - в таблице necessaries (ID = 2)
            $orderNewNecessary = Necessary::find(2);
            if (!$orderNewNecessary || stripos($orderNewNecessary->name, 'order') === false) {
                // Пробуем найти по имени
                $orderNewNecessary = Necessary::where('name', 'Order New')->first();
            }

            // \Log::info('CAD Filtering - Codes and Necessaries', [
            //     'missing_code_id' => $missingCode ? $missingCode->id : null,
            //     'missing_code_name' => $missingCode ? $missingCode->name : null,
            //     'repair_necessary_id' => $repairNecessary ? $repairNecessary->id : null,
            //     'repair_necessary_name' => $repairNecessary ? $repairNecessary->name : null,
            //     'order_new_necessary_id' => $orderNewNecessary ? $orderNewNecessary->id : null,
            //     'order_new_necessary_name' => $orderNewNecessary ? $orderNewNecessary->name : null,
            // ]);

            // Получаем TDR записи с этими статусами
            $excludedTdrQuery = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num');

            $excludedConditions = [];
            if ($missingCode) {
                $excludedConditions[] = ['codes_id', $missingCode->id];
            }
            if ($repairNecessary) {
                $excludedConditions[] = ['necessaries_id', $repairNecessary->id];
            }
            if ($orderNewNecessary) {
                $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
            }

            // \Log::info('CAD Filtering - Excluded conditions', [
            //     'conditions_count' => count($excludedConditions),
            //     'conditions' => $excludedConditions
            // ]);

            if (!empty($excludedConditions)) {
                $excludedTdrQuery->where(function($query) use ($excludedConditions) {
                    foreach ($excludedConditions as $condition) {
                        $query->orWhere($condition[0], $condition[1]);
                    }
                });

                $excludedTdrs = $excludedTdrQuery->get();
                // \Log::info('CAD Filtering - Excluded TDRs found', [
                //     'count' => $excludedTdrs->count(),
                //     'tdrs' => $excludedTdrs->map(function($tdr) {
                //         $code = $tdr->codes_id ? Code::find($tdr->codes_id) : null;
                //         $necessary = $tdr->necessaries_id ? Necessary::find($tdr->necessaries_id) : null;
                //         return [
                //             'id' => $tdr->id,
                //             'ipl_num' => $tdr->component->ipl_num ?? null,
                //             'codes_id' => $tdr->codes_id,
                //             'code_name' => $code ? $code->name : null,
                //             'necessaries_id' => $tdr->necessaries_id,
                //             'necessary_name' => $necessary ? $necessary->name : null,
                //             'qty' => $tdr->qty ?? 0,
                //         ];
                //     })->toArray()
                // ]);

                foreach ($excludedTdrs as $tdr) {
                    if ($tdr->component && $tdr->component->ipl_num) {
                        // Нормализуем IPL номер для сравнения (убираем буквенные суффиксы)
                        $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                        if (!empty($normalizedIpl)) {
                            // Суммируем количество исключенных компонентов
                            if (!isset($excludedQtyByIplCad[$normalizedIpl])) {
                                $excludedQtyByIplCad[$normalizedIpl] = 0;
                            }
                            $excludedQtyByIplCad[$normalizedIpl] += (int)($tdr->qty ?? 0);
                        }
                    }
                }

                // \Log::info('CAD Filtering - Excluded QTY by IPL', [
                //     'excluded_qty_count' => count($excludedQtyByIplCad),
                //     'excluded_qty_by_ipl' => $excludedQtyByIplCad
                // ]);
            } else {
                // \Log::warning('CAD Filtering - No excluded conditions found!', [
                //     'missing_code' => $missingCode ? $missingCode->id : null,
                //     'repair_necessary' => $repairNecessary ? $repairNecessary->id : null,
                //     'order_new' => $orderNewNecessary ? $orderNewNecessary->id : null,
                // ]);
            }

            // Создаем мапу TDR items по IPL для быстрого поиска (с нормализацией)
            // ВАЖНО: учитываем только TDR записи БЕЗ статусов Missing, Repair, Order New
            $tdrItemsMapCad = [];
            foreach ($tdrItemsCad as $item) {
                $iplNum = $item['ipl_num'];
                // Нормализуем IPL номер для группировки
                $normalizedIpl = $this->normalizeIplNum($iplNum);
                if (!empty($normalizedIpl)) {
                    // Пропускаем компоненты со статусами Missing, Repair, Order New
                    // (они уже учтены в excludedQtyByIplCad)
                    if (isset($excludedQtyByIplCad[$normalizedIpl])) {
                        continue;
                    }

                    if (!isset($tdrItemsMapCad[$normalizedIpl])) {
                        $tdrItemsMapCad[$normalizedIpl] = 0;
                    }
                    $tdrItemsMapCad[$normalizedIpl] += $item['qty'];
                }
            }

            // Фильтруем компоненты из NdtCadCsv
            $cad_components = [];
            foreach ($ndtCadCsv->cad_components as $component) {
                $itemNo = $component['ipl_num'];
                $processName = $component['process'] ?? '';

                // Нормализуем IPL номер для сравнения
                $normalizedIpl = $this->normalizeIplNum($itemNo);

                // Логируем компоненты, которые проходят фильтрацию (для отладки)
                if (stripos($itemNo, '5-90') !== false) {
                    // \Log::info('CAD Filtering - Component NOT excluded (5-90 variant)', [
                    //     'ipl_num' => $itemNo,
                    //     'normalized_ipl' => $normalizedIpl,
                    //     'in_excluded_list' => isset($excludedQtyByIplCad[$normalizedIpl]),
                    //     'excluded_qty' => $excludedQtyByIplCad[$normalizedIpl] ?? 0,
                    //     'excluded_ipls' => array_keys($excludedQtyByIplCad)
                    // ]);
                }

                // Проверяем и создаем процесс, если его нет
                if (!empty($processName)) {
                    // Проверяем существование процесса
                    $process = Process::where('process', $processName)
                        ->where('process_names_id', $cad_ids['cad_name_id'])
                        ->first();

                    if (!$process) {
                        // Создаем новый процесс
                        $process = Process::create([
                            'process' => $processName,
                            'process_names_id' => $cad_ids['cad_name_id']
                        ]);
                        // \Log::info('Created new process:', ['process' => $processName]);
                    }

                    // Проверяем привязку к manual
                    $manualProcess = ManualProcess::where('manual_id', $manual->id)
                        ->where('processes_id', $process->id)
                        ->first();

                    if (!$manualProcess) {
                        // Создаем привязку к manual
                        ManualProcess::create([
                            'manual_id' => $manual->id,
                            'processes_id' => $process->id
                        ]);
                        // \Log::info('Created manual-process binding:', [
                        //     'manual_id' => $manual->id,
                        //     'process_id' => $process->id
                        // ]);
                    }

                    // Обновляем список валидных процессов
                    $validProcesses[] = $processName;
                }

                if (!in_array($processName, $validProcesses)) {
                    // \Log::warning('Invalid process found in ModCsv:', [
                    //     'process' => $processName,
                    //     'item_no' => $itemNo,
                    //     'valid_processes' => $validProcesses
                    // ]);
                    continue;
                }

                // Получаем данные для расчета
                $csvQty = (int)($component['qty'] ?? self::DEFAULT_QTY);
                $tdrQty = $tdrItemsMapCad[$normalizedIpl] ?? 0; // Сумма QTY из TDR для этого IPL (нормализованного) БЕЗ статусов Missing, Repair, Order New
                $excludedQty = $excludedQtyByIplCad[$normalizedIpl] ?? 0; // Количество компонентов со статусами Missing, Repair, Order New

                // Определяем units_assy: если в CSV есть поле manual (manual->number),
                // ищем компонент в соответствующем manual, иначе используем общую мапу
                $unitsAssy = 1;
                if (!empty($component['manual'])) {
                    // Ищем manual по number из CSV (например, "32-11-12")
                    $componentManual = Manual::where('number', $component['manual'])->first();
                    if ($componentManual) {
                        // Ищем компонент в этом manual
                        $componentRecord = Component::where('manual_id', $componentManual->id)
                            ->where('ipl_num', $itemNo)
                            ->first();
                        if ($componentRecord && $componentRecord->units_assy) {
                            $num = (int)$componentRecord->units_assy;
                            $unitsAssy = $num > 0 ? $num : 1;
                        } else {
                            // Если не найдено в указанном manual, используем общую мапу
                            $unitsAssy = $unitsAssyByIplCad[$normalizedIpl] ?? 1;
                        }
                    } else {
                        // Если manual не найден, используем общую мапу
                        $unitsAssy = $unitsAssyByIplCad[$normalizedIpl] ?? 1;
                    }
                } else {
                    // Если поле manual отсутствует, используем общую мапу
                    $unitsAssy = $unitsAssyByIplCad[$normalizedIpl] ?? 1;
                }

                // Логика для CAD (после фильтрации по Missing, Repair, Order New):
                // 1. Вычитаем из unitsAssy количество исключенных компонентов (Missing, Repair, Order New)
                // 2. Затем вычитаем количество компонентов из TDR (без статусов Missing/Repair/Order New)
                // 3. Если результат <= 0, компонент скрывается
                // 4. Если результат > 0, показываем с qty = результат

                // Пример: unitsAssy = 4, excludedQty = 2 (Missing), tdrQty = 0
                // remaining = 4 - 2 - 0 = 2 → показываем с qty = 2

                // Пример: unitsAssy = 4, excludedQty = 0, tdrQty = 4
                // remaining = 4 - 0 - 4 = 0 → скрываем

                // Пример: unitsAssy = 4, excludedQty = 2 (Missing), tdrQty = 1
                // remaining = 4 - 2 - 1 = 1 → показываем с qty = 1

                $remaining = $unitsAssy - $excludedQty - $tdrQty;

                // Если результат <= 0, компонент скрывается
                if ($remaining <= 0) {
                    continue;
                }

                // Если результат > 0, показываем с qty = remaining
                $displayQty = $remaining;

                // Пропускаем компоненты с qty <= 0
                if ($displayQty <= 0) {
                    continue;
                }

                // Преобразуем в объект для совместимости с существующим кодом
                $componentObj = new \stdClass();
                $componentObj->ipl_num = $component['ipl_num'];
                $componentObj->part_number = $component['part_number'] ?? '';
                $componentObj->name = $component['description'] ?? '';
                $componentObj->qty = $displayQty;
                $componentObj->process_name = $processName;
                // Добавляем поле manual, если оно есть
                $componentObj->manual = $component['manual'] ?? null;
                $componentObj->eff_code = trim((string) ($component['eff_code'] ?? ''));

                $cad_components[] = $componentObj;
            }

            // Сортируем CAD компоненты: сначала по manual (если есть), потом по ipl_num
            usort($cad_components, function($a, $b) {
                // Сначала сравниваем по manual
                $manualA = $a->manual ?? '';
                $manualB = $b->manual ?? '';
                $manualCompare = strnatcasecmp($manualA, $manualB);
                if ($manualCompare !== 0) {
                    return $manualCompare;
                }

                // Если manual одинаковые, сравниваем по ipl_num
                $aParts = explode('-', $a->ipl_num ?? '');
                $bParts = explode('-', $b->ipl_num ?? '');

                // Сравниваем первую часть (до -)
                $aFirst = (int)($aParts[0] ?? 0);
                $bFirst = (int)($bParts[0] ?? 0);

                if ($aFirst !== $bFirst) {
                    return $aFirst - $bFirst;
                }

                // Если первая часть одинаковая, сравниваем вторую часть (после -)
                $aSecond = (int)($aParts[1] ?? 0);
                $bSecond = (int)($bParts[1] ?? 0);

                return $aSecond - $bSecond;
            });

            $form_number = 'CAD-STD';

            // Обновляем список процессов после возможного добавления новых
            $cad_processes = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $cad_ids)
                ->get();

            // Рассчитываем общее количество деталей на основе отфильтрованных компонентов
            $cadSum = $this->calcCadSumsFromComponents($cad_components);

            $cadFormCfg = config('tdr_forms.cadFormStd', []);
            $cadDefaultRows = (int) ($cadFormCfg['table_rows_default'] ?? 19);
            $cadRowsPerPage = (int) $request->query('cad_table_rows', $cadDefaultRows);
            $cadRowsPerPage = max(1, min(99, $cadRowsPerPage));
            $cad_table_pages = $this->buildStdProcessSheetTablePages($cad_components, $cadRowsPerPage);

            return view('admin.tdrs.cadFormStd', [
                    'current_wo' => $current_wo,
                    'manual' => $manual,
                    'cad_components' => $cad_components,
                    'cad_table_pages' => $cad_table_pages,
                    'cad_rows_per_page' => $cadRowsPerPage,
                    'cad_processes' => $cad_processes,
                    'form_number' => $form_number,
                    'manuals' => [$manual],
                    'process_name' => ProcessName::where('name', 'Cad plate')->first(),
                    'cadSum' => $cadSum,
                ] + $cad_ids);

        } catch (\Exception $e) {
            // \Log::error('Error in CAD processing:', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            throw $e;
        }
    }

    public function paintStd($workorder_id)
    {
        try {
            // Получаем рабочий заказ и связанные данные
            $current_wo = Workorder::findOrFail($workorder_id);
            $manual = $current_wo->unit->manuals;

            if (!$manual) {
                throw new \RuntimeException('Manual not found for this workorder');
            }

            // Получаем или создаем NdtCadCsv для данного workorder с автоматической загрузкой
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder_id);
            }

            // Получаем ID process names для Paint (ID = 25)
            $paintProcessName = ProcessName::find(25);

            if (!$paintProcessName) {
                throw new \RuntimeException('Paint process name not found (ID 25)');
            }

            // Извлекаем ID по именам
            $paint_ids = [
                'paint_name_id' => 25
            ];

            // Получаем manual processes через связь с processes
            $paint_processes = ManualProcess::where('manual_id', $manual->id)
                ->whereHas('process', function($query) use ($paint_ids) {
                    $query->where('process_names_id', $paint_ids['paint_name_id']);
                })
                ->with('process')
                ->get();

            // Получаем IPL номера компонентов, которые должны быть исключены из paintFormStd
            // Исключаем компоненты, присутствующие в TDR со статусами: Missing, Repair, Order New
            $excludedIplNumsPaint = [];

            // Получаем ID для Missing, Repair, Order New
            $missingCode = Code::where('name', 'Missing')->first();
            $repairCode = Code::where('name', 'Repair')->first();
            $orderNewNecessary = Necessary::where('name', 'Order New')->first();

            // Получаем TDR записи с этими статусами
            $excludedTdrQuery = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num');

            $excludedConditions = [];
            if ($missingCode) {
                $excludedConditions[] = ['codes_id', $missingCode->id];
            }
            if ($repairCode) {
                $excludedConditions[] = ['codes_id', $repairCode->id];
            }
            if ($orderNewNecessary) {
                $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
            }

            if (!empty($excludedConditions)) {
                $excludedTdrQuery->where(function($query) use ($excludedConditions) {
                    foreach ($excludedConditions as $condition) {
                        $query->orWhere($condition[0], $condition[1]);
                    }
                });

                $excludedTdrs = $excludedTdrQuery->get();
                foreach ($excludedTdrs as $tdr) {
                    if ($tdr->component && $tdr->component->ipl_num) {
                        // Нормализуем IPL номер для сравнения (убираем буквенные суффиксы)
                        $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                        if (!empty($normalizedIpl)) {
                            $excludedIplNumsPaint[$normalizedIpl] = true;
                        }
                    }
                }
            }

            // Получаем paint компоненты из NdtCadCsv
            // Примечание: QTY берется напрямую из CSV, все компоненты включаются без фильтрации
            $paint_components = collect($ndtCadCsv->paint_components ?? [])
                ->filter(function ($component) use ($excludedIplNumsPaint) {
                    $iplNum = $component['ipl_num'] ?? '';
                    // Нормализуем IPL номер для сравнения
                    $normalizedIpl = $this->normalizeIplNum($iplNum);
                    // Исключаем компоненты, присутствующие в TDR со статусами Missing, Repair, Order New
                    return !isset($excludedIplNumsPaint[$normalizedIpl]);
                })
                ->map(function ($component) use ($paint_processes, $manual) {
                $process = $paint_processes->first(function ($p) use ($component) {
                    return $p->process->id == $component['process'];
                });

                [$itemDisp, $partDisp, $descDisp] = $this->resolvePaintStdAssyDisplayFields($component, $manual);

                $obj = new \stdClass();
                $obj->ipl_num = $component['ipl_num'] ?? '';
                $obj->item_display = $itemDisp;
                $obj->part_number = $partDisp;
                $obj->name = $descDisp;
                $obj->process_name = $process ? $process->process->name :  $component['process'];
                // Количество в форме = значение QTY из CSV
                $obj->qty = (int)($component['qty'] ?? 1);
                // Добавляем поле manual, если оно есть
                $obj->manual = $component['manual'] ?? null;
                $obj->eff_code = trim((string) ($component['eff_code'] ?? ''));

                return $obj;
            })->toArray();

            // Сортируем Paint компоненты: сначала по manual (если есть), потом по ipl_num
            usort($paint_components, function($a, $b) {
                // Сначала сравниваем по manual
                $manualA = $a->manual ?? '';
                $manualB = $b->manual ?? '';
                $manualCompare = strnatcasecmp($manualA, $manualB);
                if ($manualCompare !== 0) {
                    return $manualCompare;
                }

                // Если manual одинаковые, сравниваем по ipl_num
                $aParts = explode('-', $a->ipl_num ?? '');
                $bParts = explode('-', $b->ipl_num ?? '');

                // Сравниваем первую часть (до -)
                $aFirst = (int)($aParts[0] ?? 0);
                $bFirst = (int)($bParts[0] ?? 0);

                if ($aFirst !== $bFirst) {
                    return $aFirst - $bFirst;
                }

                // Если первая часть одинаковая, сравниваем вторую часть (после -)
                $aSecond = (int)($aParts[1] ?? 0);
                $bSecond = (int)($bParts[1] ?? 0);

                return $aSecond - $bSecond;
            });

            // Генерируем номер формы
            $form_number = '014';

            // Рассчитываем общее количество деталей
            $paintSum = $this->calcPaintSums($workorder_id);

            // Разбиение на страницы теперь происходит на фронтенде через JavaScript
            // Передаём все компоненты без предварительного разбиения

            return view('admin.tdrs.paintFormStd', [
                    'current_wo' => $current_wo,
                    'manual' => $manual,
                    'paint_components' => $paint_components, // Передаём все компоненты напрямую
                    'paint_processes' => $paint_processes,
                    'form_number' => $form_number,
                    'manuals' => [$manual],
                    'process_name' => $paintProcessName,
                    'paintSum' => $paintSum,
                    // 'componentChunks' => $componentChunks, // Удалено - разбиение на фронтенде
                ] + $paint_ids);

        } catch (\Exception $e) {
            // \Log::error('Error in Paint processing:', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            throw $e;
        }
    }

    private function buildStdProcessSheetTablePages(array $components, int $rowsPerPage): array
    {
        $rowsPerPage = max(1, min(99, $rowsPerPage));
        $flat = [];
        $previousManual = null;
        foreach ($components as $component) {
            $currentManual = $component->manual ?? null;
            $shouldInsertManualRow = ($currentManual !== null && $currentManual !== '' && $currentManual !== $previousManual);
            if ($shouldInsertManualRow) {
                $flat[] = ['kind' => 'manual', 'text' => $currentManual];
            }
            $flat[] = ['kind' => 'data', 'component' => $component];
            $previousManual = $currentManual;
        }
        if ($flat === []) {
            $pages = [[]];
        } else {
            $pages = array_chunk($flat, $rowsPerPage);
        }
        $pageCount = count($pages);
        if ($pageCount >= 1) {
            $lastIdx = $pageCount - 1;
            $n = count($pages[$lastIdx]);
            $need = $rowsPerPage - $n;
            if ($need > 0 && $n > 0 && $need < $rowsPerPage) {
                for ($j = 0; $j < $need; $j++) {
                    $pages[$lastIdx][] = ['kind' => 'empty'];
                }
            }
        }

        return $pages;
    }

    public function stressStd(Request $request, $workorder_id)
    {
        try {
            // Получаем рабочий заказ и связанные данные
            $current_wo = Workorder::findOrFail($workorder_id);
            $manual = $current_wo->unit->manuals;

            if (!$manual) {
                throw new \RuntimeException('Manual not found for this workorder');
            }

            // Получаем или создаем NdtCadCsv для данного workorder с автоматической загрузкой
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder_id);
            }

            // Получаем ID process names для Stress (Bake Stress Realive)
            $processNames = ProcessName::where('id', 3)->pluck('id', 'name');

            if (!$processNames->count()) {
                throw new \RuntimeException('Stress process name not found');
            }

            // Извлекаем ID по именам
            $stress_ids = [
                'stress_name_id' => 3 // Bake (Stress Realive)
            ];

            // Получаем manual processes
            $manualProcesses = ManualProcess::where('manual_id', $manual->id)
                ->pluck('processes_id');

            // Получаем Stress processes
            $stress_processes = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $stress_ids)
                ->get();

            // Получаем существующие IPL номера
            $existingIplNums = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component')
                ->get()
                ->pluck('component.ipl_num')
                ->filter()
                ->unique()
                ->toArray();

            // Получаем все процессы для данного manual и process_name
            $validProcesses = Process::whereIn('id', $manualProcesses)
                ->where('process_names_id', $stress_ids['stress_name_id'])
                ->pluck('process')
                ->toArray();

            // Подготовка данных TDR и units_assy для расчёта остатков (STRESS)
            $tdrItemsStress = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num')
                ->get()
                ->map(function ($tdr) {
                    return [
                        'ipl_num' => $tdr->component->ipl_num ?? null,
                        'qty' => (int)($tdr->qty ?? 0),
                    ];
                })
                ->filter(function ($item) {
                    return !empty($item['ipl_num']) && $item['qty'] > 0;
                })
                ->values()
                ->toArray();

            // Мапа units_assy по IPL из Components всех manuals (с нормализацией IPL)
            // Приоритет: компоненты из текущего manual
            // Это необходимо, т.к. в CSV и TDR могут быть компоненты из других manuals
            $unitsAssyByIplStress = [];

            // Получаем все компоненты, сортируя так, чтобы сначала шли компоненты из текущего manual
            $allComponentsStress = Component::select('ipl_num', 'units_assy', 'manual_id')
                ->orderByRaw("CASE WHEN manual_id = ? THEN 0 ELSE 1 END", [$manual->id])
                ->get();

            foreach ($allComponentsStress as $component) {
                if ($component->ipl_num) {
                    $normalizedIpl = $this->normalizeIplNum($component->ipl_num);
                    if (!empty($normalizedIpl)) {
                        // Добавляем только если еще нет в мапе
                        // Благодаря сортировке, компоненты из текущего manual будут добавлены первыми
                        if (!isset($unitsAssyByIplStress[$normalizedIpl])) {
                            $num = (int)($component->units_assy ?? 1);
                            $unitsAssy = $num > 0 ? $num : 1;
                            $unitsAssyByIplStress[$normalizedIpl] = $unitsAssy;
                        }
                    }
                }
            }

            // Получаем количество компонентов со статусами Missing, Order New
            // Создаем мапу excludedQtyByIplStress - количество исключенных компонентов по IPL
            // ВАЖНО: Repair НЕ исключаем для Stress
            $excludedQtyByIplStress = [];

            // Получаем ID для Missing и Order New (Repair НЕ исключаем)
            $missingCode = Code::where('name', 'Missing')->first();
            $orderNewNecessary = Necessary::where('name', 'Order New')->first();

            // Получаем TDR записи с этими статусами
            $excludedTdrQuery = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num');

            $excludedConditions = [];
            if ($missingCode) {
                $excludedConditions[] = ['codes_id', $missingCode->id];
            }
            // Repair НЕ добавляем в исключения для Stress
            if ($orderNewNecessary) {
                $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
            }

            if (!empty($excludedConditions)) {
                $excludedTdrQuery->where(function($query) use ($excludedConditions) {
                    foreach ($excludedConditions as $condition) {
                        $query->orWhere($condition[0], $condition[1]);
                    }
                });

                $excludedTdrs = $excludedTdrQuery->get();
                // \Log::info('Stress Filtering - Excluded TDRs found', [
                //     'count' => $excludedTdrs->count(),
                //     'tdrs' => $excludedTdrs->map(function($tdr) {
                //         $code = $tdr->codes_id ? Code::find($tdr->codes_id) : null;
                //         $necessary = $tdr->necessaries_id ? Necessary::find($tdr->necessaries_id) : null;
                //         return [
                //             'id' => $tdr->id,
                //             'ipl_num' => $tdr->component->ipl_num ?? null,
                //             'codes_id' => $tdr->codes_id,
                //             'code_name' => $code ? $code->name : null,
                //             'necessaries_id' => $tdr->necessaries_id,
                //             'necessary_name' => $necessary ? $necessary->name : null,
                //             'qty' => $tdr->qty ?? 0,
                //         ];
                //     })->toArray()
                // ]);

                foreach ($excludedTdrs as $tdr) {
                    if ($tdr->component && $tdr->component->ipl_num) {
                        // Нормализуем IPL номер для сравнения (убираем буквенные суффиксы)
                        $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                        if (!empty($normalizedIpl)) {
                            // Суммируем количество исключенных компонентов
                            if (!isset($excludedQtyByIplStress[$normalizedIpl])) {
                                $excludedQtyByIplStress[$normalizedIpl] = 0;
                            }
                            $excludedQtyByIplStress[$normalizedIpl] += (int)($tdr->qty ?? 0);
                        }
                    }
                }

                // \Log::info('Stress Filtering - Excluded QTY by IPL', [
                //     'excluded_qty_count' => count($excludedQtyByIplStress),
                //     'excluded_qty_by_ipl' => $excludedQtyByIplStress
                // ]);
            }

            // Создаем мапу TDR items по IPL для быстрого поиска (с нормализацией)
            // ВАЖНО: учитываем только TDR записи БЕЗ статусов Missing, Order New
            $tdrItemsMapStress = [];
            foreach ($tdrItemsStress as $item) {
                $iplNum = $item['ipl_num'];
                // Нормализуем IPL номер для группировки
                $normalizedIpl = $this->normalizeIplNum($iplNum);
                if (!empty($normalizedIpl)) {
                    // Пропускаем компоненты со статусами Missing, Order New
                    // (они уже учтены в excludedQtyByIplStress)
                    if (isset($excludedQtyByIplStress[$normalizedIpl])) {
                        continue;
                    }

                    if (!isset($tdrItemsMapStress[$normalizedIpl])) {
                        $tdrItemsMapStress[$normalizedIpl] = 0;
                    }
                    $tdrItemsMapStress[$normalizedIpl] += $item['qty'];
                }
            }

            // Фильтруем компоненты из NdtCadCsv
            $stress_components = [];
            foreach ($ndtCadCsv->stress_components as $component) {
                $itemNo = $component['ipl_num'];
                $processName = $component['process'] ?? '';

                // Нормализуем IPL номер для сравнения
                $normalizedIpl = $this->normalizeIplNum($itemNo);

                // Проверяем и создаем процесс, если его нет
                if (!empty($processName)) {
                    // Проверяем существование процесса
                    $process = Process::where('process', $processName)
                        ->where('process_names_id', $stress_ids['stress_name_id'])
                        ->first();

                    if (!$process) {
                        // Создаем новый процесс
                        $process = Process::create([
                            'process' => $processName,
                            'process_names_id' => $stress_ids['stress_name_id']
                        ]);
                        // \Log::info('Created new stress process:', ['process' => $processName]);
                    }

                    // Проверяем привязку к manual
                    $manualProcess = ManualProcess::where('manual_id', $manual->id)
                        ->where('processes_id', $process->id)
                        ->first();

                    if (!$manualProcess) {
                        // Создаем привязку к manual
                        ManualProcess::create([
                            'manual_id' => $manual->id,
                            'processes_id' => $process->id
                        ]);
                        // \Log::info('Created manual-stress process binding:', [
                        //     'manual_id' => $manual->id,
                        //     'process_id' => $process->id
                        // ]);
                    }

                    // Обновляем список валидных процессов
                    $validProcesses[] = $processName;
                }

                if (!in_array($processName, $validProcesses)) {
                    // \Log::warning('Invalid stress process found in ModCsv:', [
                    //     'process' => $processName,
                    //     'item_no' => $itemNo,
                    //     'valid_processes' => $validProcesses
                    // ]);
                    continue;
                }

                // Получаем данные для расчета
                $csvQty = (int)($component['qty'] ?? self::DEFAULT_QTY);
                $tdrQty = $tdrItemsMapStress[$normalizedIpl] ?? 0; // Сумма QTY из TDR для этого IPL (нормализованного) БЕЗ статусов Missing, Order New
                $excludedQty = $excludedQtyByIplStress[$normalizedIpl] ?? 0; // Количество компонентов со статусами Missing, Order New

                // Определяем units_assy: если в CSV есть поле manual (manual->number),
                // ищем компонент в соответствующем manual, иначе используем общую мапу
                $unitsAssy = 1;
                if (!empty($component['manual'])) {
                    // Ищем manual по number из CSV (например, "32-11-12")
                    $componentManual = Manual::where('number', $component['manual'])->first();
                    if ($componentManual) {
                        // Ищем компонент в этом manual
                        $componentRecord = Component::where('manual_id', $componentManual->id)
                            ->where('ipl_num', $itemNo)
                            ->first();
                        if ($componentRecord && $componentRecord->units_assy) {
                            $num = (int)$componentRecord->units_assy;
                            $unitsAssy = $num > 0 ? $num : 1;
                        } else {
                            // Если не найдено в указанном manual, используем общую мапу
                            $unitsAssy = $unitsAssyByIplStress[$normalizedIpl] ?? 1;
                        }
                    } else {
                        // Если manual не найден, используем общую мапу
                        $unitsAssy = $unitsAssyByIplStress[$normalizedIpl] ?? 1;
                    }
                } else {
                    // Если поле manual отсутствует, используем общую мапу
                    $unitsAssy = $unitsAssyByIplStress[$normalizedIpl] ?? 1;
                }

                // Логика для Stress (после фильтрации по Missing, Order New, БЕЗ Repair):
                // 1. Вычитаем из unitsAssy количество исключенных компонентов (Missing, Order New)
                // 2. Затем вычитаем количество компонентов из TDR (без статусов Missing/Order New)
                // 3. Если результат <= 0, компонент скрывается
                // 4. Если результат > 0, показываем с qty = результат

                // Пример: unitsAssy = 4, excludedQty = 2 (Missing), tdrQty = 0
                // remaining = 4 - 2 - 0 = 2 → показываем с qty = 2

                // Пример: unitsAssy = 4, excludedQty = 0, tdrQty = 4
                // remaining = 4 - 0 - 4 = 0 → скрываем

                // Пример: unitsAssy = 4, excludedQty = 2 (Missing), tdrQty = 1
                // remaining = 4 - 2 - 1 = 1 → показываем с qty = 1

                $remaining = $unitsAssy - $excludedQty - $tdrQty;

                // Если результат <= 0, компонент скрывается
                if ($remaining <= 0) {
                    continue;
                }

                // Если результат > 0, показываем с qty = remaining
                $displayQty = $remaining;

                // Пропускаем компоненты с qty <= 0
                if ($displayQty <= 0) {
                    continue;
                }

                // Преобразуем в объект для совместимости с существующим кодом
                $componentObj = new \stdClass();
                $componentObj->ipl_num = $component['ipl_num'];
                $componentObj->part_number = $component['part_number'] ?? '';
                $componentObj->name = $component['description'] ?? '';
                $componentObj->qty = $displayQty;
                $componentObj->process_name = $processName;
                // Добавляем поле manual, если оно есть
                $componentObj->manual = $component['manual'] ?? null;
                $componentObj->eff_code = trim((string) ($component['eff_code'] ?? ''));

                $stress_components[] = $componentObj;
            }

            // Сортируем Stress компоненты: сначала по manual (если есть), потом по ipl_num
            usort($stress_components, function($a, $b) {
                // Сначала сравниваем по manual
                $manualA = $a->manual ?? '';
                $manualB = $b->manual ?? '';
                $manualCompare = strnatcasecmp($manualA, $manualB);
                if ($manualCompare !== 0) {
                    return $manualCompare;
                }

                // Если manual одинаковые, сравниваем по ipl_num
                $aParts = explode('-', $a->ipl_num ?? '');
                $bParts = explode('-', $b->ipl_num ?? '');

                // Сравниваем первую часть (до -)
                $aFirst = (int)($aParts[0] ?? 0);
                $bFirst = (int)($bParts[0] ?? 0);

                if ($aFirst !== $bFirst) {
                    return $aFirst - $bFirst;
                }

                // Если первая часть одинаковая, сравниваем вторую часть (после -)
                $aSecond = (int)($aParts[1] ?? 0);
                $bSecond = (int)($bParts[1] ?? 0);

                return $aSecond - $bSecond;
            });

            $form_number = 'STRESS-STD';

            // Обновляем список процессов после возможного добавления новых
            $stress_processes = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $stress_ids)
                ->get();

            // Рассчитываем общее количество деталей
            $stressSum = $this->calcStressSums($workorder_id);

            $stressFormCfg = config('tdr_forms.stressFormStd', []);
            $defaultRows = (int) ($stressFormCfg['table_rows_default'] ?? 21);
            $stressRowsPerPage = (int) $request->query('stress_table_rows', $defaultRows);
            $stressRowsPerPage = max(1, min(99, $stressRowsPerPage));
            $stress_table_pages = $this->buildStdProcessSheetTablePages($stress_components, $stressRowsPerPage);

            return view('admin.tdrs.stressFormStd', [
                    'current_wo' => $current_wo,
                    'manual' => $manual,
                    'stress_components' => $stress_components,
                    'stress_table_pages' => $stress_table_pages,
                    'stress_rows_per_page' => $stressRowsPerPage,
                    'stress_processes' => $stress_processes,
                    'form_number' => $form_number,
                    'manuals' => [$manual],
                    'process_name' => ProcessName::where('id', 3)->first(),
                    'stressSum' => $stressSum,
                ] + $stress_ids);

        } catch (\Exception $e) {
            // \Log::error('Error in Stress processing:', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            throw $e;
        }
    }

    public function specProcessForm(Request $request, $id)
    {
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);

        // Получаем данные о manual_id, связанном с этим Workorder
        $manual_id = $current_wo->unit->manual_id;

        // Получаем NDT суммы
        $ndtSums = $this->calcNdtSums($id);
        $cadSum = $this->calcCadSums($id);

        $proNameId = ProcessName::where('name', 'Cad plate')->value('id');

        $cadSum_ex = \App\Models\ExtraProcess::where('workorder_id', $current_wo->id)
            ->whereRaw("JSON_SEARCH(processes, 'one', CAST(? AS CHAR), NULL, '$[*].process_name_id') IS NOT NULL",[(string)$proNameId]
            )->sum('qty');

        $tdr_ws = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->with('component')
            ->get();
        // Извлекаем компоненты, связанные с manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        // EC в шапке: есть «только EC» (standalone_ec_only) ИЛИ старое правило (один EC на TDR без Machining/RIL)
        // Companion EC (Machining+RIL+EC) — в шапке EC не дублируем
        $ecProcessNameId = ProcessName::where('name', 'EC')->value('id');
        $ecEligibleIds = ProcessName::whereIn('name', ['Machining (EC)', 'Machining', 'Machining (Blend)', 'RIL'])->pluck('id');
        $showEcInForm = false;
        $tdrsForEcCheck = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->with('tdrProcesses')
            ->get();
        foreach ($tdrsForEcCheck as $tdr) {
            $procs = $tdr->tdrProcesses;
            $hasStandaloneEc = $ecProcessNameId && $procs->contains(
                fn ($p) => (int) $p->process_names_id === (int) $ecProcessNameId && $p->standalone_ec_only
            );
            if ($hasStandaloneEc) {
                $showEcInForm = true;
                break;
            }
            $hasEc = $procs->contains(fn($p) => (int)$p->process_names_id === (int)$ecProcessNameId);
            $hasMachiningOrRil = $procs->contains(fn($p) => $ecEligibleIds->contains((int)$p->process_names_id));
            if ($hasEc && ($procs->count() === 1 || !$hasMachiningOrRil)) {
                $showEcInForm = true;
                break;
            }
        }

        // Получаем все уникальные process_names_id из TdrProcess для данного workorder
        $processNameIds = TdrProcess::whereHas('tdr', function ($query) use ($current_wo) {
            $query->where('workorder_id', $current_wo->id)
                  ->where('use_process_forms', true);
        })->distinct()->pluck('process_names_id');

        // Получаем ProcessName по этим ID с фильтрами. EC включаем только если showEcInForm
        $processNamesQuery = ProcessName::forPicker()
            ->whereIn('id', $processNameIds)
            ->where('name', 'NOT LIKE', '%NDT%');
        if (!$showEcInForm) {
            $processNamesQuery->where('name', '!=', 'EC');
        }
        $processNames = $processNamesQuery->limit(20)->get();

        // Дополняем коллекцию до 10 элементов пустыми объектами, если элементов меньше
        $emptyProcess = new \stdClass();
        $emptyProcess->id = null;
        $emptyProcess->name = '';
        $emptyProcess->process_sheet_name = null;
        $emptyProcess->form_number = null;

        while ($processNames->count() < 10) {
            $processNames->push(clone $emptyProcess);
        }

        // Получаем Tdr, где use_process_form = true, с предварительной загрузкой TdrProcess
        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->with(['tdrProcesses' => function($query) {
                $query->whereHas('processName', function ($q) {
                        $q->where('show_in_process_picker', true);
                    })
                    ->orderBy('sort_order')
                    ->with('processName');
            }]) // Предварительная загрузка TdrProcess с сортировкой и processName
            ->with('component')
            ->get();

        // Получаем ID процессов с именем 'EC' для исключения из подсчёта number_line
        $ecProcessIds = ProcessName::where('name', 'LIKE', 'EC')->pluck('id');

        // Создаем коллекцию для результата
        $result = collect();

        // Обрабатываем каждый Tdr
        foreach ($tdrs as $tdr) {
            // Получаем связанные процессы (processName уже загружен)
            $groupedProcesses = $tdr->tdrProcesses;

            // Для данного TDR: companion EC — без отдельного номера; «только EC» — отдельный номер
            $procs = $groupedProcesses;
            $hasEc = $procs->contains(fn($p) => (int)$p->process_names_id === (int)$ecProcessNameId);
            $hasMachiningOrRil = $procs->contains(fn($p) => $ecEligibleIds->contains((int)$p->process_names_id));
            $showEcForThisTdr = $hasEc && ($procs->count() === 1 || !$hasMachiningOrRil);

            // Счётчик для number_line
            $lineNumber = 0;

            // Обрабатываем каждый процесс
            $groupedProcesses->each(function ($process) use (&$result, &$lineNumber, $tdr, $ecProcessIds, $showEcForThisTdr) {
                $isEcProcess = $ecProcessIds->contains($process->process_names_id);

                if ($isEcProcess) {
                    if ($process->standalone_ec_only) {
                        $lineNumber++;
                        $numberLine = $lineNumber;
                    } elseif ($showEcForThisTdr) {
                        $lineNumber++;
                        $numberLine = $lineNumber;
                    } else {
                        $numberLine = null;
                    }
                } else {
                    $lineNumber++;
                    $numberLine = $lineNumber;
                }

                $result->push([
                    'tdrs_id' => $tdr->id,
                    'process_name_id' => $process->process_names_id,
                    'number_line' => $numberLine,
                    'ec' => $process->ec,
                ]);
            });
        }
// Получаем все ID процессов, где name содержит 'NDT'
        $ndtIds = ProcessName::where('name', 'LIKE', '%NDT%')
            ->where('show_in_process_picker', true)
            ->pluck('id');

// Фильтруем коллекцию processes, оставляя только те записи, где process_name_id есть в $ndtIds
        $ndt_processes = $result->filter(function ($item) use ($ndtIds) {
            return $ndtIds->contains($item['process_name_id']);
        })->map(function ($item) {
            // Преобразуем каждую запись в нужный формат
            return [
                'tdrs_id' => $item['tdrs_id'],
                'number_line' => $item['number_line'],
            ];
        });

        // Quarantine: определяем для каждого TDR наличие и number_line
        $quarantineProcessNameId = ProcessName::where('name', 'Quarantine')->value('id');
        $quarantineByTdr = [];
        if ($quarantineProcessNameId) {
            foreach ($result->where('process_name_id', $quarantineProcessNameId) as $item) {
                $quarantineByTdr[$item['tdrs_id']] = $item['number_line'];
            }
        }

        // Разбиение по столбцам (макс. 6 на страницу). Детали с Quarantine = 2 столбца.
        $maxColumnsPerPage = 6;
        $componentChunks = collect();
        $currentChunk = collect();
        $currentColumns = 0;

        foreach ($tdr_ws as $component) {
            $hasQuarantine = isset($quarantineByTdr[$component->id]);
            $cols = $hasQuarantine ? 2 : 1;
            $quarantineNumberLine = $quarantineByTdr[$component->id] ?? null;

            if ($currentColumns + $cols > $maxColumnsPerPage && $currentChunk->isNotEmpty()) {
                $componentChunks->push($currentChunk);
                $currentChunk = collect();
                $currentColumns = 0;
            }

            $currentChunk->push((object)[
                'component' => $component,
                'columns' => $cols,
                'hasQuarantine' => $hasQuarantine,
                'quarantineNumberLine' => $quarantineNumberLine,
            ]);
            $currentColumns += $cols;
        }
        if ($currentChunk->isNotEmpty()) {
            $componentChunks->push($currentChunk);
        }

        // Передаем данные в представление
        return view('admin.tdrs.specProcessForm', [
            'current_wo' => $current_wo,
            'processes' => $result, // Исходная коллекция
            'ndt_processes' => $ndt_processes, // Отфильтрованная коллекция
            'ndtSums' => $ndtSums, // Добавляем NDT суммы в представление
            'cadSum' => $cadSum,
            'componentChunks' => $componentChunks,
        ], compact('tdrs', 'tdr_ws','processNames','cadSum_ex'));
    }

    public function specProcessFormEmp(Request $request, $id)
    {
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);

        // Получаем данные о manual_id, связанном с этим Workorder
        $manual_id = $current_wo->unit->manual_id;

        // Получаем NDT суммы
        $ndtSums = $this->calcNdtSums($id);
        $cadSum = $this->calcCadSums($id);

        $proNameId = ProcessName::where('name', 'Cad plate')->value('id');

        $cadSum_ex = \App\Models\ExtraProcess::where('workorder_id', $current_wo->id)
            ->whereRaw("JSON_SEARCH(processes, 'one', CAST(? AS CHAR), NULL, '$[*].process_name_id') IS NOT NULL",[(string)$proNameId]
            )->sum('qty');

        $tdr_ws = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->with('component')
            ->get();
        // Извлекаем компоненты, связанные с manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        $ecProcessNameIdEmp = ProcessName::where('name', 'EC')->value('id');
        $ecEligibleIdsEmp = ProcessName::whereIn('name', ['Machining (EC)', 'Machining', 'Machining (Blend)', 'RIL'])->pluck('id');
        $showEcInFormEmp = false;
        $tdrsForEcCheckEmp = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->with('tdrProcesses')
            ->get();
        foreach ($tdrsForEcCheckEmp as $tdrEc) {
            $procsEc = $tdrEc->tdrProcesses;
            if ($ecProcessNameIdEmp && $procsEc->contains(
                fn ($p) => (int) $p->process_names_id === (int) $ecProcessNameIdEmp && $p->standalone_ec_only
            )) {
                $showEcInFormEmp = true;
                break;
            }
            $hasEcE = $procsEc->contains(fn($p) => (int)$p->process_names_id === (int)$ecProcessNameIdEmp);
            $hasM = $procsEc->contains(fn($p) => $ecEligibleIdsEmp->contains((int)$p->process_names_id));
            if ($hasEcE && ($procsEc->count() === 1 || ! $hasM)) {
                $showEcInFormEmp = true;
                break;
            }
        }

        // Получаем все уникальные process_names_id из TdrProcess для данного workorder
        $processNameIds = TdrProcess::whereHas('tdr', function ($query) use ($current_wo) {
            $query->where('workorder_id', $current_wo->id)
                  ->where('use_process_forms', true);
        })->distinct()->pluck('process_names_id');

        // Получаем ProcessName по этим ID с фильтрами, ограничиваем до 20 элементов
        $processNamesQueryEmp = ProcessName::forPicker()
            ->whereIn('id', $processNameIds)
            ->where('name', 'NOT LIKE', '%NDT%');
        if (! $showEcInFormEmp) {
            $processNamesQueryEmp->where('name', '!=', 'EC');
        }
        $processNames = $processNamesQueryEmp
            ->limit(20)
            ->get();

        // Если пустая форма (use_process_forms=0) — подставляем названия процессов по умолчанию
        // Порядок: Machining → Stress Relief → Cad Plate → Chrome Plate → Paint (NDT — отдельный блок из 3 строк)
        if ($processNames->isEmpty()) {
            $defaultNames = ['Machining', 'Bake (Stress relief)', 'Cad plate', 'Chrome plate'];
            $processNames = ProcessName::forPicker()
                ->whereIn('name', $defaultNames)
                ->orderByRaw("FIELD(name, 'Machining', 'Bake (Stress relief)', 'Cad plate', 'Chrome plate')")
                ->get();
            $paint = ProcessName::forPicker()->where('name', 'LIKE', 'Paint%')->first();
            if ($paint) {
                $processNames->push($paint);
            }
            // Если в БД нет — используем заглушки для отображения
            if ($processNames->isEmpty()) {
                $fallbackNames = ['Machining', 'Stress Relief', 'Cad Plate', 'Chrome Plate', 'Paint'];
                $processNames = collect();
                foreach ($fallbackNames as $n) {
                    $obj = new \stdClass();
                    $obj->id = null;
                    $obj->name = $n;
                    $obj->process_sheet_name = null;
                    $obj->form_number = null;
                    $processNames->push($obj);
                }
            }
        }

        // Дополняем коллекцию до 10 элементов пустыми объектами, если элементов меньше
        $emptyProcess = new \stdClass();
        $emptyProcess->id = null;
        $emptyProcess->name = '';
        $emptyProcess->process_sheet_name = null;
        $emptyProcess->form_number = null;

        while ($processNames->count() < 10) {
            $processNames->push(clone $emptyProcess);
        }

        // Получаем Tdr, где use_process_form = true, с предварительной загрузкой TdrProcess
        $tdrs = Tdr::where('workorder_id', $current_wo->id)
            ->where('use_process_forms', true)
            ->with(['tdrProcesses' => function($query) {
                $query->whereHas('processName', function ($q) {
                        $q->where('show_in_process_picker', true);
                    })
                    ->orderBy('sort_order')
                    ->with('processName');
            }])
            ->with('component')
            ->get();

        // Получаем ID процессов с именем 'EC' для исключения из подсчёта number_line
        $ecProcessIds = ProcessName::where('name', 'LIKE', 'EC')->pluck('id');

        // Создаем коллекцию для результата (как в specProcessForm)
        $result = collect();
        $ecNameId = ProcessName::where('name', 'EC')->value('id');
        $ecElig = ProcessName::whereIn('name', ['Machining (EC)', 'Machining', 'Machining (Blend)', 'RIL'])->pluck('id');
        foreach ($tdrs as $tdr) {
            $groupedProcesses = $tdr->tdrProcesses;
            $procs = $groupedProcesses;
            $hasEc = $procs->contains(fn($p) => (int) $p->process_names_id === (int) $ecNameId);
            $hasMachiningOrRil = $procs->contains(fn($p) => $ecElig->contains((int) $p->process_names_id));
            $showEcForThisTdr = $hasEc && ($procs->count() === 1 || ! $hasMachiningOrRil);
            $lineNumber = 0;
            $groupedProcesses->each(function ($process) use (&$result, &$lineNumber, $tdr, $ecProcessIds, $showEcForThisTdr) {
                $isEcProcess = $ecProcessIds->contains($process->process_names_id);
                if ($isEcProcess) {
                    if ($process->standalone_ec_only) {
                        $lineNumber++;
                        $num = $lineNumber;
                    } elseif ($showEcForThisTdr) {
                        $lineNumber++;
                        $num = $lineNumber;
                    } else {
                        $num = null;
                    }
                } else {
                    $lineNumber++;
                    $num = $lineNumber;
                }
                $result->push([
                    'tdrs_id' => $tdr->id,
                    'process_name_id' => $process->process_names_id,
                    'number_line' => $num,
                    'ec' => $process->ec,
                ]);
            });
        }

        $ndtIds = ProcessName::where('name', 'LIKE', '%NDT%')
            ->where('show_in_process_picker', true)
            ->pluck('id');
        $ndt_processes = $result->filter(function ($item) use ($ndtIds) {
            return $ndtIds->contains($item['process_name_id']);
        })->map(function ($item) {
            return [
                'tdrs_id' => $item['tdrs_id'],
                'number_line' => $item['number_line'],
            ];
        });

        // Quarantine: определяем для каждого TDR наличие и number_line
        $quarantineProcessNameId = ProcessName::where('name', 'Quarantine')->value('id');
        $quarantineByTdr = [];
        if ($quarantineProcessNameId) {
            foreach ($result->where('process_name_id', $quarantineProcessNameId) as $item) {
                $quarantineByTdr[$item['tdrs_id']] = $item['number_line'];
            }
        }

        // Разбиение по столбцам (макс. 6 на страницу). Детали с Quarantine = 2 столбца.
        $maxColumnsPerPage = 6;
        $componentChunks = collect();
        $currentChunk = collect();
        $currentColumns = 0;

        foreach ($tdr_ws as $component) {
            $hasQuarantine = isset($quarantineByTdr[$component->id]);
            $cols = $hasQuarantine ? 2 : 1;
            $quarantineNumberLine = $quarantineByTdr[$component->id] ?? null;

            if ($currentColumns + $cols > $maxColumnsPerPage && $currentChunk->isNotEmpty()) {
                $componentChunks->push($currentChunk);
                $currentChunk = collect();
                $currentColumns = 0;
            }

            $currentChunk->push((object)[
                'component' => $component,
                'columns' => $cols,
                'hasQuarantine' => $hasQuarantine,
                'quarantineNumberLine' => $quarantineNumberLine,
            ]);
            $currentColumns += $cols;
        }
        if ($currentChunk->isNotEmpty()) {
            $componentChunks->push($currentChunk);
        }

        // Если use_process_forms = 0 (нет TDR с формами) — показываем пустую форму-шаблон
        if ($componentChunks->isEmpty()) {
            $componentChunks->push(collect());
        }

        // Передаем данные в представление
        return view('admin.tdrs.specProcessFormEmp', [
            'current_wo' => $current_wo,
            'processes' => $result,
            'ndt_processes' => $ndt_processes,
            'ndtSums' => $ndtSums,
            'cadSum' => $cadSum,
            'componentChunks' => $componentChunks,
        ], compact('tdrs', 'tdr_ws','processNames','cadSum_ex'));
    }

    public function logCardForm(Request $request, $id)
    {
//    dd($request, $id);
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);
        // Получаем данные о manual, связанном с этим Workorder
        $manual = $current_wo->unit->manual_id;
        $manual_wo = $current_wo->unit->manuals;
        $builders = Builder::all();
// dd($manual);
        $manuals = Manual::where('id', $manual)
            ->with('builder')
            ->get();

//dd($manuals, $manual);

// Получаем CSV-файл с process_type = 'log'
        $csvMedia = $manual_wo->getMedia('csv_files')->first(function ($media) {
            return $media->getCustomProperty('process_type') === self::PROCESS_TYPE_LOG;
        });

//    dd($csvMedia);

        return view('admin.tdrs.logCardForm', compact('current_wo','manuals', 'builders'));

    }

    public function tdrForm(Request $request, $id)
    {
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);

        // Получаем данные о manual_id, связанном с этим Workorder
        $manual_id = $current_wo->unit->manual_id;

        // Извлекаем компоненты, связанные с manual_id
        $components = Component::where('manual_id', $manual_id)->get();

        // Загружаем необходимые данные для всех записей
        $necessaries = Necessary::all();
        $conditions = Condition::all();
        $codes = Code::all();

        // Жадная загрузка данных для tdrs, включая все связи с компонентами, состояниями, необходимостями и кодами
        $current_wo->load('tdrs.component', 'tdrs.conditions', 'tdrs.necessaries', 'tdrs.codes');

        $necessary = Necessary::where('name', 'Order New')->first();
        $code = Code::where('name', 'Missing')->first();

        // Массивы для хранения разных типов строк
        $nullComponentConditions = []; // Для строк, где component_id == null
        $groupedByConditions = []; // Для строк, где component_id !== null и necessaries_id == Order New
        $necessaryComponents = []; // Для строк, где component_id !== null и necessaries_id !== Order New
        $hasMissingComponents = false; // Флаг наличия компонентов с кодом Missing

        foreach ($current_wo->tdrs as $tdr) {
            // Проверяем наличие компонентов с кодом Missing
            if ($tdr->codes_id == $code->id) {
                $hasMissingComponents = true;
                continue; // Пропускаем обработку этих строк, но запоминаем их наличие
            }

            // Строки с component_id == null
            if ($tdr->component_id === null) {
                $conditions = $tdr->conditions; // Получаем данные о состоянии
                if ($conditions) {
                    $description = trim((string) $tdr->description);

                    // Проверяем, является ли имя condition одним из "note 1", "note 2" и т.д.
                    $isNoteCondition = preg_match('/^note\s+\d+$/i', $conditions->name);

                    if ($isNoteCondition) {
                        // Для conditions с именами "note 1", "note 2" и т.д. добавляем только description
                        // Если description пустой, не добавляем ничего (чтобы не показывать пустые строки)
                        if ($description !== '') {
                            $nullComponentConditions[] = $description;
                        } else {
                            // Не добавляем пустую строку - если нет notes, то и нечего показывать
                        }
                    } else {
                        // Для обычных conditions добавляем имя и description
                        $conditionString = $conditions->name;
                        if ($description !== '') {
                            $conditionString .= ' ' . $description;
                        }
                        $nullComponentConditions[] = $conditionString;
                    }
                } else {
                    \Log::warning("TDR has null component_id but no conditions relation", [
                        'tdr_id' => $tdr->id
                    ]);
                }
            } elseif ($tdr->component_id !== null && $tdr->necessaries_id == $necessary->id) {
                // Группируем компоненты по состояниям, если necessaries_id == 2 ('Order New')
                $component = $tdr->component; // Получаем связанные данные о компоненте
                $conditions = $tdr->conditions; // Получаем связанные данные о состоянии
                if ($component && $conditions) {
                    // Формируем строку для компонента
                    if (!empty($tdr->description)) {
                        // Если description не пустой, выводим с описанием
                        $componentString = sprintf(
                            "(%s%s)<b> %s </b>: ( %s)", // Номер компонента и его имя
                            strtoupper($component->ipl_num), // Номер компонента
                            $tdr->qty == 1 ? '' : ', ' . $tdr->qty . 'pcs', //Если qty == 1, то пустая строка, иначе добавляем qty и "pcs"
                            strtoupper($component->name), // Имя компонента
                            strtoupper($tdr->description),
                        );
                    } else {
                        // Если description пустой или null, выводим без описания
                        $componentString = sprintf(
                            "(%s%s)<b> %s </b> ", // Номер компонента и его имя
                            strtoupper($component->ipl_num), // Номер компонента
                            $tdr->qty == 1 ? '' : ', ' . $tdr->qty . 'pcs', //Если qty == 1, то пустая строка, иначе добавляем qty и "pcs"
                            strtoupper($component->name), // Имя компонента
                        );
                    }

                    // Инициализируем массив для состояния, если он еще не существует
                    if (!isset($groupedByConditions[$conditions->name])) {
                        $groupedByConditions[$conditions->name] = [];
                    }

                    // Получаем последнюю строку в группе
                    $lastKey = count($groupedByConditions[$conditions->name]) - 1;
                    $lastString = $lastKey >= 0 ? $groupedByConditions[$conditions->name][$lastKey] : '';

                    // Проверяем длину строки
                    if (strlen($lastString . ', ' . $componentString) <= 120) {
                        // Если длина не превышает 120 символов, добавляем к последней строке
                        if ($lastKey >= 0) {
                            $groupedByConditions[$conditions->name][$lastKey] .= ', ' . $componentString;
                        } else {
                            $groupedByConditions[$conditions->name][] = $conditions->name . ' (scrap): ' . $componentString;
                        }
                    } else {
                        // Если длина превышает 120 символов, создаем новую строку
                        $groupedByConditions[$conditions->name][] = $conditions->name . ' (scrap): ' . $componentString;
                    }
                }
            } elseif ($tdr->component_id !== null && $tdr->necessaries_id !== $necessary->id) {
                // Для всех остальных компонентов, где necessaries_id != Order New
                $component = $tdr->component; // Получаем данные о компоненте
                $necessaries = $tdr->necessaries; // Получаем данные о необходимости
                $codes = $tdr->codes; // Получаем данные о кодах
                $description = $tdr->description; // Description
                if ($component && $necessaries && $codes) {
                    // Строим строку в нужном формате
                    if (!empty($description)) {
                        // Если description не пустой, выводим с описанием
                        $necessaryComponents[] = sprintf(
                            "(%s) <b>%s</b> IS NECESSARY: %s - %s ( %s )", // Формат вывода
                            strtoupper($component->ipl_num), // Номер компонента
                            strtoupper($component->name), // Имя компонента
                            strtoupper($necessaries->name), // Название необходимости
                            strtoupper($codes->name), // Название кода
                            strtoupper($description), // Название кода
                        );
                    } else {
                        // Если description пустой или null, выводим без описания
                        $necessaryComponents[] = sprintf(
                            "(%s) <b>%s</b> IS NECESSARY: %s - %s ", // Формат вывода
                            strtoupper($component->ipl_num), // Номер компонента
                            strtoupper($component->name), // Имя компонента
                            strtoupper($necessaries->name), // Название необходимости
                            strtoupper($codes->name), // Название кода
                        );
                    }
                }
            }

        }

        // Если есть компоненты с кодом Missing, добавляем строку в $nullComponentConditions
        if ($hasMissingComponents) {
            $missingCondition = Condition::where('name', 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')->first();
            if ($missingCondition) {
                $nullComponentConditions[] = $missingCondition->name;
            }
        }

// Объединяем все строки в правильном порядке
        $tdrInspections = [];

// Добавляем строки с component_id == null
        if (!empty($nullComponentConditions)) {
            $tdrInspections = array_merge($tdrInspections, $nullComponentConditions);
        }

// Добавляем группированные строки по состояниям
        foreach ($groupedByConditions as $conditionName => $components) {
            foreach ($components as $componentLine) {
                $tdrInspections[] = $componentLine;
            }
        }

// Добавляем строки с necessaries_id != Order New
        if (!empty($necessaryComponents)) {
            $tdrInspections = array_merge($tdrInspections, $necessaryComponents);
        }

//// Выводим результат
//        foreach ($tdrInspections as $inspection) {
//            echo $inspection . "\n";
//        }

        // Возвращаем данные в представление
        return view('admin.tdrs.tdrForm', compact('current_wo', 'components',
            'necessaries', 'conditions', 'codes', 'tdrInspections'));
    }

    public function wo_Process_Form($id)
    {
        $current_wo = Workorder::findOrFail($id);

        return view('admin.tdrs.wo_ProcessForm', compact('current_wo'));
    }

    public function wo_BoxTitle($id)
    {
        $current_wo = Workorder::findOrFail($id);


//        $unit_wo = Unit::where('id', $current_wo->unit_id)->get();
//        $unit_pn = $unit_wo->part_number;
        $units = Unit::all();
        $customers = Customer::all();
        $users = \App\Models\User::all();


        return view('admin.tdrs.wo_BoxTitle', compact('current_wo', 'units', 'customers', 'users'));
    }

    private function calcNdtSums(int $workorder_id): array
    {
        // Инициализация счетчиков
        $total = 0;
        $mpi = 0;
        $fpi = 0;

        try {
            // Получение рабочего заказа и связанных данных
            $current_wo = Workorder::findOrFail($workorder_id);
            $manual = $current_wo->unit->manuals;

            if (!$manual) {
                // \Log::error('Manual not found for workorder', ['workorder_id' => $workorder_id]);
                return ['total' => 0, 'mpi' => 0, 'fpi' => 0];
            }

            // Получение данных из таблицы ndt_cad_csv
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                // \Log::info('NdtCadCsv not found for workorder, creating new record', [
                //     'workorder_id' => $workorder_id,
                //     'workorder_number' => $current_wo->number ?? 'unknown'
                // ]);

                // Создаем новую запись NdtCadCsv с автоматической загрузкой из Manual
                $ndtCadCsv = NdtCadCsv::createForWorkorder($workorder_id);

                if (!$ndtCadCsv) {
                    // \Log::warning('Failed to create NdtCadCsv record', ['workorder_id' => $workorder_id]);
                    return ['total' => 0, 'mpi' => 0, 'fpi' => 0];
                }
            }

            // \Log::info('Found NdtCadCsv record', [
            //     'ndt_cad_csv_id' => $ndtCadCsv->id,
            //     'workorder_id' => $workorder_id
            // ]);

            // Получение NDT компонентов из JSON поля
            $ndtComponents = $ndtCadCsv->ndt_components ?? [];

            if (empty($ndtComponents)) {
                // \Log::info('No NDT components found in NdtCadCsv', ['workorder_id' => $workorder_id]);
                return ['total' => 0, 'mpi' => 0, 'fpi' => 0];
            }

            $ndtStdQtyMaps = $this->ndtStdExcludedAndTdrQtyByNormalizedIpl($workorder_id);
            $excludedQtyByIpl = $ndtStdQtyMaps['excluded'];
            $tdrItemsMap = $ndtStdQtyMaps['tdr'];
            $unitsAssyByIpl = $this->buildUnitsAssyByNormalizedIplMap($manual);

            // \Log::info('Processing NDT components from ndt_cad_csv table (with filtering)', [
            //     'workorder_id' => $workorder_id,
            //     'components_count' => count($ndtComponents),
            //     'excluded_qty_count' => count($excludedQtyByIpl),
            //     'tdr_items_count' => count($tdrItemsMap)
            // ]);

            // Обработка NDT компонентов из JSON поля (та же логика, что в ndtStd)
            foreach ($ndtComponents as $index => $component) {
                // Проверяем наличие обязательных полей
                if (!isset($component['ipl_num']) || empty($component['ipl_num'])) {
                    continue;
                }

                $iplNum = $component['ipl_num'];
                $normalizedIpl = $this->normalizeIplNum($iplNum);

                // Получаем данные для расчета
                $csvQty = (int) ($component['qty'] ?? self::DEFAULT_QTY);
                $tdrQty = $tdrItemsMap[$normalizedIpl] ?? 0;
                $excludedQty = $excludedQtyByIpl[$normalizedIpl] ?? 0;
                $stdQty = max(1, $csvQty);
                $unitsAssy = $this->resolveNdtStdUnitsAssyForRow($component, $iplNum, $normalizedIpl, $unitsAssyByIpl);
                $baseQty = min($stdQty, $unitsAssy);
                $remaining = $baseQty - $excludedQty - $tdrQty;

                // Если результат <= 0, компонент скрывается
                if ($remaining <= 0) {
                    continue;
                }

                $displayQty = $remaining;

                // Получаем процесс для определения MPI/FPI
                $process = $component['process'] ?? self::DEFAULT_PROCESS;

                // Вычисление сумм
                $total += $displayQty;

                if (strpos($process, '1') !== false) {
                    $mpi += $displayQty;
                } else {
                    $fpi += $displayQty;
                }
            }

            // \Log::info('NDT sums calculated from ndt_cad_csv table (with filtering)', [
            //     'workorder_id' => $workorder_id,
            //     'total' => $total,
            //     'mpi' => $mpi,
            //     'fpi' => $fpi
            // ]);

            return [
                'total' => $total,
                'mpi' => $mpi,
                'fpi' => $fpi
            ];

        } catch (\Exception $e) {
            // \Log::error('Ошибка при обработке NDT компонентов из таблицы ndt_cad_csv:', [
            //     'workorder_id' => $workorder_id,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return ['total' => 0, 'mpi' => 0, 'fpi' => 0];
        }
    }

    private function calcCadSumsFromComponents($cad_components)
    {
        try {
            $totalQty = 0;
            $totalComponents = 0;
            $componentList = [];

            foreach ($cad_components as $component) {
                $qty = (int)($component->qty ?? 1);
                $totalQty += $qty;
                $totalComponents++;
                $componentList[] = [
                    'ipl_num' => $component->ipl_num ?? '',
                    'qty' => $qty
                ];
            }

            // \Log::info('CAD calcCadSumsFromComponents - Summary', [
            //     'total_components' => $totalComponents,
            //     'total_qty' => $totalQty,
            //     'components_count' => count($componentList),
            //     'components' => $componentList,
            //     'ipl_numbers' => array_column($componentList, 'ipl_num')
            // ]);

            return [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents
            ];
        } catch (\Exception $e) {
            // \Log::error('Error in CAD sums calculation from components:', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return [
                'total_qty' => 0,
                'total_components' => 0
            ];
        }
    }

    private function calcCadSums($workorder_id)
    {
        try {
            // Получаем текущий workorder
            $current_wo = Workorder::findOrFail($workorder_id);

            // \Log::info('Starting CAD sums calculation', [
            //     'workorder_id' => $workorder_id
            // ]);

            // 1. Получаем данные из таблицы ndt_cad_csv
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                // \Log::error('NdtCadCsv not found for workorder', ['workorder_id' => $workorder_id]);
                return [
                    'total_qty' => 0,
                    'total_components' => 0
                ];
            }

            // \Log::info('Found NdtCadCsv record', [
            //     'ndt_cad_csv_id' => $ndtCadCsv->id,
            //     'cad_components_count' => count($ndtCadCsv->cad_components ?? [])
            // ]);

            // Получаем CAD компоненты из JSON поля
            $cadComponents = $ndtCadCsv->cad_components ?? [];

            if (empty($cadComponents)) {
                // \Log::warning('No CAD components found in NdtCadCsv');
                return [
                    'total_qty' => 0,
                    'total_components' => 0
                ];
            }

            // 1.5. Получаем валидные процессы (упрощенная логика - все процессы из CSV считаются валидными)
            // В cadStd процессы создаются динамически, поэтому в calcCadSums мы просто принимаем все процессы из CSV
            $validProcesses = [];
            foreach ($cadComponents as $component) {
                $processName = $component['process'] ?? '';
                if (!empty($processName) && !in_array($processName, $validProcesses)) {
                    $validProcesses[] = $processName;
                }
            }

            // \Log::info('CAD calcCadSums - Valid processes (from CSV)', [
            //     'valid_processes_count' => count($validProcesses),
            //     'valid_processes' => $validProcesses
            // ]);

            // 2. Получаем ID для Missing, Repair, Order New (та же логика, что в cadStd)
            $missingCode = Code::where('name', 'Missing')->first();
            $repairNecessary = Necessary::find(1);
            if (!$repairNecessary || stripos($repairNecessary->name, 'repair') === false) {
                $repairNecessary = Necessary::where('name', 'Repair')->first();
                if (!$repairNecessary) {
                    $repairNecessary = Necessary::where('name', 'REPAIR')->first();
                }
                if (!$repairNecessary) {
                    $repairNecessary = Necessary::where('name', 'repair')->first();
                }
            }
            $orderNewNecessary = Necessary::find(2);
            if (!$orderNewNecessary || stripos($orderNewNecessary->name, 'order') === false) {
                $orderNewNecessary = Necessary::where('name', 'Order New')->first();
            }

            // Получаем TDR записи только с исключаемыми статусами (Missing, Repair, Order New)
            $excludedTdrQuery = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num');

            $excludedConditions = [];
            if ($missingCode) {
                $excludedConditions[] = ['codes_id', $missingCode->id];
            }
            if ($repairNecessary) {
                $excludedConditions[] = ['necessaries_id', $repairNecessary->id];
            }
            if ($orderNewNecessary) {
                $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
            }

            // Создаем мапу исключенных IPL номеров (только с Missing/Repair/Order New)
            $excludedIplNums = [];
            if (!empty($excludedConditions)) {
                $excludedTdrQuery->where(function($query) use ($excludedConditions) {
                    foreach ($excludedConditions as $condition) {
                        $query->orWhere($condition[0], $condition[1]);
                    }
                });

                $excludedTdrs = $excludedTdrQuery->get();
                foreach ($excludedTdrs as $tdr) {
                    if ($tdr->component && $tdr->component->ipl_num) {
                        $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                        if (!empty($normalizedIpl)) {
                            $excludedIplNums[$normalizedIpl] = true;
                        }
                    }
                }
            }

            // Получаем все TDR компоненты для создания мапы (для логирования)
            $tdrComponents = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component')
                ->get();

            // Создаем мапу IPL номеров из TDR (только для логирования)
            $tdrIplMap = [];
            foreach ($tdrComponents as $tdr) {
                if ($tdr->component && $tdr->component->ipl_num) {
                    $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                    if (!empty($normalizedIpl)) {
                        if (!isset($tdrIplMap[$normalizedIpl])) {
                            $tdrIplMap[$normalizedIpl] = 0;
                        }
                        $tdrIplMap[$normalizedIpl] += (int)($tdr->qty ?? 0);
                    }
                }
            }

            // \Log::info('TDR Components (normalized):', [
            //     'count' => count($tdrIplMap),
            //     'ipl_numbers' => array_keys($tdrIplMap),
            //     'excluded_ipl_count' => count($excludedIplNums),
            //     'excluded_ipls' => array_keys($excludedIplNums)
            // ]);

            // 3. Сравнение и подсчет
            $totalQty = 0;
            $totalComponents = 0;
            $processedIpls = [];
            $skippedCount = 0;
            $skippedByProcess = 0;
            $combinedSkippedCount = 0;

            // \Log::info('Starting CAD calculation loop', [
            //     'total_cad_components' => count($cadComponents),
            //     'tdr_ipl_count' => count($tdrIplMap)
            // ]);

            foreach ($cadComponents as $index => $component) {
                $itemNo = trim($component['ipl_num'] ?? '');
                $qty = (int)($component['qty'] ?? 1); // Получаем qty из JSON
                $processName = $component['process'] ?? '';

                // Нормализуем IPL номер для сравнения (5-90A -> 5-90)
                $normalizedIpl = $this->normalizeIplNum($itemNo);

                // \Log::debug('Processing CAD component', [
                //     'index' => $index,
                //     'item_no' => $itemNo,
                //     'normalized_ipl' => $normalizedIpl,
                //     'qty' => $qty,
                //     'process' => $processName,
                //     'component' => $component
                // ]);

                // Исключаем компоненты только с статусами Missing, Repair, Order New
                if (!empty($normalizedIpl) && isset($excludedIplNums[$normalizedIpl])) {
                    $skippedCount++;
                    // \Log::debug('Skipping component as it has excluded status (Missing/Repair/Order New):', [
                    //     'ipl_num' => $itemNo,
                    //     'normalized_ipl' => $normalizedIpl
                    // ]);
                    continue;
                }

                // Исключаем компоненты с невалидными процессами (та же логика, что в cadStd)
                if (!empty($processName) && !in_array($processName, $validProcesses)) {
                    $skippedByProcess++;
                    // \Log::debug('Skipping component as it has invalid process:', [
                    //     'ipl_num' => $itemNo,
                    //     'process' => $processName,
                    //     'valid_processes' => $validProcesses
                    // ]);
                    continue;
                }

                // Если нормализованный IPL номер еще не был обработан (используем нормализованный для проверки дубликатов)
                if (!empty($normalizedIpl) && !in_array($normalizedIpl, $processedIpls)) {
                    $totalQty += $qty; // Используем qty из JSON
                    $totalComponents++;
                    $processedIpls[] = $normalizedIpl; // Сохраняем нормализованный IPL
                    // \Log::debug('Adding component from NdtCadCsv:', [
                    //     'ipl_num' => $itemNo,
                    //     'normalized_ipl' => $normalizedIpl,
                    //     'qty' => $qty
                    // ]);
                } else if (!empty($normalizedIpl) && in_array($normalizedIpl, $processedIpls)) {
                    // \Log::debug('Skipping duplicate component (normalized):', [
                    //     'ipl_num' => $itemNo,
                    //     'normalized_ipl' => $normalizedIpl
                    // ]);
                }
            }

            // \Log::info('CAD calculation loop completed', [
            //     'total_qty' => $totalQty,
            //     'total_components' => $totalComponents,
            //     'skipped_count' => $skippedCount,
            //     'combined_skipped_count' => $combinedSkippedCount,
            //     'processed_ipls_count' => count($processedIpls),
            //     'processed_ipls' => $processedIpls
            // ]);

            // \Log::info('CAD calcCadSums - Summary', [
            //     'total_cad_components_in_csv' => count($cadComponents),
            //     'excluded_by_status' => $skippedCount,
            //     'excluded_by_process' => $skippedByProcess,
            //     'total_qty' => $totalQty,
            //     'total_components' => $totalComponents,
            //     'valid_processes_count' => count($validProcesses),
            //     'valid_processes' => $validProcesses
            // ]);

            // \Log::info('CAD calculation results:', [
            //     'total_qty' => $totalQty,
            //     'total_components' => $totalComponents,
            //     'processed_ipls' => $processedIpls
            // ]);

            return [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents
            ];

        } catch (\Exception $e) {
            // \Log::error('Error in CAD sums calculation:', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return [
                'total_qty' => 0,
                'total_components' => 0
            ];
        }
    }

    private function calcStressSums($workorder_id)
    {
        try {
            // Получаем текущий workorder
            $current_wo = Workorder::findOrFail($workorder_id);

            // \Log::info('Starting Stress sums calculation', [
            //     'workorder_id' => $workorder_id
            // ]);

            // 1. Получаем данные из таблицы ndt_cad_csv
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                // \Log::error('NdtCadCsv not found for workorder', ['workorder_id' => $workorder_id]);
                return [
                    'total_qty' => 0,
                    'total_components' => 0
                ];
            }

            // \Log::info('Found NdtCadCsv record', [
            //     'ndt_cad_csv_id' => $ndtCadCsv->id,
            //     'stress_components_count' => count($ndtCadCsv->stress_components ?? [])
            // ]);

            // Получаем Stress компоненты из JSON поля
            $stressComponents = $ndtCadCsv->stress_components ?? [];

            if (empty($stressComponents)) {
                // \Log::warning('No Stress components found in NdtCadCsv');
                return [
                    'total_qty' => 0,
                    'total_components' => 0
                ];
            }

            // 2. Получаем ID для Missing, Order New (та же логика, что в stressStd, но Repair НЕ исключаем)
            $missingCode = Code::where('name', 'Missing')->first();
            $orderNewNecessary = Necessary::find(2);
            if (!$orderNewNecessary || stripos($orderNewNecessary->name, 'order') === false) {
                $orderNewNecessary = Necessary::where('name', 'Order New')->first();
            }

            // Получаем TDR записи только с исключаемыми статусами (Missing, Order New, но НЕ Repair)
            $excludedTdrQuery = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component:id,ipl_num');

            $excludedConditions = [];
            if ($missingCode) {
                $excludedConditions[] = ['codes_id', $missingCode->id];
            }
            if ($orderNewNecessary) {
                $excludedConditions[] = ['necessaries_id', $orderNewNecessary->id];
            }

            // Создаем мапу исключенных IPL номеров (только с Missing/Order New)
            $excludedIplNums = [];
            if (!empty($excludedConditions)) {
                $excludedTdrQuery->where(function($query) use ($excludedConditions) {
                    foreach ($excludedConditions as $condition) {
                        $query->orWhere($condition[0], $condition[1]);
                    }
                });

                $excludedTdrs = $excludedTdrQuery->get();
                foreach ($excludedTdrs as $tdr) {
                    if ($tdr->component && $tdr->component->ipl_num) {
                        $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                        if (!empty($normalizedIpl)) {
                            $excludedIplNums[$normalizedIpl] = true;
                        }
                    }
                }
            }

            // Получаем все TDR компоненты для создания мапы (только для логирования)
            $tdrComponents = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component')
                ->get();

            // Создаем мапу IPL номеров из TDR (только для логирования)
            $tdrIplMap = [];
            foreach ($tdrComponents as $tdr) {
                if ($tdr->component && $tdr->component->ipl_num) {
                    $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                    if (!empty($normalizedIpl)) {
                        if (!isset($tdrIplMap[$normalizedIpl])) {
                            $tdrIplMap[$normalizedIpl] = 0;
                        }
                        $tdrIplMap[$normalizedIpl] += (int)($tdr->qty ?? 0);
                    }
                }
            }

            // \Log::info('TDR Components (normalized):', [
            //     'count' => count($tdrIplMap),
            //     'ipl_numbers' => array_keys($tdrIplMap),
            //     'excluded_ipl_count' => count($excludedIplNums),
            //     'excluded_ipls' => array_keys($excludedIplNums)
            // ]);

            // 3. Сравнение и подсчет
            $totalQty = 0;
            $totalComponents = 0;
            $processedIpls = [];
            $skippedCount = 0;
            $combinedSkippedCount = 0;

            // \Log::info('Starting Stress calculation loop', [
            //     'total_stress_components' => count($stressComponents),
            //     'tdr_ipl_count' => count($tdrIplMap),
            //     'excluded_ipl_count' => count($excludedIplNums)
            // ]);

            foreach ($stressComponents as $index => $component) {
                $itemNo = trim($component['ipl_num'] ?? '');
                $qty = (int)($component['qty'] ?? 1); // Получаем qty из JSON

                // Нормализуем IPL номер для сравнения (5-90A -> 5-90)
                $normalizedIpl = $this->normalizeIplNum($itemNo);

                // Исключаем компоненты только с статусами Missing, Order New (Repair НЕ исключаем)
                if (!empty($normalizedIpl) && isset($excludedIplNums[$normalizedIpl])) {
                    $skippedCount++;
                    // \Log::debug('Skipping Stress component as it has excluded status (Missing/Order New):', [
                    //     'ipl_num' => $itemNo,
                    //     'normalized_ipl' => $normalizedIpl
                    // ]);
                    continue;
                }

                // Если IPL номер пустой, пропускаем
                if (empty($itemNo)) {
                    // \Log::debug('Skipping Stress component with empty IPL:', ['component' => $component]);
                    continue;
                }

                // ВАЖНО: Для Stress НЕ используем нормализацию для проверки дубликатов
                // Каждый компонент из CSV должен считаться отдельно, даже если IPL отличается только суффиксом
                // Используем оригинальный IPL для подсчета компонентов
                if (!in_array($itemNo, $processedIpls)) {
                    $totalQty += $qty; // Используем qty из JSON
                    $totalComponents++;
                    $processedIpls[] = $itemNo; // Сохраняем оригинальный IPL
                    // \Log::debug('Adding Stress component from NdtCadCsv:', [
                    //     'ipl_num' => $itemNo,
                    //     'normalized_ipl' => $normalizedIpl,
                    //     'qty' => $qty
                    // ]);
                } else {
                    // \Log::debug('Skipping duplicate Stress component (by original IPL):', [
                    //     'ipl_num' => $itemNo,
                    //     'normalized_ipl' => $normalizedIpl
                    // ]);
                }
            }

            // \Log::info('Stress calculation loop completed', [
            //     'total_qty' => $totalQty,
            //     'total_components' => $totalComponents,
            //     'skipped_count' => $skippedCount,
            //     'processed_ipls_count' => count($processedIpls),
            //     'processed_ipls' => $processedIpls
            // ]);

            // \Log::info('Stress calculation results:', [
            //     'total_qty' => $totalQty,
            //     'total_components' => $totalComponents,
            //     'processed_ipls' => $processedIpls
            // ]);

            return [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents
            ];

        } catch (\Exception $e) {
            // \Log::error('Error in Stress sums calculation:', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return [
                'total_qty' => 0,
                'total_components' => 0
            ];
        }
    }

    private function calcPaintSums($workorder_id)
    {
        try {
            // Получаем текущий workorder
            $current_wo = Workorder::findOrFail($workorder_id);

            // \Log::info('Starting Paint sums calculation', [
            //     'workorder_id' => $workorder_id
            // ]);

            // 1. Получаем данные из таблицы ndt_cad_csv
            $ndtCadCsv = $current_wo->ndtCadCsv;
            if (!$ndtCadCsv) {
                // \Log::error('NdtCadCsv not found for workorder', ['workorder_id' => $workorder_id]);
                return [
                    'total_qty' => 0,
                    'total_components' => 0
                ];
            }

            // \Log::info('Found NdtCadCsv record', [
            //     'ndt_cad_csv_id' => $ndtCadCsv->id,
            //     'paint_components_count' => count($ndtCadCsv->paint_components ?? [])
            // ]);

            // Получаем Paint компоненты из JSON поля
            $paintComponents = $ndtCadCsv->paint_components ?? [];

            if (empty($paintComponents)) {
                // \Log::warning('No Paint components found in NdtCadCsv');
                return [
                    'total_qty' => 0,
                    'total_components' => 0
                ];
            }

            // 2. Чтение TDR компонентов
            $tdrComponents = Tdr::where('workorder_id', $workorder_id)
                ->whereNotNull('component_id')
                ->with('component')
                ->get();

            // Создаем мапу IPL номеров из TDR
            $tdrIplMap = $tdrComponents->pluck('qty', 'component.ipl_num')->toArray();

            // \Log::info('TDR Components:', [
            //     'count' => count($tdrIplMap),
            //     'ipl_numbers' => array_keys($tdrIplMap)
            // ]);

            // 3. Сравнение и подсчет
            $totalQty = 0;
            $totalComponents = 0;
            $processedIpls = [];
            $skippedCount = 0;
            $combinedSkippedCount = 0;

            // \Log::info('Starting Paint calculation loop', [
            //     'total_paint_components' => count($paintComponents),
            //     'tdr_ipl_count' => count($tdrIplMap)
            // ]);

            foreach ($paintComponents as $index => $component) {
                $itemNo = trim($component['ipl_num'] ?? '');
                $qty = (int)($component['qty'] ?? 1); // Получаем qty из JSON

                // \Log::debug('Processing Paint component', [
                //     'index' => $index,
                //     'item_no' => $itemNo,
                //     'qty' => $qty,
                //     'component' => $component
                // ]);

                // Если IPL номер есть в TDR - пропускаем
                if (isset($tdrIplMap[$itemNo])) {
                    $skippedCount++;
                    // \Log::debug('Skipping component as it exists in TDR:', ['ipl_num' => $itemNo]);
                    continue;
                }

                // Проверяем совмещенные значения
                if ($this->shouldSkipItem($itemNo, array_keys($tdrIplMap))) {
                    $combinedSkippedCount++;
                    // \Log::debug('Skipping Paint component due to combined value match:', [
                    //     'item_no' => $itemNo,
                    //     'existing_ipls' => array_keys($tdrIplMap)
                    // ]);
                    continue;
                }

                // Если IPL номер еще не был обработан
                if (!in_array($itemNo, $processedIpls)) {
                    $totalQty += $qty; // Используем qty из JSON
                    $totalComponents++;
                    $processedIpls[] = $itemNo;
                    // \Log::debug('Adding component from NdtCadCsv:', ['ipl_num' => $itemNo, 'qty' => $qty]);
                }
            }

            // \Log::info('Paint calculation loop completed', [
            //     'total_qty' => $totalQty,
            //     'total_components' => $totalComponents,
            //     'skipped_count' => $skippedCount,
            //     'combined_skipped_count' => $combinedSkippedCount,
            //     'processed_ipls' => $processedIpls
            // ]);

            // \Log::info('Paint calculation results:', [
            //     'total_qty' => $totalQty,
            //     'total_components' => $totalComponents,
            //     'processed_ipls' => $processedIpls
            // ]);

            return [
                'total_qty' => $totalQty,
                'total_components' => $totalComponents
            ];

        } catch (\Exception $e) {
            // \Log::error('Error in Paint sums calculation:', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return [
                'total_qty' => 0,
                'total_components' => 0
            ];
        }
    }

    private function shouldSkipItem(string $itemNo, array $existingIplNums): bool
    {
        // Нормализация исходного значения из CSV (приведение кириллицы к латинице, верхний регистр)
        $rawItemNo = $this->normalizeAlphaNum($itemNo);

        foreach ($existingIplNums as $iplNum) {
            if (empty($iplNum)) continue;

            // Очистка строк от неалфавитно-цифровых символов для сравнения
            $cleanItemNo = $rawItemNo;
            $cleanIplNum = $this->normalizeAlphaNum($iplNum);

            // Если номера полностью совпадают, пропускаем
            if ($cleanItemNo === $cleanIplNum) {
                return true;
            }

            // Проверяем совмещенные значения в CSV (например, 1-140/1-140A, 1-140/140А)
            if (strpos($itemNo, '/') !== false) {
                $csvParts = explode('/', $itemNo);
                foreach ($csvParts as $part) {
                    $part = trim($part);
                    $cleanPart = $this->normalizeAlphaNum($part);

                    // Прямое сравнение
                    if ($cleanPart === $cleanIplNum) {
                        return true;
                    }

                    // Нормализация: если часть не содержит префикс, добавляем его из первой части
                    if (preg_match('/^(\d+-)/', $itemNo, $matches)) {
                        $prefix = $matches[1]; // например, "1-"
                        if (!preg_match('/^\d+-/', $part)) {
                            $normalizedPart = $prefix . ltrim($part);
                            $cleanNormalizedPart = $this->normalizeAlphaNum($normalizedPart);
                            if ($cleanNormalizedPart === $cleanIplNum) {
                                return true;
                            }
                        }
                    }
                }
            }

            // Проверяем совмещенные значения в базе данных
            if (strpos($iplNum, '/') !== false) {
                $dbParts = explode('/', $iplNum);
                foreach ($dbParts as $part) {
                    $part = trim($part);
                    $cleanPart = $this->normalizeAlphaNum($part);

                    // Прямое сравнение
                    if ($cleanPart === $cleanItemNo) {
                        return true;
                    }

                    // Нормализация: если часть не содержит префикс, добавляем его из первой части
                    if (preg_match('/^(\d+-)/', $iplNum, $matches)) {
                        $prefix = $matches[1];
                        if (!preg_match('/^\d+-/', $part)) {
                            $normalizedPart = $prefix . ltrim($part);
                            $cleanNormalizedPart = $this->normalizeAlphaNum($normalizedPart);
                            if ($cleanNormalizedPart === $cleanItemNo) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    private function normalizeAlphaNum(string $value): string
    {
        // Карта похожих символов Кириллица -> Латиница
        $map = [
            'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K', 'М' => 'M', 'Н' => 'H',
            'О' => 'O', 'Р' => 'P', 'С' => 'S', 'Т' => 'T', 'У' => 'Y', 'Х' => 'X',
            'а' => 'A', 'в' => 'B', 'е' => 'E', 'к' => 'K', 'м' => 'M', 'н' => 'H',
            'о' => 'O', 'р' => 'P', 'с' => 'S', 'т' => 'T', 'у' => 'Y', 'х' => 'X'
        ];
        $converted = strtr($value, $map);
        $upper = strtoupper($converted);
        return preg_replace('/[^A-Z0-9]/', '', $upper);
    }
}
