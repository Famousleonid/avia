@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 860px;
        }

        /* Select2 — поддержка тёмной и светлой темы */
        html[data-bs-theme="dark"] .select2-selection--single {
            background-color: #121212 !important;
            color: #999999 !important;
            height: 38px !important;
            border: 1px solid #495057 !important;
            align-items: center !important;
            border-radius: 8px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
            color: #999999 !important;
            line-height: 2.2 !important;
        }

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: #343A40 !important;
            color: #fff !important;
        }

        html[data-bs-theme="dark"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
            overflow-y: auto !important;
            border: 1px solid #495057 !important;
            border-radius: 8px;
            background-color: #121212 !important;
        }

        html[data-bs-theme="dark"] .select2-container .select2-results__option {
            color: #e9ecef !important;
        }

        html[data-bs-theme="dark"] .select2-container .select2-results__option--highlighted {
            background-color: #6ea8fe !important;
            color: #000000 !important;
        }

        html[data-bs-theme="light"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
            overflow-y: auto !important;
        }
    </style>

    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <h4 class="text-primary">Add Process for Manual {{$manual->number}} ({{$manual->title}})</h4>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('processes.store') }}" enctype="multipart/form-data"
                      id="createCMMForm">
                @csrf
                    <input type="hidden" name="manual_id" value="{{ $manual->id }}">
                    <input type="hidden" name="return_to" value="{{ request()->query('return_to', '') }}">

                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="process_name_id">{{ __('Process Name') }}</label>
                                <select id="process_name_id" name="process_names_id" class="form-control mt-2" required
                                        style="width: 100%">
                                    <option value="">
{{--                                        {{__('Select Process Name')}}--}}
                                    </option>
                                    @foreach ($processNames as $processName)
                                        <option value="{{ $processName->id }}">{{ $processName->name }}</option>
                                    @endforeach
                                </select>
{{--                                <button type="button" class="btn btn-link p-0 mt-1" data-bs-toggle="modal"--}}
{{--                                        data-bs-target="#addProcessNameModal">{{ __('Add Process Name') }}</button>--}}
                            </div>
                            <div class="col-md-6">
                                <h6 class="p-1">{{ __('Existing Processes') }}</h6>
                                <div id="ex_process-list" class="flex-grow-1 ps-2 border rounded p-2" style="min-height: 80px;">
                                    <!-- Динамический список existing процессов -->
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label for="process">{{ __('Enter Process') }}</label>
                            <input id="process" type="text" class="form-control mt-2" name="process"
                                   placeholder="{{ __('Enter a new process') }}" style="max-width: 500px;">
                        </div>
                    </div>

                    <div class="text-end m-3">
                        <button type="submit" class="btn btn-outline-primary mt-3 ">{{ __('Save') }}</button>
                        <a href="{{ request()->query('return_to', route('processes.index')) }}" class="btn btn-outline-secondary mt-3">{{ __('Back') }}
                        </a>
                    </div>
                </form>
            </div>

            <!-- Модальное окно для добавления названия процесса -->
            <div class="modal fade" id="addProcessNameModal" tabindex="-1" aria-labelledby="addProcessNameModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addProcessNameModalLabel">{{ __('Add Process Name') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>
                        <form action="{{ route('process_names.store') }}" method="POST" id="addProcessName">
                            @csrf
                        <div class="modal-body">
                            <input type="hidden" name="manual_id" value="{{ $manual->id }}">

                            <!-- Форма для добавления нового названия процесса -->
                                <div class="form-group">
                                    <label for="name">{{ __('Process name') }}</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="process_sheet_name">{{ __('Process Sheet Name') }}</label>
                                    <input type="text" class="form-control" id="process_sheet_name" name="process_sheet_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="form_number">{{ __('Form Number') }}</label>
                                    <input type="text" class="form-control" id="form_number" name="form_number" required>
                                </div>

                        </div>
                        <div class="modal-footer ">
                            <button type="submit" class="btn btn-outline-primary " >
                                {{ __('Save') }}
                            </button>

                            <button type="button" class="btn btn-outline-secondary " data-bs-dismiss="modal">
                                {{ __('Cansel') }}
                            </button>

                        </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const processNameSelect = document.getElementById('process_name_id');
            const exProcessList = document.getElementById('ex_process-list');
            const manualId = document.querySelector('input[name="manual_id"]').value;

            // Select2 для Process Name с поиском
            if (typeof $ !== 'undefined' && $.fn.select2 && processNameSelect) {
                $(processNameSelect).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: '{{__('Select process Name')}}',
                    allowClear: false,
                    templateResult: function(data) { if (!data.id) return null; return data.text; },
                    templateSelection: function(data) { if (!data.id) return data.text || ''; return data.text; }
                });
            }

            // Загрузка Existing processes при смене Process Name
            if (processNameSelect && exProcessList) {
                const loadExisting = function() {
                    const processNameId = (typeof $ !== 'undefined') ? $(processNameSelect).val() : processNameSelect.value;

                    if (processNameId) {
                        fetch(`/get-processes?processNameId=${processNameId}&manualId=${manualId}`)
                            .then(response => {
                                if (!response.ok) throw new Error('Ошибка загрузки данных');
                                return response.json();
                            })
                            .then(data => {
                                exProcessList.innerHTML = '';
                                if (data.existingProcesses && data.existingProcesses.length > 0) {
                                    data.existingProcesses.forEach(process => {
                                        const div = document.createElement('div');
                                        div.className = 'process-item';
                                        div.textContent = process.process;
                                        div.style.marginBottom = '5px';
                                        exProcessList.appendChild(div);
                                    });
                                } else {
                                    exProcessList.innerHTML = '<div class="text-muted small">There are no existing processes</div>';
                                }
                            })
                            .catch(error => {
                                console.error('Ошибка:', error);
                                exProcessList.innerHTML = '<div class="text-danger small">Failed to load processes</div>';
                            });
                    } else {
                        exProcessList.innerHTML = '';
                    }
                };

                if (typeof $ !== 'undefined') {
                    $(processNameSelect).on('change', loadExisting);
                } else {
                    processNameSelect.addEventListener('change', loadExisting);
                }
            }
        });
    </script>

@endsection
