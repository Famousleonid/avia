@extends(request()->query('modal') ? 'admin.master-embed' : 'admin.master')

@section('content')
    <style>
        .container {
            max-width: {{ $bushingContext ? '720px' : '860px' }};
        }

        .bushing-process-context {
            display: grid;
            grid-template-columns: minmax(0, 0.65fr) minmax(0, 1.35fr);
            gap: .75rem;
            padding: .75rem 1rem;
            border: 1px solid var(--avia-border, #40566d);
            border-radius: .6rem;
            background: color-mix(in srgb, var(--avia-surface-raised, #263545) 82%, transparent);
        }

        .bushing-process-context__label {
            color: var(--bs-secondary-color);
            font-size: .72rem;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .bushing-process-context__value {
            margin-top: .3rem;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .bushing-existing-processes {
            min-height: 72px;
            max-height: 132px;
            overflow-y: auto;
            overflow-wrap: anywhere;
            background: var(--avia-input, #152331);
        }

        .bushing-existing-process {
            display: flex;
            gap: .55rem;
            padding: .5rem .65rem;
            border-bottom: 1px solid color-mix(in srgb, var(--avia-border, #40566d) 70%, transparent);
        }

        .bushing-existing-process:last-child {
            border-bottom: 0;
        }

        .bushing-existing-process i {
            flex: 0 0 auto;
            margin-top: .15rem;
            color: var(--bs-info);
        }

        .bushing-new-process-panel {
            padding: .8rem 1rem 1rem;
            border: 1px dashed var(--avia-border, #40566d);
            border-radius: .6rem;
        }

        @media (max-width: 575.98px) {
            .bushing-process-context {
                grid-template-columns: 1fr;
            }
        }

        /* Select2 — поддержка тёмной и светлой темы */
        html[data-bs-theme="dark"] .select2-selection--single {
            background-color: var(--avia-input) !important;
            color: #999999 !important;
            height: 38px !important;
            border: 1px solid var(--avia-border) !important;
            align-items: center !important;
            border-radius: 8px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
            color: #999999 !important;
            line-height: 2.2 !important;
        }

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: var(--avia-surface-raised) !important;
            color: #fff !important;
        }

        html[data-bs-theme="dark"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
            overflow-y: auto !important;
            border: 1px solid var(--avia-border) !important;
            border-radius: 8px;
            background-color: var(--avia-input) !important;
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

    <div class="container {{ $bushingContext ? 'mt-2' : 'mt-3' }}">
        <div class="card bg-gradient">
            <div class="card-header">
                @if($bushingContext)
                    <h5 class="text-info mb-1">{{ __('Add Bushing Process') }}</h5>
                    <div class="small text-body-secondary">{{ __('Choose a process name, review what already exists, then add a new process only if needed.') }}</div>
                @else
                    <h4 class="text-primary">Add Process for Manual {{$manual->number}} ({{$manual->title}})</h4>
                @endif
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('processes.store') }}" enctype="multipart/form-data"
                      id="createCMMForm" data-no-spinner>
                @csrf
                    <input type="hidden" name="manual_id" value="{{ $manual->id }}">
                    <input type="hidden" name="return_to" value="{{ request()->query('return_to', '') }}">
                    @if($bushingContext)
                        <input type="hidden" name="context" value="bushing">
                        <input type="hidden" name="workorder_id" value="{{ $workorder->id }}">
                    @endif

                    <div class="form-group">
                        @if($bushingContext)
                            <div class="bushing-process-context mb-3" aria-label="{{ __('Workorder CMM context') }}">
                                <div>
                                    <div class="bushing-process-context__label">{{ __('Workorder') }}</div>
                                    <div class="bushing-process-context__value">W{{ $workorder->number }}</div>
                                </div>
                                <div>
                                    <div class="bushing-process-context__label">{{ __('CMM') }}</div>
                                    <div class="bushing-process-context__value">{{ $manual->number }}@if(filled($manual->title)) · {{ $manual->title }}@endif</div>
                                </div>
                            </div>

                            <div>
                                <label for="process_name_id">{{ __('Process Name') }}</label>
                                <select id="process_name_id" name="process_names_id" class="form-control mt-2" required
                                        style="width: 100%">
                                    <option value="">
                                    </option>
                                    @foreach ($processNames as $processName)
                                        <option value="{{ $processName->id }}" @selected((string) old('process_names_id', request()->query('process_name_id')) === (string) $processName->id)>{{ $processName->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
                                    <label class="mb-0">{{ __('Existing Processes in this CMM') }}</label>
                                    <span class="small text-body-secondary" id="existingProcessCount"></span>
                                </div>
                                <div id="ex_process-list" class="bushing-existing-processes border rounded" aria-live="polite">
                                    <div class="text-muted small p-3">{{ __('Choose a process name first.') }}</div>
                                </div>
                                <div class="form-text">{{ __('These processes are already available. Add your own only when the required wording is missing.') }}</div>
                            </div>

                            <div class="bushing-new-process-panel mt-3">
                                <label for="process" class="fw-semibold">{{ __('Add Your Process') }}</label>
                                <div class="form-text mt-0 mb-2">{{ __('Enter the exact process specification that must be added to this CMM.') }}</div>
                                <textarea id="process" class="form-control" name="process" rows="2" required maxlength="255" disabled
                                          placeholder="{{ __('Select a process name before entering a new process') }}">{{ old('process') }}</textarea>
                                <div class="small text-warning mt-2 d-none" id="processCreateMessage" role="alert"></div>
                            </div>
                        @else
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="process_name_id">{{ __('Process Name') }}</label>
                                    <select id="process_name_id" name="process_names_id" class="form-control mt-2" required
                                            style="width: 100%">
                                        <option value=""></option>
                                        @foreach ($processNames as $processName)
                                            <option value="{{ $processName->id }}" @selected((string) old('process_names_id', request()->query('process_name_id')) === (string) $processName->id)>{{ $processName->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="p-1">{{ __('Existing Processes') }}</h6>
                                    <div id="ex_process-list" class="ps-2 border rounded p-2" style="height: calc(10.5em + 1rem + 2px); line-height: 1.5; overflow-y: auto; overflow-wrap: anywhere;"></div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label for="process">{{ __('Enter Process') }}</label>
                                <textarea id="process" class="form-control mt-2" name="process" rows="3" required maxlength="255"
                                          placeholder="{{ __('Enter a new process') }}">{{ old('process') }}</textarea>
                            </div>

                            <div class="mt-3">
                                <label for="process_comment">{{ __('Comment') }}</label>
                                <textarea id="process_comment"
                                          class="form-control mt-2"
                                          name="process_comment" maxlength="2000"
                                          rows="4">{{ old('process_comment') }}</textarea>
                            </div>
                        @endif
                    </div>

                    <div class="text-end {{ $bushingContext ? 'mt-2' : 'm-3' }}">
                        <button type="submit" class="btn btn-outline-primary {{ $bushingContext ? '' : 'mt-3' }}" @disabled($bushingContext)>{{ $bushingContext ? __('Add Process') : __('Save') }}</button>
                        @if(request()->query('modal'))
                            <button type="button" class="btn btn-outline-secondary {{ $bushingContext ? '' : 'mt-3' }}" id="processCreateCancelBtn">{{ __('Cancel') }}</button>
                        @else
                            <a href="{{ request()->query('return_to', route('manuals.show', ['manual' => $manual->id, 'tab' => 'processes'])) }}" class="btn btn-outline-secondary mt-3">{{ __('Back') }}</a>
                        @endif
                    </div>
                </form>
            </div>

            @unless($bushingContext)
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
            @endunless

        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const processNameSelect = document.getElementById('process_name_id');
            const exProcessList = document.getElementById('ex_process-list');
            const manualId = document.querySelector('input[name="manual_id"]')?.value;
            const inModal = {{ request()->query('modal') ? 'true' : 'false' }};
            const bushingContext = {{ $bushingContext ? 'true' : 'false' }};
            const workorderId = document.querySelector('input[name="workorder_id"]')?.value || '';
            const processInput = document.getElementById('process');
            const processCreateMessage = document.getElementById('processCreateMessage');
            const existingProcessCount = document.getElementById('existingProcessCount');
            const form = document.getElementById('createCMMForm');
            const submitBtn = form?.querySelector('button[type="submit"]');

            // Select2 для Process Name с поиском
            if (typeof $ !== 'undefined' && $.fn.select2 && processNameSelect) {
                $(processNameSelect).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: '{{__('Select process Name')}}',
                    allowClear: false,
                    dropdownParent: inModal && window.parent !== window ? $(document.body) : undefined,
                    templateResult: function(data) { if (!data.id) return null; return data.text; },
                    templateSelection: function(data) { if (!data.id) return data.text || ''; return data.text; }
                });
            }

            let loadExisting = function () {};
            let existingRequest = 0;
            // Загрузка Existing processes при смене Process Name
            if (processNameSelect && exProcessList) {
                loadExisting = function() {
                    const requestId = ++existingRequest;
                    const processNameId = (typeof $ !== 'undefined') ? $(processNameSelect).val() : processNameSelect.value;

                    if (processNameId) {
                        const params = new URLSearchParams({ processNameId: processNameId, manualId: manualId });
                        if (bushingContext) {
                            params.set('context', 'bushing');
                            params.set('workorder_id', workorderId);
                            exProcessList.innerHTML = '<div class="text-muted small p-3">{{ __("Loading...") }}</div>';
                            if (processInput) processInput.disabled = true;
                            if (submitBtn) submitBtn.disabled = true;
                        }

                        fetch(@json(route('processes.getProcesses')) + '?' + params.toString(), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin'
                        })
                            .then(async response => {
                                const data = await response.json().catch(function() { return {}; });
                                if (!response.ok) throw new Error(data.message || '{{ __("Error while loading data") }}');
                                return data;
                            })
                            .then(data => {
                                if (requestId !== existingRequest) return;
                                exProcessList.innerHTML = '';
                                if (data.existingProcesses && data.existingProcesses.length > 0) {
                                    data.existingProcesses.forEach(process => {
                                        const div = document.createElement('div');
                                        div.className = bushingContext ? 'bushing-existing-process' : 'process-item mb-1';
                                        if (bushingContext) {
                                            const icon = document.createElement('i');
                                            icon.className = 'bi bi-check2-circle';
                                            const body = document.createElement('div');
                                            const text = document.createElement('div');
                                            text.textContent = process.process;
                                            body.appendChild(text);
                                            if (process.process_comment) {
                                                const comment = document.createElement('div');
                                                comment.className = 'small text-body-secondary mt-1';
                                                comment.textContent = process.process_comment;
                                                body.appendChild(comment);
                                            }
                                            div.append(icon, body);
                                        } else {
                                            div.textContent = process.process;
                                        }
                                        exProcessList.appendChild(div);
                                    });
                                } else {
                                    exProcessList.innerHTML = '<div class="text-muted small p-3">{{ __("There are no existing processes") }}</div>';
                                }

                                if (existingProcessCount) {
                                    const count = data.existingProcesses?.length || 0;
                                    existingProcessCount.textContent = count + ' {{ __("found") }}';
                                }

                                if (bushingContext) {
                                    const canCreate = data.canCreateProcess === true;
                                    if (processInput) processInput.disabled = !canCreate;
                                    if (processInput && canCreate) processInput.placeholder = '{{ __("Enter a new process") }}';
                                    if (submitBtn) submitBtn.disabled = !canCreate;
                                    if (processCreateMessage) {
                                        processCreateMessage.textContent = canCreate ? '' : (data.createProcessMessage || '{{ __("You cannot add a process for this process name.") }}');
                                        processCreateMessage.classList.toggle('d-none', canCreate);
                                    }
                                    if (canCreate && processInput) processInput.focus();
                                }
                            })
                            .catch(error => {
                                if (requestId !== existingRequest) return;
                                console.error('Ошибка:', error);
                                exProcessList.innerHTML = '<div class="text-danger small p-3"></div>';
                                exProcessList.firstElementChild.textContent = error.message || '{{ __("Failed to load processes") }}';
                                if (existingProcessCount) existingProcessCount.textContent = '';
                                if (processInput) processInput.disabled = bushingContext;
                                if (submitBtn) submitBtn.disabled = bushingContext;
                            });
                    } else {
                        exProcessList.innerHTML = bushingContext
                            ? '<div class="text-muted small p-3">{{ __("Choose a process name first.") }}</div>'
                            : '';
                        if (existingProcessCount) existingProcessCount.textContent = '';
                        if (processInput) processInput.disabled = bushingContext;
                        if (submitBtn) submitBtn.disabled = bushingContext;
                        if (processCreateMessage) processCreateMessage.classList.add('d-none');
                    }
                };

                if (typeof $ !== 'undefined') {
                    $(processNameSelect).on('change', loadExisting);
                } else {
                    processNameSelect.addEventListener('change', loadExisting);
                }
                loadExisting();
            }

            // Modal mode: Cancel button and AJAX form submit
            if (inModal && window.parent !== window) {
                const cancelBtn = document.getElementById('processCreateCancelBtn');
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function() {
                        (window.top || window.parent).postMessage({ type: 'addProcessesCancel' }, '*');
                    });
                }
            }

                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        if (submitBtn?.disabled || !form.reportValidity()) return;
                        const origHtml = submitBtn ? submitBtn.innerHTML : '';
                        if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
                        const fd = new FormData(form);
                        fetch(form.action, {
                            method: 'POST',
                            body: fd,
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        })
                        .then(async function(r) {
                            const data = await r.json().catch(function() { return {}; });
                            if (!r.ok) throw new Error(Object.values(data.errors || {}).flat().join('\n') || data.message || '{{ __("Failed to submit.") }}');
                            return data;
                        })
                        .then(function(data) {
                            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
                            if (data.success) {
                                if (inModal && window.parent !== window) {
                                (window.top || window.parent).postMessage({
                                    type: 'addProcessesSuccess',
                                    message: data.message || '{{ __("Process added successfully.") }}',
                                    process: data.process || null,
                                    processName: processNameSelect?.options[processNameSelect.selectedIndex]?.text || ''
                                }, '*');
                                } else {
                                    document.getElementById('process').value = '';
                                    const processComment = document.getElementById('process_comment');
                                    if (processComment) processComment.value = '';
                                    loadExisting();
                                    window.showNotification(data.message || '{{ __("Process added successfully.") }}', 'success');
                                    document.getElementById('process').focus();
                                }
                            } else {
                                window.notifyError(data.message || (data.errors ? JSON.stringify(data.errors) : '') || '{{ __("Error.") }}');
                            }
                        })
                        .catch(function(error) {
                            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origHtml; }
                            window.notifyError(error.message || '{{ __("Failed to submit.") }}');
                        });
                    });
                }
        });
    </script>

@endsection
