@if($woBushing && $bushData)
    {{-- Показ сохраненных данных в режиме просмотра --}}
    <style>
        .bushing-view-table { table-layout: fixed; width: 100%; }
        .bushing-view-table .bushing-col { white-space: nowrap; }
        .bushing-view-table .vendor-select-sm { min-width: 60px; max-width: 80px; font-size: 0.9rem; padding: 0.15rem 0.25rem; }
        .bushing-view-table th.bushing-process-col,
        .bushing-view-table td.bushing-process-col { overflow: hidden; box-sizing: border-box; vertical-align: middle; }
        .bushing-view-table th.bushing-process-col { text-overflow: ellipsis; white-space: nowrap; }
        .bushing-view-table td.bushing-process-col:not(:has(.bushing-process-include-checkbox)) { text-overflow: ellipsis; white-space: nowrap; }
        .bushing-view-table td.bushing-process-col .bushing-process-cell-inner {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            min-width: 0;
            max-width: 100%;
        }
        /* min-width:0 + ellipsis на потомке flex — иначе длинный текст раздувает строку */
        .bushing-view-table td.bushing-process-col .bushing-process-cell-text {
            min-width: 0;
            flex: 1 1 auto;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .bushing-view-table th.bushing-process-col > div { overflow: hidden; max-width: 100%; }
        .bushing-view-table .bushing-process-include-checkbox { cursor: pointer; flex-shrink: 0; }

        /* 1280x1024 и ниже: компактнее заголовки и controls, чтобы не ломало сетку */
        @media (max-width: 1280px) {
            .bushing-view-table thead th {
                font-size: .82rem;
                padding: .28rem .22rem;
                line-height: 1.12;
            }
            .bushing-view-table .vendor-select-sm {
                min-width: 54px;
                max-width: 66px;
                height: 24px;
                font-size: .72rem;
                padding: 0 .18rem;
            }
            .bushing-view-table .form-btn {
                font-size: .72rem;
                line-height: 1.05;
                padding: .18rem .32rem;
            }
            .bushing-view-table th.bushing-process-col > div {
                gap: .2rem !important;
            }
        }
    </style>
    <div class="w-100 mt-3">
        <div class="table-wrapper table-scroll-container w-100" style="max-height: calc(100vh - 320px); overflow: auto;">
            <table class="display table shadow table-hover align-middle table-bordered bg-gradient dir-table bushing-view-table
             w-100">
                <colgroup>
                    <col style="width: 18%;">
                    <col style="width: 5%;">
                    <col style="width: 11%;">
                    <col style="width: 11%;">
                    <col style="width: 11%;">
                    <col style="width: 11%;">
                    <col style="width: 11%;">
                    <col style="width: 11%;">
                    <col style="width: 11%;">
                </colgroup>
                <thead style="position: sticky; top: 0; z-index: 10; background: #031e3a;">
                    <tr class="header-row">
                        <th class="text-primary text-center bushing-col">Bushings</th>
                        <th class="text-primary text-center">QTY</th>
                        <th class="text-primary text-center bushing-process-col">
                            Machining<br>
                            @if($woBushing)
                                @php
                                    $machiningProcessName = \App\Models\ProcessName::where('name', 'Machining')->first();
                                    $hasMachiningData = false;
                                    if (!empty($bushData)) {
                                        foreach ($bushData as $bushItem) {
                                            if (isset($bushItem['processes']['machining']) && !empty($bushItem['processes']['machining'])) {
                                                $hasMachiningData = true;
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_machining">
                                        <option value="">Vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option style="font-size: 10px;" value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                    @if($machiningProcessName && $hasMachiningData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $machiningProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_machining" data-process-key="machining" >Form</a>
                                    @else
                                        <span class="text-muted">Form</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted" >Form</span>
                            @endif
                        </th>
                        <th class="text-primary text-center bushing-process-col">
                            Stress Relief<br>
                            @if($woBushing)
                                @php
                                    $stressReliefProcessName = null;
                                    if (!empty($bushData)) {
                                        foreach ($bushData as $bushItem) {
                                            $srId = $bushItem['processes']['stress_relief'] ?? null;
                                            if (!empty($srId)) {
                                                $srProc = \App\Models\Process::find($srId);
                                                if ($srProc) {
                                                    $stressReliefProcessName = \App\Models\ProcessName::find($srProc->process_names_id);
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    if (!$stressReliefProcessName) {
                                        $stressReliefProcessName = \App\Models\ProcessName::where('name', 'Bake (Stress relief)')->first()
                                            ?? \App\Models\ProcessName::where('name', 'Stress Relief')->first();
                                    }
                                    $hasStressReliefData = false;
                                    if (!empty($bushData)) {
                                        foreach ($bushData as $bushItem) {
                                            if (isset($bushItem['processes']['stress_relief']) && !empty($bushItem['processes']['stress_relief'])) { $hasStressReliefData = true; break; }
                                        }
                                    }
                                @endphp
                                <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_stress_relief">
                                        <option value="">Vendor</option>
                                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                    @if($stressReliefProcessName && $hasStressReliefData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $stressReliefProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_stress_relief" data-process-key="stress_relief">Form</a>
                                    @else
                                        <span class="text-muted">Form</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Form</span>
                            @endif
                        </th>
                        <th class="text-primary text-center bushing-process-col">
                            NDT<br>
                            @if($woBushing)
                                @php
                                    $ndtProcessName = \App\Models\ProcessName::where('name', 'NDT-1')->first();
                                    $hasNdtData = false;
                                    if (!empty($bushData)) {
                                        foreach ($bushData as $bushItem) {
                                            if (isset($bushItem['processes']['ndt']) && !empty($bushItem['processes']['ndt'])) { $hasNdtData = true; break; }
                                        }
                                    }
                                @endphp
                                <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_ndt">
                                        <option value="">Vendor</option>
                                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                    @if($ndtProcessName && $hasNdtData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $ndtProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_ndt" data-process-key="ndt">Form</a>
                                    @else
                                        <span class="text-muted">Form</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Form</span>
                            @endif
                        </th>
                        <th class="text-primary text-center bushing-process-col">
                            Passivation<br>
                            @if($woBushing)
                                @php
                                    $passivationProcessName = \App\Models\ProcessName::where('name', 'Passivation')->first();
                                    $hasPassivationData = false;
                                    if (!empty($bushData)) {
                                        foreach ($bushData as $bushItem) {
                                            if (isset($bushItem['processes']['passivation']) && !empty($bushItem['processes']['passivation'])) { $hasPassivationData = true; break; }
                                        }
                                    }
                                @endphp
                                <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_passivation">
                                        <option value="">Vendor</option>
                                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                    @if($passivationProcessName && $hasPassivationData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $passivationProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_passivation" data-process-key="passivation">Form</a>
                                    @else
                                        <span class="text-muted">Form</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Form</span>
                            @endif
                        </th>
                        <th class="text-primary text-center bushing-process-col">
                            CAD<br>
                            @if($woBushing)
                                @php
                                    $cadProcessName = \App\Models\ProcessName::where('name', 'Cad plate')->first();
                                    $hasCadData = false;
                                    if (!empty($bushData)) {
                                        foreach ($bushData as $bushItem) {
                                            if (isset($bushItem['processes']['cad']) && !empty($bushItem['processes']['cad'])) { $hasCadData = true; break; }
                                        }
                                    }
                                @endphp
                                <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_cad">
                                        <option value="">Vendor</option>
                                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                    @if($cadProcessName && $hasCadData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $cadProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_cad" data-process-key="cad">Form</a>
                                    @else
                                        <span class="text-muted">Form</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Form</span>
                            @endif
                        </th>
                        <th class="text-primary text-center bushing-process-col">
                            Anodizing<br>
                            @if($woBushing)
                                @php
                                    $anodizingProcessName = \App\Models\ProcessName::where('name', 'Anodizing')->first();
                                    $hasAnodizingData = false;
                                    if (!empty($bushData)) {
                                        foreach ($bushData as $bushItem) {
                                            if (isset($bushItem['processes']['anodizing']) && !empty($bushItem['processes']['anodizing'])) { $hasAnodizingData = true; break; }
                                        }
                                    }
                                @endphp
                                <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_anodizing">
                                        <option value="">Vendor</option>
                                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                    @if($anodizingProcessName && $hasAnodizingData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $anodizingProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_anodizing" data-process-key="anodizing">Form</a>
                                    @else
                                        <span class="text-muted">Form</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Form</span>
                            @endif
                        </th>
                        <th class="text-primary text-center bushing-process-col">
                            Xylan<br>
                            @if($woBushing)
                                @php
                                    $xylanProcessName = \App\Models\ProcessName::where('name', 'Xylan coating')->first();
                                    $hasXylanData = false;
                                    if (!empty($bushData)) {
                                        foreach ($bushData as $bushItem) {
                                            if (isset($bushItem['processes']['xylan']) && !empty($bushItem['processes']['xylan'])) { $hasXylanData = true; break; }
                                        }
                                    }
                                @endphp
                                <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                                    <select class="form-select form-select-sm vendor-select-sm" name="vendor_id" id="vendor_xylan">
                                        <option value="">Vendor</option>
                                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                    @if($xylanProcessName && $hasXylanData)
                                        <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $xylanProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning form-btn" data-vendor-select="vendor_xylan" data-process-key="xylan">Form</a>
                                    @else
                                        <span class="text-muted">Form</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Form</span>
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
                                    $bushIplNum = $component->bush_ipl_num ?: 'No Bush IPL Number';
                                    if(!$savedBushingsGrouped->has($bushIplNum)) { $savedBushingsGrouped[$bushIplNum] = collect(); }
                                    $ndtValue = data_get($bushItem, 'processes.ndt', []);
                                    if (is_null($ndtValue)) { $ndtValue = []; } elseif (!is_array($ndtValue)) { $ndtValue = [$ndtValue]; }
                                    $savedBushingsGrouped[$bushIplNum]->push([
                                        'component' => $component,
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
                    @endphp
                    @foreach($savedBushingsGrouped as $bushIplNum => $savedBushings)
                        @foreach($savedBushings as $savedBushing)
                            @php
                                $component = $savedBushing['component'];
                                $data = $savedBushing['data'];
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
                                <td class="ps-4 bushing-col" style="width: 150px"><strong>{{ $component->ipl_num }}</strong> -
                                    <span
                                        class="text-muted">{{ $component->part_number }}</span></td>
                                <td class="text-center">{{ $data['qty'] ?? '-' }}</td>
                                <td class="bushing-process-col" title="{{ $machiningProcess ? $machiningProcess->process : '-' }}">
                                    @if($machiningProcess)
                                        <div class="bushing-process-cell-inner">
                                            <input type="checkbox" class="form-check-input bushing-process-include-checkbox mt-0" data-process-key="machining" data-component-id="{{ $component->id }}" autocomplete="off" title="{{ __('Include in Machining form') }}">
                                            <span class="bushing-process-cell-text">{{ $machiningProcess->process }}</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="bushing-process-col" title="{{ $stressReliefProcess ? $stressReliefProcess->process : '-' }}">
                                    @if($stressReliefProcess)
                                        <div class="bushing-process-cell-inner">
                                            <input type="checkbox" class="form-check-input bushing-process-include-checkbox mt-0" data-process-key="stress_relief" data-component-id="{{ $component->id }}" autocomplete="off" title="{{ __('Include in Stress Relief form') }}">
                                            <span class="bushing-process-cell-text">{{ $stressReliefProcess->process }}</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="bushing-process-col" title="{{ !empty($ndtNames) ? implode(' / ', $ndtNames) : '-' }}">
                                    @if(!empty($ndtNames))
                                        <div class="bushing-process-cell-inner">
                                            <input type="checkbox" class="form-check-input bushing-process-include-checkbox mt-0" data-process-key="ndt" data-component-id="{{ $component->id }}" autocomplete="off" title="{{ __('Include in NDT form') }}">
                                            <span class="bushing-process-cell-text">{{ implode(' / ', $ndtNames) }}</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="bushing-process-col" title="{{ $passivationProcess ? $passivationProcess->process : '-' }}">
                                    @if($passivationProcess)
                                        <div class="bushing-process-cell-inner">
                                            <input type="checkbox" class="form-check-input bushing-process-include-checkbox mt-0" data-process-key="passivation" data-component-id="{{ $component->id }}" autocomplete="off" title="{{ __('Include in Passivation form') }}">
                                            <span class="bushing-process-cell-text">{{ $passivationProcess->process }}</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="bushing-process-col" title="{{ $cadProcess ? $cadProcess->process : '-' }}">
                                    @if($cadProcess)
                                        <div class="bushing-process-cell-inner">
                                            <input type="checkbox" class="form-check-input bushing-process-include-checkbox mt-0" data-process-key="cad" data-component-id="{{ $component->id }}" autocomplete="off" title="{{ __('Include in CAD form') }}">
                                            <span class="bushing-process-cell-text">{{ $cadProcess->process }}</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="bushing-process-col" title="{{ $anodizingProcess ? $anodizingProcess->process : '-' }}">
                                    @if($anodizingProcess)
                                        <div class="bushing-process-cell-inner">
                                            <input type="checkbox" class="form-check-input bushing-process-include-checkbox mt-0" data-process-key="anodizing" data-component-id="{{ $component->id }}" autocomplete="off" title="{{ __('Include in Anodizing form') }}">
                                            <span class="bushing-process-cell-text">{{ $anodizingProcess->process }}</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="bushing-process-col" title="{{ $xylanProcess ? $xylanProcess->process : '-' }}">
                                    @if($xylanProcess)
                                        <div class="bushing-process-cell-inner">
                                            <input type="checkbox" class="form-check-input bushing-process-include-checkbox mt-0" data-process-key="xylan" data-component-id="{{ $component->id }}" autocomplete="off" title="{{ __('Include in Xylan form') }}">
                                            <span class="bushing-process-cell-text">{{ $xylanProcess->process }}</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
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
