@extends('mobile.master')

@section('style')

    <style>
        html, body {
            padding: 0;
            margin: 0;
        }

        .table-responsive {
            max-height: calc(100vh - 160px);
            overflow-y: auto;
        }

        table {
            table-layout: fixed;
            width: 100% !important;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 6px;
            text-align: center;
            vertical-align: middle;
            font-size: 0.75rem;
        }

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 1020;
            background-color: #0d6efd;
            color: white;
        }

        .table td img {
            width: 40px;
            height: 40px;
        }

        @media (max-width: 768px) {
            .table th, .table td {
                font-size: 0.7rem;
                padding: 4px;
            }
        }
        .text-format {
            font-size: 0.75rem;
            line-height: 1;
        }

        @keyframes blink-shadow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.8);
            }
            50% {
                box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.8);
            }
        }

        .select-error {
            animation: blink-shadow 0.5s ease-in-out 3;
            border-color: #dc3545 !important;
            border-width: 1px;
        }

    </style>
@endsection

@section('content')

    <div class="container-fluid d-flex flex-column bg-dark p-0"  style="min-height: calc(100vh - 80px);padding-top: 60px; ">

        <div class="row g-0 flex-grow-1" style="background-color:#343A40;">
            <div class="col-12 p-0">
                <div class="bg-dark py-2 px-3 d-flex justify-content-between align-items-center border-bottom mt-3">
                    <span class="text-success-emphasis text-format">{{ __('Components') }} ({{ $components->count() }})</span>

                    <div class="w-80 me-2">
                        <form method="GET" action="{{ route('mobile.components') }}" class="d-flex w-100 me-2">
                            <select name="workorder_id"
                                    id="selectedWorkorderId"
                                    class="form-select form-select-sm"
                                    onchange="this.form.submit()">
                                <option value="">Select Workorder</option>
                                @foreach($workorders as $wo)
                                    <option value="{{ $wo->id }}" {{ request('workorder_id') == $wo->id ? 'selected' : '' }}>
                                        {{ $wo->number }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <button class="btn btn-success btn text-format" id="openAddComponentBtn" {{ $workorders->count() ? '' : 'disabled' }}>
                        {{ __('Add Component') }}
                    </button>
                </div>

                @if($components->count())
                    <div class="table-responsive p-2 " >
                        <table id="componentTable" class="table table-sm table-striped table-bordered table-dark m-0 shadow">
                            <thead>
                            <tr>
                                <th class="sortable">{{ __('Manual') }}</th>
                                <th class="sortable">{{ __('IPL Number') }}</th>
                                <th class="sortable">{{ __('Component') }}</th>
                                <th class="sortable">{{ __('Part number') }}</th>
                                <th>{{ __('Image') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($components as $component)
                                <tr>
                                    <td>{{ $component->manuals->number }}</td>
                                    <td>{{ $component->ipl_num }}</td>
                                    <td>{{ $component->name }}</td>
                                    <td>{{ $component->part_number }}</td>
                                    <td>
                                        <a href="{{ $component->getFirstMediaBigUrl('components') }}" data-fancybox="component-{{ $component->id }}">
                                            <img class="rounded-circle" src="{{ $component->getFirstMediaThumbnailUrl('components') }}" alt="Img">
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-light py-4">{{ __('COMPONENTS NOT CREATED') }}</div>
                @endif

            </div>
        </div>
    </div>


    <!-- Modal -->
    <div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content bg-dark text-light">
                <form id="componentUploadForm" method="POST" enctype="multipart/form-data" action="{{ route('mobile.component.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addComponentModalLabel">Add Component</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <input type="hidden" name="workorder_id" id="modal_workorder_id">
                    <div class="modal-body">

                        <div class="mb-3">
                            <label for="ipl_num" class="form-label">IPL Number</label>
                            <input type="text" name="ipl_num" id="ipl_num" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="part_number" class="form-label">Part Number</label>
                            <input type="text" name="part_number" id="part_number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Component Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="photo" class="form-label">Component Photo</label>
                            <input type="file" name="photo" accept="image/*" capture="environment" class="form-control" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('scripts')

    <script>
        Fancybox.bind('[data-fancybox^="component-"]', {
            Toolbar: ["zoom", "fullscreen", "close"],
            dragToClose: false,
            showClass: "fancybox-fadeIn",
            hideClass: "fancybox-fadeOut"
        });


        // Сортировка таблицы
        const table = document.getElementById('componentTable');
        const headers = document.querySelectorAll('.sortable');

        headers.forEach(header => {
            header.addEventListener('click', () => {
                const index = Array.from(header.parentNode.children).indexOf(header);
                const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                header.dataset.direction = direction;

                const rows = Array.from(table.querySelectorAll('tbody tr'));
                rows.sort((a, b) => {
                    const aText = a.cells[index].innerText.trim();
                    const bText = b.cells[index].innerText.trim();
                    return direction === 'asc'
                        ? aText.localeCompare(bText)
                        : bText.localeCompare(aText);
                });

                rows.forEach(row => table.querySelector('tbody').appendChild(row));
            });
        });

        document.getElementById('openAddComponentBtn').addEventListener('click', function () {
            const select = document.getElementById('selectedWorkorderId');
            const selectedId = select.value;

            if (!selectedId) {
                select.classList.add('select-error');

                // Убираем эффект после анимации (1.5 сек)
                setTimeout(() => {
                    select.classList.remove('select-error');
                }, 1500);

                return;
            }

            // если выбран — передаём ID
            document.getElementById('modal_workorder_id').value = selectedId;

            const modal = new bootstrap.Modal(document.getElementById('addComponentModal'));
            modal.show();
        });
        // Обработка отправки формы с включением спиннера
        const form = document.getElementById('componentUploadForm');
        form.addEventListener('submit', function () {
            showLoadingSpinner();
        });

    </script>
@endsection
