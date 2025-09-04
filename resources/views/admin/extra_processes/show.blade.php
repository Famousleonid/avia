@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: hidden;
            width: 850px;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 80px;
            max-width: 500px;
            padding-left: 10px;
        }

        /* Стили для длинного текста процесса */
        .process-text-long {
            font-size: 0.90rem;
            line-height: 0.9;
            letter-spacing: -0.3px;
            transform: scale(0.9);
            transform-origin: left;
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            min-width: 80px;
            max-width: 90px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 50px;
            max-width: 250px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 50px;
            max-width: 500px;
        }

        .table th:nth-child(4), .table td:nth-child(4) {
            min-width: 100px;
            max-width: 200px;

        }

        .table thead th {
            position: sticky;
            height: 50px;
            top: -1px;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        @media (max-width: 1200px) {
            .table th:nth-child(5), .table td:nth-child(5),
            .table th:nth-child(2), .table td:nth-child(2),
            .table th:nth-child(3), .table td:nth-child(3) {
                display: none;
            }
        }

        .table th.sortable {
            cursor: pointer;
        }

        .clearable-input {
            position: relative;
            width: 400px;
        }

        .clearable-input .form-control {
            padding-right: 2.5rem;
        }

        .clearable-input .btn-clear {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }
    </style>

    <div class="card-shadow">
        <div class="card-header m-1 shadow">
            <div class="d-flex justify-content-between">
                <div class="d-flex" style="width: 500px">
                    <div style="width: 350px">
                        <h4 class="text-primary  ms-2">{{__('Work Order: ')}} {{$current_wo->number}}</h4>
                        <div>
                            <h4 class="ps-2">{{__('Component Extra Processes')}}</h4>
                        </div>

                    </div>
                    @if(isset($processGroups) && count($processGroups) > 0)
                        @php
                            $hasMultipleComponents = false;
                            foreach($processGroups as $group) {
                                if($group['count'] > 1) {
                                    $hasMultipleComponents = true;
                                    break;
                                }
                            }
                        @endphp
                        @if($hasMultipleComponents)
                            <button type="button" class="btn btn-outline-info me-2"
                                    style="height: 60px; width: 150px"
                                    data-bs-toggle="modal"
                                    data-bs-target="#groupFormsModal">
                                <i class="fas fa-print"></i> Group Process Forms
                            </button>
                        @endif
                    @endif


                </div>

                <div class="">
                    <a href="{{ route('extra_process.create', $current_wo->id) }}" class="btn btn-outline-success"
                       style="height:60px; width: 180px">
                        <i class="fas fa-plus"></i> Create Component Processes
                    </a>

                    <a href="{{ route('tdrs.show', ['tdr'=>$current_wo->id]) }}"
                       class="btn btn-outline-secondary me-2" style="height: 60px;width: 110px">{{ __('Back to Work Order')
                            }} </a>
                </div>

        </div>
        <div class="card-body">
            <div class="d-flex justify-content-center">
                <div class="table-wrapper mt-3">
                    <table class="display table table-hover table-bordered bg-gradient shadow">
                        <thead>
                        <tr>
                            <th class="text-primary text-center">IPL</th>
                            <th class="text-primary text-center">Name</th>
                            <th class="text-primary text-center" style="width: 400px">Processes</th>
                            <th class="text-primary text-center" style="width: 150px">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($extra_components as $extra_component)
                                @php
                                    // Отладочная информация
                                    \Log::info('Extra component in view', [
                                        'extra_component_id' => $extra_component->id,
                                        'component_id' => $extra_component->component_id,
                                        'component_relation' => $extra_component->component ? [
                                            'id' => $extra_component->component->id,
                                            'name' => $extra_component->component->name,
                                            'ipl_num' => $extra_component->component->ipl_num
                                        ] : null
                                    ]);
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $extra_component->component ? $extra_component->component->ipl_num : 'N/A' }}</td>
                                    <td class="text-center">{{ $extra_component->component ? $extra_component->component->name : 'N/A' }}</td>
                                    <td class="ps-2 ">
                                        @if($extra_component->processes)
                                            @if(is_array($extra_component->processes) && array_keys($extra_component->processes) !== range(0, count($extra_component->processes) - 1))
                                                {{-- Старая структура: ассоциативный массив --}}
                                                @foreach($extra_component->processes as $processNameId => $processId)
                                                    @php
                                                        $processName = \App\Models\ProcessName::find($processNameId);
                                                        $process = \App\Models\Process::find($processId);
                                                    @endphp
                                                    @if($processName && $process)
                                                        <div class="mb-2  d-flex">
                                                            <strong>{{ $processName->name }}:</strong><br>
                                                            <span class="badge bg-primary me-1 @if(strlen($process->process) > 50) process-text-long @endif">{{ $process->process }}</span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @else
                                                {{-- Новая структура: массив объектов --}}
                                                @foreach($extra_component->processes as $processItem)
                                                    @php
                                                        $processName = \App\Models\ProcessName::find($processItem['process_name_id']);
                                                        $process = \App\Models\Process::find($processItem['process_id']);
                                                    @endphp
                                                    @if($processName && $process)
                                                        <div class="mb-2  d-flex">
                                                            <strong>{{ $processName->name }}:</strong><br>
                                                            <span class="badge bg-gradient ms-2 @if(strlen($process->process) > 40) process-text-long @endif">{{ $process->process
                                                            }}</span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @endif
                                        @else
                                            <span class="text-muted">No processes defined</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div style="width: 100px">
                                            @if($extra_component->component)
                                                <a href="{{ route('extra_processes.create_processes', ['workorderId' => $current_wo->id, 'componentId' => $extra_component->component->id]) }}"
                                                   class="btn btn-outline-success btn-sm"
                                                   onclick="console.log('Navigating to:', '{{ route('extra_processes.create_processes', ['workorderId' => $current_wo->id, 'componentId' => $extra_component->component->id]) }}')">
                                                    {{__('Add')}}
                                                </a>
                                            @else
                                                <span class="text-muted">Component not found (ID: {{ $extra_component->component_id }})</span>
                                            @endif
                                            @if($extra_component->component)
                                                <a href="{{ route('extra_processes.processes', ['workorderId' => $current_wo->id, 'componentId' => $extra_component->component->id]) }}"
                                                   class="btn btn-outline-primary btn-sm"> {{__('Processes')}}
                                                </a>
                                            @else
                                                <span class="text-muted">Component not found</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal - Group Process Forms -->
    @if(isset($processGroups) && count($processGroups) > 0)
        <div class="modal fade" id="groupFormsModal" tabindex="-1" aria-labelledby="groupFormsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="groupFormsModalLabel">
                            <i class="fas fa-print"></i> {{ __('Group Process Forms') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <p class="text-muted mb-3">
                                    <i class="fas fa-info-circle"></i>
                                    Select a process type to generate a grouped form with all components that have the same process.
                                </p>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            @foreach($processGroups as $processNameId => $group)
                                <a href="{{ route('extra_processes.show_group_forms', ['id' => $current_wo->id, 'processNameId' => $processNameId]) }}"
                                   class="btn btn-outline-warning btn-lg" target="_blank">
                                    <i class="fas fa-print me-2"></i>
                                    {{ $group['process_name']->name }}
                                    <span class="badge bg-primary ms-2">{{ $group['count'] }} component(s)</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
