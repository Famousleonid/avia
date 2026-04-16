@php
    $embed = $embed ?? false;
    $bushingTableColumnWidths = $bushingTableColumnWidths ?? [
        'bushing' => '28%',
        'select' => '70px',
        'qty' => '15px',
        'machining' => '10%',
        'stress_relief' => '10%',
        'ndt' => '20px',
        'passivation' => '10%',
        'cad' => '10%',
        'anodizing' => '10%',
        'xylan' => '10%',
    ];
@endphp
<style>
    .bushing-create-table {
        table-layout: fixed;
        width: 100%;
        min-width: 100%;
    }
    table.bushing-create-table th,
    table.bushing-create-table td {
        box-sizing: border-box;
        padding: .12rem .16rem !important;
        vertical-align: middle;
    }
    table.bushing-create-table col.bushing-col-qty,
    table.bushing-create-table th.bushing-col-qty,
    table.bushing-create-table td.bushing-col-qty {
        width: {{ $bushingTableColumnWidths['qty'] }} !important;
        min-width: {{ $bushingTableColumnWidths['qty'] }} !important;
        max-width: {{ $bushingTableColumnWidths['qty'] }} !important;
    }
    table.bushing-create-table col.bushing-col-ndt,
    table.bushing-create-table th.bushing-col-ndt,
    table.bushing-create-table td.bushing-col-ndt {
        width: {{ $bushingTableColumnWidths['ndt'] }} !important;
        min-width: {{ $bushingTableColumnWidths['ndt'] }} !important;
        max-width: {{ $bushingTableColumnWidths['ndt'] }} !important;
    }
    table.bushing-create-table th.bushing-col-qty,
    table.bushing-create-table td.bushing-col-qty,
    table.bushing-create-table th.bushing-col-ndt,
    table.bushing-create-table td.bushing-col-ndt {
        box-sizing: border-box;
        overflow: hidden;
        text-overflow: clip;
        white-space: nowrap;
    }
    .bushing-create-table .qty-input {
        max-width: 100% !important;
        width: 100% !important;
        min-width: 0 !important;
        padding-left: 1px !important;
        padding-right: 1px !important;
    }
    .bushing-create-table .bushing-ipl {
        display: inline-block;
        font-size: 10px !important;
        font-weight: 400 !important;
        line-height: 1.05 !important;
    }
    .bushing-create-table .bushing-part-number {
        font-size: 12px !important;
    }
