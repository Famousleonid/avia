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
use App\Models\StdProcess;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Unit;
use App\Models\User;
use App\Models\WoBushingLine;
use App\Models\Workorder;
use App\Models\WorkorderUnitInspection;
use App\Services\ManualIplBranchRuleResolver;
use App\Services\WorkorderStdProcessItemsService;
use App\Services\WorkorderStdListProcessesService;
use App\Support\KitPrlGrouping;
use App\Support\LogCardDestructionCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TdrPrintFormController extends Controller
{
    // TODO(tdr-refactor): This is the first extraction from TdrController; split by printed form family before changing form behavior again.
    private const DEFAULT_QTY = 1;
    private const DEFAULT_PROCESS = 1;
    private const PROCESS_TYPE_LOG = 'log';

    public function prlForm(Request $request, $id){
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);

        // Получаем данные о manual_id, связанном с этим Workorder
        $manual_id = $current_wo->unit->manual_id;

        // Извлекаем компоненты, связанные с manual_id
        $components = $this->filterComponentsForUnit(
            Component::where('manual_id', $manual_id)->get(),
            $current_wo
        );

        $builders = Builder::all();
        $codes = Code::all();
        $necessaries = Necessary::all();

        $necessary = Necessary::where('name', 'Order New')->first();
        $code = Code::missing();

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
            }, 'orderComponentAssembly'])
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
            $assemblyA = $a->orderComponentAssembly ?? null;
            $assemblyB = $b->orderComponentAssembly ?? null;

            // Используем assy_ipl_num если есть, иначе ipl_num
            $iplA = ($assemblyA && $assemblyA->assy_ipl_num)
                ? $assemblyA->assy_ipl_num
                : ($componentA->ipl_num ?? '');
            $iplB = ($assemblyB && $assemblyB->assy_ipl_num)
                ? $assemblyB->assy_ipl_num
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

        return $this->renderPrlForm($current_wo, $ordersParts);
    }

    public function bushingPrlForm(Request $request, $id)
    {
        $current_wo = Workorder::findOrFail($id);
        $ordersParts = $this->buildBushingPrlRows($current_wo);

        return $this->renderPrlForm($current_wo, $ordersParts, 'PARTS REPLACEMENT LIST');
    }

    public function bushPrlForm(Request $request, $id)
    {
        $current_wo = Workorder::findOrFail($id);
        $ordersParts = $this->buildBushingPrlRows($current_wo);

        return $this->renderPrlForm($current_wo, $ordersParts, 'PARTS REPLACEMENT LIST');
    }

    public function kitForm(Request $request, $id)
    {
        $current_wo = Workorder::findOrFail($id);
        $ordersParts = $this->buildKitPrlRows($current_wo);

        return $this->renderPrlForm($current_wo, $ordersParts, 'PARTS REPLACEMENT LIST - KIT', false);
    }

    private function renderPrlForm(Workorder $current_wo, $ordersParts, string $formTitle = 'PARTS REPLACEMENT LIST', bool $showPrintMarkQr = true)
    {
        $manual_id = $current_wo->unit->manual_id;
        $components = $this->filterComponentsForUnit(
            Component::where('manual_id', $manual_id)->get(),
            $current_wo
        );
        $builders = Builder::all();
        $codes = Code::all();
        $necessaries = Necessary::all();
        $manuals = Manual::where('id', $manual_id)
            ->with('builder')
            ->get();
        $ordersParts = collect($ordersParts)->values();
        $uniqueManuals = $ordersParts->map(function ($tdr) {
            return is_array($tdr) ? ($tdr['manual'] ?? null) : ($tdr->manual ?? null);
        })->filter(function ($manual) {
            return $manual !== null && $manual !== '';
        })->unique()->values()->toArray();
        $hasMultipleManuals = count($uniqueManuals) > 1;

        return view('admin.tdrs.prlForm', compact(
            'current_wo',
            'components',
            'manuals',
            'builders',
            'codes',
            'necessaries',
            'ordersParts',
            'uniqueManuals',
            'hasMultipleManuals',
            'formTitle',
            'showPrintMarkQr'
        ));
    }

    private function buildBushingPrlRows(Workorder $workorder)
    {
        return WoBushingLine::query()
            ->where(function ($query) use ($workorder): void {
                $query->where('workorder_id', $workorder->id)
                    ->orWhereHas('woBushing', fn ($woBushing) => $woBushing->where('workorder_id', $workorder->id));
            })
            ->with(['component' => function ($query): void {
                $query->select('id', 'name', 'part_number', 'ipl_num', 'assy_part_number', 'assy_ipl_num', 'manual_id')
                    ->with('manual:id,number');
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (WoBushingLine $line): bool => $line->component !== null)
            ->sort(function (WoBushingLine $left, WoBushingLine $right): int {
                $manualCompare = strnatcasecmp(
                    (string) ($left->component->manual->number ?? ''),
                    (string) ($right->component->manual->number ?? '')
                );
                if ($manualCompare !== 0) {
                    return $manualCompare;
                }

                $iplCompare = StdProcess::compareIplValues(
                    (string) ($left->component->ipl_num ?? ''),
                    (string) ($right->component->ipl_num ?? '')
                );
                if ($iplCompare !== 0) {
                    return $iplCompare;
                }

                return strnatcasecmp(
                    (string) ($left->component->part_number ?? ''),
                    (string) ($right->component->part_number ?? '')
                );
            })
            ->map(function (WoBushingLine $line): array {
                $row = $this->makePrlArrayRow(
                    $line->component,
                    max(1, (int) ($line->qty ?? 1))
                );
                $row['codes'] = ['code' => 'K'];

                return $row;
            })
            ->values();
    }

    private function buildKitPrlRows(Workorder $workorder)
    {
        $manualId = (int) ($workorder->unit->manual_id ?? 0);
        $kitComponents = $this->filterComponentsForUnit(
            Component::query()
                ->where('manual_id', $manualId)
                ->where('kit', true)
                ->where(function ($query): void {
                    $query->where('is_bush', false)->orWhereNull('is_bush');
                })
                ->with('manual:id,number')
                ->get(),
            $workorder
        );

        return $this->buildKitPrlRowsForComponents($kitComponents, 'KIT');
    }

    private function buildKitPrlRowsForComponents($components, string $code)
    {
        return $components
            ->groupBy(fn (Component $component): string => KitPrlGrouping::groupKeyForComponent($component))
            ->map(function ($group) use ($code) {
                /** @var \Illuminate\Support\Collection<int, Component> $group */
                $sorted = $group->sort(function (Component $left, Component $right): int {
                    $iplCompare = StdProcess::compareIplValues(
                        (string) ($left->ipl_num ?? ''),
                        (string) ($right->ipl_num ?? '')
                    );
                    if ($iplCompare !== 0) {
                        return $iplCompare;
                    }

                    return strnatcasecmp((string) ($left->part_number ?? ''), (string) ($right->part_number ?? ''));
                })->values();
                $representative = $sorted->first();
                // KIT grouped rows are alternatives for the technician, so do not add variant quantities together.
                $qty = $sorted->max(fn (Component $component): int => max(1, (int) ($component->units_assy ?? 1)));

                $row = $this->makePrlArrayRow($representative, max(1, (int) $qty));
                $row['sort_ipl_num'] = (string) ($representative->ipl_num ?? '');
                $row['component']['ipl_num'] = $sorted
                    ->pluck('ipl_num')
                    ->filter()
                    ->unique()
                    ->implode("\n");
                $row['component']['part_number'] = $sorted
                    ->pluck('part_number')
                    ->filter()
                    ->unique()
                    ->implode("\n");
                $row['component']['name'] = $sorted
                    ->pluck('name')
                    ->filter()
                    ->unique()
                    ->implode("\n");
                $row['codes'] = ['code' => $code];

                return $row;
            })
            ->sort(function (array $left, array $right): int {
                $leftComponent = $left['component'] ?? [];
                $rightComponent = $right['component'] ?? [];

                return StdProcess::compareIplValues(
                    (string) ($left['sort_ipl_num'] ?? $leftComponent['ipl_num'] ?? ''),
                    (string) ($right['sort_ipl_num'] ?? $rightComponent['ipl_num'] ?? '')
                );
            })
            ->values();
    }

    private function makePrlArrayRow(Component $component, int $qty): array
    {
        return [
            'manual' => $component->manual->number ?? null,
            'component' => [
                'id' => $component->id,
                'name' => $component->name,
                'part_number' => $component->part_number,
                'ipl_num' => $component->ipl_num,
                'assy_part_number' => $component->assy_part_number,
                'assy_ipl_num' => $component->assy_ipl_num,
            ],
            'qty' => $qty,
            'codes' => ['code' => ''],
            'po_num' => '',
            'notes' => '',
        ];
    }

    public function ndtStd(Request $request, $workorder_id)
    {
        $current_wo = Workorder::findOrFail($workorder_id);
        $this->rebuildStdSnapshotForForm($current_wo);
        $manual = $current_wo->unit->manuals;

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

        $ndt_components = $this->stdSnapshotRowsToFormComponents(
            StdProcess::snapshotComponentsForWorkorder($current_wo, StdProcess::STD_NDT)
        );
        $ndtSums = $this->calcNdtSumsFromFormComponents($ndt_components);

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

        $ndt_components = $this->sortStdFormComponents($ndt_components);

        $form_number = 'NDT-STD';

        $ndtFormCfg = config('tdr_forms.ndtFormStd', []);
        $ndtDefaultRows = (int) ($ndtFormCfg['table_rows_default'] ?? 18);
        $ndtRowsPerPage = (int) $request->query('ndt_table_rows', $ndtDefaultRows);
        $ndtRowsPerPage = max(1, min(99, $ndtRowsPerPage));
        $ndt_table_pages = $this->buildStdProcessSheetTablePages($ndt_components, $ndtRowsPerPage, true, true);

        return view('admin.tdrs.ndtFormStd', [
                'current_wo' => $current_wo,
                'manual' => $manual,
                'ndt_components' => $ndt_components,
                'ndt_table_pages' => $ndt_table_pages,
                'ndt_rows_per_page' => $ndtRowsPerPage,
                'ndtSums' => $ndtSums,
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
            $this->rebuildStdSnapshotForForm($current_wo);
            $manual = $current_wo->unit->manuals;

            if (!$manual) {
                throw new \RuntimeException('Manual not found for this workorder');
            }


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


            $cad_components = $this->stdSnapshotRowsToFormComponents(
                StdProcess::snapshotComponentsForWorkorder($current_wo, StdProcess::STD_CAD)
            );

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

            $cad_components = $this->sortStdFormComponents($cad_components);

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

    public function paintStd(Request $request, $workorder_id)
    {
        try {
            // Получаем рабочий заказ и связанные данные
            $current_wo = Workorder::findOrFail($workorder_id);
            $this->rebuildStdSnapshotForForm($current_wo);
            $manual = $current_wo->unit->manuals;

            if (!$manual) {
                throw new \RuntimeException('Manual not found for this workorder');
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
            $missingCode = Code::missing();
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

            // Paint STD uses current Component rows with paint_list, not the stored CSV snapshot.
            $paint_components = collect(StdProcess::snapshotComponentsForWorkorder($current_wo, StdProcess::STD_PAINT))
                ->filter(function ($component) use ($excludedIplNumsPaint) {
                    $iplNum = $component['ipl_num'] ?? '';
                    // Нормализуем IPL номер для сравнения
                    $normalizedIpl = $this->normalizeIplNum($iplNum);
                    // Исключаем компоненты, присутствующие в TDR со статусами Missing, Repair, Order New
                    return !isset($excludedIplNumsPaint[$normalizedIpl]);
                })
                ->map(function ($component) use ($paint_processes, $manual) {
                    $process = $paint_processes->first(function ($p) use ($component) {
                        return (int) $p->process->id === (int) ($component['process'] ?? 0);
                    });

                    [$itemDisp, $partDisp, $descDisp] = $this->resolvePaintStdAssyDisplayFields($component, $manual);

                    $obj = new \stdClass();
                    $obj->ipl_num = (string) ($component['ipl_num'] ?? '');
                    $obj->item_display = $itemDisp;
                    $obj->part_number = $partDisp;
                    $obj->name = $descDisp;
                    $obj->process_name = $process ? $process->process->name : 'No Paint process configured';
                    $obj->qty = max(1, (int) ($component['qty'] ?? 1));
                    $obj->manual = $component['manual'] ?? (string) ($manual->number ?? '');
                    $obj->eff_code = trim((string) ($component['eff_code'] ?? ''));

                    return $obj;
                })->toArray();

            $paint_components = $this->collapseStdSuffixVariantRows($paint_components);

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
            $paint_components = $this->sortStdFormComponents($paint_components);

            $form_number = '014';

            // Рассчитываем общее количество деталей
            $paintSum = $this->calcPaintSumsFromComponents($paint_components);

            $paintFormCfg = config('tdr_forms.paintFormStd', []);
            $paintDefaultRows = (int) ($paintFormCfg['table_rows_default'] ?? 18);
            $paintRowsPerPage = (int) $request->query('paint_table_rows', $paintDefaultRows);
            $paintRowsPerPage = max(1, min(99, $paintRowsPerPage));
            $paint_table_pages = $this->buildStdProcessSheetTablePages($paint_components, $paintRowsPerPage);

            return view('admin.tdrs.paintFormStd', [
                    'current_wo' => $current_wo,
                    'manual' => $manual,
                    'paint_components' => $paint_components,
                    'paint_table_pages' => $paint_table_pages,
                    'paint_rows_per_page' => $paintRowsPerPage,
                    'paint_processes' => $paint_processes,
                    'form_number' => $form_number,
                    'manuals' => [$manual],
                    'process_name' => $paintProcessName,
                    'paintSum' => $paintSum,
                ] + $paint_ids);

        } catch (\Exception $e) {
            // \Log::error('Error in Paint processing:', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            throw $e;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, \stdClass>
     */
    private function stdSnapshotRowsToFormComponents(array $rows): array
    {
        $components = array_values(array_map(function (array $row): \stdClass {
            $component = new \stdClass();
            $component->ipl_num = (string) ($row['ipl_num'] ?? '');
            $component->part_number = (string) ($row['part_number'] ?? '');
            $component->name = (string) ($row['description'] ?? '');
            $component->qty = max(1, (int) ($row['qty'] ?? 1));
            $component->process_name = (string) ($row['process'] ?? '');
            $component->manual = trim((string) ($row['manual'] ?? '')) ?: null;
            $component->eff_code = trim((string) ($row['eff_code'] ?? ''));

            return $component;
        }, $rows));

        return $this->collapseStdSuffixVariantRows($components);
    }

    /**
     * @param  array<int, \stdClass>  $components
     * @return array<int, \stdClass>
     */
    private function sortStdFormComponents(array $components): array
    {
        usort($components, function (\stdClass $a, \stdClass $b): int {
            $manualCompare = strnatcasecmp((string) ($a->manual ?? ''), (string) ($b->manual ?? ''));
            if ($manualCompare !== 0) {
                return $manualCompare;
            }

            $iplCompare = StdProcess::compareIplValues($a->sort_ipl_num ?? $a->ipl_num ?? '', $b->sort_ipl_num ?? $b->ipl_num ?? '');
            if ($iplCompare !== 0) {
                return $iplCompare;
            }

            return strnatcasecmp((string) ($a->part_number ?? ''), (string) ($b->part_number ?? ''));
        });

        return $components;
    }

    /**
     * Collapse rows like 8-240A / 8-240B or 1-272 / 1-272A into one display row
     * when the base IPL, manual and process match.
     *
     * @param  array<int, \stdClass>  $components
     * @return array<int, \stdClass>
     */
    private function collapseStdSuffixVariantRows(array $components): array
    {
        $collapsed = [];
        $indexByKey = [];

        foreach ($components as $component) {
            $this->initializeCollapsedStdRowValues($component);
            $groupKey = $this->stdSuffixVariantGroupKey($component);

            if ($groupKey === null) {
                $collapsed[] = $component;
                continue;
            }

            if (! array_key_exists($groupKey, $indexByKey)) {
                $indexByKey[$groupKey] = count($collapsed);
                $collapsed[] = $component;

                continue;
            }

            $index = $indexByKey[$groupKey];
            $target = $collapsed[$index];

            $target->_ipl_values = $this->appendUniqueStdCollapsedValue($target->_ipl_values, (string) $component->ipl_num);
            if (property_exists($target, '_item_display_values') && property_exists($component, 'item_display')) {
                $target->_item_display_values = $this->appendUniqueStdCollapsedValue($target->_item_display_values, (string) ($component->item_display ?? ''));
            }
            $target->_part_number_values = $this->appendUniqueStdCollapsedValue($target->_part_number_values, (string) ($component->part_number ?? ''));
            $target->_description_values = $this->appendUniqueStdCollapsedValue($target->_description_values, (string) ($component->name ?? ''));

            $this->applyCollapsedStdRowDisplay($target);

            $collapsed[$index] = $target;
        }

        return array_values($collapsed);
    }

    private function stdSuffixVariantGroupKey(\stdClass $component): ?string
    {
        $ipl = trim((string) ($component->ipl_num ?? ''));

        if (! preg_match('/^(\d+[A-Za-z]*-\d+)(?:[A-Za-z]+)?$/', $ipl, $matches)) {
            return null;
        }

        $baseIpl = strtoupper((string) ($matches[1] ?? ''));
        $manual = trim((string) ($component->manual ?? ''));
        $process = trim((string) ($component->process_name ?? ''));

        return implode('|', [$manual, $baseIpl, $process]);
    }

    private function initializeCollapsedStdRowValues(\stdClass $component): void
    {
        $component->sort_ipl_num = (string) ($component->sort_ipl_num ?? $component->ipl_num ?? '');
        $component->_ipl_values = $this->appendUniqueStdCollapsedValue([], (string) ($component->ipl_num ?? ''));
        $component->_part_number_values = $this->appendUniqueStdCollapsedValue([], (string) ($component->part_number ?? ''));
        $component->_description_values = $this->appendUniqueStdCollapsedValue([], (string) ($component->name ?? ''));
        if (property_exists($component, 'item_display')) {
            $component->_item_display_values = $this->appendUniqueStdCollapsedValue([], (string) ($component->item_display ?? ''));
        }
        $this->applyCollapsedStdRowDisplay($component);
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, string>
     */
    private function appendUniqueStdCollapsedValue(array $values, string $value): array
    {
        $trimmed = trim($value);
        if ($trimmed === '' || in_array($trimmed, $values, true)) {
            return $values;
        }

        $values[] = $trimmed;

        return $values;
    }

    private function applyCollapsedStdRowDisplay(\stdClass $component): void
    {
        $iplValues = $component->_ipl_values ?? [];
        $lineCount = max(1, count($iplValues));

        $component->ipl_num = implode("\n", $iplValues);
        $component->part_number = implode("\n", $component->_part_number_values ?? []);
        $component->name = implode("\n", $component->_description_values ?? []);
        $component->row_line_count = $lineCount;
        $component->row_height = 32 + (($lineCount - 1) * 16);

        if (property_exists($component, '_item_display_values')) {
            $component->item_display = implode("\n", $component->_item_display_values ?? []);
        }
    }

    private function buildStdProcessSheetTablePages(
        array $components,
        int $rowsPerPage,
        bool $includeManualRows = true,
        bool $paginateByRowSlots = false
    ): array
    {
        $rowsPerPage = max(1, min(99, $rowsPerPage));
        $flat = [];
        $previousManual = null;
        foreach ($components as $component) {
            $currentManual = $component->manual ?? null;
            $shouldInsertManualRow = $includeManualRows
                && ($currentManual !== null && $currentManual !== '' && $currentManual !== $previousManual);
            if ($shouldInsertManualRow) {
                $flat[] = ['kind' => 'manual', 'text' => $currentManual];
            }
            $flat[] = ['kind' => 'data', 'component' => $component];
            $previousManual = $currentManual;
        }
        if ($flat === []) {
            $pages = [[]];
        } elseif ($paginateByRowSlots) {
            $pages = $this->paginateStdProcessSheetRowsBySlots($flat, $rowsPerPage);
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

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function paginateStdProcessSheetRowsBySlots(array $rows, int $rowsPerPage): array
    {
        $pages = [];
        $page = [];
        $usedSlots = 0;

        foreach ($rows as $row) {
            $slots = $this->stdProcessSheetRowSlots($row, $rowsPerPage);

            if ($page !== [] && ($usedSlots + $slots) > $rowsPerPage) {
                $this->fillStdProcessSheetPage($page, $usedSlots, $rowsPerPage);
                $pages[] = $page;
                $page = [];
                $usedSlots = 0;
            }

            $page[] = $row;
            $usedSlots += $slots;
        }

        if ($page !== []) {
            $this->fillStdProcessSheetPage($page, $usedSlots, $rowsPerPage);
            $pages[] = $page;
        }

        return $pages ?: [[]];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function stdProcessSheetRowSlots(array $row, int $rowsPerPage): int
    {
        if (($row['kind'] ?? '') !== 'data') {
            return 1;
        }

        $component = $row['component'] ?? null;
        $lineCount = is_object($component) ? (int) ($component->row_line_count ?? 1) : 1;

        return max(1, min($rowsPerPage, $lineCount));
    }

    /**
     * @param  array<int, array<string, mixed>>  $page
     */
    private function fillStdProcessSheetPage(array &$page, int $usedSlots, int $rowsPerPage): void
    {
        for ($slot = $usedSlots; $slot < $rowsPerPage; $slot++) {
            $page[] = ['kind' => 'empty'];
        }
    }

    public function stressStd(Request $request, $workorder_id)
    {
        try {
            // Получаем рабочий заказ и связанные данные
            $current_wo = Workorder::findOrFail($workorder_id);
            $this->rebuildStdSnapshotForForm($current_wo);
            $manual = $current_wo->unit->manuals;

            if (!$manual) {
                throw new \RuntimeException('Manual not found for this workorder');
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


            $stress_components = $this->stdSnapshotRowsToFormComponents(
                StdProcess::snapshotComponentsForWorkorder($current_wo, StdProcess::STD_STRESS)
            );

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

            $stress_components = $this->sortStdFormComponents($stress_components);

            $form_number = 'STRESS-STD';

            // Обновляем список процессов после возможного добавления новых
            $stress_processes = Process::whereIn('id', $manualProcesses)
                ->whereIn('process_names_id', $stress_ids)
                ->get();

            // Рассчитываем общее количество деталей
            $stressSum = $this->calcStdSumsFromComponents($stress_components);

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
        $this->rebuildStdSnapshotForForm($current_wo);

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
        $components = $this->filterComponentsForUnit(
            Component::where('manual_id', $manual_id)->get(),
            $current_wo
        );

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
            $prevNameId = null;
            $prevNumberLine = null;

            // Обрабатываем каждый процесс
            $groupedProcesses->each(function ($process) use (&$result, &$lineNumber, &$prevNameId, &$prevNumberLine, $tdr, $ecProcessIds, $showEcForThisTdr) {
                $isEcProcess = $ecProcessIds->contains($process->process_names_id);
                $nameId = $process->process_names_id;

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
                    if ($nameId === $prevNameId) {
                        $numberLine = $prevNumberLine;
                    } else {
                        $lineNumber++;
                        $numberLine = $lineNumber;
                    }
                }
                $prevNameId = $nameId;
                $prevNumberLine = $numberLine;

                $result->push([
                    'tdrs_id' => $tdr->id,
                    'process_name_id' => $process->process_names_id,
                    'number_line' => $numberLine,
                    'ec' => $process->ec,
                    'repair_order' => trim((string) ($process->repair_order ?? '')),
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
                'repair_order' => $item['repair_order'] ?? '',
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
        $spPageCount = max(1, $componentChunks->count());
        $bushingPageCount = WoBushingLine::where('workorder_id', $current_wo->id)->exists() ? 1 : 0;
        $combinedSpecPageTotal = $spPageCount + $bushingPageCount;
        $specPageOffset = 0;

        return view('admin.tdrs.specProcessForm', [
            'current_wo' => $current_wo,
            'processes' => $result, // Исходная коллекция
            'ndt_processes' => $ndt_processes, // Отфильтрованная коллекция
            'ndtSums' => $ndtSums, // Добавляем NDT суммы в представление
            'cadSum' => $cadSum,
            'componentChunks' => $componentChunks,
            'combinedSpecPageTotal' => $combinedSpecPageTotal,
            'specPageOffset' => $specPageOffset,
        ], compact('tdrs', 'tdr_ws','processNames','cadSum_ex'));
    }

    public function specProcessFormEmp(Request $request, $id)
    {
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);
        $this->rebuildStdSnapshotForForm($current_wo);

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
        $components = $this->filterComponentsForUnit(
            Component::where('manual_id', $manual_id)->get(),
            $current_wo
        );

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
            $prevNameId = null;
            $prevNumberLine = null;
            $groupedProcesses->each(function ($process) use (&$result, &$lineNumber, &$prevNameId, &$prevNumberLine, $tdr, $ecProcessIds, $showEcForThisTdr) {
                $isEcProcess = $ecProcessIds->contains($process->process_names_id);
                $nameId = $process->process_names_id;
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
                    if ($nameId === $prevNameId) {
                        $num = $prevNumberLine;
                    } else {
                        $lineNumber++;
                        $num = $lineNumber;
                    }
                }
                $prevNameId = $nameId;
                $prevNumberLine = $num;
                $result->push([
                    'tdrs_id' => $tdr->id,
                    'process_name_id' => $process->process_names_id,
                    'number_line' => $num,
                    'ec' => $process->ec,
                    'repair_order' => trim((string) ($process->repair_order ?? '')),
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
                'repair_order' => $item['repair_order'] ?? '',
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
        $spPageCount = max(1, $componentChunks->count());
        $bushingPageCount = WoBushingLine::where('workorder_id', $current_wo->id)->exists() ? 1 : 0;
        $combinedSpecPageTotal = $spPageCount + $bushingPageCount;
        $specPageOffset = 0;

        return view('admin.tdrs.specProcessFormEmp', [
            'current_wo' => $current_wo,
            'processes' => $result,
            'ndt_processes' => $ndt_processes,
            'ndtSums' => $ndtSums,
            'cadSum' => $cadSum,
            'componentChunks' => $componentChunks,
            'combinedSpecPageTotal' => $combinedSpecPageTotal,
            'specPageOffset' => $specPageOffset,
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

        return view('admin.tdrs.logCardForm', compact('current_wo','manuals', 'builders'));

    }

    public function tdrForm(Request $request, $id)
    {
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);

        // Получаем данные о manual_id, связанном с этим Workorder
        $manual_id = $current_wo->unit->manual_id;

        // Извлекаем компоненты, связанные с manual_id
        $components = $this->filterComponentsForUnit(
            Component::where('manual_id', $manual_id)->get(),
            $current_wo
        );

        // Загружаем необходимые данные для всех записей
        $necessaries = Necessary::all();
        $conditions = Condition::all();
        $codes = Code::all();

        // Жадная загрузка данных для tdrs, включая все связи с компонентами, состояниями, необходимостями и кодами
        $current_wo->load('tdrs.component', 'tdrs.conditions', 'tdrs.necessaries', 'tdrs.codes');

        $necessary = Necessary::where('name', 'Order New')->first();
        $code = Code::missing();

        // Массивы для хранения разных типов строк
        $nullComponentConditions = []; // Для строк, где component_id == null
        $groupedByConditions = []; // Для строк, где component_id !== null и necessaries_id == Order New
        $necessaryComponents = []; // Для строк, где component_id !== null и necessaries_id !== Order New
        $hasMissingComponents = false; // Флаг наличия компонентов с кодом Missing

        $missingConditionName = Condition::NAME_PARTS_MISSING;
        $unitInspections = WorkorderUnitInspection::query()
            ->with('condition:id,name')
            ->where('workorder_id', $current_wo->id)
            ->where(function ($query) {
                $query->where('use_tdr', true)
                    ->orWhereNull('use_tdr');
            })
            ->orderBy('id')
            ->get();

        $unitInspectionLines = $unitInspections
            ->map(function (WorkorderUnitInspection $inspection) use ($missingConditionName) {
                $conditionName = trim((string) ($inspection->condition->name ?? ''));
                $notes = trim((string) ($inspection->notes ?? ''));

                if (strcasecmp($conditionName, $missingConditionName) === 0) {
                    return null;
                }

                if ($conditionName !== '' && preg_match('/^note\s+\d+$/i', $conditionName)) {
                    return $notes !== '' ? $notes : null;
                }

                if ($conditionName === '') {
                    return $notes !== '' ? $notes : null;
                }

                return $notes !== '' ? $conditionName . ' (' . $notes . ')' : $conditionName;
            })
            ->filter()
            ->values()
            ->all();

        $unitInspectionSourceTdrIds = $unitInspections
            ->pluck('source_tdr_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($current_wo->tdrs as $tdr) {
            // Проверяем наличие компонентов с кодом Missing
            if ($tdr->codes_id == $code->id) {
                $hasMissingComponents = true;
                continue; // Пропускаем обработку этих строк, но запоминаем их наличие
            }

            // Строки с component_id == null
            if ($tdr->component_id === null) {
                if (in_array((int) $tdr->id, $unitInspectionSourceTdrIds, true)) {
                    continue;
                }

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
                    $componentName = trim((string) $component->name);
                    $descriptionText = trim((string) $description);
                    $showDescription = $descriptionText !== ''
                        && strcasecmp($descriptionText, $componentName) !== 0;
                    // Строим строку в нужном формате
                    if ($showDescription) {
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
            $missingCondition = Condition::where('name', $missingConditionName)->first();
            if ($missingCondition) {
                $nullComponentConditions[] = $missingCondition->name;
            }
        }

// Объединяем все строки в правильном порядке
        $tdrInspections = $unitInspectionLines;

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

    private function normalizeIplNum($iplNum): string
    {
        if (empty($iplNum)) {
            return '';
        }

        return preg_replace('/[A-Z]+$/', '', trim((string) $iplNum));
    }

    private function tdrRowExcludedForNdtStd(Tdr $tdr, ?Code $missingCode, ?Code $repairCode, ?Necessary $orderNewNecessary): bool
    {
        if ($missingCode !== null && (int) $tdr->codes_id === (int) $missingCode->id) {
            return true;
        }
        if ($repairCode !== null && (int) $tdr->codes_id === (int) $repairCode->id) {
            return true;
        }
        if ($orderNewNecessary !== null && (int) $tdr->necessaries_id === (int) $orderNewNecessary->id) {
            return true;
        }

        return false;
    }

    /**
     * @return array{excluded: array<string, int>, tdr: array<string, int>}
     */
    private function ndtStdExcludedAndTdrQtyByNormalizedIpl(int $workorderId): array
    {
        $excludedQtyByIpl = [];
        $missingCode = Code::missing();
        $repairCode = Code::where('name', 'Repair')->first();
        $orderNewNecessary = Necessary::where('name', 'Order New')->first();

        $excludedTdrQuery = Tdr::where('workorder_id', $workorderId)
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

        if (! empty($excludedConditions)) {
            $excludedTdrQuery->where(function ($query) use ($excludedConditions) {
                foreach ($excludedConditions as $condition) {
                    $query->orWhere($condition[0], $condition[1]);
                }
            });

            foreach ($excludedTdrQuery->get() as $tdr) {
                if (! $tdr->component || empty($tdr->component->ipl_num)) {
                    continue;
                }

                $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
                if ($normalizedIpl === '') {
                    continue;
                }

                $excludedQtyByIpl[$normalizedIpl] = ($excludedQtyByIpl[$normalizedIpl] ?? 0) + (int) ($tdr->qty ?? 0);
            }
        }

        $tdrItemsMap = [];
        $allTdrForNdtMap = Tdr::where('workorder_id', $workorderId)
            ->whereNotNull('component_id')
            ->with('component:id,ipl_num')
            ->get();

        foreach ($allTdrForNdtMap as $tdr) {
            if ($this->tdrRowExcludedForNdtStd($tdr, $missingCode, $repairCode, $orderNewNecessary)) {
                continue;
            }
            if (! $tdr->component || empty($tdr->component->ipl_num)) {
                continue;
            }

            $qty = (int) ($tdr->qty ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $normalizedIpl = $this->normalizeIplNum($tdr->component->ipl_num);
            if ($normalizedIpl === '') {
                continue;
            }

            $tdrItemsMap[$normalizedIpl] = ($tdrItemsMap[$normalizedIpl] ?? 0) + $qty;
        }

        return [
            'excluded' => $excludedQtyByIpl,
            'tdr' => $tdrItemsMap,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function buildUnitsAssyByNormalizedIplMap(Manual $manual): array
    {
        $unitsAssyByIpl = [];
        $allComponents = Component::select('ipl_num', 'units_assy', 'manual_id')
            ->orderByRaw('CASE WHEN manual_id = ? THEN 0 ELSE 1 END', [$manual->id])
            ->get();

        foreach ($allComponents as $component) {
            if (! $component->ipl_num) {
                continue;
            }

            $normalizedIpl = $this->normalizeIplNum($component->ipl_num);
            if ($normalizedIpl === '' || isset($unitsAssyByIpl[$normalizedIpl])) {
                continue;
            }

            $num = (int) ($component->units_assy ?? 1);
            $unitsAssyByIpl[$normalizedIpl] = $num > 0 ? $num : 1;
        }

        return $unitsAssyByIpl;
    }

    private function resolveNdtStdUnitsAssyForRow(array $component, string $iplNum, string $normalizedIpl, array $unitsAssyByIpl): int
    {
        if (! empty($component['manual'])) {
            $componentManual = Manual::where('number', $component['manual'])->first();
            if ($componentManual) {
                $componentRecord = Component::where('manual_id', $componentManual->id)
                    ->where('ipl_num', $iplNum)
                    ->first();

                if ($componentRecord && $componentRecord->units_assy) {
                    $num = (int) $componentRecord->units_assy;

                    return $num > 0 ? $num : 1;
                }
            }
        }

        return max(1, $unitsAssyByIpl[$normalizedIpl] ?? 1);
    }

    /**
     * @param  array<string, mixed>  $paintRow
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolvePaintStdAssyDisplayFields(array $paintRow, Manual $defaultManual): array
    {
        $iplNum = (string) ($paintRow['ipl_num'] ?? '');
        $item = $iplNum;
        $part = (string) ($paintRow['part_number'] ?? '');
        $desc = (string) ($paintRow['description'] ?? '');

        $componentRecord = null;
        if (! empty($paintRow['manual'])) {
            $componentManual = Manual::where('number', $paintRow['manual'])->first();
            if ($componentManual) {
                $componentRecord = Component::query()
                    ->where('manual_id', $componentManual->id)
                    ->where('ipl_num', $iplNum)
                    ->first(['assy_ipl_num', 'assy_part_number', 'name']);
            }
        }

        if (! $componentRecord && $iplNum !== '') {
            $componentRecord = Component::query()
                ->where('manual_id', $defaultManual->id)
                ->where('ipl_num', $iplNum)
                ->first(['assy_ipl_num', 'assy_part_number', 'name']);
        }

        if (! $componentRecord) {
            return [$item, $part, $desc];
        }

        $assyIplNum = trim((string) ($componentRecord->assy_ipl_num ?? ''));
        $assyPartNumber = trim((string) ($componentRecord->assy_part_number ?? ''));

        if ($assyIplNum !== '') {
            $item = (string) $componentRecord->assy_ipl_num;
        }
        if ($assyPartNumber !== '') {
            $part = (string) $componentRecord->assy_part_number;
        }
        if (($assyIplNum !== '' || $assyPartNumber !== '') && trim((string) ($componentRecord->name ?? '')) !== '') {
            $desc = (string) $componentRecord->name;
        }

        return [$item, $part, $desc];
    }

    private function calcNdtSums(int $workorder_id): array
    {
        $currentWo = Workorder::findOrFail($workorder_id);
        $rows = StdProcess::snapshotComponentsForWorkorder($currentWo, StdProcess::STD_NDT);

        return $this->calcNdtSumsFromFormComponents(
            $this->stdSnapshotRowsToFormComponents($rows)
        );
    }

    private function calcNdtSumsFromFormComponents(array $components): array
    {
        $total = 0;
        $mpi = 0;
        $fpi = 0;

        foreach ($components as $component) {
            $qty = max(0, (int) ($component->qty ?? 0));
            $bucket = $this->resolvePrimaryNdtBucket((string) ($component->process_name ?? ''));

            $total += $qty;

            if ($bucket === 1) {
                $mpi += $qty;
            } elseif ($bucket === 4) {
                $fpi += $qty;
            }
        }

        return ['total' => $total, 'mpi' => $mpi, 'fpi' => $fpi];
    }

    private function resolvePrimaryNdtBucket(string $process): ?int
    {
        if (! preg_match('/\d+/', $process, $matches)) {
            return null;
        }

        $firstNumber = (int) $matches[0];

        return in_array($firstNumber, [1, 4], true) ? $firstNumber : null;
    }

    private function calcCadSumsFromComponents($cad_components)
    {
        return $this->calcStdTotalsFromComponents($cad_components);
    }

    private function calcStdSumsFromComponents($components): array
    {
        return $this->calcStdTotalsFromComponents($components);
    }

    private function calcPaintSumsFromComponents($paint_components): array
    {
        return $this->calcStdTotalsFromComponents($paint_components);
    }

    private function calcCadSums($workorder_id)
    {
        $currentWo = Workorder::findOrFail($workorder_id);
        $rows = StdProcess::snapshotComponentsForWorkorder($currentWo, StdProcess::STD_CAD);

        return $this->calcCadSumsFromComponents(
            $this->stdSnapshotRowsToFormComponents($rows)
        );
    }

    private function calcStdTotalsFromComponents($components): array
    {
        if (empty($components)) {
            return [
                'total_qty' => 0,
                'total_components' => 0,
            ];
        }

        $totalQty = 0;
        $totalComponents = 0;

        foreach ($components as $component) {
            $totalQty += max(0, (int) ($component->qty ?? 0));
            $totalComponents++;
        }

        return [
            'total_qty' => $totalQty,
            'total_components' => $totalComponents,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{total_qty: int, total_components: int}
     */
    private function calcStdTotalsFromRows(array $rows): array
    {
        return [
            'total_qty' => array_sum(array_map(static fn (array $row): int => max(0, (int) ($row['qty'] ?? 0)), $rows)),
            'total_components' => count($rows),
        ];
    }

    private function filterComponentsForUnit($components, Workorder $workorder)
    {
        $resolver = app(ManualIplBranchRuleResolver::class);
        $manualId = (int) ($workorder->unit->manual_id ?? 0);

        return $components
            ->filter(function (Component $component) use ($resolver, $workorder, $manualId): bool {
                return $resolver->allowsComponentForUnit(
                    $workorder->unit,
                    (string) ($component->ipl_num ?? ''),
                    $manualId
                );
            })
            ->values();
    }

    private function rebuildStdSnapshotForForm(Workorder $workorder): void
    {
        $workorder->loadMissing('unit.manuals');
        app(WorkorderStdProcessItemsService::class)->rebuild($workorder);
    }
}

