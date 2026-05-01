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

        #groupFormsModal .modal-dialog {
            max-height: 80vh;
            margin: 1.75rem auto;
        }
        #groupFormsModal .modal-content {
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }
        #groupFormsModal .modal-header {
            flex-shrink: 0;
        }
        #groupFormsModal .modal-body {
            overflow-y: auto;
            min-height: 0;
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
                        <h5>{{__('All Part Processes')}}</h5>
                    </div>


                    <div class="ps-2 d-flex align-items-center" style="width: 540px">
                        @if(isset($processGroups) && count($processGroups) > 0)
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                data-bs-toggle="modal" data-bs-target="#groupFormsModal">
                                <i class="fas fa-print"></i> {{ __('Group Process Forms') }}
                            </button>
                        @endif
                    </div>

                    <x-paper-button
                        text="SP Form"
                        href="{{ route('tdrs.specProcessForm', ['id'=> $current_wo->id]) }}"
                        target="_blank"
                        size="landscape"
                        width="100"
                    />
                </div>
                <a href="{{ route('tdrs.show', ['id'=>$current_wo->id]) }}"
                   class="btn btn-outline-secondary " style="height: 60px; width: 90px;line-height: 1.2rem;align-content: center">
                    {{ __('Back to TDR') }} </a>
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
                                                    $processData = \App\Models\TdrProcess::normalizeStoredProcessIds($processes->processes);
                                                    // Проверяем, что $processData является массивом
                                                    if (!is_array($processData)) {
                                                        $processData = [];
                                                    }
                                                    // Получаем имя процесса из связанной модели ProcessName (с проверкой на null)
                                                    $processName = $processes->processName ? $processes->processName->name : 'N/A';
                                                @endphp
                                                
                                                @if(!$processes->processName)
                                                    @continue
                                                @endif

                                                @if(is_array($processData) && !empty($processData))
                                                    @foreach($processData as $processId)
                                                    @if(isset($proces[$processId]))
                                                        @php
                                                            $catalogRow = $proces[$processId];
                                                            $lineProcessName = $catalogRow->process_name?->name ?? $processName;
                                                        @endphp
                                                        {{ $lineProcessName }} :
                                                        {{ $catalogRow->process }}@if($processes->ec) ( EC )@endif<br>
                                                    @endif
                                                    @endforeach
                                                @endif
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
                                                           <p><strong>{{ __('Part PN: ') }}</strong>{{
                                                           $tdr->component->part_number }}</p>
                                                           <p><strong>{{ __('Part Name: ') }}</strong>{{
                                                           $tdr->component->name }}</p>
                                                           <p><strong>{{ __('Part IPL: ') }}</strong>{{
                                                           $tdr->component->ipl_num }}</p>
                                                           <p><strong>{{ __('Part SN: ') }}</strong>{{
                                                           $tdr->serial_number }}</p>
                                                           @if($tdr->assy_serial_number)
                                                               <p><strong>{{ __('Part Assy SN: ') }}</strong>{{
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
            <div class="modal-dialog modal-lg modal-dialog-centered">
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
                                        <th class="text-primary text-center" style="width: 25%;">Parts</th>
                                        <th class="text-primary text-center" style="width: 25%;">Vendor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @include('admin.tdrs.partials.all-parts-group-forms-modal-body', ['processGroups' => $processGroups, 'vendors' => $vendors, 'current_wo' => $current_wo])
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @include('admin.tdrs.partials.all-parts-group-forms-modal-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('groupFormsModal');
            if (modal && typeof window.initAllPartsGroupFormModalRows === 'function') {
                window.initAllPartsGroupFormModalRows(modal);
            }
        });
    </script>
@endsection
