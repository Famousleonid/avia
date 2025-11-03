@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: hidden;
            width: 1050px;
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
            min-width: 180px;
            max-width: 290px;
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

    <div class="card-shadow ">
        <div class="card-header m-1 shadow">
            <div class="d-flex justify-content-between">
                <div>
                    <h4 class="text-primary  ms-2">{{__('Work Order: ')}} {{$current_wo->number}}</h4>
                    <div>
                                            <h4 class="ps-xl-5">{{__('LOG CARD')}}</h4>
                    </div>

                </div>
                <div class="ps-2 d-flex" style="width: 300px;">
                        @if($log_card)
                            <a href="{{ route('log_card.edit', $log_card->id) }}" class="btn btn-outline-primary"
                               style="height: 60px;width: 100px">
                                <i class="fas fa-edit"></i> Edit Log Card
                            </a>
                        @else
                            <a href="{{ route('log_card.create', $current_wo->id) }}" class="btn btn-success" style="height:
                            60px; width: 100px">
                                <i class="fas fa-plus"></i> Create Log Card
                            </a>
                        @endif
                </div>


                <div class="ps-2 d-flex" style="width: 600px;">
                    @if($log_card)
                    <a href="{{ route('log_card.logCardForm', ['id'=> $current_wo->id]) }}"
                       class="btn btn-outline-warning mb-3 formLink "
                       target="_blank"
                       id="#" style=" height: 60px; width: 80px">
                        <i class="bi bi-file-earmark-excel"> Log Card </i>
                    </a>
                    @endif
                </div>

                    <div class="">
                        <a href="{{ route('tdrs.show', ['id'=>$current_wo->id]) }}"
                           class="btn btn-outline-secondary me-2" style="height: 60px;width: 110px">{{ __('Back to Work Order')
                            }} </a>
                    </div>





            </div>

        </div>

        @if($log_card)

    <div class="d-flex justify-content-center mt-3">
        <div class="table-wrapper me-3">
            <table class="display table shadow table-hover align-middle table-bordered bg-gradient">
                <thead>
                <tr>
                    <th class="text-primary text-center">Description</th>
                    <th class="text-primary text-center">Part Number / Assy PN</th>
                    <th class="text-primary text-center">Serial Number</th>
                    <th class="text-primary text-center">ASSY Serial Number</th>
                    <th class="text-primary text-center">Reason to Removed</th>
                </tr>
                </thead>
                <tbody>

                @foreach($componentData as $item)

                    @php
                        $comp = $components->firstWhere('id', $item['component_id']);
                        $hasSerialNumber = !empty($item['serial_number']);
                        $hasAssySerialNumber = isset($item['assy_serial_number']) && !empty($item['assy_serial_number']);
                    @endphp

                    <tr>
                        <td>
                            {{ $comp ? $comp->name : '' }}
                            @if($hasAssySerialNumber && !$hasSerialNumber)
                                , S/A
                            @endif
                        </td>
                        <td>
                            @if($hasAssySerialNumber && !$hasSerialNumber)
                                {{ $comp ? $comp->assy_part_number : '' }}
                            @else
                                {{ $comp ? $comp->part_number : '' }}
                            @endif
                        </td>
                        <td>{{ $item['serial_number'] }}</td>
                        <td>
                            @if(isset($item['assy_serial_number']) && $item['assy_serial_number'])
                            {{ $item['assy_serial_number'] }}
                            @endif
                        </td>
                        <td>
                            @if($item['reason'])
                                @php
                                    $code = $codes->firstWhere('id', $item['reason']);
                                @endphp
                                {{ $code ? $code->name : $item['reason'] }}
                            @endif
                        </td>
                    </tr>
                @endforeach

                </tbody>
            </table>
        </div>
    </div>
        @else
<h3 class="text-center mt-3">{{__('No Log Card for this WorkOrders')}}</h3>
        @endif


    </div>


@endsection
