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
            max-width: 400px;
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

    <div class="card shadow">
        <div class="card-header m-1 shadow">
            <div class="d-flex justify-content-between">
                <div class="d-flex">
                    <div style="width: 260px">
                        <h5 class="text-primary me-5">{{__('Work Order: ')}} {{$current_wo->number}}</h5>
                        <h5>{{__('All Components Processes')}}</h5>
                    </div>


                    <div class="ps-2 d-flex" style="width: 540px">
                        @if(isset($processGroups) && count($processGroups) > 0)
                            <div style="width: 250px">
                                <x-paper-button-multy
                                    text="Group Process Forms"
                                    color="outline-primary"
                                    size="landscape"
                                    width="100"
                                    ariaLabel="Group Process Forms"
                                    data-bs-toggle="modal"
                                    data-bs-target="#groupFormsModal"
                                />
                            </div>
                        @endif
                    </div>

                    <x-paper-button
                        text="SP Form"
                        href="{{ route('tdrs.specProcessForm', ['id'=> $current_wo->id]) }}"
                        target="_blank"
                    />
                </div>
                <a href="{{ route('tdrs.show', ['id'=>$current_wo->id]) }}"
                   class="btn btn-outline-secondary " style="height: 60px; width: 110px">{{ __('Back to Work Order') }} </a>
            </div>
        </div>
        <div>
            <div class="d-flex justify-content-center">
                <div class="me-3">
                    <div class="table-wrapper me-3">
                        <table class="display table table-sm table-hover align-middle table-bordered bg-gradient">
                            <thead>
                            <tr>
                                <th class="text-primary text-center">IPL</th>
                                <th class="text-primary text-center">Name</th>
                                <th class="text-primary text-center" style="width: 400px">Processes</th>
                                <th class="text-primary text-center" style="width: 150px">Action</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($tdrs as $tdr)
                                @if($tdr->use_process_forms )
                                    <tr>
                                        <td class="text-center">
                                            <a href="#" data-bs-toggle="modal"
                                               data-bs-target="#componentModal{{$tdr->component->id }}">
                                                {{$tdr->component->ipl_num}}
                                            </a>

                                        </td>
                                        <td class="text-center" > {{$tdr->component->name}}</td>
                                        <td class="ms-1">
{{--                                            {{$tdr->id}}--}}
                                            @php
                                                // Получаем все процессы для этого компонента и сортируем по sort_order
                                                $componentProcesses = $tdrProcesses->where('tdrs_id', $tdr->id)->sortBy('sort_order');
                                            @endphp
                                            @foreach($componentProcesses as $processes)
                                                @php
                                                    // Декодируем JSON-поле processes
                                                    $processData = json_decode($processes->processes, true);
                                                    // Получаем имя процесса из связанной модели ProcessName
                                                    $processName = $processes->processName->name;
                                                @endphp

                                                @foreach($processData as $processId)
                                                    {{ $processName }} :
                                                    @if(isset($proces[$processId]))
                                                        {{ $proces[$processId]->process }}@if($processes->ec) ( EC )@endif<br>
                                                    @endif
                                                @endforeach
                                            @endforeach

                                        </td>
                                        <td class="text-center">
                                            <div style="width: 100px">
                                                <a href="{{ route('tdr-processes.createProcesses',['tdrId'=>$tdr->id])}}"
                                                   class="btn btn-outline-success btn-sm"> {{__('Add')}}
                                                    {{--                                                <i class="bi bi-plus-circle"></i>--}}
                                                </a>
                                                <a href="{{ route('tdr-processes.processes',['tdrId'=>$tdr->id])}}"
                                                   class="btn btn-outline-primary btn-sm"> {{__('Processes')}}
                                                    {{--                                                <i class="bi bi-pencil-square"></i>--}}
                                                </a>
                                            </div>

                                        </td>

                                    </tr>

                                   <div class="modal fade" id="componentModal{{$tdr->component->id }}" tabindex="-1"
                                        role="dialog" aria-labelledby="componentModalLabel{{$tdr->component->id }}"
                                        aria-hidden="true">
                                       <div class="modal-dialog modal-dialog-centered" role="document">
                                           <div class="modal-content bg-gradient">
                                               <div class="modal-header">
                                                   <div>
                                                       <h5 class="modal-title">{{__('Work Order: ')}} {{$current_wo->number}}</h5>
                                                   </div>
                                               </div>
                                               <div class="modal-body">
                                                   <div class="d-flex">
                                                       <div class="me-2">
                                                           <img class=""
                                                                src="{{ $tdr->component->getFirstMediaBigUrl('component')}}"
                                                                width="200"  alt="Image"/>
                                                       </div>
                                                       <div>
                                                           <p><strong>{{ __('Component PN: ') }}</strong>{{
                                                           $tdr->component->part_number }}</p>
                                                           <p><strong>{{ __('Component Name: ') }}</strong>{{
                                                           $tdr->component->name }}</p>
                                                           <p><strong>{{ __('Component IPL: ') }}</strong>{{
                                                           $tdr->component->ipl_num }}</p>
                                                           <p><strong>{{ __('Component SN: ') }}</strong>{{
                                                           $tdr->serial_number }}</p>
                                                           @if($tdr->assy_serial_number)
                                                               <p><strong>{{ __('Component Assy SN: ') }}</strong>{{
                                                           $tdr->assy_serial_number }}</p>
                                                           @endif
                                                       </div>
                                                   </div>
                                               </div>
                                           </div>
                                       </div>
                                   </div>

                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div><!---- Table  --->
                <div>
                </div>
                <div></div>
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
                            @foreach($processGroups as $groupKey => $group)
                                        @php
                                            // Используем processNameId как ключ для всех процессов
                                            $actualProcessNameId = $groupKey;
                                            // Отображаем название процесса
                                            $displayName = $group['process_name']->name;
                                        @endphp
                                        <tr>
                                            <td class="align-middle ">
                                                <div class="position-relative d-inline-block ms-5">
                                                <x-paper-button
                                                    text="{{ $displayName }} "
                                                    size="landscape"
                                                    width="120px"
                                                    href="{{ route('tdrs.show_group_forms', ['id' => $current_wo->id, 'processNameId' => $actualProcessNameId]) }}"
                                                    target="_blank"
                                                    class="group-form-button"
                                                    data-process-name-id="{{ $actualProcessNameId }}"
                                                > </x-paper-button>

                                                    <span class="badge bg-success  mt-1 ms-1 process-qty-badge"
                                                          style="position: absolute; top: -5px; left: 5px; min-width: 20px;
                                                          height: 30px;
                                              display: flex; align-items: center; justify-content: center; font-size: 0.7rem; padding: 0 5px;">
                                                        {{$group['qty'] }} pcs</span>

                                                </div>

                                            </td>
                                            <td class="align-middle">
                                                <div class="component-checkboxes" data-process-name-id="{{ $actualProcessNameId }}">
                                                    @if($group['count'] > 1)
                                                        @foreach($group['components'] as $componentKey => $component)
                                                            @php
                                                                // Создаем составной ключ для идентификации компонента
                                                                $componentIdentifier = sprintf(
                                                                    '%s_%s_%s',
                                                                    $component['ipl_num'] ?? '',
                                                                    $component['part_number'] ?? '',
                                                                    $component['serial_number'] ?? ''
                                                                );
                                                            @endphp
                                                            <div class="form-check">
                                                                <input class=" ms-1 form-check-input component-checkbox"
                                                                       type="checkbox"
                                                                       value="{{ $componentIdentifier }}"
                                                                       data-component-id="{{ $component['id'] }}"
                                                                       data-ipl-num="{{ $component['ipl_num'] ?? '' }}"
                                                                       data-part-number="{{ $component['part_number'] ?? '' }}"
                                                                       data-serial-number="{{ $component['serial_number'] ?? '' }}"
                                                                       id="component_{{ $actualProcessNameId }}_{{ $componentKey }}"
                                                                       data-process-name-id="{{ $actualProcessNameId }}"
                                                                       data-qty="{{ $component['qty'] }}"
                                                                       checked>
                                                                <label class="form-check-label" for="component_{{ $actualProcessNameId }}_{{ $componentKey }}">
                                                                    <strong>{{ $component['ipl_num'] }}</strong> -
                                                                    {{ Str::limit($component['name'], 40) }}
                                                                    @if(isset($component['serial_number']) && $component['serial_number'])
                                                                        <span class="text-muted">(SN: {{ $component['serial_number'] }})</span>
                                                                    @endif
                                                                    <span class="">Qty: {{ $component['qty'] }}</span>
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        @foreach($group['components'] as $componentKey => $component)
                                                            @php
                                                                // Создаем составной ключ для идентификации компонента
                                                                $componentIdentifier = sprintf(
                                                                    '%s_%s_%s',
                                                                    $component['ipl_num'] ?? '',
                                                                    $component['part_number'] ?? '',
                                                                    $component['serial_number'] ?? ''
                                                                );
                                                            @endphp
                                                            <div class="form-check">
                                                                <input class="ms-1 form-check-input component-checkbox"
                                                                       type="checkbox"
                                                                       value="{{ $componentIdentifier }}"
                                                                       data-component-id="{{ $component['id'] }}"
                                                                       data-ipl-num="{{ $component['ipl_num'] ?? '' }}"
                                                                       data-part-number="{{ $component['part_number'] ?? '' }}"
                                                                       data-serial-number="{{ $component['serial_number'] ?? '' }}"
                                                                       id="component_{{ $actualProcessNameId }}_{{ $componentKey }}"
                                                                       data-process-name-id="{{ $actualProcessNameId }}"
                                                                       data-qty="{{ $component['qty'] }}"
                                                                       checked
                                                                       disabled>
                                                                <label class="form-check-label" for="component_{{ $actualProcessNameId }}_{{ $componentKey }}">
                                                                    <strong>{{ $component['ipl_num'] }}</strong> -
                                                                    {{ Str::limit($component['name'], 40) }}
                                                                    @if(isset($component['serial_number']) && $component['serial_number'])
                                                                        <span class="text-muted">(SN: {{ $component['serial_number'] }})</span>
                                                                    @endif
                                                                    <span class="">Qty: {{ $component['qty'] }}</span>
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                    </div>
                                            </td>
                                            <td class="align-middle">
                                        <select class="form-select vendor-select"
                                                data-process-name-id="{{ $actualProcessNameId }}"
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
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const vendorSelects = document.querySelectorAll('.vendor-select');
            const groupFormLinks = document.querySelectorAll('.group-form-link');
            const groupFormButtons = document.querySelectorAll('.group-form-button');
            const componentCheckboxes = document.querySelectorAll('.component-checkbox');

            // Функция для обновления URL с учетом vendor и компонентов
            function updateLinkUrl(processNameId) {
                // Пробуем найти ссылку или кнопку
                let link = document.querySelector(`.group-form-link[data-process-name-id="${processNameId}"]`);
                if (!link) {
                    link = document.querySelector(`.group-form-button[data-process-name-id="${processNameId}"]`);
                }
                if (!link) return;

                const originalUrl = link.getAttribute('href');
                if (!originalUrl) return;

                const url = new URL(originalUrl, window.location.origin);

                // Добавляем vendor_id если выбран
                const vendorSelect = document.querySelector(`.vendor-select[data-process-name-id="${processNameId}"]`);
                if (vendorSelect && vendorSelect.value) {
                    url.searchParams.set('vendor_id', vendorSelect.value);
                } else {
                    url.searchParams.delete('vendor_id');
                }

                // Добавляем component_ids и serial_numbers из выбранных чекбоксов
                const checkedBoxes = document.querySelectorAll(
                    `.component-checkbox[data-process-name-id="${processNameId}"]:checked`
                );
                if (checkedBoxes.length > 0) {
                    const selectedComponentIds = Array.from(checkedBoxes).map(checkbox => checkbox.getAttribute('data-component-id'));
                    const selectedSerialNumbers = Array.from(checkedBoxes).map(checkbox => checkbox.getAttribute('data-serial-number') || '');
                    const selectedIplNums = Array.from(checkedBoxes).map(checkbox => checkbox.getAttribute('data-ipl-num') || '');
                    const selectedPartNumbers = Array.from(checkedBoxes).map(checkbox => checkbox.getAttribute('data-part-number') || '');
                    
                    url.searchParams.set('component_ids', selectedComponentIds.join(','));
                    url.searchParams.set('serial_numbers', selectedSerialNumbers.join(','));
                    url.searchParams.set('ipl_nums', selectedIplNums.join(','));
                    url.searchParams.set('part_numbers', selectedPartNumbers.join(','));
                } else {
                    url.searchParams.delete('component_ids');
                    url.searchParams.delete('serial_numbers');
                    url.searchParams.delete('ipl_nums');
                    url.searchParams.delete('part_numbers');
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

            // Обработчик клика по paper-button кнопкам форм
            groupFormButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const processNameId = this.getAttribute('data-process-name-id');
                    if (processNameId) {
                        // Обновляем URL перед переходом
                        updateLinkUrl(processNameId);
                        // Получаем обновленный URL и устанавливаем его
                        const updatedUrl = this.getAttribute('href');
                        if (updatedUrl) {
                            this.setAttribute('href', updatedUrl);
                        }
                    }
                });
            });

            // Инициализация URL и badge при загрузке страницы
            document.querySelectorAll('.group-form-link, .group-form-button').forEach(link => {
                const processNameId = link.getAttribute('data-process-name-id');
                if (processNameId) {
                    updateLinkUrl(processNameId);
                    updateQuantityBadge(processNameId);
                }
            });
        });
    </script>
@endsection
