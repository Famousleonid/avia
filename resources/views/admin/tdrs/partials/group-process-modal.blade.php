@php
    $groupProcessRows = collect($processGroups ?? [])->values();
@endphp

@if($groupProcessRows->isNotEmpty())
    <style>
        #tdrGroupProcessModal .modal-dialog {
            max-height: 86vh;
        }

        #tdrGroupProcessModal .modal-content {
            display: flex;
            flex-direction: column;
            max-height: 86vh;
        }

        #tdrGroupProcessModal .modal-body {
            min-height: 0;
            overflow-y: auto;
        }

        #tdrGroupProcessModal .tdr-group-process-panel {
            border: 1px solid var(--bs-border-color);
            border-radius: 6px;
            padding: .75rem;
        }

        #tdrGroupProcessModal .component-checkboxes {
            max-height: min(360px, 44vh);
            overflow-y: auto;
        }

        #tdrGroupProcessModal .component-checkboxes .form-check {
            border-radius: 4px;
            margin-bottom: .35rem;
            padding: .25rem .25rem .25rem 1.75rem;
        }

        #tdrGroupProcessModal .component-checkboxes .form-check:hover {
            background-color: rgba(128, 128, 128, .1);
        }

        #tdrGroupProcessModal .tdr-group-process-print {
            min-width: 60px;
        }

        #tdrGroupProcessModal .tdr-group-process-print.disabled {
            opacity: .45;
            pointer-events: none;
        }
    </style>

    <div class="modal fade" id="tdrGroupProcessModal" tabindex="-1" aria-labelledby="tdrGroupProcessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="tdrGroupProcessModalLabel">
                        <i class="fas fa-print"></i> {{ __('Group Process') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-8">
                            <label for="tdrGroupProcessSelect" class="form-label mb-1">{{ __('Process Name') }}</label>
                            <select class="form-select form-select-sm" id="tdrGroupProcessSelect">
                                @foreach($groupProcessRows as $group)
                                    <option value="{{ $group['row_uid'] }}">
                                        {{ $group['display_name'] ?? ($group['process_name']->name ?? 'N/A') }}
                                        ({{ $group['position_count'] ?? $group['qty'] }} {{ __('pos.') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @foreach($groupProcessRows as $group)
                        @php
                            $rowUid = $group['row_uid'];
                            $actualProcessNameId = $group['representative_process_name_id'];
                            $displayName = $group['display_name'] ?? ($group['process_name']->name ?? 'N/A');
                            $rowKind = $group['row_kind'] ?? 'standard';
                        @endphp
                        <section class="tdr-group-process-panel process-group-form-row d-none"
                                 data-group-process-panel
                                 data-group-form-row="{{ $rowUid }}"
                                 data-process-name-id="{{ $actualProcessNameId }}"
                                 data-row-kind="{{ $rowKind }}">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="fw-semibold text-primary me-auto">{{ $displayName }}</div>
                                <span class="badge bg-success process-qty-badge"
                                      data-group-form-row="{{ $rowUid }}"
                                      data-position-unit="{{ __('pos.') }}">
                                    {{ $group['position_count'] ?? $group['qty'] }} {{ __('pos.') }}
                                </span>
                                <a href="{{ route('tdrs.show_group_forms', ['id' => $current_wo->id, 'processNameId' => $actualProcessNameId]) }}"
                                   target="_blank"
                                   class="btn btn-outline-primary btn-sm group-form-button tdr-group-process-print"
                                   title="{{ __('Print Form') }}"
                                   aria-label="{{ __('Print Form') }}"
                                   data-process-name-id="{{ $actualProcessNameId }}"
                                   data-group-form-row="{{ $rowUid }}">
                                    {{ __('Form') }}
                                </a>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-8">
                                    <div class="component-checkboxes" data-group-form-row="{{ $rowUid }}">
                                        @foreach($group['components'] ?? [] as $componentKey => $component)
                                            <div class="form-check">
                                                <input class="form-check-input component-checkbox"
                                                       type="checkbox"
                                                       value="{{ ($component['ipl_num'] ?? '') . '_' . ($component['part_number'] ?? '') . '_' . ($component['serial_number'] ?? '') }}"
                                                       data-component-id="{{ $component['id'] }}"
                                                       data-tdr-id="{{ $component['tdr_id'] ?? '' }}"
                                                       data-ipl-num="{{ $component['ipl_num'] ?? '' }}"
                                                       data-part-number="{{ $component['part_number'] ?? '' }}"
                                                       data-serial-number="{{ $component['serial_number'] ?? '' }}"
                                                       data-process-name-id="{{ $actualProcessNameId }}"
                                                       data-group-form-row="{{ $rowUid }}"
                                                       data-order-qty="{{ (int) ($component['qty'] ?? 1) }}"
                                                       id="tdrGroupProcess_c_{{ $rowUid }}_{{ $componentKey }}"
                                                       checked>
                                                <label class="form-check-label" for="tdrGroupProcess_c_{{ $rowUid }}_{{ $componentKey }}">
                                                    <strong>{{ $component['ipl_num'] ?? '' }}</strong>
                                                    {{ $component['part_number'] ?? '' }}
                                                    {{ $component['name'] ?? '' }}
                                                    @if(!empty($component['serial_number']))
                                                        <span class="text-muted">SN: {{ $component['serial_number'] }}</span>
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label mb-1">{{ __('Vendor') }}</label>
                                    <select class="form-select form-select-sm vendor-select"
                                            data-group-form-row="{{ $rowUid }}"
                                            data-process-name-id="{{ $actualProcessNameId }}">
                                        <option value="">{{ __('No vendor') }}</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @once
        @include('admin.tdrs.partials.process-group-forms-modal-script')
    @endonce

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('tdrGroupProcessModal');
            if (!modal) {
                return;
            }

            if (typeof window.initProcessGroupFormModalRows === 'function') {
                window.initProcessGroupFormModalRows(modal);
            }

            var select = document.getElementById('tdrGroupProcessSelect');
            var panels = modal.querySelectorAll('[data-group-process-panel]');

            function showSelectedPanel() {
                var selectedRow = select ? select.value : '';
                panels.forEach(function (panel) {
                    panel.classList.toggle('d-none', panel.getAttribute('data-group-form-row') !== selectedRow);
                });
            }

            if (select) {
                select.addEventListener('change', showSelectedPanel);
            }

            showSelectedPanel();
        });
    </script>
@endif
