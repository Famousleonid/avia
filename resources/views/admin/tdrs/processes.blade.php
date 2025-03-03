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

    <div class="card shadow">
        <div class="card-header m-1 shadow">
            <div class="d-flex justify-content-between">
                <div>
                    <h5 class="text-primary me-5">{{__('Work Order: ')}} {{$current_wo->number}}</h5>
                    <h5>{{__('All Components Processes')}}</h5>

                    <a href="{{ route('admin.tdrs.ndtForm', ['id'=> $current_wo->id]) }}"
                       class="btn btn-outline-warning mb-1 formLink "
                       target="_blank"
                       id="#" style=" height: 40px">

                        <i class="bi bi-file-earmark-excel"> NDT Form</i>
                    </a>
                    <button class="btn btn-outline-warning" data-bs-toggle="modal"
                            data-bs-target="#formsModal">
                        {{__('Forms')}}
                    </button>
                </div>
                <a href="{{ route('admin.tdrs.show', ['tdr'=>$current_wo->id]) }}"
                   class="btn btn-outline-secondary mt-3" style="height: 40px">{{ __('Back to Work Order') }} </a>
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
                                <th class="text-primary text-center">Action</th>
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
                                            @foreach($tdrProcesses as $processes)
                                                @if($processes->tdrs_id == $tdr->id)
                                                    @php
                                                        // Декодируем JSON-поле processes
                                                        $processData = json_decode($processes->processes, true);
                                                        // Получаем имя процесса из связанной модели ProcessName
                                                        $processName = $processes->processName->name;
                                                    @endphp

                                                    @foreach($processData as $processId)
                                                        {{ $processName }} :
                                                        @if(isset($proces[$processId]))
                                                            {{ $proces[$processId]->process }}<br>
                                                        @endif
                                                    @endforeach
                                                @endif
                                            @endforeach

                                        </td>
                                        <td class="text-center">
                                            <div style="width: 100px">
                                                <a href="{{ route('admin.tdr-processes.createProcesses',['tdrId'=>$tdr->id])}}"
                                                   class="btn btn-outline-success btn-sm"> {{__('Add')}}
                                                    {{--                                                <i class="bi bi-plus-circle"></i>--}}
                                                </a>
                                                <a href="{{ route('admin.tdr-processes.processes',['tdrId'=>$tdr->id])}}"
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
                                                                src="{{ $tdr->component->getBigImageUrl('component')}}"
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
                    <!-- Modal Forms -->
                    <div class="modal fade" id="formsModal" tabindex="-1" role="dialog" aria-labelledby="formsModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                            <div class="modal-content bg-gradient">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="formsModalLabel">{{ __('Forms Processes') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                                </div>
                                <div class="modal-body">
                                    @php
                                        // Группировка процессов по типу для всех компонентов
                                        $globalGroupedProcesses = [];
                                        foreach($tdrProcesses as $process) {
                                            // Получаем имя процесса, например: "Machining (place 1)" или "NDT-1"
                                            $processName = $process->processName->name;
                                            $baseType = '';
                                            if(strpos($processName, 'Machining') !== false) {
                                                $baseType = 'Machining';
                                            } elseif(strpos($processName, 'NDT') !== false) {
                                                $baseType = 'NDT';
                                            }
                                            // Если тип определён и ещё не добавлен в массив, запоминаем его
                                            if($baseType && !isset($globalGroupedProcesses[$baseType])) {
                                                // Декодируем JSON-поле processes и берём первый processId
                                                $processIds = json_decode($process->processes, true);
                                                if(!empty($processIds)) {
                                                    $globalGroupedProcesses[$baseType] = [
                                                        'tdrId'     => $process->tdrs_id,
                                                        'processId' => $processIds[0]
                                                    ];
                                                }
                                            }
                                        }
                                    @endphp

                                    <div class="row">
                                        @foreach($globalGroupedProcesses as $type => $data)
                                            <div class="col-md-4 mb-3">
                                                <a href="{{ route('admin.tdr-processes.processesForm', [
                                'id' => $current_wo->id,
                                'tdrId'     => $data['tdrId'],
                                'processId' => $data['processId']
                            ]) }}" target="_blank" class="btn btn-outline-primary btn-block">
                                                    {{ $type }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div></div>
            </div>
        </div>

    </div>
@endsection
