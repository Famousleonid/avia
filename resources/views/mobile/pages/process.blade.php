@extends('mobile.master')

@section('style')

    <style>
        html, body {
            padding: 0;
            margin: 0;
        }

        .text-format {
            font-size: 0.75rem;
            line-height: 1;
        }

        .gradient-pane {
            background: #343A40;
            color: #f8f9fa;
        }

        .component-list-wrapper {
            max-height: calc(100vh - 210px);
            overflow: auto;
        }

        .process-table {
            font-size: 0.7rem;
        }

        .process-table th,
        .process-table td {
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
        }

        .process-table th:first-child,
        .process-table td:first-child {
            text-align: left;
        }

        .process-table input[type="date"] {
            font-size: 0.75rem;
            padding: 2px 4px;
            height: 28px;
        }
    </style>
@endsection

@section('content')

    <div class="container-fluid d-flex flex-column bg-dark p-0"
         style="min-height: calc(100vh - 80px); padding-top: 60px;">

        {{-- Блок информации о воркордере --}}
        <div id="block-info" class="rounded-3 border border-info gradient-pane shadow-sm" style="margin: 5px; padding: 3px;">
            <div class="d-flex  align-items-center w-100 fw-bold fs-2 ms-3">
                <div class="d-flex align-items-center">
                    @if(!$workorder->isDone())
                        <span class="text-info">W {{ $workorder->number }}</span>
                    @else
                        <span class="text-secondary">{{ $workorder->number }}</span>
                    @endif
                </div>
                <div class="d-flex align-items-center ms-3">
                    @if($workorder->approve_at)
                        <img src="{{ asset('img/ok.png') }}" width="20"
                             title="{{ $workorder->approve_at->format('d.m.Y') }} {{ $workorder->approve_name }}">
                    @else
                        <img src="{{ asset('img/icon_no.png') }}" width="12">
                    @endif
                </div>
                <div class="d-flex align-items-center ms-auto ">
                    @if($workorder->open_at)
                        <span class="text-secondary fw-normal fs-6 me-4">Open at: {{ $workorder->open_at->format('d-M-Y') }}</span>
                    @else
                        <span class="text-secondary fw-normal fs-6 me-4">Open at: - null - </span>
                    @endif
                </div>
            </div>
        </div>

        <hr class="border-secondary opacity-50 my-2">

        <div class="row g-0 flex-grow-1" style="background-color:#343A40;">
            <div class="col-12 p-0">

                {{-- Header: Components --}}
                <div class="bg-dark py-2 px-3 d-flex justify-content-between align-items-center border-bottom mt-3">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="mb-0 text-primary">{{ __('Components') }}</h6>
                        <span class="text-info">({{ $components->count() }})</span>
                    </div>

                    <button class="btn btn-success btn-sm text-format" id="openAddComponentBtn">
                        {{ __('Add Component') }}
                    </button>
                </div>

                @if($components->isEmpty())
                    <div class="text-center text-muted small py-3">
                        {{ __('COMPONENTS NOT CREATED') }}
                    </div>
                @else
                    {{-- Список компонентов + мини-таблицы процессов --}}
                    <div class="list-group list-group-flush component-list-wrapper">
                        @foreach($components as $component)
                            @if(!$component) @continue @endif
                            <div class="list-group-item bg-transparent text-light border-secondary">

                                {{-- Шапка компонента: картинка + инфа --}}
                                <div class="d-flex align-items-center gap-2">
                                    <div class="flex-shrink-0">
                                        <a href="{{ $component->getFirstMediaBigUrl('components') }}"
                                           data-fancybox="component-{{ $component->id }}">
                                            <img class="rounded-circle"
                                                 src="{{ $component->getFirstMediaThumbnailUrl('components') }}"
                                                 alt="Img" width="40" height="40">
                                        </a>
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-info">
                                            {{ $component->name ?? ('#'.$component->id) }}
                                        </div>

                                        {{--                                        <div class="small text-secondary">--}}
                                        {{--                                            <span class="me-2">--}}
                                        {{--                                                <span class="text-muted">Manual:</span>--}}
                                        {{--                                                {{ $component->manuals->number ?? '—' }}--}}
                                        {{--                                            </span>--}}
                                        {{--                                        </div>--}}

                                        <div class="small text-secondary">
                                            <span class="me-2">
                                                <span class="text-muted">IPL:</span>
                                                {{ $component->ipl_num ?? '—' }}
                                            </span>
                                            <span class="me-2">
                                                <span class="text-muted">P/N:</span>
                                                {{ $component->part_number ?? '—' }}
                                            </span>
                                        </div>

                                        @if($component->eff_code)
                                            <div class="small text-secondary">
                                                <span class="text-muted">EFF:</span> {{ $component->eff_code }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @php
                                    $allProcesses = $component->processesForWorkorder ?? collect();
                                @endphp

                                @if($allProcesses->isNotEmpty())
                                    <div class="mt-2 ps-2">
                                        <table class="table table-sm table-dark table-bordered mb-2 align-middle process-table">
                                            <thead>
                                            <tr>
                                                <th style="width:40%;">
                                                    <div class="fw-semibold text-info">
                                                        Processes
                                                    </div>
                                                </th>
                                                <th style="width:30%; text-align: center"
                                                    class="fw-normal text-muted">
                                                    Sent (edit)
                                                </th>
                                                <th style="width:30%; text-align: center"
                                                    class="fw-normal text-muted">
                                                    Returned (edit)
                                                </th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($allProcesses as $pr)
                                                <tr>
                                                    <td class="text-start">
                                                        {{ $pr->processName->name ?? '—' }}
                                                    </td>
                                                    <td>
                                                        <form method="POST"
                                                              action="{{ route('tdrprocesses.updateDate', $pr) }}"
                                                              class="auto-submit-form">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="date"
                                                                   name="date_start"
                                                                   class="form-control form-control-sm"
                                                                   value="{{ $pr->date_start?->format('Y-m-d') }}"
                                                                   placeholder="...">
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <form method="POST"
                                                              action="{{ route('tdrprocesses.updateDate', $pr) }}"
                                                              class="auto-submit-form">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="date"
                                                                   name="date_finish"
                                                                   class="form-control form-control-sm finish-input"
                                                                   value="{{ $pr->date_finish?->format('Y-m-d') }}"
                                                                   placeholder="...">
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="mt-2 ps-2 small text-muted">
                                        No processes for this component on this workorder.
                                    </div>
                                @endif

                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addComponentModal" tabindex="-1"
         aria-labelledby="addComponentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content bg-dark text-light">
                <form id="componentUploadForm"
                      method="POST"
                      enctype="multipart/form-data"
                      action="{{ route('mobile.component.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addComponentModalLabel">Add Component</h5>
                        <button type="button" class="btn-close btn-close-white"
                                data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <input type="hidden" name="workorder_id" id="modal_workorder_id" value="{{ $workorder->id }}">

                    <div class="modal-body">

                        <div class="mb-3">
                            <label for="ipl_num" class="form-label">IPL Number</label>
                            <input type="text" name="ipl_num" id="ipl_num"
                                   class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="part_number" class="form-label">Part Number</label>
                            <input type="text" name="part_number" id="part_number"
                                   class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="eff_code" class="form-label">EFF Code</label>
                            <input type="text" name="eff_code" id="eff_code"
                                   class="form-control"
                                   placeholder="Enter EFF code (optional)">
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Component Name</label>
                            <input type="text" name="name" id="name"
                                   class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="assy_ipl_num" class="form-label">Assembly IPL Number</label>
                            <input type="text" name="assy_ipl_num" id="assy_ipl_num"
                                   class="form-control"
                                   placeholder="Enter assembly IPL number (optional)">
                        </div>

                        <div class="mb-3">
                            <label for="assy_img" class="form-label">Assembly Image</label>
                            <input type="file" name="assy_img"
                                   accept="image/*" capture="environment"
                                   class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="assy_part_number" class="form-label">Assembly Part Number</label>
                            <input type="text" name="assy_part_number" id="assy_part_number"
                                   class="form-control"
                                   placeholder="Enter assembly part number (optional)">
                        </div>

                        <div class="mb-3">
                            <label for="photo" class="form-label">Component Photo</label>
                            <input type="file" name="photo"
                                   accept="image/*" capture="environment"
                                   class="form-control" required>
                        </div>

                        <div class="d-flex justify-content-between">
                            <div class="d-flex">
                                <div class="form-check">
                                    <input class="form-check-input"
                                           type="checkbox" id="log_card" name="log_card">
                                    <label class="form-check-label" for="log_card">
                                        Log Card
                                    </label>
                                </div>

                                <div class="form-check ms-3">
                                    <input class="form-check-input"
                                           type="checkbox" id="repair" name="repair">
                                    <label class="form-check-label" for="repair">
                                        Repair
                                    </label>
                                </div>

                                <div class="form-check ms-3">
                                    <input class="form-check-input"
                                           type="checkbox" id="is_bush" name="is_bush"
                                           onchange="toggleBushIPL()">
                                    <label class="form-check-label" for="is_bush">
                                        Is Bush
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Bush IPL Number field --}}
                        <div class="form-group mb-3" id="bush_ipl_container" style="display: none;">
                            <label for="bush_ipl_num" class="form-label">
                                Initial Bushing IPL Number
                            </label>
                            <input id='bush_ipl_num' type="text"
                                   class="form-control" name="bush_ipl_num"
                                   pattern="^\d+-\d+[A-Za-z]?$"
                                   title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B)">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">Cancel
                        </button>
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


        document.getElementById('openAddComponentBtn').addEventListener('click', function () {
            document.getElementById('modal_workorder_id').value = {{ $workorder->id }};

            const modal = new bootstrap.Modal(document.getElementById('addComponentModal'));
            modal.show();
        });


        const form = document.getElementById('componentUploadForm');
        form.addEventListener('submit', function () {
            if (typeof showLoadingSpinner === 'function') {
                showLoadingSpinner();
            }
        });

        // Автосабмит дат процессов (Sent / Returned)
        document.addEventListener('change', function (e) {
            const input = e.target;
            const form = input.closest('.auto-submit-form');
            if (form) {
                form.submit();
            }
        });

        // Показ/скрытие поля Bush IPL Number
        function toggleBushIPL() {
            const isBushCheckbox = document.getElementById('is_bush');
            const bushIPLContainer = document.getElementById('bush_ipl_container');
            const bushIPLInput = document.getElementById('bush_ipl_num');

            if (isBushCheckbox.checked) {
                bushIPLContainer.style.display = 'block';
                bushIPLInput.required = true;
            } else {
                bushIPLContainer.style.display = 'none';
                bushIPLInput.required = false;
                bushIPLInput.value = '';
            }
        }

        window.toggleBushIPL = toggleBushIPL;
    </script>
@endsection
