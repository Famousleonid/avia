@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 1020px;
        }
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
            max-width: 200px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 50px;
            max-width: 250px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 50px;
            max-width: 250px;
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
        /*!* Стили для дропдауна *!*/
        .process-dropdown {
            appearance: none; /* Убираем стандартный стиль браузера */
            background-color: transparent;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
            /*width: 350px; !* Настройте ширину по необходимости *!*/
        }

        /*!* Стиль для плейсхолдера *!*/
        /*.process-dropdown option[value=""] {*/
        /*    color: #999; !* Серый цвет для плейсхолдера *!*/
        /*}*/

        /* Стиль для открытого дропдауна */
        .process-dropdown:focus {
            /*background-color: #fff;*/
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h4 class="text-primary">{{ __('Edit Manual Processes') }}</h4>
                    <h4 class="pe-3">{{$manual->number}} ({{$manual->title}})</h4>
                </div>
            </div>
            <div class="card-body">

                <div class="table-wrapper me-3 p-2">
                    <table id="processTable" class="display table table-hover table-striped align-middle table-bordered">
                        <thead class="bg-gradient">
                            <tr>
                                <th class="text-primary sortable text-center" style="width: 300px">{{__('Process Name')}}</th>
                                <th class="text-primary text-center">{{__('Processes')}}</th>
                                <th class="text-primary text-center" style="width: 200px">{{__('Action')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($man_processes as $man_process)
                                <tr>
                                    <td class="text-center">
                                        @foreach($processes as $process)
                                            @if($process->id == $man_process->processes_id)
                                                @foreach($processNames as $proName)
                                                    @if($proName->id == $process->process_names_id)
                                                     {{$proName->name}}
                                                    @endif
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </td>
                                    <td class="text-center">
                                        @foreach($processes as $process)
                                            @if($process->id == $man_process->processes_id)
                                                {{$process->process}}
                                            @endif
                                        @endforeach
                                    </td>
                                    <td class="text-center">
                                        {{$man_process->id}}
                                        <a href="#" class="btn btn-outline-primary btn-sm btn-edit" data-process-id="{{$man_process->id}}" data-process="{{$process->process}}">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form id="" action="#" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger btn-delete" type="button" data-process-id="{{$man_process->id}}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Process</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        @csrf
                        <input type="hidden" id="editProcessId" name="process_id">
                        <div class="mb-3">
                            <label for="editProcess" class="form-label">Process</label>
                            <input type="text" class="form-control" id="editProcess" name="process">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateProcess">Update</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Обработка нажатия на кнопку Edit
            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function() {
                    const processId = this.getAttribute('data-process-id');
                    const process = this.getAttribute('data-process');

                    document.getElementById('editProcessId').value = processId;
                    document.getElementById('editProcess').value = process;

                    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                    editModal.show();
                });
            });

            // Обработка нажатия на кнопку Update в модальном окне
            document.getElementById('updateProcess').addEventListener('click', function() {
                const processId = document.getElementById('editProcessId').value;
                const process = document.getElementById('editProcess').value;

                fetch(`/processes/${processId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ process: process })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error updating process');
                        }
                    });
            });

            // Обработка нажатия на кнопку Delete
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function() {
                    const processId = this.getAttribute('data-process-id');

                    if (confirm('Are you sure you want to delete this process?')) {
                        fetch(`admin/manual_processes/${processId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    location.reload();
                                } else {
                                    alert('Error deleting process');
                                }
                            });
                    }
                });
            });
        });
    </script>


@endsection
