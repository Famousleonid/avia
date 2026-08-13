<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use App\Models\Code;
use App\Models\Component;
use App\Models\LogCard;
use App\Models\Manual;
use App\Models\Necessary;
use App\Models\StdProcess;
use App\Models\Workorder;
use App\Models\Tdr;
use App\Services\LogCardTdrAccessService;
use App\Services\LogCardPayloadProtectionService;
use App\Services\ManualIplBranchRuleResolver;
use App\Support\LogCardDestructionCertificate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
    public function partial(Request $request, $workorder_id)
    {
        $current_wo = Workorder::findOrFail($workorder_id);
        $codes = Code::all();
        $logCardTdrAccess = app(LogCardTdrAccessService::class)->forWorkorder($current_wo, auth()->user());
        $log_card = LogCard::where('workorder_id', $current_wo->id)->first();
        $componentData = [];
        if ($log_card && $log_card->component_data) {
            $componentData = is_array($log_card->component_data)
                ? $log_card->component_data
                : json_decode($log_card->component_data, true);
        }
        $componentData = is_array($componentData) ? $componentData : [];
        $componentData = collect($componentData)
            ->map(function ($row, int $storageIndex) {
                if (is_array($row)) {
                    $row['_storage_index'] = $storageIndex;
                }

                return $row;
            })
            ->all();
        $editMode = $request->boolean('edit') && $log_card && ! empty($componentData);

        $savedComponentRows = collect($componentData)
            ->filter(fn ($row) => is_array($row)
                && ($row['row_type'] ?? '') !== 'manual'
                && ! empty($row['component_id']));
        $savedComponentIds = $savedComponentRows
            ->pluck('component_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
        $savedComponentsById = Component::with(['manual', 'assemblies'])
            ->whereIn('id', $savedComponentIds)
            ->get()
            ->keyBy('id');
        $componentData = $this->sortSavedLogCardRowsByPartNumber($componentData, $savedComponentsById);

        $primaryManualId = (int) ($current_wo->unit->manual_id ?? 0);
        $primarySavedIds = $savedComponentIds
            ->filter(fn ($id) => (int) ($savedComponentsById->get($id)->manual_id ?? 0) === $primaryManualId)
            ->values()
            ->all();
        $ctx = $this->prepareGroupedLogCardComponents($current_wo, null, true, $primarySavedIds);
        $ctx['components'] = $this->componentsForSavedLogCardRows($ctx['components'], $componentData);

        $savedExtraManualSections = collect();
        if ($editMode) {
            $savedExtraManualIds = $savedComponentIds
                ->map(fn ($id) => (int) ($savedComponentsById->get($id)->manual_id ?? 0))
                ->filter(fn ($manualId) => $manualId > 0 && $manualId !== $primaryManualId)
                ->unique()
                ->values();

            $savedExtraManualSections = $savedExtraManualIds
                ->map(function (int $manualId) use ($current_wo, $savedComponentRows, $savedComponentsById) {
                    $manual = Manual::find($manualId);
                    if (! $manual) {
                        return null;
                    }

                    $manualSavedIds = $savedComponentRows
                        ->filter(function ($row) use ($manualId, $savedComponentsById) {
                            $componentId = (int) ($row['component_id'] ?? 0);

                            return (int) ($savedComponentsById->get($componentId)->manual_id ?? 0) === $manualId;
                        })
                        ->pluck('component_id')
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all();

                    return array_merge(
                        $this->prepareGroupedLogCardComponents($current_wo, $manualId, false, $manualSavedIds),
                        [
                            'manual' => $manual,
                            'sectionKey' => 'manual_'.$manualId,
                        ]
                    );
                })
                ->filter()
                ->values();
        }
        $availableLogCardManuals = Manual::query()
            ->whereKeyNot((int) ($current_wo->unit->manual_id ?? 0))
            ->get(['id', 'number', 'title'])
            ->sortBy(
                fn (Manual $manual): string => (string) $manual->number,
                SORT_NATURAL | SORT_FLAG_CASE
            )
            ->values();


        [$presetByIplGroup, $separateQueue] = $this->splitLogCardComponentPresets($componentData);

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
            'editing_saved_log_card' => (bool) $editMode,
            'original_component_data' => $log_card
                ? collect($componentData)->map(function ($row) {
                    if (is_array($row)) {
                        unset($row['_storage_index']);
                    }

                    return $row;
                })->values()->all()
                : [],
            'group_map' => $tabMetaGroupMap,
            'group_keys_ordered' => $groupKeysOrdered,
            'read_only' => (bool) ($logCardTdrAccess['read_only'] ?? false),
            'read_only_message' => $logCardTdrAccess['message'] ?? null,
        ];

        return view(
            'admin.log_card.partial',
            array_merge(compact(
                'current_wo',
                'log_card',
                'codes',
                'componentData',
                'presetByIplGroup',
                'separateQueue',
                'tabMeta',
                'logCardTdrAccess',
                'availableLogCardManuals',
                'editMode',
                'savedExtraManualSections'
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
     *   orderedComponents: \Illuminate\Support\Collection,
     *   components: \Illuminate\Database\Eloquent\Collection,
     *   tdrs: \Illuminate\Database\Eloquent\Collection,
     *   code: ?\App\Models\Code,
     *   necessary: ?\App\Models\Necessary
     * }
     */
    public function manualComponentsPartial(Request $request, Workorder $workorder, Manual $manual)
    {
        $logCardTdrAccess = app(LogCardTdrAccessService::class)->forWorkorder($workorder, auth()->user());
        if ($logCardTdrAccess['read_only'] ?? false) {
            return response($logCardTdrAccess['message'] ?? 'Log Card editing is locked.', 423);
        }

        $ctx = $this->prepareGroupedLogCardComponents($workorder, (int) $manual->id, false);

        return view('admin.log_card.partials.draft-manual-rows', array_merge($ctx, [
            'manual' => $manual,
            'sectionKey' => 'manual_'.$manual->id,
            'logCardTdrReadOnly' => false,
        ]));
    }

    private function prepareGroupedLogCardComponents(
        Workorder $current_wo,
        ?int $manualId = null,
        bool $filterForWorkorderUnit = true,
        array $alwaysIncludeComponentIds = []
    ): array
    {
        $manual_id = $manualId ?: (int) $current_wo->unit->manual_id;
        $manual = Manual::find($manual_id);

        $necessary = Necessary::where('name', 'Order New')->first();
        $code = Code::missing();

        $alwaysIncludeComponentIds = collect($alwaysIncludeComponentIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $components = Component::with(['assemblies'])
            ->where('manual_id', $manual_id)
            ->where(function ($query) use ($alwaysIncludeComponentIds) {
                $query->where('log_card', 1);
                if ($alwaysIncludeComponentIds->isNotEmpty()) {
                    $query->orWhereIn('id', $alwaysIncludeComponentIds);
                }
            })
            ->get();
        if ($filterForWorkorderUnit) {
            $components = $this->filterLogCardComponentsForUnit($components, $current_wo);
            if ($alwaysIncludeComponentIds->isNotEmpty()) {
                $savedComponents = Component::with(['assemblies'])
                    ->where('manual_id', $manual_id)
                    ->whereIn('id', $alwaysIncludeComponentIds)
                    ->get();
                $components = $components
                    ->merge($savedComponents)
                    ->unique('id');
            }
        }
        $components = $this->sortLogCardComponentsByPartNumber($components);

        $tdrs = Tdr::where('workorder_id', $current_wo->id)->with(['codes', 'necessaries'])->get();

        $groupedComponents = $components->groupBy(function ($component) {
            if (preg_match('/^([A-Za-z0-9]+-\d+)/', $component->ipl_num, $matches)) {
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
                'components' => $this->sortLogCardComponentsByPartNumber($filteredGroup)->map(function ($component) use ($tdrs, $code, $necessary) {
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

        $groupedComponents = $groupedComponents->sort(function (array $left, array $right): int {
            $leftComponent = data_get($left['components']->first(), 'component');
            $rightComponent = data_get($right['components']->first(), 'component');
            $partNumberCompare = strnatcasecmp(
                (string) ($leftComponent?->part_number ?? ''),
                (string) ($rightComponent?->part_number ?? '')
            );

            return $partNumberCompare !== 0
                ? $partNumberCompare
                : StdProcess::compareIplValues(
                    (string) ($left['ipl_group'] ?? ''),
                    (string) ($right['ipl_group'] ?? '')
                );
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

        $separateComponents = $separateComponents
            ->sort(function (array $left, array $right): int {
                $partNumberCompare = strnatcasecmp(
                    (string) ($left['component']->part_number ?? ''),
                    (string) ($right['component']->part_number ?? '')
                );

                return $partNumberCompare !== 0
                    ? $partNumberCompare
                    : ((int) ($left['unit_index'] ?? 0) <=> (int) ($right['unit_index'] ?? 0));
            })
            ->values();

        $orderedComponents = $groupedComponents
            ->flatMap(function (array $group, $groupIndex) {
                return $group['components']->map(function (array $componentDataRow) use ($group, $groupIndex): array {
                    return [
                        'row_type' => 'component',
                        'component' => $componentDataRow['component'],
                        'component_data_row' => $componentDataRow,
                        'group' => $group,
                        'group_index' => (string) $groupIndex,
                        'unit_index' => 0,
                    ];
                });
            })
            ->concat($separateComponents->map(function (array $row, int $index): array {
                return [
                    'row_type' => 'unit',
                    'component' => $row['component'],
                    'unit_row' => $row,
                    'separate_index' => $index,
                    'unit_index' => (int) ($row['unit_index'] ?? 0),
                ];
            }))
            ->sort(function (array $left, array $right): int {
                $leftComponent = $left['component'];
                $rightComponent = $right['component'];
                $partNumberCompare = strnatcasecmp(
                    (string) ($leftComponent->part_number ?? ''),
                    (string) ($rightComponent->part_number ?? '')
                );
                if ($partNumberCompare !== 0) {
                    return $partNumberCompare;
                }

                $iplCompare = StdProcess::compareIplValues(
                    (string) ($leftComponent->ipl_num ?? ''),
                    (string) ($rightComponent->ipl_num ?? '')
                );
                if ($iplCompare !== 0) {
                    return $iplCompare;
                }

                $componentCompare = (int) $leftComponent->id <=> (int) $rightComponent->id;
                if ($componentCompare !== 0) {
                    return $componentCompare;
                }

                return (int) ($left['unit_index'] ?? 0) <=> (int) ($right['unit_index'] ?? 0);
            })
            ->values();

        return [
            'groupedComponents' => $groupedComponents,
            'separateComponents' => $separateComponents,
            'orderedComponents' => $orderedComponents,
            'components' => $components,
            'tdrs' => $tdrs,
            'code' => $code,
            'necessary' => $necessary,
            'manual' => $manual,
        ];
    }

    private function componentsForSavedLogCardRows($components, array $componentData)
    {
        $componentIds = collect($componentData)
            ->filter(fn ($row) => is_array($row) && ! empty($row['component_id']))
            ->map(fn ($row) => (int) $row['component_id'])
            ->filter()
            ->unique()
            ->values();

        if ($componentIds->isEmpty()) {
            return $components;
        }

        $existingIds = $components->pluck('id')->map(fn ($id) => (int) $id);
        $missingIds = $componentIds->diff($existingIds)->values();
        if ($missingIds->isEmpty()) {
            return $components;
        }

        return $components
            ->merge(Component::with(['manual', 'assemblies'])->whereIn('id', $missingIds)->get())
            ->unique('id')
            ->values();
    }

    private function filterLogCardComponentsForUnit($components, Workorder $workorder)
    {
        $resolver = app(ManualIplBranchRuleResolver::class);
        $manualId = (int) ($workorder->unit->manual_id ?? 0);

        return $components
            ->filter(function (Component $component) use ($resolver, $workorder, $manualId): bool {
                return $workorder->unit
                    && $resolver->allowsComponentForUnit(
                        $workorder->unit,
                        (string) ($component->ipl_num ?? ''),
                        $manualId
                    );
            })
            ->values();
    }

    private function sortSavedLogCardRowsByPartNumber(array $rows, $componentsById): array
    {
        $sections = [];
        $section = ['header' => null, 'rows' => []];

        foreach ($rows as $row) {
            if (is_array($row) && ($row['row_type'] ?? '') === 'manual') {
                if ($section['header'] !== null || $section['rows'] !== []) {
                    $sections[] = $section;
                }
                $section = ['header' => $row, 'rows' => []];

                continue;
            }

            $section['rows'][] = $row;
        }

        if ($section['header'] !== null || $section['rows'] !== []) {
            $sections[] = $section;
        }

        return collect($sections)
            ->flatMap(function (array $section) use ($componentsById): array {
                $sortedRows = collect($section['rows'])
                    ->sort(function ($left, $right) use ($componentsById): int {
                        if (! is_array($left) || ! is_array($right)) {
                            return is_array($left) <=> is_array($right);
                        }

                        $leftComponent = $componentsById->get((int) ($left['component_id'] ?? 0));
                        $rightComponent = $componentsById->get((int) ($right['component_id'] ?? 0));
                        $partNumberCompare = strnatcasecmp(
                            trim((string) ($left['part_number'] ?? $leftComponent?->part_number ?? '')),
                            trim((string) ($right['part_number'] ?? $rightComponent?->part_number ?? ''))
                        );
                        if ($partNumberCompare !== 0) {
                            return $partNumberCompare;
                        }

                        $iplCompare = StdProcess::compareIplValues(
                            (string) ($leftComponent?->ipl_num ?? ''),
                            (string) ($rightComponent?->ipl_num ?? '')
                        );

                        return $iplCompare !== 0
                            ? $iplCompare
                            : ((int) ($left['unit_index'] ?? 0) <=> (int) ($right['unit_index'] ?? 0));
                    })
                    ->values()
                    ->all();

                return $section['header'] === null
                    ? $sortedRows
                    : array_merge([$section['header']], $sortedRows);
            })
            ->values()
            ->all();
    }

    private function sortLogCardComponentsByPartNumber($components)
    {
        return $components
            ->sort(function (Component $left, Component $right): int {
                $partNumberCompare = strnatcasecmp(
                    (string) ($left->part_number ?? ''),
                    (string) ($right->part_number ?? '')
                );
                if ($partNumberCompare !== 0) {
                    return $partNumberCompare;
                }

                return StdProcess::compareIplValues(
                    (string) ($left->ipl_num ?? ''),
                    (string) ($right->ipl_num ?? '')
                );
            })
            ->values();
    }

    public function logCardForm(Request $request, $id)
    {
        // Загрузка Workorder по ID
        $current_wo = Workorder::findOrFail($id);
        // Получаем данные о manual, связанном с этим Workorder
        $manual = $current_wo->unit->manual_id;
        $manual_wo = $current_wo->unit->manuals;

        $builders = Builder::all();

        $log_card = LogCard::where('workorder_id', $current_wo->id)->first();

        // Получаем массив из JSON
        $componentData = [];
        if ($log_card && $log_card->component_data) {
            $componentData = is_array($log_card->component_data)
                ? $log_card->component_data
                : json_decode($log_card->component_data, true);
        }
        $componentData = is_array($componentData) ? $componentData : [];

        $manualIds = collect($componentData)
            ->filter(fn ($row) => is_array($row) && ! empty($row['manual_id']))
            ->map(fn ($row) => (int) $row['manual_id'])
            ->push((int) $manual)
            ->filter()
            ->unique()
            ->values();

        $componentIds = collect($componentData)
            ->filter(fn ($row) => is_array($row) && ! empty($row['component_id']))
            ->map(fn ($row) => (int) $row['component_id'])
            ->filter()
            ->unique()
            ->values();

        $manuals = Manual::whereIn('id', $manualIds)
            ->with('builder')
            ->get();

        $components = Component::whereIn('id', $componentIds)->get();

        // A manual separator is useful only when the printed Log Card combines
        // components from more than one manual. Infer missing legacy manual_id
        // values from the component itself before deciding what to print.
        $componentsById = $components->keyBy(fn (Component $component) => (int) $component->id);
        $representedManualIds = collect($componentData)
            ->filter(fn ($row) => is_array($row)
                && ($row['row_type'] ?? '') !== 'manual'
                && ! empty($row['component_id']))
            ->map(function ($row) use ($componentsById) {
                $manualId = (int) ($row['manual_id'] ?? 0);
                if ($manualId > 0) {
                    return $manualId;
                }

                return (int) ($componentsById->get((int) $row['component_id'])?->manual_id ?? 0);
            })
            ->filter()
            ->unique()
            ->values();

        if ($representedManualIds->count() <= 1) {
            $componentData = collect($componentData)
                ->reject(fn ($row) => is_array($row) && ($row['row_type'] ?? '') === 'manual')
                ->values()
                ->all();
        }

        $log_count= count($componentData);
        $firstPrintPageRows = 9;
        $continuationPrintPageRows = 20;

        // Разделяем на две части
        $componentData_1 = [];
        $componentData_2 = [];

        if ($log_count > 9) {
            $componentData_1 = array_slice($componentData, 0, $firstPrintPageRows);
            $componentData_2 = array_slice($componentData, $firstPrintPageRows);
        }
        $continuationPrintPages = array_chunk($componentData_2, $continuationPrintPageRows);
        $totalPrintPages = 1 + count($continuationPrintPages);
        $log_count_1= count($componentData_1);
        $log_count_2= count($componentData_2);

        // Загружаем коды для отображения названий
        $codes = Code::all();

        if ($log_count > 9) {

            return view('admin.log_card.logCardForm2', compact('current_wo','manuals', 'builders',  'log_card',
                'components' ,'componentData_1',
                'componentData_2', 'log_count_1', 'log_count_2', 'codes',
                'firstPrintPageRows', 'continuationPrintPageRows',
                'continuationPrintPages', 'totalPrintPages'
            ));

        }else {
            return view('admin.log_card.logCardForm', compact('current_wo','manuals', 'builders', 'componentData', 'log_card', 'components' ,'log_count', 'codes'));

        }



    }

    /**
     * Printable Certificate of Destruction.
     */
    public function sertDistrForm(Request $request, $id)
    {
        $current_wo = Workorder::with('customer')->findOrFail($id);
        $logCard = LogCardDestructionCertificate::logCardForWorkorder($current_wo);
        $rows = LogCardDestructionCertificate::rowsForWorkorder($current_wo);

        return view('admin.log_card.sertDistrForm', [
            'current_wo' => $current_wo,
            'rows' => $rows,
            'manualRow' => LogCardDestructionCertificate::manualRowFor($logCard),
            'manualSelected' => LogCardDestructionCertificate::manualSelectedFor($logCard),
            'certificateDate' => LogCardDestructionCertificate::certificateDateFor($logCard),
            'saveUrl' => route('log_card.destruction_certificate.update', ['id' => $current_wo->id]),
        ]);
    }

    public function updateDestructionCertificate(Request $request, $id)
    {
        abort_unless($request->user()?->can('manager.qa'), 403);

        $current_wo = Workorder::findOrFail($id);
        $data = $request->validate([
            'selected_keys' => ['nullable', 'array'],
            'selected_keys.*' => ['string'],
            'certificate_date' => ['nullable', 'string', 'max:32'],
            'manual_selected' => ['nullable', 'boolean'],
            'manual_row' => ['nullable', 'array'],
            'manual_row.part_number' => ['nullable', 'string', 'max:255'],
            'manual_row.description' => ['nullable', 'string', 'max:255'],
            'manual_row.serial_number' => ['nullable', 'string', 'max:255'],
        ]);

        $logCard = LogCard::firstOrCreate(['workorder_id' => $current_wo->id]);
        $logCard->destruction_certificate_data = LogCardDestructionCertificate::normalizeCertificateData($data);
        $logCard->save();

        return response()->json([
            'ok' => true,
            'data' => $logCard->destruction_certificate_data,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
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
        $workorder = Workorder::findOrFail((int) $request->input('workorder_id'));
        if ($lockedResponse = $this->denyLockedTdrLogCardMutation($request, $workorder)) {
            return $lockedResponse;
        }

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

        $incomingRows = $this->decodeLogCardRows($request->input('component_data'));
        $protectedRows = app(LogCardPayloadProtectionService::class)->protectCreate($incomingRows);
        $componentData = $this->normalizeLogCardComponentData(
            json_encode($protectedRows, JSON_UNESCAPED_UNICODE),
            $workorder
        );

//        dd($componentData);

        $logCard = \App\Models\LogCard::create([
            'workorder_id'    => $workorder_id,
            'component_data' => $componentData,
        ]);

        $summary = LogCard::summarizeForActivity($logCard);
        $logCard->logActivityEvent('created', [], $summary);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Log Card created successfully!', 'log_card_id' => $logCard->id]);
        }

        return redirect()->route('tdrs.show', ['id' => $workorder_id])
                ->with('success', 'Log Card created successfully!');
    }



        // Получаем массив из JSON
        // Загружаем коды для отображения названий


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
        $components = $this->filterLogCardComponentsForUnit($components, $current_wo);

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
        $log_card = \App\Models\LogCard::findOrFail($id);
        $workorder = Workorder::findOrFail($log_card->workorder_id);
        if ($lockedResponse = $this->denyLockedTdrLogCardMutation($request, $workorder)) {
            return $lockedResponse;
        }

        $request->validate([
            'workorder_id' => 'required|integer|exists:workorders,id',
            'component_data' => 'required|string',
        ]);
        $this->validateLogCardComponentData($request);

        if ((int) $request->input('workorder_id') !== (int) $log_card->workorder_id) {
            throw ValidationException::withMessages([
                'workorder_id' => __('A Log Card cannot be moved to another workorder.'),
            ]);
        }

        $incomingRows = $this->decodeLogCardRows($request->input('component_data'));
        $protector = app(LogCardPayloadProtectionService::class);

        $log_card = DB::transaction(function () use ($id, $incomingRows, $workorder, $protector) {
            $lockedLogCard = LogCard::query()->lockForUpdate()->findOrFail($id);
            $beforeComponentData = $lockedLogCard->component_data;
            $storedRows = $this->decodeLogCardRows($beforeComponentData);
            $protectedRows = $protector->protectUpdate($incomingRows, $storedRows);

            $lockedLogCard->component_data = $this->normalizeLogCardComponentData(
                json_encode($protectedRows, JSON_UNESCAPED_UNICODE),
                $workorder
            );
            if ($lockedLogCard->isDirty()) {
                $changes = LogCard::buildActivityChanges([
                    'component_data' => [$beforeComponentData, $lockedLogCard->component_data],
                ]);
                $lockedLogCard->save();
                $lockedLogCard->logActivityEvent('updated', $changes['old'], $changes['attributes']);
            }

            return $lockedLogCard;
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Log Card updated successfully!']);
        }

        return redirect()->route('tdrs.show', ['id' => $log_card->workorder_id])
                ->with('success', 'Log Card updated successfully!');
    }

    public function updateInlineField(Request $request, LogCard $log_card)
    {
        $workorder = Workorder::findOrFail($log_card->workorder_id);
        if ($lockedResponse = $this->denyLockedTdrLogCardMutation($request, $workorder)) {
            return $lockedResponse;
        }

        $data = $request->validate([
            'row' => ['required', 'integer', 'min:0', 'max:500'],
            'field' => ['required', 'in:included,serial_number,assy_serial_number,reason,new_serial_number'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        $rowIndex = (int) $data['row'];
        $afterValue = trim((string) ($data['value'] ?? ''));
        if ($data['field'] === 'included') {
            $afterValue = filter_var($afterValue, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        DB::transaction(function () use ($log_card, $rowIndex, $data, $afterValue) {
            $lockedLogCard = LogCard::query()->lockForUpdate()->findOrFail($log_card->id);
            $rows = $this->decodeLogCardRows($lockedLogCard->component_data);
            abort_unless(
                isset($rows[$rowIndex])
                && is_array($rows[$rowIndex])
                && ($rows[$rowIndex]['row_type'] ?? '') !== 'manual'
                && ! empty($rows[$rowIndex]['component_id']),
                422
            );

            $beforeValue = data_get($rows, $rowIndex.'.'.$data['field']);
            if ($beforeValue === $afterValue) {
                return;
            }

            $rows[$rowIndex][$data['field']] = $afterValue;
            $lockedLogCard->component_data = json_encode(array_values($rows), JSON_UNESCAPED_UNICODE);
            $lockedLogCard->save();
            $lockedLogCard->logActivityEvent(
                'updated',
                ['component_data.'.$rowIndex.'.'.$data['field'] => $beforeValue],
                ['component_data.'.$rowIndex.'.'.$data['field'] => $afterValue],
                [
                    'row' => $rowIndex,
                    'field' => $data['field'],
                    'source' => 'tdrs_show_log_card_inline',
                    'side' => 'left',
                ]
            );
        });

        return response()->json([
            'success' => true,
            'field' => $data['field'],
            'value' => $afterValue,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $log_card = LogCard::findOrFail($id);
        $workorder = Workorder::findOrFail($log_card->workorder_id);
        if ($lockedResponse = $this->denyLockedTdrLogCardMutation(request(), $workorder)) {
            return $lockedResponse;
        }

        $workorderId = $log_card->workorder_id;
        DB::transaction(function () use ($log_card) {
            $lockedLogCard = LogCard::query()->lockForUpdate()->findOrFail($log_card->id);
            if (app(LogCardPayloadProtectionService::class)->logCardContainsQaOwnedData(
                $lockedLogCard->component_data,
                $lockedLogCard->component_data_out,
                $lockedLogCard->destruction_certificate_data
            )) {
                throw ValidationException::withMessages([
                    'log_card' => __('A Log Card containing QA data cannot be reset from the ordinary Log Card screen.'),
                ]);
            }

            $summary = LogCard::summarizeForActivity($lockedLogCard);
            $lockedLogCard->logActivityEvent('deleted', $summary, []);
            $lockedLogCard->delete();
        });

        request()->session()->flash('success', 'Log Card reset successfully!');

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Log Card reset successfully!',
                'workorder_id' => $workorderId,
            ]);
        }

        return redirect()->route('tdrs.show', ['id' => $workorderId])
            ->with('success', 'Log Card reset successfully!');
    }

    /**
     * Разрешает сохранить Log Card с любым непустым подмножеством позиций (минимум одна).
     *
     * @throws ValidationException
     */
    private function normalizeLogCardComponentData(string $raw, Workorder $workorder): string
    {
        $rows = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($rows)) {
            return $raw;
        }

        $componentIds = collect($rows)
            ->filter(fn ($row) => is_array($row) && ! empty($row['component_id']))
            ->map(fn ($row) => (int) $row['component_id'])
            ->filter()
            ->unique()
            ->values();

        if ($componentIds->isEmpty()) {
            return json_encode(array_values($rows), JSON_UNESCAPED_UNICODE);
        }

        $components = Component::with('assemblies')
            ->whereIn('id', $componentIds)
            ->get()
            ->keyBy('id');

        $tdrAssyByComponent = Tdr::where('workorder_id', $workorder->id)
            ->whereIn('component_id', $componentIds)
            ->with('orderComponentAssembly')
            ->get()
            ->filter(fn (Tdr $tdr) => $tdr->orderComponentAssembly)
            ->groupBy('component_id')
            ->map(fn ($items) => $items->first()->orderComponentAssembly);

        $missingCode = Code::missing();
        $orderNew = Necessary::query()->where('name', 'Order New')->first();
        $tdrByComponent = Tdr::query()
            ->where('workorder_id', $workorder->id)
            ->whereIn('component_id', $componentIds)
            ->with(['codes', 'necessaries'])
            ->get()
            ->groupBy('component_id')
            ->map(fn ($items) => $items->first());

        foreach ($rows as &$row) {
            if (! is_array($row) || ($row['row_type'] ?? '') === 'manual' || empty($row['component_id'])) {
                continue;
            }

            $componentId = (int) $row['component_id'];
            $component = $components->get($componentId);
            if (! array_key_exists('reason', $row)) {
                $row['reason'] = $this->getReasonForRemove(
                    $tdrByComponent->get($componentId),
                    $missingCode,
                    $orderNew
                );
            }
            $assemblyId = (int) ($row['component_assembly_id'] ?? 0);
            $assyPartNumber = trim((string) ($row['assy_part_number'] ?? ''));
            $assyIplNum = trim((string) ($row['assy_ipl_num'] ?? ''));
            $assembly = null;

            if ($component && $assemblyId > 0) {
                $assembly = $component->assemblies->firstWhere('id', $assemblyId);
            }

            if (! $assembly && $assyPartNumber === '' && $assyIplNum === '') {
                $assembly = $tdrAssyByComponent->get($componentId);
            }

            if (! $assembly && $component && $assyPartNumber === '' && $assyIplNum === '' && $component->assemblies->count() === 1) {
                $assembly = $component->assemblies->first();
            }

            if ($assembly) {
                $row['component_assembly_id'] = (string) $assembly->id;
                $row['assy_part_number'] = (string) ($assembly->assy_part_number ?? '');
                $row['assy_ipl_num'] = (string) ($assembly->assy_ipl_num ?? '');
                $row['units_assy'] = (string) ($assembly->units_assy ?? ($row['units_assy'] ?? ''));

                continue;
            }

            if ($component && $assyPartNumber === '' && trim((string) ($component->assy_part_number ?? '')) !== '') {
                $row['assy_part_number'] = trim((string) $component->assy_part_number);
            }

            if ($component && ! empty($row['unit_index'])) {
                $row['units_assy'] = (string) ($component->units_assy ?? ($row['units_assy'] ?? ''));
            }

            if ($assemblyId === 1 && trim((string) ($row['assy_part_number'] ?? '')) === '' && $assyIplNum === '') {
                $row['component_assembly_id'] = '';
            }
        }
        unset($row);

        return json_encode(array_values($rows), JSON_UNESCAPED_UNICODE);
    }

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
        $hasComponentRow = false;
        foreach ($decoded as $row) {
            if (is_array($row) && ($row['row_type'] ?? '') === 'manual') {
                continue;
            }
            if (!is_array($row) || (!isset($row['component_id']) || $row['component_id'] === '' || $row['component_id'] === null)) {
                throw ValidationException::withMessages([
                    'component_data' => [__('Каждая строка должна содержать component_id.')],
                ]);
            }
            $hasComponentRow = true;
        }
        if (! $hasComponentRow) {
            throw ValidationException::withMessages([
                'component_data' => [__('Добавьте в Log Card хотя бы одну позицию (выберите компонент).')],
            ]);
        }
    }

    private function decodeLogCardRows(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($row, $key) => is_int($key) && is_array($row))
            ->values()
            ->all();
    }

    private function denyLockedTdrLogCardMutation(Request $request, Workorder $workorder): JsonResponse|RedirectResponse|null
    {
        $access = app(LogCardTdrAccessService::class)->forWorkorder($workorder, $request->user());
        if (! ($access['read_only'] ?? false)) {
            return null;
        }

        $message = $access['message'] ?? 'Log Card editing is locked. Please contact Quality Manager.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 423);
        }

        return redirect()
            ->route('tdrs.show', ['id' => $workorder->id])
            ->withErrors(['log_card' => $message]);
    }

}
