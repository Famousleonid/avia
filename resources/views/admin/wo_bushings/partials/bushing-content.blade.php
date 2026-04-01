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
        .bushing-view-table .bushing-process-include-checkbox,
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
                        <col style="width: 5%;">
                        <col style="width: 6%;">
                    @endfor
                </colgroup>
                <thead class="wo-bush-thead" style="background: #031e3a;">
                    <tr class="header-row">
                        <th rowspan="2" class="text-primary text-center align-middle bushing-col">{{ __('Bushings') }}</th>
                        <th rowspan="2" class="text-primary text-center align-middle">{{ __('QTY') }}</th>
                        <th colspan="2" class="text-primary text-center">{{ __('Machining') }}</th>
                        <th colspan="2" class="text-primary text-center">{{ __('Stress Relief') }}</th>
                        <th colspan="2" class="text-primary text-center">{{ __('NDT') }}</th>
                        <th colspan="2" class="text-primary text-center">{{ __('Passivation') }}</th>
                        <th colspan="2" class="text-primary text-center">{{ __('CAD') }}</th>
                        <th colspan="2" class="text-primary text-center">{{ __('Anodizing') }}</th>
                        <th colspan="2" class="text-primary text-center">{{ __('Xylan') }}</th>
                    </tr>
                    <tr class="bushing-process-subhead">
                        {{-- Machining --}}
                        <th class="bushing-subcol-batch text-center p-1">
                            @if($woBushing)
                                <div class="d-flex flex-column gap-1 align-items-stretch">
                                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-1 js-bushing-create-batch"
                                            data-url="{{ $batchCreateUrl }}" data-process-key="machining">{{ __('Group') }}</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 js-bushing-ungroup-batch"
                                            data-url="{{ $batchUngroupUrl }}" data-process-key="machining">{{ __('Ungroup') }}</button>
                                </div>
                            @endif
                        </th>
                        <th class="bushing-subcol-form text-center p-1">
                            @if($woBushing)
                                <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_machining">
                                        <option value="">{{ __('Vendor') }}</option>
                                        @foreach($vendors as $vendor)
                                            <option style="font-size: 10px;" value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                    @if($machiningProcessName && $hasMachiningData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $machiningProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_machining" data-process-key="machining">{{ __('Form') }}</a>
                                    @else
                                        <span class="text-muted small">{{ __('Form') }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small">{{ __('Form') }}</span>
                            @endif
                        </th>
                        {{-- Stress --}}
                        <th class="bushing-subcol-batch text-center p-1">
                            @if($woBushing)
                                <div class="d-flex flex-column gap-1 align-items-stretch">
                                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-1 js-bushing-create-batch"
                                            data-url="{{ $batchCreateUrl }}" data-process-key="stress_relief">{{ __('Group') }}</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 js-bushing-ungroup-batch"
                                            data-url="{{ $batchUngroupUrl }}" data-process-key="stress_relief">{{ __('Ungroup') }}</button>
                                </div>
                            @endif
                        </th>
                        <th class="bushing-subcol-form text-center p-1">
                            @if($woBushing)
                                <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_stress_relief">
                                        <option value="">{{ __('Vendor') }}</option>
                                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                    @if($stressReliefProcessName && $hasStressReliefData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $stressReliefProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_stress_relief" data-process-key="stress_relief">{{ __('Form') }}</a>
                                    @else
                                        <span class="text-muted small">{{ __('Form') }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small">{{ __('Form') }}</span>
                            @endif
                        </th>
                        {{-- NDT --}}
                        <th class="bushing-subcol-batch text-center p-1">
                            @if($woBushing)
                                <div class="d-flex flex-column gap-1 align-items-stretch">
                                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-1 js-bushing-create-batch"
                                            data-url="{{ $batchCreateUrl }}" data-process-key="ndt">{{ __('Group') }}</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 js-bushing-ungroup-batch"
                                            data-url="{{ $batchUngroupUrl }}" data-process-key="ndt">{{ __('Ungroup') }}</button>
                                </div>
                            @endif
                        </th>
                        <th class="bushing-subcol-form text-center p-1">
                            @if($woBushing)
                                <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_ndt">
                                        <option value="">{{ __('Vendor') }}</option>
                                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                    @if($ndtProcessName && $hasNdtData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $ndtProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_ndt" data-process-key="ndt">{{ __('Form') }}</a>
                                    @else
                                        <span class="text-muted small">{{ __('Form') }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small">{{ __('Form') }}</span>
                            @endif
                        </th>
                        {{-- Passivation --}}
                        <th class="bushing-subcol-batch text-center p-1">
                            @if($woBushing)
                                <div class="d-flex flex-column gap-1 align-items-stretch">
                                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-1 js-bushing-create-batch"
                                            data-url="{{ $batchCreateUrl }}" data-process-key="passivation">{{ __('Group') }}</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 js-bushing-ungroup-batch"
                                            data-url="{{ $batchUngroupUrl }}" data-process-key="passivation">{{ __('Ungroup') }}</button>
                                </div>
                            @endif
                        </th>
                        <th class="bushing-subcol-form text-center p-1">
                            @if($woBushing)
                                <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_passivation">
                                        <option value="">{{ __('Vendor') }}</option>
                                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                    @if($passivationProcessName && $hasPassivationData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $passivationProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_passivation" data-process-key="passivation">{{ __('Form') }}</a>
                                    @else
                                        <span class="text-muted small">{{ __('Form') }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small">{{ __('Form') }}</span>
                            @endif
                        </th>
                        {{-- CAD --}}
                        <th class="bushing-subcol-batch text-center p-1">
                            @if($woBushing)
                                <div class="d-flex flex-column gap-1 align-items-stretch">
                                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-1 js-bushing-create-batch"
                                            data-url="{{ $batchCreateUrl }}" data-process-key="cad">{{ __('Group') }}</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 js-bushing-ungroup-batch"
                                            data-url="{{ $batchUngroupUrl }}" data-process-key="cad">{{ __('Ungroup') }}</button>
                                </div>
                            @endif
                        </th>
                        <th class="bushing-subcol-form text-center p-1">
                            @if($woBushing)
                                <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_cad">
                                        <option value="">{{ __('Vendor') }}</option>
                                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                    @if($cadProcessName && $hasCadData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $cadProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_cad" data-process-key="cad">{{ __('Form') }}</a>
                                    @else
                                        <span class="text-muted small">{{ __('Form') }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small">{{ __('Form') }}</span>
                            @endif
                        </th>
                        {{-- Anodizing --}}
                        <th class="bushing-subcol-batch text-center p-1">
                            @if($woBushing)
                                <div class="d-flex flex-column gap-1 align-items-stretch">
                                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-1 js-bushing-create-batch"
                                            data-url="{{ $batchCreateUrl }}" data-process-key="anodizing">{{ __('Group') }}</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 js-bushing-ungroup-batch"
                                            data-url="{{ $batchUngroupUrl }}" data-process-key="anodizing">{{ __('Ungroup') }}</button>
                                </div>
                            @endif
                        </th>
                        <th class="bushing-subcol-form text-center p-1">
                            @if($woBushing)
                                <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_anodizing">
                                        <option value="">{{ __('Vendor') }}</option>
                                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                    @if($anodizingProcessName && $hasAnodizingData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $anodizingProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_anodizing" data-process-key="anodizing">{{ __('Form') }}</a>
                                    @else
                                        <span class="text-muted small">{{ __('Form') }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small">{{ __('Form') }}</span>
                            @endif
                        </th>
                        {{-- Xylan --}}
                        <th class="bushing-subcol-batch text-center p-1">
                            @if($woBushing)
                                <div class="d-flex flex-column gap-1 align-items-stretch">
                                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-1 js-bushing-create-batch"
                                            data-url="{{ $batchCreateUrl }}" data-process-key="xylan">{{ __('Group') }}</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 js-bushing-ungroup-batch"
                                            data-url="{{ $batchUngroupUrl }}" data-process-key="xylan">{{ __('Ungroup') }}</button>
                                </div>
                            @endif
                        </th>
                        <th class="bushing-subcol-form text-center p-1">
                            @if($woBushing)
                                <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_xylan">
                                        <option value="">{{ __('Vendor') }}</option>
                                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                    @if($xylanProcessName && $hasXylanData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $xylanProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_xylan" data-process-key="xylan">{{ __('Form') }}</a>
                                    @else
                                        <span class="text-muted small">{{ __('Form') }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small">{{ __('Form') }}</span>
                            @endif
                        </th>
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
                    @foreach($savedBushingsGrouped as $groupKey => $savedBushings)
                        @foreach($savedBushings as $savedBushing)
                            @php
                                $component = $savedBushing['component'];
                                $data = $savedBushing['data'];
                                $assignments = $processAssignments[$component->id] ?? [];
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
                                ])
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'stress_relief',
                                    'process' => $stressReliefProcess,
                                    'assignment' => $assignments['stress_relief'] ?? null,
                                    'detailTitle' => $stressReliefProcess ? trim((string) $stressReliefProcess->process) : '',
                                ])
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'ndt',
                                    'process' => null,
                                    'ndtNames' => $ndtNames,
                                    'assignment' => $assignments['ndt'] ?? null,
                                    'detailTitle' => count($ndtNames) ? implode(' / ', $ndtNames) : '',
                                ])
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'passivation',
                                    'process' => $passivationProcess,
                                    'assignment' => $assignments['passivation'] ?? null,
                                    'detailTitle' => $passivationProcess ? trim((string) $passivationProcess->process) : '',
                                ])
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'cad',
                                    'process' => $cadProcess,
                                    'assignment' => $assignments['cad'] ?? null,
                                    'detailTitle' => $cadProcess ? trim((string) $cadProcess->process) : '',
                                ])
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'anodizing',
                                    'process' => $anodizingProcess,
                                    'assignment' => $assignments['anodizing'] ?? null,
                                    'detailTitle' => $anodizingProcess ? trim((string) $anodizingProcess->process) : '',
                                ])
                                @include('admin.wo_bushings.partials.bushing-process-cells', [
                                    'component' => $component,
                                    'processKey' => 'xylan',
                                    'process' => $xylanProcess,
                                    'assignment' => $assignments['xylan'] ?? null,
                                    'detailTitle' => $xylanProcess ? trim((string) $xylanProcess->process) : '',
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
