@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: auto;
            width: 100%;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 120px;
            max-width: 200px;
            padding: 8px 12px;
            vertical-align: middle;
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            min-width: 180px;
            max-width: 250px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 80px;
            max-width: 100px;
            text-align: center;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 80px;
            max-width: 100px;
            text-align: center;
        }

        .table thead th {
            position: sticky;
            height: 60px;
            top: -1px;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
            background-color: #f8f9fa;
        }

        .form-select, .form-control {
            font-size: 0.875rem;
            padding: 0.375rem 0.5rem;
        }

        .header-row th {
            border-bottom: 2px solid #dee2e6;
            font-weight: bold;
        }

        .sub-header-row th {
            border-top: none;
            font-size: 0.8rem;
            color: #6c757d;
            font-style: italic;
        }

        .bushing-checkbox {
            transform: scale(1.2);
        }

        .qty-input {
            width: 70px;
            text-align: center;
        }

        .table-info {
            background-color: #d1ecf1 !important;
        }

        .table-info td {
            border-bottom: 2px solid #bee5eb !important;
            font-weight: bold;
            color: #0c5460;
        }

        .ps-4 {
            padding-left: 1.5rem !important;
        }

        .badge {
            font-size: 0.8rem;
        }

        .text-readonly {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            color: #495057;
        }
    </style>

    <div class="card-shadow">
        <div class="card-header m-1 shadow">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="text-primary ms-2">{{__('Work Order: ')}} {{$current_wo->number}}</h4>
                    <div>
                        <h4 class="ps-xl-2">{{__('BUSHINGS PROCESSES')}}</h4>
                    </div>
                </div>
                <div class="ps-2 d-flex" style="width: 400px;">
                    @if($woBushing)
                        <a href="{{ route('wo_bushings.edit', $woBushing->id) }}" class="btn btn-outline-primary me-2"
                           style="height: 60px;width: 100px">
                            <i class="fas fa-edit"></i> Edit Bushings
                        </a>
                        <div style="width: 100px"></div>
                        <a href="{{ route('wo_bushings.specProcessForm', $woBushing->id) }}" class="btn btn-outline-warning"
                           style="height: 60px;width: 120px" target="_blank">
                            <i class="fas fa-list"></i> Spec Process Form
                        </a>
                    @else
                        @if($bushings->flatten()->count() > 0)
                            <a href="{{ route('wo_bushings.create', $current_wo->id) }}" class="btn btn-success"
                               style="height: 60px; width: 100px">
                                <i class="fas fa-plus"></i> Create Bushings
                            </a>
                        @endif
                    @endif
                </div>
                <div class="">
                    <a href="{{ route('tdrs.show', ['tdr'=>$current_wo->id]) }}"
                       class="btn btn-outline-secondary me-2" style="height: 60px;width: 110px">
                        {{ __('Back to Work Order') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mx-3 mt-3" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($woBushing && $bushData)
            {{-- Показ сохраненных данных в режиме просмотра --}}
            <div class="d-flex justify-content-center mt-3">
                <div class="table-wrapper me-3">
                    <table class="display table shadow table-hover align-middle table-bordered bg-gradient">
                        <thead>
                            <tr class="header-row">
                                <th class="text-primary text-center">Bushings</th>
                                <th class="text-primary text-center">QTY</th>
                                <th class="text-primary text-center">
                                    Machining<br>
                                    @if($woBushing)
                                        @php
                                            $machiningProcessName = \App\Models\ProcessName::where('name', 'Machining')->first();
                                            // Проверяем, есть ли сохраненные данные для Machining
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
                                        @if($machiningProcessName && $hasMachiningData)
                                            <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id,
                                            'processNameId' => $machiningProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning mt-1">Form</a>
                                        @else
                                            <span class="text-muted">Form</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Form</span>
                                    @endif
                                </th>
                                <th class="text-primary text-center">
                                    NDT<br>
                                    @if($woBushing)
                                        @php
                                            $ndtProcessName = \App\Models\ProcessName::where('name', 'NDT-1')->first();
                                            // Проверяем, есть ли сохраненные данные для NDT
                                            $hasNdtData = false;
                                            if (!empty($bushData)) {
                                                foreach ($bushData as $bushItem) {
                                                    if (isset($bushItem['processes']['ndt']) && !empty($bushItem['processes']['ndt'])) {
                                                        $hasNdtData = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        @endphp
                                        @if($ndtProcessName && $hasNdtData)
                                            <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id,
                                            'processNameId' => $ndtProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning mt-1">Form</a>
                                        @else
                                            <span class="text-muted">Form</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Form</span>
                                    @endif
                                </th>
                                <th class="text-primary text-center">
                                    Passivation<br>
                                    @if($woBushing)
                                        @php
                                            $passivationProcessName = \App\Models\ProcessName::where('name', 'Passivation')->first();
                                            // Проверяем, есть ли сохраненные данные для Passivation
                                            $hasPassivationData = false;
                                            if (!empty($bushData)) {
                                                foreach ($bushData as $bushItem) {
                                                    if (isset($bushItem['processes']['passivation']) && !empty($bushItem['processes']['passivation'])) {
                                                        $hasPassivationData = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        @endphp
                                        @if($passivationProcessName && $hasPassivationData)
                                            <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id,
                                            'processNameId' => $passivationProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning mt-1">Form</a>
                                        @else
                                            <span class="text-muted">Form</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Form</span>
                                    @endif
                                </th>
                                <th class="text-primary text-center">
                                    CAD<br>
                                    @if($woBushing)
                                        @php
                                            $cadProcessName = \App\Models\ProcessName::where('name', 'Cad plate')->first();
                                            // Проверяем, есть ли сохраненные данные для CAD
                                            $hasCadData = false;
                                            if (!empty($bushData)) {
                                                foreach ($bushData as $bushItem) {
                                                    if (isset($bushItem['processes']['cad']) && !empty($bushItem['processes']['cad'])) {
                                                        $hasCadData = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        @endphp
                                        @if($cadProcessName && $hasCadData)
                                            <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id,
                                            'processNameId' => $cadProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning mt-1">Form</a>
                                        @else
                                            <span class="text-muted">Form</span>
                                        @endif
                                    @else
                                            <span class="text-muted">Form</span>
                                    @endif
                                </th>
                                <th class="text-primary text-center">
                                    Xylan<br>
                                    @if($woBushing)
                                        @php
                                            $xylanProcessName = \App\Models\ProcessName::where('name', 'Xylan coating')->first();
                                            // Проверяем, есть ли сохраненные данные для Xylan
                                            $hasXylanData = false;
                                            if (!empty($bushData)) {
                                                foreach ($bushData as $bushItem) {
                                                    if (isset($bushItem['processes']['xylan']) && !empty($bushItem['processes']['xylan'])) {
                                                        $hasXylanData = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        @endphp
                                        @if($xylanProcessName && $hasXylanData)
                                            <a href="{{ route('wo_bushings.processesForm', ['id' => $woBushing->id,
                                            'processNameId' => $xylanProcessName->id]) }}" target="_blank" class="btn btn-sm btn-outline-warning mt-1">Form</a>
                                        @else
                                            <span class="text-muted">Form</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Form</span>
                                    @endif
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Обрабатываем новую структуру bush_data
                                $savedBushingsGrouped = collect();
                                foreach($bushData as $bushItem) {
                                    if(isset($bushItem['bushing'])) {
                                        $component = $bushings->flatten()->firstWhere('id', $bushItem['bushing']);
                                        if($component) {
                                            $bushIplNum = $component->bush_ipl_num ?: 'No Bush IPL Number';
                                            if(!$savedBushingsGrouped->has($bushIplNum)) {
                                                $savedBushingsGrouped[$bushIplNum] = collect();
                                            }
                                            $savedBushingsGrouped[$bushIplNum]->push([
                                                'component' => $component,
                                                'data' => [
                                                    'qty' => $bushItem['qty'],
                                                    'machining' => $bushItem['processes']['machining'] ?? null,
                                                    'ndt' => $bushItem['processes']['ndt'] ?? null,
                                                    'passivation' => $bushItem['processes']['passivation'] ?? null,
                                                    'cad' => $bushItem['processes']['cad'] ?? null,
                                                    'xylan' => $bushItem['processes']['xylan'] ?? null,
                                                ]
                                            ]);
                                        }
                                    }
                                }
                            @endphp

                            @foreach($savedBushingsGrouped as $bushIplNum => $savedBushings)
                                {{-- Individual saved bushings in the group --}}
                                @foreach($savedBushings as $savedBushing)
                                    @php
                                        $component = $savedBushing['component'];
                                        $data = $savedBushing['data'];

                                        // Получаем названия процессов
                                        $machiningProcess = $machiningProcesses->firstWhere('id', $data['machining'] ?? null);
                                        $ndtProcess = $ndtProcesses->firstWhere('id', $data['ndt'] ?? null);
                                        $passivationProcess = $passivationProcesses->firstWhere('id', $data['passivation'] ?? null);
                                        $cadProcess = $cadProcesses->firstWhere('id', $data['cad'] ?? null);
                                        $xylanProcess = $xylanProcesses->firstWhere('id', $data['xylan'] ?? null);
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <strong>{{ $component->ipl_num }}</strong> - <span class="text-muted">{{ $component->part_number }}</span>
                                        </td>
                                        <td class="text-center">
                                            {{ $data['qty'] ?? '-' }}
                                        </td>
                                        <td>
                                            {{ $machiningProcess ? $machiningProcess->process : '-' }}
                                        </td>
                                        <td>
                                            {{ $ndtProcess ? $ndtProcess->process_name->name : '-' }}
                                        </td>
                                        <td>
                                            {{ $passivationProcess ? $passivationProcess->process : '-' }}
                                        </td>
                                        <td>
                                            {{ $cadProcess ? $cadProcess->process : '-' }}
                                        </td>
                                        <td>
                                            {{ $xylanProcess ? $xylanProcess->process : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($bushings->flatten()->count() > 0)
            {{-- Если есть втулки, но нет сохраненных данных --}}
            <div class="text-center mt-5">
                <h3 class="text-muted">{{__('No Bushings Data Found')}}</h3>
                <p class="text-muted">{{__('No bushings data has been created for this Work Order yet.')}}</p>
{{--                <a href="{{ route('wo_bushings.create', $current_wo->id) }}" class="btn btn-success btn-lg mt-3">--}}
{{--                    <i class="fas fa-plus"></i> {{__('Create Bushings Data')}}--}}
{{--                </a>--}}
            </div>
        @else
            {{-- Если нет втулок вообще --}}
            <div class="text-center mt-5">
                <h3 class="text-muted">{{__('No Bushings Available')}}</h3>
                <p class="text-muted">{{__('No components with "Is Bush" marked are found for this manual.')}}</p>
                <a href="{{ route('components.create') }}" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> {{__('Add Components')}}
                </a>
            </div>
        @endif
    </div>



@endsection
