@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: auto;
            width: 100%;
            max-width: 1080px;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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

        .table th:nth-child(1), .table td:nth-child(1) { width: 80px; }
        .table th:nth-child(2), .table td:nth-child(2) { width: 260px; }
        .table th:nth-child(3), .table td:nth-child(3) { width: 600px; }
        .table th:nth-child(4), .table td:nth-child(4) { width: 140px; }

        .table thead th {
            position: sticky;
            height: 50px;
            top: -1px;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        @media (max-width: 1200px) {
            .table th:nth-child(4), .table td:nth-child(4),
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

        /* Стили для модального окна Group Process Forms */
        .group-form-link {
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .group-form-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .vendor-select {
            transition: all 0.3s ease;
        }

        .vendor-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
    </style>

    <div class="card-shadow">
        <div class="card-header m-1 shadow">
            <div class="d-flex justify-content-between">
                <div class="d-flex" style="width: 1080px">
                    <div style="width: 550px">
                        <h4 class="text-primary  ms-2">{{__('Work Order: ')}} {{$current_wo->number}}</h4>
                        <div>
                            <h4 class="ps-2">{{__('Component Extra Processes')}}</h4>
                        </div>

                    </div>
                    @if(isset($processGroups) && count($processGroups) > 0)
                        <button type="button" class="btn btn-outline-info me-2"
                                style="height: 60px; width: 150px"
                                data-bs-toggle="modal"
                                data-bs-target="#groupFormsModal">
                            <i class="fas fa-print"></i> Group Process Forms
                        </button>
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
                            <th class="text-primary text-center" style="width: 50px">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($extra_components as $extra_component)
{{--                                @php--}}
{{--                                    // Отладочная информация--}}
{{--                                    \Log::info('Extra component in view', [--}}
{{--                                        'extra_component_id' => $extra_component->id,--}}
{{--                                        'component_id' => $extra_component->component_id,--}}
{{--                                        'component_relation' => $extra_component->component ? [--}}
{{--                                            'id' => $extra_component->component->id,--}}
{{--                                            'name' => $extra_component->component->name,--}}
{{--                                            'ipl_num' => $extra_component->component->ipl_num--}}
{{--                                        ] : null--}}
{{--                                    ]);--}}
{{--                                @endphp--}}
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
{{--                        <!-- Временная отладочная информация -->--}}
{{--                        <div class="alert alert-info mb-3">--}}
{{--                            <strong>Debug Info:</strong><br>--}}
{{--                            Total process groups: {{ count($processGroups) }}<br>--}}
{{--                            Total extra components: {{ $extra_components->count() }}<br>--}}
{{--                            <hr>--}}
{{--                            @foreach($processGroups as $processNameId => $group)--}}
{{--                                <strong>Process ID {{ $processNameId }}:</strong> {{ $group['process_name']->name }} - qty: {{ $group['qty'] }}, components: {{ $group['count'] }}<br>--}}
{{--                            @endforeach--}}
{{--                            <hr>--}}
{{--                            <strong>All Extra Components:</strong><br>--}}
{{--                            @foreach($extra_components as $extra_component)--}}
{{--                                Component ID: {{ $extra_component->component_id }} --}}
{{--                                @if($extra_component->component)--}}
{{--                                    ({{ $extra_component->component->name }})--}}
{{--                                @endif--}}
{{--                                - Processes: {{ json_encode($extra_component->processes) }}<br>--}}
{{--                            @endforeach--}}
{{--                        </div>--}}

                        <div class="row">
                            <div class="col-12">
                                <p class="text-muted mb-3">
                                    <i class="fas fa-info-circle"></i>
                                    Select a process type to generate a grouped form with all components that have the same process. Each process can have its own vendor.
                                </p>
                            </div>
                        </div>

                        <div class="d-grid gap-3">
                            @foreach($processGroups as $processNameId => $group)
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <a href="{{ route('extra_processes.show_group_forms', ['id' => $current_wo->id, 'processNameId' => $processNameId]) }}"
                                           class="btn btn-outline-warning btn-lg w-100 group-form-link"
                                           data-process-name-id="{{ $processNameId }}"
                                           target="_blank">
                                            <i class="fas fa-print me-2"></i>
                                            {{ $group['process_name']->name }}
                                            <span class="badge bg-primary ms-2">{{ $group['qty'] }} pcs</span>
                                        </a>
                                        <!-- Временная отладочная информация -->
{{--                                        <small class="text-muted">Debug: Process ID {{ $processNameId }}, Qty: {{ $group['qty'] }}, Components: {{ $group['count'] }}</small>--}}
                                    </div>
                                    <div class="col-4">
                                        <select class="form-select vendor-select"
                                                data-process-name-id="{{ $processNameId }}"
                                                style="font-size: 0.9rem;">
                                            <option value="">No vendor</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
{{--                    <div class="modal-footer d-flex justify-content-between">--}}
{{--                        <div class="text-muted">Total qty: {{ $totalQty ?? ($extra_components->sum('qty') ?? 0) }}</div>--}}
{{--                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>--}}
{{--                    </div>--}}
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const vendorSelects = document.querySelectorAll('.vendor-select');
            const groupFormLinks = document.querySelectorAll('.group-form-link');

            // Обработчик изменения выбора vendor для каждого дропдауна
            vendorSelects.forEach(vendorSelect => {
                vendorSelect.addEventListener('change', function() {
                    const processNameId = this.getAttribute('data-process-name-id');
                    const selectedVendorId = this.value;

                    // Находим соответствующую кнопку процесса
                    const correspondingLink = document.querySelector(`.group-form-link[data-process-name-id="${processNameId}"]`);

                    if (correspondingLink) {
                        const originalUrl = correspondingLink.getAttribute('href');
                        const url = new URL(originalUrl, window.location.origin);

                        if (selectedVendorId) {
                            url.searchParams.set('vendor_id', selectedVendorId);
                        } else {
                            url.searchParams.delete('vendor_id');
                        }

                        correspondingLink.setAttribute('href', url.toString());
                    }
                });
            });

            // Обработчик клика по кнопкам форм
            groupFormLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const processNameId = this.getAttribute('data-process-name-id');

                    // Находим соответствующий дропдаун vendor
                    const correspondingVendorSelect = document.querySelector(`.vendor-select[data-process-name-id="${processNameId}"]`);

                    if (correspondingVendorSelect) {
                        const selectedVendorId = correspondingVendorSelect.value;

                        if (selectedVendorId) {
                            const originalUrl = this.getAttribute('href');
                            const url = new URL(originalUrl, window.location.origin);
                            url.searchParams.set('vendor_id', selectedVendorId);
                            this.setAttribute('href', url.toString());
                        }
                    }
                });
            });
        });
    </script>
@endsection
