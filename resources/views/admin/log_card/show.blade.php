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

    <div class="card-shadow ">
        <div class="card-header m-1 shadow">
            <div class="d-flex justify-content-between m-3">
                <div>
                    <h4 class="text-primary me-5">{{__('Work Order: ')}} {{$current_wo->number}}</h4>
                    <h4 class="ps-xl-5">{{__('LOG CARD')}}</h4>

                        <a href="{{ route('log_card.logCardForm', ['id'=> $current_wo->id]) }}"
                           class="btn btn-outline-warning mb-3 formLink "
                           target="_blank"
                           id="#" style=" height: 40px">
                            <i class="bi bi-file-earmark-excel">Log Card </i>
                        </a>

                </div>
                <div>
                    <a href="{{ route('tdrs.show', ['tdr'=>$current_wo->id]) }}"
                       class="btn btn-outline-secondary mt-3" style="height: 40px">{{ __('Back to Work Order') }} </a>
                    <div class="mt-3">
                        @if($log_card)
                            <a href="{{ route('log_card.edit', $log_card->id) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit Log Card
                            </a>
                        @else
                            <a href="{{ route('log_card.create', $current_wo->id) }}" class="btn btn-success">
                                <i class="fas fa-plus"></i> Create Log Card
                            </a>
                        @endif
                    </div>

                </div>

            </div>

        </div>
    <div class="d-flex justify-content-center">
        <div class="table-wrapper me-3">
            <table class="display table shadow table-hover align-middle table-bordered bg-gradient">
                <thead>
                <tr>
                    <th class="text-primary text-center">Description</th>
                    <th class="text-primary text-center">Part Number</th>
                    <th class="text-primary text-center">Serial Number</th>
                    <th class="text-primary text-center">Reason to Removed</th>
{{--                    <th class="text-primary text-center">Action</th>--}}
                </tr>
                </thead>
                <tbody>

                @foreach($componentData as $item)

                    @php
                        $comp = $components->firstWhere('id', $item['component_id']);

                    @endphp
                    <tr>
                        <td>{{ $comp ? $comp->name : '' }}</td>
                        <td>{{ $comp ? $comp->part_number : '' }}</td>
                        <td>{{ $item['serial_number'] }}</td>
                        <td>{{ $item['reason'] }}</td>
                    </tr>
                @endforeach

                </tbody>
            </table>
        </div>
    </div>




    </div>


@endsection
