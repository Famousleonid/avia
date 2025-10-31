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
            margin-top: 5px;
        }

        .table th:nth-child(1), .table td:nth-child(1) { width: 80px; }
        .table th:nth-child(2), .table td:nth-child(2) { width: 260px; }
        .table th:nth-child(3), .table td:nth-child(3) { width: 500px; }
        .table th:nth-child(4), .table td:nth-child(4) { width: 140px; }

        .table thead th {
            position: sticky;
            height: 50px;
            top: -1px;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        @media (max-width: 600px) {
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

        /* Стили для модального окна Group Process Forms */
        #groupFormsModal .table {
            margin-bottom: 0;
        }

        #groupFormsModal .table th {
            font-weight: 600;
        }

        #groupFormsModal .table td {
            vertical-align: middle;
            padding: 1rem;
        }

        .component-checkboxes {
            max-height: 200px;
            overflow-y: auto;
            padding: 0.5rem;
            color: inherit;
        }

        .component-checkbox:checked + .form-check-label {
            font-weight: 500;
            color: inherit;
        }

        .component-checkboxes .form-check {
            margin-bottom: 0.5rem;
            padding: 0.25rem;
            border-radius: 4px;
            transition: background-color 0.2s;
            color: inherit;
        }

        .component-checkboxes .form-check:hover {
            background-color: rgba(128, 128, 128, 0.1);
        }

        .component-checkboxes .form-check-label {
            font-size: 0.9rem;
            cursor: pointer;
            margin-left: 0.5rem;
            color: inherit;
        }

        .component-checkboxes strong {
            color: inherit;
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
                            <th class="text-primary text-center" style="width: 10%">IPL</th>
                            <th class="text-primary text-center"  style="width:15%">Name</th>
                            <th class="text-primary text-center" style="width: 60%">Processes</th>
                            <th class="text-primary text-center" style="width: 15%">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($extra_components as $extra_component)

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
                                                            <span class=" me-1 @if(strlen($process->process) > 50) process-text-long @endif">{{ $process->process }}</span>
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
                                                            <span class="  ms-2 @if(strlen($process->process) > 80)
                                                            process-text-long @endif">{{ $process->process
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
            <div class="modal-dialog modal-lg">
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
                                    Select a process type to generate a grouped form with all components that have the same process. Each process can have its own vendor and component selection.
                                </p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered bg-gradient shadow">
                                <thead>
                                    <tr>
                                        <th class="text-primary text-center" style="width: 25%;">Process</th>
                                        <th class="text-primary text-center" style="width: 25%;">Components</th>
                                        <th class="text-primary text-center" style="width: 25%;">Vendor</th>
                                    </tr>
                                </thead>
                                <tbody>
                            @foreach($processGroups as $processNameId => $group)
                                        <tr>
                                            <td class="align-middle">
                                        <a href="{{ route('extra_processes.show_group_forms', ['id' => $current_wo->id, 'processNameId' => $processNameId]) }}"
                                                   class="btn btn-outline-warning w-100 group-form-link"
                                           data-process-name-id="{{ $processNameId }}"
                                           target="_blank">
                                            <i class="fas fa-print me-2"></i>
                                            {{ $group['process_name']->name }}
                                                    <br>
                                                    <span class="badge bg-primary mt-1 ms-1 process-qty-badge"
                                                          data-process-name-id="{{ $processNameId }}">
                                                        {{ $group['qty'] }} pcs
                                                    </span>
                                                </a>
                                            </td>
                                            <td class="align-middle">
                                                <div class="component-checkboxes" data-process-name-id="{{ $processNameId }}">
                                                    @if($group['count'] > 1)
                                                        @foreach($group['components'] as $component)
                                                            <div class="form-check">
                                                                <input class=" ms-1 form-check-input component-checkbox"
                                                                       type="checkbox"
                                                                       value="{{ $component['id'] }}"
                                                                       id="component_{{ $processNameId }}_{{ $component['id'] }}"
                                                                       data-process-name-id="{{ $processNameId }}"
                                                                       data-qty="{{ $component['qty'] }}"
                                                                       checked>
                                                                <label class="form-check-label" for="component_{{ $processNameId }}_{{ $component['id'] }}">
                                                                    <strong>{{ $component['ipl_num'] }}</strong> -
                                                                    {{ Str::limit($component['name'], 40) }}
                                                                    <span class="">Qty: {{ $component['qty'] }}</span>
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        @foreach($group['components'] as $component)
                                                            <div class="form-check">
                                                                <input class="ms-1 form-check-input component-checkbox"
                                                                       type="checkbox"
                                                                       value="{{ $component['id'] }}"
                                                                       id="component_{{ $processNameId }}_{{ $component['id'] }}"
                                                                       data-process-name-id="{{ $processNameId }}"
                                                                       data-qty="{{ $component['qty'] }}"
                                                                       checked
                                                                       disabled>
                                                                <label class="form-check-label" for="component_{{ $processNameId }}_{{ $component['id'] }}">
                                                                    <strong>{{ $component['ipl_num'] }}</strong> -
                                                                    {{ Str::limit($component['name'], 40) }}
                                                                    <span class="">Qty: {{ $component['qty'] }}</span>
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                    </div>
                                            </td>
                                            <td class="align-middle">
                                        <select class="form-select vendor-select"
                                                data-process-name-id="{{ $processNameId }}"
                                                style="font-size: 0.9rem;">
                                            <option value="">No vendor</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                            @endforeach
                                        </select>
                                            </td>
                                        </tr>
                            @endforeach
                                </tbody>
                            </table>
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
            const componentCheckboxes = document.querySelectorAll('.component-checkbox');

            // Функция для обновления URL с учетом vendor и компонентов
            function updateLinkUrl(processNameId) {
                const link = document.querySelector(`.group-form-link[data-process-name-id="${processNameId}"]`);
                if (!link) return;

                const originalUrl = link.getAttribute('href');
                const url = new URL(originalUrl, window.location.origin);

                // Добавляем vendor_id если выбран
                const vendorSelect = document.querySelector(`.vendor-select[data-process-name-id="${processNameId}"]`);
                if (vendorSelect && vendorSelect.value) {
                    url.searchParams.set('vendor_id', vendorSelect.value);
                } else {
                    url.searchParams.delete('vendor_id');
                }

                // Добавляем component_ids из выбранных чекбоксов
                const checkedBoxes = document.querySelectorAll(
                    `.component-checkbox[data-process-name-id="${processNameId}"]:checked`
                );
                if (checkedBoxes.length > 0) {
                    const selectedComponents = Array.from(checkedBoxes).map(checkbox => checkbox.value);
                    url.searchParams.set('component_ids', selectedComponents.join(','));
                } else {
                    url.searchParams.delete('component_ids');
                }

                link.setAttribute('href', url.toString());
            }

            // Функция для обновления badge с количеством
            function updateQuantityBadge(processNameId) {
                const checkedBoxes = document.querySelectorAll(
                    `.component-checkbox[data-process-name-id="${processNameId}"]:checked:not([disabled])`
                );
                const badge = document.querySelector(
                    `.process-qty-badge[data-process-name-id="${processNameId}"]`
                );

                if (badge && checkedBoxes.length > 0) {
                    let totalQty = 0;
                    checkedBoxes.forEach(checkbox => {
                        const qty = parseInt(checkbox.getAttribute('data-qty')) || 0;
                        totalQty += qty;
                    });
                    badge.textContent = `${totalQty} pcs`;
                }
            }

            // Обработчик изменения выбора vendor для каждого дропдауна
            vendorSelects.forEach(vendorSelect => {
                vendorSelect.addEventListener('change', function() {
                    const processNameId = this.getAttribute('data-process-name-id');
                    updateLinkUrl(processNameId);
                });
            });

            // Обработчик изменения чекбоксов компонентов
            componentCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const processNameId = this.getAttribute('data-process-name-id');
                    updateLinkUrl(processNameId);
                    updateQuantityBadge(processNameId);
                });
            });

            // Обработчик клика по кнопкам форм
            groupFormLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const processNameId = this.getAttribute('data-process-name-id');
                    updateLinkUrl(processNameId);
                });
            });

            // Инициализация URL и badge при загрузке страницы
            document.querySelectorAll('.group-form-link').forEach(link => {
                const processNameId = link.getAttribute('data-process-name-id');
                updateLinkUrl(processNameId);
                updateQuantityBadge(processNameId);
            });
        });
    </script>
@endsection