</style>
<form id="bushings-form" method="POST" action="{{ route('wo_bushings.store') }}" class="bushing-create-form" data-embed="{{ $embed ? '1' : '0' }}">
    @csrf
    <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
    <div class="d-flex justify-content-end gap-2 mb-3">
        @if(!$embed)
        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#addBushingsFromManualModal">
            <i class="fas fa-exchange-alt"></i> {{ __('Add from Manual') }}
        </button>
        @endif
        @if($embed)
        <button type="button" class="btn btn-outline-primary btn-sm open-add-processes-modal" data-add-processes-url="{{ route('processes.create', ['manual_id' => $current_wo->unit->manual_id, 'return_to' => $returnTo ?? route('wo_bushings.create', $current_wo->id)]) }}">
            <i class="fas fa-cogs"></i> {{ __('Add Processes') }}
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm open-add-part-modal" data-add-part-url="{{ route('components.create', ['manual_id' => $current_wo->unit->manual_id ?? null, 'redirect' => $returnTo ?? route('wo_bushings.create', $current_wo->id)]) }}">
            <i class="fas fa-plus"></i> {{ __('Add Part') }}
        </button>
        @else
        <a href="{{ route('processes.create', ['manual_id' => $current_wo->unit->manual_id, 'return_to' => route('wo_bushings.create', $current_wo->id)]) }}"
           class="btn btn-outline-primary btn-sm">
            <i class="fas fa-cogs"></i> {{ __('Add Processes') }}
        </a>
        <a href="{{ route('components.create', ['manual_id' => $current_wo->unit->manual_id ?? null, 'redirect' => route('wo_bushings.create', $current_wo->id)]) }}"
           class="btn btn-outline-primary btn-sm">
            <i class="fas fa-plus"></i> {{ __('Add Component') }}
        </a>
        @endif
    </div>
    <div class="table-wrapper" style="max-height: 65vh; overflow: auto; margin-right: 0; padding-right: 0;">
        <table class="display table shadow table-hover align-middle table-bordered dir-table mb-0 bushing-create-table">
            <colgroup>
                <col style="width: {{ $bushingTableColumnWidths['bushing'] }};">
                <col style="width: {{ $bushingTableColumnWidths['select'] }};">
                <col class="bushing-col-qty" style="width: {{ $bushingTableColumnWidths['qty'] }};">
                <col style="width: {{ $bushingTableColumnWidths['machining'] }};">
                <col style="width: {{ $bushingTableColumnWidths['stress_relief'] }};">
                <col class="bushing-col-ndt" style="width: {{ $bushingTableColumnWidths['ndt'] }};">
                <col style="width: {{ $bushingTableColumnWidths['passivation'] }};">
                <col style="width: {{ $bushingTableColumnWidths['cad'] }};">
                <col style="width: {{ $bushingTableColumnWidths['anodizing'] }};">
                <col style="width: {{ $bushingTableColumnWidths['xylan'] }};">
            </colgroup>
            <thead class="header-row bg-gradient">
                <tr>
                    <th class="text-primary text-center">Bushings</th>
                    <th class="text-primary text-center"
                        title="{{ __("Check or uncheck to include or exclude each bushing from this row's group. Processes apply to all selected bushings in the row.") }}">Select</th>
                    <th class="text-primary text-center bushing-col-qty">QTY</th>
                    <th class="text-primary text-center">Machining</th>
                    <th class="text-primary text-center">Stress Relief</th>
                    <th class="text-primary text-center bushing-col-ndt">NDT</th>
                    <th class="text-primary text-center">Passivation</th>
                    <th class="text-primary text-center">CAD</th>
                    <th class="text-primary text-center">Anodizing</th>
                    <th class="text-primary text-center">Xylan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bushings as $bushIplNum => $bushingGroup)
                <tr>
                    <td class="ps-2">
                        @foreach($bushingGroup as $bushing)
                            <div class="mb-1"><span><span class="bushing-ipl" style="font-size: 10px !important; font-weight: 400 !important; line-height: 1.05 !important;">{{ $bushing->ipl_num }}</span> - <span class="bushing-part-number" style="font-size: 12px !important;">{{ $bushing->part_number }}</span></span></div>
                        @endforeach
                    </td>
                    <td class="text-center">
                        <div class="text-start">
                            @foreach($bushingGroup as $bushing)
                                <div class="mb-1">
                                    <input type="checkbox" name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][components][]"
                                           value="{{ $bushing->id }}" class="form-check-input me-1 component-checkbox"
                                           data-group="{{ $bushIplNum ?: 'no_ipl' }}"
                                           data-units-assy="{{ $bushing->units_assy ?? 1 }}"
                                           title="{{ __('Include this bushing in this group (shared QTY and processes for the row)') }}">
                                    <small>{{ $bushing->ipl_num }}</small>
                                </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="text-center bushing-col-qty">
                        <input type="number" name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][qty]"
                               class="form-control qty-input" min="0" value="1"
                               data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                    </td>
                    <td>
                        <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][machining]"
                                class="form-select form-select-sm" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                            <option value="">---</option>
                            @foreach($machiningProcesses as $process)
                                <option value="{{ $process->id }}">{{ Str::limit($process->process, 50) }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][stress_relief]"
                                class="form-select form-select-sm" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                            <option value="">---</option>
                            @foreach($stressReliefProcesses as $process)
                                <option value="{{ $process->id }}">{{ Str::limit($process->process, 50) }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="bushing-col-ndt">
                        <div class="ndt-checkboxes" data-group="{{ $bushIplNum ?: 'no_ipl' }}" style="max-height: 120px; overflow-y: auto;">
                            @foreach($ndtProcesses as $process)
                                <div class="form-check">
                                    <input type="checkbox"
                                           name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][ndt][]"
                                           value="{{ $process->id }}" class="form-check-input" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                                    <label class="form-check-label" style="font-size: 0.8rem;">{{ $process->process_name->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][passivation]"
                                class="form-select form-select-sm" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                            <option value="">---</option>
                            @foreach($passivationProcesses as $process)
                                <option value="{{ $process->id }}">{{ Str::limit($process->process, 50) }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][cad]"
                                class="form-select form-select-sm" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                            <option value="">---</option>
                            @foreach($cadProcesses as $process)
                                <option value="{{ $process->id }}">{{ Str::limit($process->process, 50) }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][anodizing]"
                                class="form-select form-select-sm" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                            <option value="">---</option>
                            @foreach($anodizingProcesses as $process)
                                <option value="{{ $process->id }}">{{ Str::limit($process->process, 50) }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="group_bushings[{{ $bushIplNum ?: 'no_ipl' }}][xylan]"
                                class="form-select form-select-sm" data-group="{{ $bushIplNum ?: 'no_ipl' }}" disabled>
                            <option value="">---</option>
                            @foreach($xylanProcesses as $process)
                                <option value="{{ $process->id }}">{{ Str::limit($process->process, 50) }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</form>
@if(!$embed && isset($manuals))
<!-- Add from Manual modal (only on non-embed) -->
<div class="modal fade" id="addBushingsFromManualModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add Bushings from Another Manual') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="manual_select" class="form-label">{{ __('Select Manual') }}</label>
                    <select class="form-select" id="manual_select" style="width: 100%;">
                        <option value="">{{ __('-- Select Manual --') }}</option>
                        @foreach($manuals as $manual)
                            @if($manual->id != $current_wo->unit->manual_id)
                                <option value="{{ $manual->id }}">{{ $manual->number }} - {{ $manual->title }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div id="bushings_selection" style="display: none;">
                    <label class="form-label">{{ __('Select Bushings') }}</label>
                    <div id="bushings_list" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 0.25rem;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="add_selected_bushings_btn" style="display: none;">{{ __('Add Selected Bushings') }}</button>
            </div>
        </div>
    </div>
</div>
@endif
<script>
(function() {
    var form = document.getElementById('bushings-form');
    if (!form) return;

    window.bushingClearForm = function() {
        if (!confirm('{{ __("Are you sure you want to clear all data?") }}')) return;
        form.reset();
        form.querySelectorAll('[data-group]').forEach(function(field) {
            if (!field.classList.contains('component-checkbox')) {
                field.disabled = true;
                if (field.classList.contains('qty-input')) field.value = '1';
            }
        });
    };

    window.bushingToggleGroupFields = function(groupName) {
        if (groupName === null || groupName === undefined) return;
        var g = String(groupName);
        var groupCheckboxes = Array.prototype.slice.call(document.querySelectorAll('.component-checkbox')).filter(function (cb) {
            return cb.getAttribute('data-group') === g;
        });
        var groupFields = Array.prototype.slice.call(document.querySelectorAll('[data-group]')).filter(function (el) {
            return el.getAttribute('data-group') === g && !el.classList.contains('component-checkbox');
        });
        var hasSelected = groupCheckboxes.some(function(c){ return c.checked; });
        var firstChecked = groupCheckboxes.find(function(c){ return c.checked; });
        var unitsAssy = firstChecked ? (firstChecked.getAttribute('data-units-assy') || '1') : '1';
        groupFields.forEach(function(field) {
            if (field.type === 'checkbox' && field.name && field.name.indexOf('[ndt]') !== -1) {
                field.disabled = !hasSelected;
                if (!hasSelected) field.checked = false;
            } else if (field.tagName === 'SELECT' || field.classList.contains('qty-input')) {
                field.disabled = !hasSelected;
                if (!hasSelected) {
                    field.value = field.classList.contains('qty-input') ? '1' : '';
                } else if (field.classList.contains('qty-input')) {
                    field.value = unitsAssy;
                }
            }
        });
    };

    form.querySelectorAll('[data-group]').forEach(function(field) {
        if (!field.classList.contains('component-checkbox')) field.disabled = true;
    });

    form.addEventListener('change', function (e) {
        var t = e.target;
        if (t && t.classList && t.classList.contains('component-checkbox')) {
            window.bushingToggleGroupFields(t.getAttribute('data-group'));
        }
    });
})();
</script>
