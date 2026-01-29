@extends('admin.master')

@section('content')
    <style>
        /* Стили для Select2 (темная и светлая темы) */
        html[data-bs-theme="dark"] .select2-selection--single {
            background-color: #121212 !important;
            color: gray !important;
            height: 38px !important;
            border: 1px solid #495057 !important;
            align-items: center !important;
            border-radius: 8px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
            color: #999999;
            line-height: 2.2 !important;
        }

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: #343A40 !important;
        }

        html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-right: 25px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
            overflow-y: auto !important;
            border: 1px solid #ccc !important;
            border-radius: 8px;
            color: white;
            background-color: #121212 !important;
        }

        html[data-bs-theme="light"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
            overflow-y: auto !important;
        }

        /* Стили для светлой темы */
        html[data-bs-theme="light"] .select2-selection--single {
            background-color: #fff !important;
            color: #212529 !important;
        }

        html[data-bs-theme="light"] .select2-container .select2-selection__rendered {
            color: #212529 !important;
        }

        html[data-bs-theme="dark"] .select2-container .select2-results__option:hover {
            background-color: #6ea8fe;
            color: #000000;
        }

        .select2-container .select2-selection__clear {
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 1;
        }

        /* Увеличенный размер для дополнительного NDT селекта */
        .select2-ndt-plus + .select2-container .select2-selection--multiple {
            min-height: 70px !important;
            padding: 12px !important;
            font-size: 16px !important;
            line-height: 1.5 !important;
            display: flex !important;
            align-items: center !important;
        }

        .select2-ndt-plus + .select2-container .select2-selection__rendered {
            padding: 8px 12px !important;
            min-height: 60px !important;
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: flex-end !important;
            align-items: center !important;
            flex-grow: 1 !important;
        }

        .select2-ndt-plus + .select2-container .select2-selection__choice {
            margin: 6px 6px 6px 0 !important;
            padding: 8px 12px !important;
            font-size: 15px !important;
            line-height: 1.4 !important;
        }

        .select2-ndt-plus + .select2-container .select2-search--inline {
            order: -1 !important;
            flex-grow: 0 !important;
            margin-right: auto !important;
        }

        .select2-ndt-plus + .select2-container .select2-search--inline .select2-search__field {
            padding: 8px 12px !important;
            font-size: 16px !important;
            min-height: 40px !important;
            width: auto !important;
            min-width: 200px !important;
        }
    </style>

    <div class="container mt-3 bg-gradient" style="width: 850px">
        <div class="card bg-gradient">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h4 class="text-primary">{{ __('Edit Part Processes') }}</h4>
                    <h4 class="pe-3">{{ __('W') }}{{ $current_tdr->workorder->number }}</h4>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        {{ $current_tdr->component->name }}
                        PN: {{ $current_tdr->component->part_number }}
                        SN: {{ $current_tdr->serial_number }}
                    </div>
                </div>
            </div>
            <div class="card-body">
                @php
                    // Определяем переменные для использования в форме
                    $currentPlusProcess = $current_tdr_processes->plus_process ?? '';
                    $currentProcessName = $current_tdr_processes->processName;
                    $isNdtProcess = $currentProcessName && strpos($currentProcessName->name, 'NDT-') === 0;
                    $currentPlusProcessIds = !empty($currentPlusProcess) ? explode(',', $currentPlusProcess) : [];
                @endphp
                <form method="POST" action="{{ route('tdr-processes.update', $current_tdr_processes->id) }}" enctype="multipart/form-data" id="editCPForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tdrs_id" value="{{ $current_tdr->id }}">
                    <input type="hidden" name="processes[0][plus_process]" id="plus_process_hidden" value="{{ $currentPlusProcess }}">

                    <!-- Контейнер для строк с процессами -->
                    <div id="processes-container" data-manual-id="{{ $current_tdr->workorder->unit->manual_id ?? '' }}">
                        <div class="process-row mb-3">
                            <div class="row" >
                                <div class="col-md-3" style="width: 200px">
                                    <label for="process_names">Process Name:</label>
                                    <select name="processes[0][process_names_id]" class="form-control select2-process" required>
                                        <option value="">Select Process Name</option>
                                        @foreach ($processNames as $processName)
                                            <option value="{{ $processName->id }}" {{ $current_tdr_processes->process_names_id == $processName->id ? 'selected' : '' }}>
                                                {{ $processName->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="process">Processes:</label>
                                    <div class="process-options">
                                        {{-- Процессы будут загружены динамически через JavaScript --}}
                                        @if($current_tdr_processes->process_names_id)
                                            {{-- Показываем текущие выбранные процессы при загрузке страницы --}}
                                            @php
                                                $currentProcesses = json_decode($current_tdr_processes->processes, true) ?: [];
                                                $currentProcessNameId = $current_tdr_processes->process_names_id;
                                            @endphp
                                            @foreach ($processes as $process)
                                                @if($process->process_names_id == $currentProcessNameId)
                                                    <div class="form-check" data-process-name-id="{{ $process->process_names_id }}">
                                                        <input type="checkbox" name="processes[0][process][]" value="{{ $process->id }}" class="form-check-input"
                                                            {{ in_array($process->id, $currentProcesses) ? 'checked' : '' }}>
                                                        <label class="form-check-label">{{ $process->process }}</label>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>

                                    {{-- Дополнительный селект для NDT процессов (скрыт по умолчанию, показывается через JavaScript) --}}
                                    <div class="ndt-plus-process-container mt-3" style="display: {{ $isNdtProcess ? 'block' : 'none' }};">
                                        <label for="ndt_plus_process_0">Additional NDT Process(es):</label>
                                        <select name="processes[0][ndt_plus_process][]"
                                                class="form-control select2-ndt-plus"
                                                id="ndt_plus_process_0"
                                                data-row-index="0"
                                                multiple
                                                style="width: 100%; min-height: 70px; padding: 12px; font-size: 16px;">
                                            @foreach ($ndtProcessNames as $ndtProcessName)
                                                <option value="{{ $ndtProcessName->id }}"
                                                        data-process-name="{{ $ndtProcessName->name }}"
                                                        {{ in_array((string)$ndtProcessName->id, $currentPlusProcessIds) ? 'selected' : '' }}>
                                                    {{ $ndtProcessName->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="ndt-plus-process-options mt-2">
                                            {{-- Здесь будут чекбоксы для дополнительных NDT процессов --}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
{{--                                    <label for="ec">EC:</label>--}}
                                    <div class="form-check mt-2" id="ec-checkbox-container" style="display: none;">
                                        <input type="checkbox" name="processes[0][ec]" value="1" class="form-check-input" id="ec_edit"
                                            {{ $current_tdr_processes->ec ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ec_edit">
                                            EC
                                        </label>
                                    </div>
                                    <div>
                                        <label for="description" class="form-label" style="margin-bottom: -5px">Description</label>
                                        <input type="text" class="form-control" id="description" name="description" value="{{ old('description', $current_tdr_processes->description) }}" placeholder="Enter Description">
                                        <label for="notes" class="form-label" style="margin-bottom: -5px">Notes</label>
                                        <input type="text" class="form-control" id="notes" name="notes" value="{{ old('notes', $current_tdr_processes->notes) }}" placeholder="Enter Notes">
                                    </div>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-3 me-4">
                        <button type="submit" class="btn btn-outline-primary mt-3" id="updateButton">{{ __('Update') }}</button>
                        <a href="{{ route('tdr-processes.processes', ['tdrId' => $current_tdr->id]) }}" class="btn btn-outline-secondary mt-3">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/tdr-processes/edit-process/edit-process.js') }}"></script>
    <script>
        (function() {
            const config = {
                getProcessesUrl: '{{ route('processes.getProcesses') }}',
                ndtProcessNames: @json($ndtProcessNames->pluck('id')->toArray()),
                ndtProcessNamesData: @json($ndtProcessNames->keyBy('id')),
                ecEligibleProcessNameIds: @json($ecEligibleProcessNameIds ?? []),
                currentProcesses: @json(json_decode($current_tdr_processes->processes, true) ?: [])
            };
            if (window.TdrProcessEditForm) TdrProcessEditForm.init(config);
        })();
    </script>
@endsection

