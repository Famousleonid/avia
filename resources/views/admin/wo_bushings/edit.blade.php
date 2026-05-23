@extends(request()->query('fragment') ? 'admin.fragment' : (request()->query('modal') ? 'admin.master-embed' : 'admin.master'))

@section('content')
    <style>
        .bushing-edit-shell { max-width: 100%; }
        .bushing-edit-card { background: var(--bs-body-bg); border: 1px solid rgba(125, 140, 155, .35); }
        .bushing-edit-table-wrap { max-height: calc(88vh - 170px); overflow-y: auto; overflow-x: hidden; }
        .bushing-edit-table { width: min(1280px, 100%); max-width: 1280px; table-layout: fixed; }
        .bushing-edit-table th { position: sticky; top: 0; z-index: 2; background: #303841; font-size: .78rem; }
        .bushing-edit-table td { vertical-align: middle; font-size: .8rem; }
        .bushing-line-list { display: flex; flex-direction: column; gap: .45rem; }
        .bushing-line-list > div { min-height: 24px; display: flex; align-items: center; }
        .bushing-part-cell { white-space: normal; line-height: 1.25; }
        .bushing-select-cell { display: inline-flex; align-items: center; gap: .4rem; white-space: nowrap; }
        .bushing-select-ipl { color: var(--bs-body-color); font-size: .8rem; }
        .bushing-qty-input { width: 70px; text-align: center; }
        .bushing-process-toggle { display: flex; justify-content: center; }
        .bushing-process-cell .form-select { width: 100%; min-width: 0; font-size: .75rem; padding: .2rem .35rem; }
        .bushing-ndt-select { min-width: 0 !important; }
        .bushing-edit-table .form-check-input {
            width: 1.05rem;
            height: 1.05rem;
        }
        .bushing-process-line.is-hidden .bushing-process-control { display: none; }
        .bushing-readonly-qty { color: var(--bs-secondary-color); }
        .bushing-edit-actions { gap: .5rem; }
        .bushing-edit-shell[data-embedded="1"] .bushing-edit-card {
            background: transparent;
            border: 0;
        }
        .bushing-edit-shell[data-embedded="1"] .bushing-edit-table-wrap {
            max-height: calc(90vh - 92px);
        }
    </style>

    @php
        $embedded = request()->query('fragment') || request()->query('modal');
        $bushDataByComponent = collect($bushData ?? [])->keyBy(fn ($item) => (int) ($item['bushing'] ?? 0));
        $processColumnsBeforeNdt = [
            'machining' => ['label' => 'Machining', 'options' => $machiningProcesses],
            'stress_relief' => ['label' => 'Stress Relief', 'options' => $stressReliefProcesses],
        ];
        $processColumnsAfterNdt = [
            'passivation' => ['label' => 'Passivation', 'options' => $passivationProcesses],
            'cad' => ['label' => 'CAD', 'options' => $cadProcesses],
            'anodizing' => ['label' => 'Anodizing', 'options' => $anodizingProcesses],
            'xylan' => ['label' => 'Xylan', 'options' => $xylanProcesses],
        ];
    @endphp

    <div class="bushing-edit-shell" id="editBushingFormRoot" data-embedded="{{ $embedded ? '1' : '0' }}">
        <div class="card bushing-edit-card">
            @if($bushings->flatten()->count() > 0)
                <form id="bushings-form" method="POST" action="{{ route('wo_bushings.update', $woBushing->id) }}">
                    @csrf
                    @method('PUT')
            @endif

            @unless($embedded)
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="text-info mb-0">{{ __('Update Bushings List') }} WO {{ $current_wo->number }}</h5>
                    <div class="d-flex bushing-edit-actions">
                        @if($bushings->flatten()->count() > 0)
                            <button type="submit" class="btn btn-success btn-sm" id="editBushingSubmitBtn">
                                <i class="fas fa-save"></i> {{ __('Update Bushings Data') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="editBushingClearBtn">
                                <i class="fas fa-eraser"></i> {{ __('Clear All') }}
                            </button>
                        @endif
                        <a href="{{ route('wo_bushings.show', $current_wo->id) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                        </a>
                    </div>
                </div>
            @endunless

            @if($bushings->flatten()->count() > 0)
                <div class="card-body {{ $embedded ? 'p-0' : 'p-2' }}">
                    <div class="table-responsive bushing-edit-table-wrap">
                        <table class="table table-bordered table-hover align-middle bushing-edit-table mb-0">
                            <colgroup>
                                <col style="width: 16.8%;">
                                <col style="width: 4.8%;">
                                <col style="width: 5.5%;">
                                <col style="width: 5.5%;">
                                <col style="width: 6.6%;">
                                <col style="width: 9.4%;">
                                <col style="width: 10.2%;">
                                <col style="width: 6.25%;">
                                <col style="width: 9%;">
                                <col style="width: 9%;">
                                <col style="width: 9%;">
                                <col style="width: 8%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-info text-center">{{ __('Bushing') }}</th>
                                    <th class="text-info text-center">{{ __('Part Qty') }}</th>
                                    <th class="text-info text-center">{{ __('Select') }}</th>
                                    <th class="text-info text-center">{{ __('WO Qty') }}</th>
                                    <th class="text-info text-center">{{ __('Processes') }}</th>
                                    <th class="text-info text-center">{{ __('Machining') }}</th>
                                    <th class="text-info text-center">{{ __('Stress Relief') }}</th>
                                    <th class="text-info text-center">{{ __('NDT') }}</th>
                                    <th class="text-info text-center">{{ __('Passivation') }}</th>
                                    <th class="text-info text-center">{{ __('CAD') }}</th>
                                    <th class="text-info text-center">{{ __('Anodizing') }}</th>
                                    <th class="text-info text-center">{{ __('Xylan') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bushings as $bushIplNum => $bushingGroup)
                                    @php
                                        $groupKey = (string) ($bushIplNum ?: 'no_ipl');
                                    @endphp
                                    <tr class="bushing-row" data-group-key="{{ $groupKey }}">
                                        <td class="bushing-part-cell">
                                            <div class="bushing-line-list">
                                                @foreach($bushingGroup as $bushing)
                                                    <div><strong>{{ $bushing->ipl_num }}</strong> - {{ $bushing->part_number }}</div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center bushing-readonly-qty">
                                            <div class="bushing-line-list align-items-center">
                                                @foreach($bushingGroup as $bushing)
                                                    <div>{{ $bushing->units_assy ?? 1 }}</div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="bushing-line-list align-items-start">
                                                @foreach($bushingGroup as $bushing)
                                                    @php
                                                        $existing = $bushDataByComponent->get((int) $bushing->id);
                                                        $selected = $existing !== null;
                                                    @endphp
                                                    <div>
                                                        <span class="bushing-select-cell">
                                                            <input type="hidden" name="group_bushings[{{ $groupKey }}][items][{{ $bushing->id }}][selected]" value="0">
                                                            <input type="checkbox"
                                                                   name="group_bushings[{{ $groupKey }}][items][{{ $bushing->id }}][selected]"
                                                                   value="1"
                                                                   class="form-check-input component-checkbox"
                                                                   {{ $selected ? 'checked' : '' }}>
                                                            <span class="bushing-select-ipl">{{ $bushing->ipl_num }}</span>
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="bushing-line-list align-items-center">
                                                @foreach($bushingGroup as $bushing)
                                                    @php
                                                        $existing = $bushDataByComponent->get((int) $bushing->id);
                                                        $rowQty = $existing['qty'] ?? ($bushing->units_assy ?? 1);
                                                    @endphp
                                                    <div>
                                                        <input type="number"
                                                               name="group_bushings[{{ $groupKey }}][items][{{ $bushing->id }}][qty]"
                                                               class="form-control form-control-sm bushing-qty-input"
                                                               min="1"
                                                               value="{{ $rowQty }}"
                                                               data-part-qty="{{ $bushing->units_assy ?? 1 }}">
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="bushing-line-list align-items-center">
                                                @foreach($bushingGroup as $bushing)
                                                    @php
                                                        $existing = $bushDataByComponent->get((int) $bushing->id);
                                                        $selected = $existing !== null;
                                                        $rowNeedProcesses = $selected ? (bool) ($existing['need_processes'] ?? true) : false;
                                                    @endphp
                                                    <div class="bushing-process-toggle">
                                                        <input type="hidden" name="group_bushings[{{ $groupKey }}][items][{{ $bushing->id }}][need_processes]" value="0">
                                                        <input type="checkbox"
                                                               name="group_bushings[{{ $groupKey }}][items][{{ $bushing->id }}][need_processes]"
                                                               value="1"
                                                               class="form-check-input bushing-need-processes"
                                                               {{ $rowNeedProcesses ? 'checked' : '' }}>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>

                                        @foreach($processColumnsBeforeNdt as $field => $column)
                                            <td class="bushing-process-cell">
                                                <div class="bushing-line-list">
                                                    @foreach($bushingGroup as $bushing)
                                                        @php
                                                            $existing = $bushDataByComponent->get((int) $bushing->id);
                                                            $rowProcesses = $existing['processes'] ?? [];
                                                        @endphp
                                                        <div class="bushing-process-line" data-component-id="{{ $bushing->id }}">
                                                            <select name="group_bushings[{{ $groupKey }}][items][{{ $bushing->id }}][{{ $field }}]"
                                                                    class="form-select form-select-sm bushing-process-control"
                                                                    data-process-field="{{ $field }}">
                                                                <option value="">...</option>
                                                                @foreach($column['options'] as $process)
                                                                    <option value="{{ $process->id }}" {{ ($rowProcesses[$field] ?? null) == $process->id ? 'selected' : '' }}>
                                                                        {{ $process->process }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        @endforeach

                                        <td class="bushing-process-cell">
                                            <div class="bushing-line-list">
                                                @foreach($bushingGroup as $bushing)
                                                    @php
                                                        $existing = $bushDataByComponent->get((int) $bushing->id);
                                                        $rowProcesses = $existing['processes'] ?? [];
                                                        $selectedNdt = collect($rowProcesses['ndt'] ?? [])->first();
                                                    @endphp
                                                    <div class="bushing-process-line" data-component-id="{{ $bushing->id }}">
                                                        <select name="group_bushings[{{ $groupKey }}][items][{{ $bushing->id }}][ndt]"
                                                                class="form-select form-select-sm bushing-process-control bushing-ndt-select"
                                                                data-process-field="ndt">
                                                            <option value="">...</option>
                                                            @foreach($ndtProcesses as $process)
                                                                <option value="{{ $process->id }}" {{ (int) $selectedNdt === (int) $process->id ? 'selected' : '' }}>
                                                                    {{ $process->process_name->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>

                                        @foreach($processColumnsAfterNdt as $field => $column)
                                            <td class="bushing-process-cell">
                                                <div class="bushing-line-list">
                                                    @foreach($bushingGroup as $bushing)
                                                        @php
                                                            $existing = $bushDataByComponent->get((int) $bushing->id);
                                                            $rowProcesses = $existing['processes'] ?? [];
                                                        @endphp
                                                        <div class="bushing-process-line" data-component-id="{{ $bushing->id }}">
                                                            <select name="group_bushings[{{ $groupKey }}][items][{{ $bushing->id }}][{{ $field }}]"
                                                                    class="form-select form-select-sm bushing-process-control"
                                                                    data-process-field="{{ $field }}">
                                                                <option value="">...</option>
                                                                @foreach($column['options'] as $process)
                                                                    <option value="{{ $process->id }}" {{ ($rowProcesses[$field] ?? null) == $process->id ? 'selected' : '' }}>
                                                                        {{ $process->process }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                </form>
            @else
                <div class="card-body text-center py-5">
                    <h3 class="text-muted">{{ __('No Bushings available for this Work Order') }}</h3>
                    <p class="text-muted">{{ __('No components with "Is Bush" marked are found for this manual.') }}</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        window.initEditBushingForm = function(root) {
            root = root || document;
            var formRoot = root.querySelector ? root.querySelector('#editBushingFormRoot') : document.getElementById('editBushingFormRoot');
            var form = root.querySelector ? root.querySelector('#bushings-form') : document.getElementById('bushings-form');
            if (!form || form.dataset.initialized === '1') return;
            form.dataset.initialized = '1';

            function notify(message, type) {
                if (window.tdrShowNotify) window.tdrShowNotify(message, type || 'warning');
                else if (window.showNotification) window.showNotification(message, type || 'warning');
                else alert(message);
            }

            function rowState(row) {
                var selectedChecks = Array.prototype.slice.call(row.querySelectorAll('.component-checkbox'));
                var selected = selectedChecks.some(function(checkbox) { return checkbox.checked; });
                var need = Array.prototype.slice.call(row.querySelectorAll('.bushing-need-processes')).some(function(checkbox) {
                    return !checkbox.disabled && checkbox.checked;
                });
                return { selected: selected, need: need };
            }

            function componentIdFromName(name) {
                var match = String(name || '').match(/\[items]\[(\d+)]/);
                return match ? match[1] : '';
            }

            function syncRow(row) {
                row.querySelectorAll('.component-checkbox').forEach(function(checkbox) {
                    var componentPrefix = checkbox.name.replace('[selected]', '');
                    var componentId = componentIdFromName(checkbox.name);
                    var qty = row.querySelector('input[name="' + componentPrefix + '[qty]"]');
                    var need = row.querySelector('input[name="' + componentPrefix + '[need_processes]"][type="checkbox"]');
                    var showProcesses = checkbox.checked && need && need.checked;

                    if (qty) {
                        qty.disabled = !checkbox.checked;
                        if (checkbox.checked && (!qty.value || parseInt(qty.value, 10) < 1)) {
                            qty.value = qty.getAttribute('data-part-qty') || '1';
                        }
                    }
                    if (need) need.disabled = !checkbox.checked;

                    row.querySelectorAll('.bushing-process-line[data-component-id="' + componentId + '"]').forEach(function(line) {
                        line.classList.toggle('is-hidden', !showProcesses);
                        line.querySelectorAll('.bushing-process-control').forEach(function(control) {
                            control.disabled = !showProcesses;
                            if (!showProcesses) control.value = '';
                        });
                    });
                });
            }

            function syncAllRows() {
                form.querySelectorAll('.bushing-row').forEach(syncRow);
            }

            syncAllRows();
            form.addEventListener('change', function(e) {
                var row = e.target.closest('.bushing-row');
                if (row && (e.target.classList.contains('component-checkbox')
                    || e.target.classList.contains('bushing-need-processes'))) {
                    syncRow(row);
                }
            });

            function clearBushingForm() {
                if (!confirm('{{ __("Are you sure you want to clear all data?") }}')) return;
                form.querySelectorAll('.bushing-row').forEach(function(row) {
                    row.querySelectorAll('.component-checkbox').forEach(function(selected) { selected.checked = false; });
                    row.querySelectorAll('.bushing-need-processes').forEach(function(need) { need.checked = false; });
                    row.querySelectorAll('.bushing-qty-input').forEach(function(qty) { qty.value = qty.getAttribute('data-part-qty') || '1'; });
                    row.querySelectorAll('.bushing-process-control').forEach(function(control) { control.value = ''; });
                    syncRow(row);
                });
            }

            window.clearEditBushingForm = clearBushingForm;
            root.querySelector('#editBushingClearBtn')?.addEventListener('click', clearBushingForm);

            root.querySelector('#editBushingCancelBtn')?.addEventListener('click', function() {
                if (typeof window.handleEditBushingCancel === 'function') {
                    window.handleEditBushingCancel();
                }
            });

            form.addEventListener('submit', function(e) {
                var selectedRows = Array.prototype.slice.call(form.querySelectorAll('.bushing-row'))
                    .filter(function(row) {
                        return Array.prototype.slice.call(row.querySelectorAll('.component-checkbox')).some(function(checkbox) {
                            return checkbox.checked;
                        });
                    });

                if (selectedRows.length === 0) {
                    e.preventDefault();
                    notify('{{ __("Please select at least one component before submitting.") }}');
                    return;
                }

                var hasErrors = false;
                selectedRows.forEach(function(row) {
                    row.querySelectorAll('.component-checkbox').forEach(function(checkbox) {
                        if (!checkbox.checked) return;
                        var componentPrefix = checkbox.name.replace('[selected]', '');
                        var qty = row.querySelector('input[name="' + componentPrefix + '[qty]"]');
                        var need = row.querySelector('input[name="' + componentPrefix + '[need_processes]"][type="checkbox"]');
                        var ndt = row.querySelector('select[name="' + componentPrefix + '[ndt]"]');

                        if (!qty || !qty.value || parseInt(qty.value, 10) < 1) {
                            if (qty) qty.style.borderColor = 'red';
                            hasErrors = true;
                        } else {
                            qty.style.borderColor = '';
                        }
                        if (need && need.checked && ndt && ndt.options.length > 1 && !ndt.value) {
                            ndt.style.borderColor = 'red';
                            hasErrors = true;
                        } else if (ndt) {
                            ndt.style.borderColor = '';
                        }
                    });
                });

                if (hasErrors) {
                    e.preventDefault();
                    notify('{{ __("Please enter WO Qty and NDT for selected bushings with processes.") }}');
                    return;
                }

                if (!formRoot || formRoot.dataset.embedded !== '1') return;

                e.preventDefault();
                var submitBtn = root.querySelector('#editBushingSubmitBtn') || document.getElementById('editBushingModalSubmitBtn');
                var originalHtml = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                }

                var fd = new FormData(form);
                fd.append('_method', 'PUT');
                fetch(form.action, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                    .then(function(response) {
                        return response.json().catch(function() { return {}; });
                    })
                    .then(function(data) {
                        if (data.success) {
                            if (typeof window.handleEditBushingSaved === 'function') window.handleEditBushingSaved();
                            return;
                        }
                        notify(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : '{{ __("Error") }}'), 'error');
                    })
                    .catch(function() {
                        notify('{{ __("Failed to submit.") }}', 'error');
                    })
                    .finally(function() {
                        if (typeof window.hideLoadingSpinner === 'function') window.hideLoadingSpinner();
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        }
                    });
            });
        };

        window.initEditBushingForm(document);
    </script>
@endsection
