@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 80px;
            max-width: 190px;
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
            max-width: 250px;
        }

        .table th:nth-child(6), .table td:nth-child(6) {
            min-width: 50px;
            max-width: 70px;
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
            <div class="d-flex">
                <div>
                    <h5 class="text-primary me-5">{{__('Work Order: ')}} {{$current_wo->number}}</h5>
                    <h5>Processes</h5>
                </div>

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
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tdrs as $tdr)

                                @if($tdr->use_process_forms == true)

                                    <tr>
                                        <td class="text-center">
                                            <a href="#" data-bs-toggle="modal"
                                               data-bs-target="#componentModal{{$tdr->component->id }}">
                                                {{$tdr->component->ipl_num}}
                                            </a>

                                        </td>
                                        <td class="text-center"> {{$tdr->component->name}}</td>

                                    </tr>

                                   <div class="modal fade" id="componentModal{{$tdr->component->id }}" tabindex="-1"
                                        role="dialog" aria-labelledby="componentModalLabel{{$tdr->component->id }}"
                                        aria-hidden="true">
                                       <div class="modal-dialog modal-dialog-centered" role="document">
                                           <div class="modal-content bg-gradient">
                                               <div class="modal-header">
                                                   <div>
                                                       <h5 class="modal-title"></h5>
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
                <div></div>
                <div></div>
            </div>
        </div>


    </div>
@endsection
