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
        <div class="card-header my-1 shadow">
            <div class="d-flex justify-content-between">
                <h5 class="text-primary manage-header">{{__('Manage Components')}}( <span class="text-success">{{$components->count()}}
                    </span>)
                </h5>

                <div class="d-flex my-2">
                    <div class="clearable-input ps-2">
                        <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                        <button class="btn-clear text-secondary" onclick="document.getElementById('searchInput').value = ''; document.getElementById('searchInput').dispatchEvent(new Event('input'))">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">{{ __('Add
                    Component') }}</button>


                </div>
        </div>

        @if(count($components))
            <div class="table-wrapper me-3 p-2 pt-0">
                <table id="componentTable" class="display table table-sm table-hover table-striped align-middle table-bordered">
                <thead class="bg-gradient">
                <tr>
                    <th class="text-center  sortable">{{__('Manual')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                    <th class="text-center  sortable">{{__('IPL Number')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                    <th class="text-center sortable ">{{__('Component')}} <i class="bi bi-chevron-expand ms-1"></i></th>
{{--                    <th class="text-center text-primary bg-gradient ">Description</th>--}}
                    <th class="text-center  sortable">{{__('Part number')}} <i class="bi bi-chevron-expand ms-1"></i></th>
                    <th class=" text-center " style="width: 120px">{{__('Image')}}</th>

                    <th class="text-center ">Action</th>
                </tr>
                </thead>
                    <tbody>
                        @foreach($components as $component)
                            <tr>
                                <td class="text-center">{{$component->manuals->number}}</td>
                                <td class="text-center">{{$component->ipl_num}}</td>
                                <td class="text-center">{{$component->name}}</td>
                                <td class="text-center">{{$component->part_number}}</td>
                                <td class="text-center">
                                    <a href="{{ $component->getBigImageUrl('component') }}" data-fancybox="gallery">
                                        <img class="rounded-circle" src="{{ $component->getThumbnailUrl('component') }}" width="40"
                                             height="40" alt="IMG"/>
                                    </a>
                                    <a href="{{ $component->getBigImageUrl('assy_component') }}" data-fancybox="gallery">
                                        <img class="rounded-circle" src="{{ $component->getThumbnailUrl('assy_component') }}" width="40"
                                             height="40" alt="IMG"/>
                                    </a>
                                </td>


                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else
            <H5 CLASS="text-center">{{__('COMPONENTS NOT CREATED')}}</H5>

        @endif

    </div>
        <!-- Create Modal -->
        <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog ">
                <div class="modal-content bg-gradient" style="width: 650px">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Component</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="createForm" method="POST" action="{{ route('admin.components.store') }}" enctype="multipart/form-data">

                            @csrf
                            <div class="mb-3">
                                <!-- Выпадающий список для выбора CMM -->
                                <div class="mb-3">
                                    <label for="manual_id" class="form-label">CMM</label>
                                    <select class="form-select" id="manual_id" name="manual_id">
                                        <option value="">{{ __('Select CMM') }}</option>
                                        @foreach($manuals as $manual)
                                            <option value="{{ $manual->id }}">{{ $manual->number }} ({{ $manual->title }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="">
                                    <label for="name">{{ __('Name') }}</label>
                                    <input id='name' type="text" class="form-control" name="name" required>
                                </div>
                                <div class="d-flex">
                                    <div class="m-3">
                                        <div class="">
                                            <label for="ipl_num">{{ __('IPL Number') }}</label>
                                            <input id='ipl_num' type="text" class="form-control" name="ipl_num" required>
                                        </div>
                                        <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                            <div class="form-group">
                                                <strong>{{__('Image:')}}</strong>
                                                <input type="file" name="img" class="form-control" placeholder="Image">
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <label for="part_number">{{ __('Part Number') }}</label>
                                            <input id='part_number' type="text" class="form-control"
                                                   name="part_number" required>
                                        </div>

                                    </div>

                                    <div class="m-3">
                                        <div class="">
                                            <label for="assy_ipl_num">{{ __('Assembly IPL Number') }}</label>
                                            <input id='assy_ipl_num' type="text" class="form-control" name="assy_ipl_num" >
                                        </div>
                                        <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                            <div class="form-group">
                                                <strong>{{__(' Assy Image:')}}</strong>
                                                <input type="file" name="assy_img" class="form-control" placeholder="Image">
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <label for="assy_part_number">{{ __(' Assembly Part Number') }}</label>
                                            <input id='assy_part_number' type="text" class="form-control"
                                                   name="assy_part_number" >
                                        </div>
                                    </div>
                                </div>



                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-outline-primary " onclick="showLoadingSpinner()">Save</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>

        // Sorting
        const table = document.getElementById('componentTable');
        const headers = document.querySelectorAll('.sortable');
        headers.forEach(header => {
            header.addEventListener('click', () => {
                const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                header.dataset.direction = direction;
                rows.sort((a, b) => {
                    const aText = a.cells[columnIndex].innerText.trim();
                    const bText = b.cells[columnIndex].innerText.trim();
                    return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                });
                rows.forEach(row => table.querySelector('tbody').appendChild(row));
            });
        });

        // Search
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', () => {
            const filter = searchInput.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

    </script>
@endsection
