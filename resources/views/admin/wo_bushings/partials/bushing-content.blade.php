@php
    $processAssignments = $processAssignments ?? [];
@endphp
@if($woBushing && ($linesExist ?? !empty($bushData)))
    @php
        $machiningProcessName = \App\Models\ProcessName::where('name', 'Machining')->first();
        $stressReliefProcessName = null;
        $ndtProcessName = \App\Models\ProcessName::where('name', 'NDT-1')->first();
        $passivationProcessName = \App\Models\ProcessName::where('name', 'Passivation')->first();
        $cadProcessName = \App\Models\ProcessName::where('name', 'Cad plate')->first();
        $anodizingProcessName = \App\Models\ProcessName::where('name', 'Anodizing')->first();
        $xylanProcessName = \App\Models\ProcessName::where('name', 'Xylan coating')->first();
        $hasMachiningData = $hasStressReliefData = $hasNdtData = $hasPassivationData = $hasCadData = $hasAnodizingData = $hasXylanData = false;
        foreach ($bushData ?? [] as $bushItem) {
            if (!empty($bushItem['processes']['machining'])) {
                $hasMachiningData = true;
            }
            if (!empty($bushItem['processes']['stress_relief']) && !$stressReliefProcessName) {
                $srProc = \App\Models\Process::find($bushItem['processes']['stress_relief']);
                if ($srProc) {
                    $stressReliefProcessName = \App\Models\ProcessName::find($srProc->process_names_id);
                }
            }
            if (isset($bushItem['processes']['stress_relief']) && !empty($bushItem['processes']['stress_relief'])) {
                $hasStressReliefData = true;
            }
            if (isset($bushItem['processes']['ndt']) && !empty($bushItem['processes']['ndt'])) {
                $hasNdtData = true;
            }
            if (isset($bushItem['processes']['passivation']) && !empty($bushItem['processes']['passivation'])) {
                $hasPassivationData = true;
            }
            if (isset($bushItem['processes']['cad']) && !empty($bushItem['processes']['cad'])) {
                $hasCadData = true;
            }
            if (isset($bushItem['processes']['anodizing']) && !empty($bushItem['processes']['anodizing'])) {
                $hasAnodizingData = true;
            }
            if (isset($bushItem['processes']['xylan']) && !empty($bushItem['processes']['xylan'])) {
                $hasXylanData = true;
            }
        }
        if (!$stressReliefProcessName) {
            $stressReliefProcessName = \App\Models\ProcessName::where('name', 'Bake (Stress relief)')->first()
                ?? \App\Models\ProcessName::where('name', 'Stress Relief')->first();
        }
        $batchCreateUrl = route('wo_bushings.batches.create', $woBushing);
        $batchUngroupUrl = route('wo_bushings.batches.ungroup', $woBushing);
    @endphp
    {{-- Показ сохраненных данных в режиме просмотра --}}
    <style>
        .bushing-view-table { table-layout: fixed; width: 100%; }
        .bushing-view-table .bushing-col { white-space: nowrap; }
        .bushing-view-table .vendor-select-sm { min-width: 56px; max-width: 76px; font-size: 0.85rem; padding: 0.12rem 0.2rem; }
        .bushing-subcol-batch, .bushing-subcol-form { vertical-align: middle; }
        .bushing-view-table thead.wo-bush-thead tr:first-child th {
            position: sticky; top: 0; z-index: 12; background: #031e3a;
        }
        .bushing-view-table thead.wo-bush-thead tr:nth-child(2) th {
            position: sticky; top: 2.6rem; z-index: 11; background: #031e3a;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.06);
        }
        .bushing-view-table .bushing-batch-group-checkbox,
        .bushing-view-table .bushing-batch-ungroup-checkbox { cursor: pointer; flex-shrink: 0; }
        .bushing-view-table th.bushing-subcol-form > .d-flex { overflow: hidden; max-width: 100%; }
        @media (max-width: 1280px) {
            .bushing-view-table thead th { font-size: .8rem; padding: .24rem .18rem; }
            .bushing-view-table .vendor-select-sm { min-width: 50px; max-width: 64px; font-size: .7rem; }
            .bushing-view-table .form-btn { font-size: .7rem; padding: .15rem .28rem; }
            .bushing-view-table thead.wo-bush-thead tr:nth-child(2) th { top: 2.35rem; }
        }
    </style>
    <div class="w-100 mt-3">
        <div class="table-wrapper table-scroll-container w-100" style="max-height: calc(100vh - 280px); overflow: auto;">
            <table class="display table shadow table-hover align-middle table-bordered bg-gradient dir-table bushing-view-table w-100">
                <colgroup>
                    <col style="width: 14%;">
                    <col style="width: 4%;">
                    @for($i = 0; $i < 7; $i++)
                        <col style="width: 11%;">
                    @endfor
                </colgroup>
                <thead class="wo-bush-thead" style="background: #031e3a;">
                    <tr class="header-row">
                        <th rowspan="2" class="text-primary text-center align-middle bushing-col">{{ __('Bushings') }}</th>
                        <th rowspan="2" class="text-primary text-center align-middle">{{ __('QTY') }}</th>
                        <th class="text-primary text-center">{{ __('Machining') }}</th>
                        <th class="text-primary text-center">{{ __('Stress Relief') }}</th>
                        <th class="text-primary text-center">{{ __('NDT') }}</th>
                        <th class="text-primary text-center">{{ __('Passivation') }}</th>
                        <th class="text-primary text-center">{{ __('CAD') }}</th>
                        <th class="text-primary text-center">{{ __('Anodizing') }}</th>
                        <th class="text-primary text-center">{{ __('Xylan') }}</th>
                    </tr>
                    <tr class="bushing-process-subhead">
                        @php
                            $headerCells = [
                                ['key' => 'machining', 'vendor' => 'vendor_machining', 'pn' => $machiningProcessName, 'has' => $hasMachiningData],
                                ['key' => 'stress_relief', 'vendor' => 'vendor_stress_relief', 'pn' => $stressReliefProcessName, 'has' => $hasStressReliefData],
                                ['key' => 'ndt', 'vendor' => 'vendor_ndt', 'pn' => $ndtProcessName, 'has' => $hasNdtData],
                                ['key' => 'passivation', 'vendor' => 'vendor_passivation', 'pn' => $passivationProcessName, 'has' => $hasPassivationData],
                                ['key' => 'cad', 'vendor' => 'vendor_cad', 'pn' => $cadProcessName, 'has' => $hasCadData],
                                ['key' => 'anodizing', 'vendor' => 'vendor_anodizing', 'pn' => $anodizingProcessName, 'has' => $hasAnodizingData],
                                ['key' => 'xylan', 'vendor' => 'vendor_xylan', 'pn' => $xylanProcessName, 'has' => $hasXylanData],
                            ];
                        @endphp
                        @foreach($headerCells as $hc)
                            <th class="bushing-subcol-batch text-center p-1">
                                @if($woBushing)
                                    <div class="d-flex flex-column gap-1 align-items-stretch">
                                        <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap mt-1">
                                            <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="{{ $hc['vendor'] }}">
                                                <option value="">{{ __('Vendor') }}</option>
                                                @foreach($vendors as $vendor)
                                                    <option style="font-size: 10px;" value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                                @endforeach
                                            </select>
                                            @if($hc['pn'] && $hc['has'])
                                                <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $hc['pn']->id]) }}"
                                                   target="_blank" class="btn btn-sm btn-outline-warning form-btn"
                                                   data-vendor-select="{{ $hc['vendor'] }}" data-process-key="{{ $hc['key'] }}">{{ __('Form') }}</a>
                                            @else
                                                <span class="text-muted small">{{ __('Form') }}</span>
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-info py-0 px-1 js-bushing-create-batch"
                                                data-url="{{ $batchCreateUrl }}" data-process-key="{{ $hc['key'] }}">{{ __('Group') }}</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 js-bushing-ungroup-batch"
                                                data-url="{{ $batchUngroupUrl }}" data-process-key="{{ $hc['key'] }}">{{ __('Ungroup') }}</button>
                                    </div>
                                @else
                                    <span class="text-muted small">{{ __('Form') }}</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php
                        $savedBushingsGrouped = collect();
                        foreach($bushData as $bushItem) {
                            if(isset($bushItem['bushing'])) {
                                $component = $bushings->flatten()->firstWhere('id', $bushItem['bushing']);
                                if($component) {
                                    $groupKey = $bushItem['group_key'] ?? null;
                                    if ($groupKey === null || $groupKey === '') {
                                        $groupKey = $component->bush_ipl_num ?: 'no_ipl';
                                    } elseif ($groupKey === 'No Bush IPL Number') {
                                        $groupKey = 'no_ipl';
                                    }
                                    if(!$savedBushingsGrouped->has($groupKey)) { $savedBushingsGrouped[$groupKey] = collect(); }
                                    $ndtValue = data_get($bushItem, 'processes.ndt', []);
                                    if (is_null($ndtValue)) { $ndtValue = []; } elseif (!is_array($ndtValue)) { $ndtValue = [$ndtValue]; }
                                    $savedBushingsGrouped[$groupKey]->push([
                                        'component' => $component,
                                        'line_id' => (int) ($bushItem['line_id'] ?? 0),
                                        'sort_order' => (int) ($bushItem['sort_order'] ?? 0),
                                        'data' => [
                                            'qty' => $bushItem['qty'] ?? 1,
                                            'machining' => data_get($bushItem, 'processes.machining'),
                                            'stress_relief' => data_get($bushItem, 'processes.stress_relief'),
                                            'ndt' => $ndtValue,
                                            'passivation' => data_get($bushItem, 'processes.passivation'),
                                            'cad' => data_get($bushItem, 'processes.cad'),
                                            'anodizing' => data_get($bushItem, 'processes.anodizing'),
                                            'xylan' => data_get($bushItem, 'processes.xylan'),
                                        ]
                                    ]);
                                }
                            }
                        }
                        $savedBushingsGrouped = $savedBushingsGrouped->map(function ($groupRows) {
                            return $groupRows->sortBy('sort_order')->values();
                        })->sortBy(function ($groupRows) {
                            return $groupRows->min('sort_order');
                        });
                    @endphp
                    @php
                        $batchLabelsByProcess = [];
                        foreach ($processAssignments as $componentAssignments) {
                            if (!is_array($componentAssignments)) {
                                continue;
                            }
                            foreach ($componentAssignments as $pKey => $assignment) {
                                $bId = (int) ($assignment['batch_id'] ?? 0);
                                if ($bId <= 0) {
                                    continue;
                                }
                                if (!isset($batchLabelsByProcess[$pKey])) {
                                    $batchLabelsByProcess[$pKey] = [];
                                }
                                $batchLabelsByProcess[$pKey][$bId] = true;
                            }
                        }
                        foreach ($batchLabelsByProcess as $pKey => $batches) {
                            $ids = array_keys($batches);
                            sort($ids, SORT_NUMERIC);
                            $labels = [];
                            foreach ($ids as $idx => $idVal) {
                                $labels[$idVal] = 'Grp ' . ($idx + 1);
                            }
                            $batchLabelsByProcess[$pKey] = $labels;
                        }
                        $sentLabelsByProcess = [];
                        $retLabelsByProcess = [];
                        foreach ($batchLabelsByProcess as $pKey => $labels) {
                            $ids = array_keys($labels);
                            sort($ids, SORT_NUMERIC);
                            $sentLabelsByProcess[$pKey] = [];
                            $retLabelsByProcess[$pKey] = [];
                            foreach ($ids as $idx => $idVal) {
                                $n = $idx + 1;
                                $sentLabelsByProcess[$pKey][(int) $idVal] = 'sent'.$n;
                                $retLabelsByProcess[$pKey][(int) $idVal] = 'Ret('.$n.')';
                            }
                        }
                    @endphp
                    @foreach($savedBushingsGrouped as $groupKey => $savedBushings)
                        @foreach($savedBushings as $savedBushing)
                            @php
                                $component = $savedBushing['component'];
                                $lineId = (int) ($savedBushing['line_id'] ?? 0);
                                $data = $savedBushing['data'];
                                $assignments = $lineId > 0 ? ($processAssignments[$lineId] ?? []) : [];
                                $machiningProcess = $machiningProcesses->firstWhere('id', $data['machining'] ?? null);
                                $stressReliefProcess = $stressReliefProcesses->firstWhere('id', $data['stress_relief'] ?? null);
                                $ndtIds = isset($data['ndt']) ? (array)$data['ndt'] : [];
                                $ndtNames = [];
                                foreach ($ndtIds as $ndtId) {
                                    $ndtProc = $ndtProcesses->firstWhere('id', $ndtId);
                                    if ($ndtProc) { $ndtNames[] = $ndtProc->process_name->name; }
                                }
                                $passivationProcess = $passivationProcesses->firstWhere('id', $data['passivation'] ?? null);
                                $cadProcess = $cadProcesses->firstWhere('id', $data['cad'] ?? null);
                                $anodizingProcess = $anodizingProcesses->firstWhere('id', $data['anodizing'] ?? null);
                                $xylanProcess = $xylanProcesses->firstWhere('id', $data['xylan'] ?? null);
                            @endphp
                            <tr>
                                <td class="ps-4 bushing-col"><strong>{{ $component->ipl_num }}</strong> —
                                    <span class="text-muted">{{ $component->part_number }}</span></td>
                                <td class="text-center">{{ $data['qty'] ?? '-' }}</td>
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'machining',
                                    'process' => $machiningProcess,
                                    'assignment' => $assignments['machining'] ?? null,
                                    'detailTitle' => $machiningProcess ? trim((string) $machiningProcess->process) : '',
                                    'batchLabel' => $batchLabelsByProcess['machining'][(int) (($assignments['machining']['batch_id'] ?? 0))] ?? 'Grp',
                                    'sentLabelsByProcess' => $sentLabelsByProcess,
                                    'retLabelsByProcess' => $retLabelsByProcess,
                                ])
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'stress_relief',
                                    'process' => $stressReliefProcess,
                                    'assignment' => $assignments['stress_relief'] ?? null,
                                    'detailTitle' => $stressReliefProcess ? trim((string) $stressReliefProcess->process) : '',
                                    'batchLabel' => $batchLabelsByProcess['stress_relief'][(int) (($assignments['stress_relief']['batch_id'] ?? 0))] ?? 'Grp',
                                    'sentLabelsByProcess' => $sentLabelsByProcess,
                                    'retLabelsByProcess' => $retLabelsByProcess,
                                ])
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'ndt',
                                    'process' => null,
                                    'ndtNames' => $ndtNames,
                                    'assignment' => $assignments['ndt'] ?? null,
                                    'detailTitle' => count($ndtNames) ? implode(' / ', $ndtNames) : '',
                                    'batchLabel' => $batchLabelsByProcess['ndt'][(int) (($assignments['ndt']['batch_id'] ?? 0))] ?? 'Grp',
                                    'sentLabelsByProcess' => $sentLabelsByProcess,
                                    'retLabelsByProcess' => $retLabelsByProcess,
                                ])
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'passivation',
                                    'process' => $passivationProcess,
                                    'assignment' => $assignments['passivation'] ?? null,
                                    'detailTitle' => $passivationProcess ? trim((string) $passivationProcess->process) : '',
                                    'batchLabel' => $batchLabelsByProcess['passivation'][(int) (($assignments['passivation']['batch_id'] ?? 0))] ?? 'Grp',
                                    'sentLabelsByProcess' => $sentLabelsByProcess,
                                    'retLabelsByProcess' => $retLabelsByProcess,
                                ])
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'cad',
                                    'process' => $cadProcess,
                                    'assignment' => $assignments['cad'] ?? null,
                                    'detailTitle' => $cadProcess ? trim((string) $cadProcess->process) : '',
                                    'batchLabel' => $batchLabelsByProcess['cad'][(int) (($assignments['cad']['batch_id'] ?? 0))] ?? 'Grp',
                                    'sentLabelsByProcess' => $sentLabelsByProcess,
                                    'retLabelsByProcess' => $retLabelsByProcess,
                                ])
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'anodizing',
                                    'process' => $anodizingProcess,
                                    'assignment' => $assignments['anodizing'] ?? null,
                                    'detailTitle' => $anodizingProcess ? trim((string) $anodizingProcess->process) : '',
                                    'batchLabel' => $batchLabelsByProcess['anodizing'][(int) (($assignments['anodizing']['batch_id'] ?? 0))] ?? 'Grp',
                                    'sentLabelsByProcess' => $sentLabelsByProcess,
                                    'retLabelsByProcess' => $retLabelsByProcess,
                                ])
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'xylan',
                                    'process' => $xylanProcess,
                                    'assignment' => $assignments['xylan'] ?? null,
                                    'detailTitle' => $xylanProcess ? trim((string) $xylanProcess->process) : '',
                                    'batchLabel' => $batchLabelsByProcess['xylan'][(int) (($assignments['xylan']['batch_id'] ?? 0))] ?? 'Grp',
                                    'sentLabelsByProcess' => $sentLabelsByProcess,
                                    'retLabelsByProcess' => $retLabelsByProcess,
                                ])
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif($bushings->flatten()->count() > 0)
    @include('admin.wo_bushings.partials.create-form', ['embed' => true])

@else
    <div class="text-center mt-5">
        <h3 class="text-muted">{{__('No Bushings Available')}}</h3>
        <p class="text-muted">{{__('No components with "Is Bush" marked are found for this manual.')}}</p>
        <button type="button" class="btn btn-primary mt-3 open-add-part-modal" data-add-part-url="{{ route('components.create', ['manual_id' => $current_wo->unit->manual_id ?? null, 'redirect' => $returnTo ?? route('wo_bushings.show', $current_wo->id)]) }}">
            <i class="fas fa-plus"></i> {{__('Add Part')}}
        </button>
    </div>
@endif
