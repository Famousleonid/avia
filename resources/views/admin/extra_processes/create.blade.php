@extends('admin.master-embed')

@section('content')
    <style>
        .container {
            max-width: 100%;
        }

        .extra-part-process-grid { display: grid; grid-template-columns: minmax(0, 1fr) 85px minmax(0, 1.3fr) minmax(0, 2fr); gap: 12px; align-items: start; }
        .extra-part-process-grid > div { min-width: 0; }
        .extra-part-process-grid .select2-container { width: 100% !important; }
        .extra-part-process-grid .form-label { display: block; margin-bottom: 6px; }
        .extra-part-description { grid-column: 1 / -1; }
        .extra-part-description textarea { width: 100%; resize: vertical; }
        .quick-spec-selected { width: 100%; text-align: left; white-space: normal; overflow-wrap: anywhere; }
        .select2-results__option { white-space: nowrap; min-height: 32px; line-height: 20px; padding-top: 6px; padding-bottom: 6px; }
        .select2-container .select2-results > .select2-results__options { overflow-x: auto; max-height: 320px; }
        .select2-dropdown { width: var(--extra-part-dropdown-width, auto) !important; }
        @media (max-width: 575px) { .extra-part-process-grid { grid-template-columns: minmax(0, 1fr) 85px; } }

        /* ----------------------------------- Select 2 Dark Theme -------------------------------------*/

        html[data-bs-theme="dark"] #createForm .form-control,
        html[data-bs-theme="dark"] #createForm .form-select,
        html[data-bs-theme="dark"] #createForm .select2-container .select2-selection--single,
        html[data-bs-theme="dark"] #createForm .quick-spec-selected,
        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: #181818 !important;
            color: #e9ecef !important;
        }

        html[data-bs-theme="dark"] .select2-selection--single {
            background-color: #181818 !important;
            color: gray !important;
            height: 38px !important;
            border: 1px solid var(--avia-border) !important;
            align-items: center !important;
            border-radius: 8px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
            color: #999999;
            line-height: 2.2 !important;
        }

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field  {
            background-color: #181818 !important;
        }

        html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-right: 25px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-dropdown {
            max-height: none !important;
            overflow-y: auto !important;
            border: 1px solid #ccc !important;
            border-radius: 8px;
            color: white;
            background-color: #181818 !important;
        }

        html[data-bs-theme="light"] .select2-container .select2-dropdown {
            max-height: none !important;
            overflow-y: auto !important;

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
        .card-body {
            max-height: none;
            overflow-y: auto;
        }
    </style>

    <div class="container mt-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="text-primary mb-0">{{ $editingRecord ? __('Edit separate process form').' #'.$editingRecord->id : __('New separate process form') }}</h5>
                <div class="d-flex gap-2 align-items-center flex-shrink-0">
                    @if($editingRecord && $editingItem)
                    <a id="printSavedExtraForm" class="btn btn-outline-info btn-sm" target="_blank" rel="noopener" title="Print saved form" aria-label="Print saved form" href="{{ route('extra_processes.show_form', ['id' => $editingRecord->id, 'processNameId' => $editingItem['process_name_id'], 'vendor_id' => $editingItem['vendor_id'] ?? null]) }}"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></a>
                    @endif
                    <button type="submit" form="createForm" class="btn btn-outline-primary btn-sm" disabled>{{ !$editingRecord ? __('Save & Open Form') : __('Save') }}</button>
                    <button type="button" id="openSavedExtraForms" class="btn btn-outline-info btn-sm" title="Saved forms" aria-label="Saved forms"><i class="bi bi-archive" aria-hidden="true"></i></button>
                    <button type="button" class="btn btn-outline-secondary btn-sm {{ !$editingRecord ? 'd-none' : '' }}" id="addExtraPartCancelBtn" title="Cancel" aria-label="Cancel"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </div>
            </div>
            <div class="card-body">
                <form id="createForm" data-no-spinner novalidate role="form" method="POST" action="{{route('extra_processes.store')}}" class="createForm">
                    @csrf
                    @if($editingRecord && count($savedItems) > 1)
                    <div class="mb-3"><label for="savedExtraProcess">Saved process</label>
                        <select id="savedExtraProcess" class="form-select">
                        @foreach($savedItems as $savedIndex => $savedItem)
                            <option value="{{ $savedIndex }}" @selected($savedIndex === $editIndex)>{{ $savedIndex + 1 }}. {{ \App\Models\ProcessName::find($savedItem['process_name_id'])?->name }} — {{ \App\Models\Process::find($savedItem['process_id'])?->process }}</option>
                        @endforeach
                        </select>
                    </div>
                    @endif
                    <input type="hidden" name="workorder_id" value="{{$current_wo->id }}">

                    <div class="row mb-3">
                        <div class="col">
                            <label for="i_manual_id" class="form-label pe-2">Manual</label>
                            <div class="form-group">
                                <select name="manual_id" id="i_manual_id" class="form-control" style="width: 400px">
                                    <option value="">---</option>
                                    @foreach($manuals as $manual)
                                        <option value="{{ $manual->id }}"
                                            {{ $manual->id == $manual_id ? 'selected' : '' }}>
                                            {{ $manual->number }} : {{ $manual->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <label for="i_component_id" class="form-label pe-2">Part</label>
                            <div class="form-group ">
                                <select name="component_id" id="i_component_id" class="form-control" style="width: 300px" required>
                                    <option selected value="">---</option>
                                    @foreach($components as $component)
                                        <option value="{{ $component->id }}"
                                                data-has_assy="{{ $component->assy_part_number ? 'true' : 'false' }}"
                                                data-title="{{ $component->name }}">
                                            {{ $component->ipl_num }} : {{ $component->part_number }} - {{ $component->name }}
                                        </option>
                                    @endforeach
                                </select>


                            </div>
                        </div>
                    </div>



                    <div id="processes-container" data-manual-id="{{ $manual_id }}">
                        <div class="process-row mb-3">
                            <div class="extra-part-process-grid">
                                <div>
                                    <label for="serial_num" class="form-label">Serial Number</label>
                                    <input type="text" name="serial_num" id="serial_num" class="form-control">
                                </div>
                                <div>
                                    <label for="qty" class="form-label">Quantity</label>
                                    <input type="number" name="qty" id="qty" class="form-control" value="1" min="1" required>
                                </div>
                                <div>
                                    <label for="extra_process_name" class="form-label">Process Name:</label>
                                    <select id="extra_process_name" name="processes[0][process_names_id]" class="form-control select2-process" required>
                                        <option value=""></option>
                                        @foreach ($processNames as $processName)
                                            @if(!$editingRecord && !\App\Models\ProcessName::canPrintProcessForm($processName)) @continue @endif
                                            <option value="{{ $processName->id }}">{{ $processName->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Processes (Specification):</label>
                                    <div class="process-options"></div>
                                </div>
                                <div class="extra-part-description">
                                    <label for="quick_vendor" class="form-label">Vendor</label>
                                    <select id="quick_vendor" class="form-control"><option value="">No vendor</option>
                                    @foreach(\App\Models\Vendor::orderBy('name')->get() as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="extra-part-description">
                                    <label for="description_0" class="form-label">Description</label>
                                    <textarea class="form-control" id="description_0" name="processes[0][description]" rows="3" placeholder="CMM fig.___ pg. ___"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        const editingRecord = @json($editingRecord);
        const editingItem = @json($editingItem);
        const editingIndex = @json($editIndex);
        const savedSpecificationText = @json($editingItem ? \App\Models\Process::find($editingItem['process_id'])?->process : null);
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('openSavedExtraForms').addEventListener('click', function() {
                window.parent.postMessage({type:'openSavedExtraForms'}, window.location.origin);
            });
            var cancelBtn = document.getElementById('addExtraPartCancelBtn');
            if (cancelBtn && window.parent !== window) {
                cancelBtn.addEventListener('click', function() {
                    if (editingRecord) {
                        window.location.href = @json(route('extra_process.create', $current_wo->id).'?modal=1&quick=1');
                        return;
                    }

                });
            }
        });
        // Инициализация select2 для выбора компонента
        $(document).ready(function() {
            // Устанавливаем значение по умолчанию для Manual
            const defaultManualId = {{ $manual_id }};
            if (defaultManualId) {
                $('#i_manual_id').val(defaultManualId).trigger('change.select2');
            }

            // Инициализация select2 для выбора компонента
            $('#i_component_id').select2({
                placeholder: 'Search IPL, P/N or part name',
                minimumResultsForSearch: 0,
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "No components found";
                    }
                },
                sorter: function(data) {
                    return data.sort(function(a, b) {
                        // Извлекаем IPL номер из текста (всё до первого ":")
                        const aIpl = a.text.split(':')[0].trim();
                        const bIpl = b.text.split(':')[0].trim();

                        // Разбиваем IPL номер на части (например, "1-40" -> ["1", "40"])
                        const aParts = aIpl.split('-');
                        const bParts = bIpl.split('-');

                        // Сравниваем первую часть (до дефиса)
                        const aFirst = parseInt(aParts[0]);
                        const bFirst = parseInt(bParts[0]);
                        if (aFirst !== bFirst) {
                            return aFirst - bFirst;
                        }

                        // Если первые части равны, сравниваем вторую часть
                        const aSecond = aParts[1].replace(/[^0-9]/g, ''); // Убираем буквы
                        const bSecond = bParts[1].replace(/[^0-9]/g, '');
                        const aSecondNum = parseInt(aSecond);
                        const bSecondNum = parseInt(bSecond);

                        if (aSecondNum !== bSecondNum) {
                            return aSecondNum - bSecondNum;
                        }

                        // Если числовые части равны, сравниваем буквенные суффиксы
                        const aSuffix = aParts[1].replace(/[0-9]/g, '');
                        const bSuffix = bParts[1].replace(/[0-9]/g, '');
                        return aSuffix.localeCompare(bSuffix);
                    });
                }
            });

            // Инициализация Select2 для Manual
            $('#i_manual_id').select2({
                placeholder: '---',
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true
            });

            // Инициализация Select2 для Process Name
            $('.select2-process').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '',
                allowClear: false,
                templateResult: function(data) { if (!data.id || data.id === '') return null; return data.text; },
                templateSelection: function(data) { if (data.id && data.id !== '') return data.text; return ''; }
            }).on('select2:select', function(e) {
                loadProcessesForRow(e.target);
            });

            $('#quick_vendor').select2({width:'100%', placeholder:'Vendor', allowClear:true});
            applyTheme();
            if (editingRecord) {
                $('#i_component_id').val(editingRecord.component_id).trigger('change.select2');
                $('#serial_num').val(editingRecord.serial_num || '');
                $('#qty').val(editingRecord.qty);
                $('#description_0').val(editingItem.description || '');
                $('#quick_vendor').val(editingItem.vendor_id || '').trigger('change.select2');
                $('#extra_process_name').val(editingItem.process_name_id).trigger('change.select2');
                loadProcessesForRow(document.getElementById('extra_process_name'), editingItem.process_id);
            }
            $('#savedExtraProcess').on('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('process_index', this.value);
                window.location.href = url.toString();
            });

            // Dropdowns use their text width, independently of the compact field.
            $(document).on('select2:open.extraPartWidth', 'select', function() {
                const select = this;
                requestAnimationFrame(function() {
                    const instance = $(select).data('select2');
                    const dropdownRoot = instance?.$dropdown?.[0];
                    const dropdown = dropdownRoot?.matches('.select2-dropdown') ? dropdownRoot : dropdownRoot?.querySelector('.select2-dropdown');
                    if (!dropdown) return;
                    const option = dropdown.querySelector('.select2-results__option');
                    const style = getComputedStyle(option || dropdown);
                    const context = document.createElement('canvas').getContext('2d');
                    context.font = style.font;
                    const longest = Array.from(select.options).reduce((width, item) =>
                        Math.max(width, context.measureText(item.text.trim()).width), 0);
                    const field = instance.$container[0].getBoundingClientRect();
                    const width = Math.min(window.innerWidth - 24, Math.max(field.width, Math.ceil(longest + 48)));
                    dropdown.style.setProperty('width', width + 'px', 'important');
                    dropdown.style.setProperty('--extra-part-dropdown-width', width + 'px');
                    dropdown.style.setProperty('max-width', (window.innerWidth - 24) + 'px', 'important');
                    const host = dropdown.parentElement;
                    host.style.width = width + 'px';
                    host.style.left = Math.max(12, Math.min(field.left, window.innerWidth - width - 12)) + window.scrollX + 'px';
                    dropdown.querySelectorAll('.select2-results__option').forEach(item => { item.title = item.textContent.trim(); });
                });
            });
        });

        // Функция для загрузки компонентов по manual_id
        function loadComponentsByManual(manualId) {
            const ajaxUrl = '{{ route("api.get-components-by-manual") }}';
            console.log('Loading components for manual_id:', manualId);

            $.ajax({
                url: ajaxUrl,
                method: 'GET',
                data: {
                    manual_id: manualId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // Очищаем текущие опции в дропдауне компонентов
                    $('#i_component_id').empty().append('<option value="">---</option>');

                    // Добавляем новые опции
                    response.components.forEach(function(component) {
                        $('#i_component_id').append(
                            '<option value="' + component.id + '" ' +
                            'data-has_assy="' + (component.assy_part_number ? 'true' : 'false') + '" ' +
                            'data-title="' + component.name + '">' +
                            component.ipl_num + ' : ' + component.part_number + ' - ' + component.name +
                            '</option>'
                        );
                    });

                    // Обновляем Select2
                    $('#i_component_id').trigger('change');
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка загрузки компонентов:', error);
                }
            });
        }

        // Обработчик изменения Manual
        $('#i_manual_id').on('change', function() {
            const selectedManualId = $(this).val();
            document.getElementById('processes-container').dataset.manualId = selectedManualId || '{{ $manual_id }}';
            $('.select2-process').val('').trigger('change.select2');
            document.querySelectorAll('.process-options').forEach(el => el.replaceChildren());
            $('#i_component_id').empty().append(new Option('', '')).trigger('change.select2');

            if (selectedManualId) {
                loadComponentsByManual(selectedManualId);
            } else {
                // Если manual не выбран, загружаем компоненты по начальному manual_id
                const defaultManualId = {{ $manual_id }};
                if (defaultManualId) {
                    loadComponentsByManual(defaultManualId);
                } else {
                    // Если нет начального manual_id, очищаем дропдаун
                    $('#i_component_id').empty().append('<option value="">---</option>').trigger('change');
                }
            }
        });

        // Обработка отправки формы
        document.getElementById('createForm').addEventListener('submit', function (event) {
            event.preventDefault();

            const workorderId = document.querySelector('input[name="workorder_id"]').value;
            const componentId = document.querySelector('select[name="component_id"]').value;
            const serial_num = document.querySelector('input[name="serial_num"]').value;
            const qty = document.querySelector('input[name="qty"]').value;
            const processRows = document.querySelectorAll('.process-row');
            const processesData = [];
            let hasSelectedSpecification = false;

            processRows.forEach(row => {
                const processNameSelect = row.querySelector('select[name*="[process_names_id]"]');
                const processNameId = processNameSelect.value;

                const selectedSpecification = row.querySelector('.quick-spec');

                // Получаем значение description
                const descriptionInput = row.querySelector('[name*="[description]"]');
                const descriptionValue = descriptionInput ? descriptionInput.value : null;

                // Получаем значение notes
                const notesInput = row.querySelector('input[name*="[notes]"]');
                const notesValue = notesInput ? notesInput.value.trim() : null;

                if (selectedSpecification && selectedSpecification.value) {
                    const processId = selectedSpecification.value;
                    processesData.push({
                        process_names_id: processNameId,
                        processes: [parseInt(processId)], // Массив с одним элементом для совместимости
                        description: descriptionValue || null,
                        notes: notesValue || null
                    });
                    hasSelectedSpecification = true;
                }
            });

            if (!hasSelectedSpecification) {
                window.showNotification('Process not added because no process is selected.');
                return;
            }

            if (!componentId) {
                window.showNotification('Please select a component.');
                return;
            }

            const requestBody = {
                workorder_id: workorderId,
                component_id: componentId,
                serial_num: serial_num,
                qty: qty,
                processes: JSON.stringify(processesData),
                vendor_id: document.getElementById('quick_vendor')?.value || null,
                quick_form: true
            };

            if (editingRecord) {
                Object.assign(requestBody, {
                    process_index: editingIndex,
                    process_name_id: processesData[0].process_names_id,
                    process_id: processesData[0].processes[0],
                    description: processesData[0].description
                });
            }

            const submitButton = document.querySelector('button[type="submit"]');
            if (submitButton.dataset.saving) return;
            submitButton.dataset.saving = '1';
            submitButton.disabled = true;
            const printTab = !editingRecord ? window.open('about:blank', '_blank') : null;
            fetch(editingRecord ? @json(url('/extra_processes')).concat('/', editingRecord.id) : @json(route('extra_processes.store')), {
                method: editingRecord ? 'PUT' : 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestBody)
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.message) {
                        window.showNotification(data.message);
                    }
                    if (data.success && editingRecord) {
                        const printLink = document.getElementById('printSavedExtraForm');
                        const savedUrl = new URL(@json(url('/extra_processes')) + '/' + editingRecord.id + '/form/' + requestBody.process_name_id, window.location.origin);
                        if (requestBody.vendor_id) savedUrl.searchParams.set('vendor_id', requestBody.vendor_id);
                        if (printLink) printLink.href = savedUrl.toString();
                        window.parent.postMessage({type: 'quickExtraPartSaved'}, window.location.origin);
                        return;
                    }
                    if (data.success) {
                        const formUrl = new URL(data.form_url, window.location.origin);
                        const vendor = document.getElementById('quick_vendor')?.value;
                        if (vendor) formUrl.searchParams.set('vendor_id', vendor);
                        if (printTab) printTab.location.href = formUrl.toString();
                        else { const link = document.createElement('a'); link.href = data.form_url; link.target = '_blank'; link.textContent = 'Open saved form'; document.getElementById('createForm').appendChild(link); }
                        window.parent.postMessage({type: 'quickExtraPartSaved'}, window.location.origin);
                        return;
                    }

                })
                .catch(error => {
                    if (printTab) printTab.close();
                    console.error('Error:', error);
                    window.notifyError('Error saving processes: ' + error.message);
                }).finally(() => { delete submitButton.dataset.saving; submitButton.disabled = false; });
        });

        // Функция загрузки процессов для выбранного Process Name
        function loadProcessesForRow(selectElement, savedProcessId = null) {
            const processNameId = selectElement.value;
            const processRow = selectElement.closest('.process-row');
            const processOptionsContainer = processRow ? processRow.querySelector('.process-options') : null;
            const manualId = document.getElementById('processes-container') ? document.getElementById('processes-container').dataset.manualId : null;
            const saveButton = document.querySelector('button[type="submit"]');

            if (!processNameId || !processOptionsContainer) return;

            processOptionsContainer.innerHTML = '';

            return fetch(`/get-process/${processNameId}?manual_id=${manualId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (selectElement.value !== processNameId || document.getElementById('processes-container').dataset.manualId !== manualId) return;
                    if (savedProcessId && !data.some(item => String(item.id) === String(savedProcessId)) && savedSpecificationText) {
                        data.push({id:savedProcessId, process:savedSpecificationText});
                    }
                    if (data && data.length > 0) {
                        const select = document.createElement('select');
                        select.className = 'form-control quick-spec';
                        select.required = true;
                        select.appendChild(new Option('', ''));
                        data.forEach(process => select.appendChild(new Option(process.process, process.id)));
                        processOptionsContainer.appendChild(select);
                        $(select).select2({width:'100%', placeholder:'Select specification', minimumResultsForSearch:0});
                        const selectedText = document.createElement('button');
                        selectedText.type = 'button';
                        selectedText.className = 'btn btn-outline-secondary quick-spec-selected d-none';
                        selectedText.title = 'Change specification';
                        processOptionsContainer.appendChild(selectedText);
                        function showSelectedSpecification() {
                            const hasSelection = Boolean(select.value);
                            selectedText.textContent = hasSelection ? select.selectedOptions[0].text : '';
                            selectedText.classList.toggle('d-none', !hasSelection);
                            $(select).data('select2').$container.toggleClass('d-none', hasSelection);
                        }
                        $(select).on('select2:select', function() {
                            $(select).select2('close');
                            showSelectedSpecification();
                            document.getElementById('description_0')?.focus({preventScroll:true});
                        }).on('select2:close', showSelectedSpecification);
                        selectedText.addEventListener('click', function() {
                            selectedText.classList.add('d-none');
                            $(select).data('select2').$container.removeClass('d-none');
                            $(select).select2('open');
                        });
                        if (savedProcessId) {
                            $(select).val(String(savedProcessId)).trigger('change.select2');
                            showSelectedSpecification();
                        }
                        if (saveButton) saveButton.disabled = false;
                    } else {
                        const noSpecLabel = document.createElement('div');
                        noSpecLabel.classList.add('text-muted', 'mt-2');
                        noSpecLabel.innerHTML = 'No specification. Add specification for this process.';
                        processOptionsContainer.appendChild(noSpecLabel);
                        if (saveButton) saveButton.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Ошибка при получении процессов:', error);
                    if (saveButton) saveButton.disabled = true;
                });
        }

        // Support native Process Name changes before Select2 initialization.
        document.addEventListener('change', function (event) {
            if (event.target.name && event.target.name.includes('[process_names_id]') && !$(event.target).hasClass('select2-hidden-accessible')) {
                loadProcessesForRow(event.target);
            }
        });

        function applyTheme() {
            const isDark = document.documentElement.getAttribute('data-bs-theme');
            const selectContainer = $('.select2-container');
            if (isDark === 'dark') {
                selectContainer.addClass('select2-dark').removeClass('select2-light');
                $('.select2-container .select2-dropdown').addClass('select2-dark').removeClass('select2-light');
            } else {
                selectContainer.addClass('select2-light').removeClass('select2-dark');
                $('.select2-container .select2-dropdown').addClass('select2-light').removeClass('select2-dark');
            }
        }

    </script>
@endsection
