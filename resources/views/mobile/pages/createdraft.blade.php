{{-- resources/views/mobile/pages/createdraft.blade.php --}}
@extends('mobile.master')

@section('style')
    <style>
        /* card spacing */
        .draft-wrap { padding: 12px 12px 90px; }

        /* Select2 always full width */
        .select2-container { width: 100% !important; }
        .select2-dropdown { z-index: 9999; }

        /* limit dropdown height on mobile */
        .select2-results__options{ max-height: 60vh !important; }

        /* -------- Dark theme for Select2 -------- */
        .select2-container--default .select2-selection--single{
            background:#000 !important;
            border:1px solid #6c757d !important;
            min-height: 38px;
        }
        .select2-container--default .select2-selection__rendered{
            color:#f8f9fa !important;
            line-height: 36px !important;
            padding-left: 10px;
        }
        .select2-container--default .select2-selection__arrow{
            height:36px !important;
            right: 6px;
        }
        .select2-container--default .select2-dropdown{
            background:#000 !important;
            border:1px solid #6c757d !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field{
            background:#111 !important;
            border:1px solid #6c757d !important;
            color:#f8f9fa !important;
            outline: none;
        }
        .select2-container--default .select2-results__option{
            color:#f8f9fa !important;
            background: transparent !important;
        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{
            background:#0dcaf0 !important;
            color:#000 !important;
        }
        .select2-container--default .select2-results__option[aria-selected="true"]{
            background:#222 !important;
        }

        /* modal dark */
        .modal-content.bg-dark { border: 1px solid #6c757d; }

        .draft-flags-row .form-check {
            min-height: 34px;
            margin-bottom: 0;
        }

        .draft-flags-row .form-check span {
            line-height: 1.15;
        }

        .draft-box-row {
            min-height: 38px;
        }

        .draft-box-status {
            min-width: 0;
            letter-spacing: 0;
        }

        .draft-box-status-value {
            letter-spacing: 0;
        }

        .draft-box-notes-preview {
            letter-spacing: 0;
        }

        .draft-box-choice {
            min-height: 42px;
            text-transform: uppercase;
        }
    </style>
@endsection

@section('content')
    <div class="draft-wrap">

        <div class="card bg-dark text-light border-secondary " id="draftCard">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="fw-semibold">Create Draft Workorder</div>
                <div class="small text-white-50">
                    Draft № <span class="text-info fw-semibold">{{ $draftNumber }}</span>
                </div>
            </div>

            <div class="card-body pt-0 mt-0">


                <form method="POST" action="{{ route('mobile.draft.store') }}" id="draftForm">
                    @csrf

                    <div class="row g-2 mb-2">
                        {{-- Draft number preview (not sent) --}}
                        <div class="col-6">
                            <label class="form-label small text-white-50 mb-1">Draft Number</label>
                            <input type="text" class="form-control bg-black text-info border-secondary" value="{{ $draftNumber }}" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-white-50 mb-1">Open date</label>
                            <input type="text" name="open_at" id="draftOpenAt"
                                   class="form-control bg-black text-light border-secondary @error('open_at') is-invalid @enderror"
                                   maxlength="11"
                                   value="{{ old('open_at', $defaultOpenDate) }}"
                                   placeholder=".... /.... /......"
                                   data-project-date
                                   autocomplete="off">
                            @error('open_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Unit with Select2 + Add button --}}
                    <div class="mb-2">
                        <label class="form-label small text-white-50 mb-1 d-flex justify-content-between align-items-center">
                            <span>Unit <span class="text-danger">*</span></span>
                            <button type="button" class="btn btn-sm btn-outline-info py-0"
                                    data-bs-toggle="modal" data-bs-target="#addUnitModal">
                                + Add
                            </button>
                        </label>

                        <select name="unit_id" id="unit_id"
                                class="form-select bg-black text-light border-secondary @error('unit_id') is-invalid @enderror"
                                required>
                            <option value="" selected disabled>— Select Unit —</option>
                            @foreach($units as $u)
                                <option value="{{ $u->id }}" data-name="{{ $u->name ?? '' }}">
                                    {{ $u->part_number }}@if($u->manual) ({{ $u->manual->number }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-2">
                        <label class="form-label small text-white-50 mb-1">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id"
                                class="form-select bg-black text-light border-secondary @error('customer_id') is-invalid @enderror"
                                required>
                            <option value="" selected disabled>— Select Customer —</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-2">
                        <label class="form-label small text-white-50 mb-1">Serial number</label>
                        <input name="serial_number"
                               class="form-control bg-black text-light border-secondary @error('serial_number') is-invalid @enderror"
                               value="{{ old('serial_number') }}" placeholder="s/n">
                        @error('serial_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mt-2">
                        <label class="form-label small text-white-50 mb-1">Description</label>
                        <input name="description" id="description"
                               class="form-control bg-black text-light border-secondary @error('description') is-invalid @enderror"
                               value="{{ old('description') }}">
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mt-2">
                        <label class="form-label small text-white-50 mb-1">Storage</label>

                        <div class="row g-2">
                            <div class="col-4">
                                <input name="storage_rack"
                                       placeholder="Rack"
                                       class="form-control bg-black text-light border-secondary @error('storage_rack') is-invalid @enderror">
                            </div>

                            <div class="col-4">
                                <input name="storage_level"
                                       placeholder="Level"
                                       class="form-control bg-black text-light border-secondary @error('storage_level') is-invalid @enderror">
                            </div>

                            <div class="col-4">
                                <input name="storage_column"
                                       placeholder="Column"
                                       class="form-control bg-black text-light border-secondary @error('storage_column') is-invalid @enderror">
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="row g-2 small draft-flags-row">
                            <div class="col-6">
                                <label class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" name="external_damage" value="1" @checked(old('external_damage'))>
                                    <span>External Damage</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" name="nameplate_missing" value="1" @checked(old('nameplate_missing'))>
                                    <span>Name Plate Missing</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="draft-box-row d-flex align-items-center gap-2 mt-3">
                        <input type="hidden"
                               name="arrival_box_status"
                               id="arrivalBoxStatus"
                               value="{{ old('arrival_box_status') }}">
                        <input type="hidden"
                               name="arrival_box_notes"
                               id="arrivalBoxNotes"
                               value="{{ old('arrival_box_notes') }}">
                        <button type="button"
                                class="btn btn-outline-info btn-sm px-3"
                                data-bs-toggle="modal"
                                data-bs-target="#arrivalBoxModal">
                            Box
                        </button>
                        <div class="draft-box-status flex-fill text-white-50 small text-truncate">
                            Box: <span id="arrivalBoxStatusText" class="draft-box-status-value text-secondary fw-semibold">—</span>
                            <span id="arrivalBoxNotesText" class="draft-box-notes-preview text-white-50"></span>
                        </div>
                    </div>
                    @error('arrival_box_status') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                    @error('arrival_box_notes') <div class="small text-danger mt-1">{{ $message }}</div> @enderror

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-outline-info flex-fill">Save Draft</button>
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary flex-fill">Cancel</a>
                    </div>
                </form>



            </div>
        </div>
    </div>

    {{-- MODAL: Arrival box condition --}}
    <div class="modal fade" id="arrivalBoxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header border-secondary">
                    <h6 class="modal-title">Box condition</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-success w-100 draft-box-choice" data-box-status="ok">OK</button>
                        <button type="button" class="btn btn-outline-info w-100 draft-box-choice" data-box-status="easy">Light repair</button>
                        <button type="button" class="btn btn-outline-warning w-100 draft-box-choice" data-box-status="medium">Medium repair</button>
                        <button type="button" class="btn btn-outline-danger w-100 draft-box-choice" data-box-status="hard">Hard repair</button>
                        <button type="button" class="btn btn-outline-light w-100 draft-box-choice" data-box-status="replace">New box</button>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small text-white-50 mb-1" for="arrivalBoxNotesInput">Notes</label>
                        <textarea id="arrivalBoxNotesInput"
                                  class="form-control bg-black text-light border-secondary"
                                  rows="3"
                                  maxlength="1000"
                                  placeholder="Box notes">{{ old('arrival_box_notes') }}</textarea>
                    </div>
                </div>

                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-info" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Add pending Unit for Draft --}}
    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header border-secondary">
                    <h6 class="modal-title">Add Pending Unit</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2 d-none">
                        <label class="form-label small text-white-50 mb-1 d-none">Manual (CMM)</label>
                        <select id="manual_id" class="form-select bg-black text-light border-secondary d-none">
                            <option value="" selected disabled>— Select Manual —</option>
                            @foreach($manuals as $m)
                                <option value="{{ $m->id }}">
                                    {{ $m->number }} @if($m->lib) ({{ $m->lib }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small text-white-50 mb-1">Part Number <span class="text-danger">*</span></label>
                        <input id="unitPn" class="form-control bg-black text-light border-secondary" placeholder="Enter PN">
                    </div>

                    <div class="small text-warning">
                        Manual will be assigned by manager before the Draft is released.
                    </div>
                </div>

                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="btnCreateUnit" class="btn btn-outline-info">Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const hasSelect2 = !!(window.jQuery && typeof window.jQuery.fn?.select2 === 'function');
            const $ = window.jQuery;

            const openAtInput = document.getElementById('draftOpenAt');
            function normalizeOpenDateMonth() {
                if (!openAtInput) return;

                openAtInput.value = String(openAtInput.value || '').replace(/\/([a-z]{3})\//i, function (_, month) {
                    return '/' + month.charAt(0).toUpperCase() + month.slice(1).toLowerCase() + '/';
                });
            }

            normalizeOpenDateMonth();
            openAtInput?.addEventListener('input', normalizeOpenDateMonth);
            openAtInput?.addEventListener('change', function () {
                setTimeout(normalizeOpenDateMonth, 0);
            });

            function attachOpenDatePickerNormalizer() {
                normalizeOpenDateMonth();

                const picker = openAtInput?._projectDatePicker;
                if (!picker || picker._draftCapitalMonthNormalizerInstalled) return;

                picker._draftCapitalMonthNormalizerInstalled = true;

                picker.config.onChange.push(function () {
                    setTimeout(normalizeOpenDateMonth, 0);
                });

                picker.config.onClose.push(function () {
                    setTimeout(normalizeOpenDateMonth, 0);
                });
            }

            attachOpenDatePickerNormalizer();
            window.addEventListener('load', function () {
                setTimeout(attachOpenDatePickerNormalizer, 0);
                setTimeout(attachOpenDatePickerNormalizer, 250);
            });

            // --- Unit select2 (search + no overflow)
            if (hasSelect2 && document.getElementById('unit_id')) {
                const $unit = $('#unit_id');
                if (!$unit.data('select2')) {
                    $unit.select2({
                        width: '100%',
                        dropdownParent: $('#draftCard'),
                        placeholder: '— Select Unit —',
                        allowClear: true
                    });
                }
            }

            // --- Manual select2 inside modal (search)
            if (hasSelect2 && document.getElementById('manual_id')?.offsetParent) {
                const $manual = $('#manual_id');
                if (!$manual.data('select2')) {
                    $manual.select2({
                        width: '100%',
                        dropdownParent: $('#addUnitModal'),
                        placeholder: '— Select Manual —',
                        allowClear: true
                    });
                }
            }

            // --- Auto fill description from selected unit
            const unitSelect = document.getElementById('unit_id');
            const desc = document.getElementById('description');
            if (unitSelect && desc) {
                unitSelect.addEventListener('change', function () {
                    const opt = this.options[this.selectedIndex];
                    const name = opt?.getAttribute('data-name') || '';
                    desc.value = name;
                });
            }

            const arrivalBoxStatus = document.getElementById('arrivalBoxStatus');
            const arrivalBoxNotes = document.getElementById('arrivalBoxNotes');
            const arrivalBoxNotesInput = document.getElementById('arrivalBoxNotesInput');
            const arrivalBoxStatusText = document.getElementById('arrivalBoxStatusText');
            const arrivalBoxNotesText = document.getElementById('arrivalBoxNotesText');
            const arrivalBoxLabels = {
                ok: 'OK',
                easy: 'Light repair',
                medium: 'Medium repair',
                hard: 'Hard repair',
                replace: 'New box',
            };
            const arrivalBoxTextClasses = {
                ok: 'text-success',
                easy: 'text-info',
                medium: 'text-warning',
                hard: 'text-danger',
                replace: 'text-light',
            };

            function updateArrivalBoxNotes() {
                if (!arrivalBoxNotes || !arrivalBoxNotesInput || !arrivalBoxNotesText) return;

                const notes = String(arrivalBoxNotesInput.value || '').trim();
                arrivalBoxNotes.value = notes;
                arrivalBoxNotesText.textContent = notes ? ' · ' + notes : '';
                arrivalBoxNotesText.title = notes;
            }

            function updateArrivalBoxStatus(status) {
                if (!arrivalBoxStatus || !arrivalBoxStatusText) return;

                arrivalBoxStatus.value = status || '';
                arrivalBoxStatusText.textContent = arrivalBoxLabels[status] || '—';
                arrivalBoxStatusText.classList.remove('text-secondary', 'text-success', 'text-info', 'text-warning', 'text-danger', 'text-light');
                arrivalBoxStatusText.classList.add(arrivalBoxTextClasses[status] || 'text-secondary');

                document.querySelectorAll('[data-box-status]').forEach(button => {
                    button.classList.toggle('active', button.dataset.boxStatus === status);
                });
            }

            updateArrivalBoxStatus(arrivalBoxStatus?.value || '');
            updateArrivalBoxNotes();

            arrivalBoxNotesInput?.addEventListener('input', updateArrivalBoxNotes);

            document.querySelectorAll('[data-box-status]').forEach(button => {
                button.addEventListener('click', () => {
                    updateArrivalBoxStatus(button.dataset.boxStatus || '');
                });
            });

            // --- Create pending Unit (part_number only; manager assigns manual later)
            const btnCreateUnit = document.getElementById('btnCreateUnit');
            btnCreateUnit?.addEventListener('click', async () => {
                const pn = (document.getElementById('unitPn').value || '').trim();

                if (!pn) { window.showNotification('Part Number is required'); return; }

                try {
                    if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

                    const res = await fetch("{{ route('mobile.draft.units.pending.store') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ part_number: pn })
                    });

                    const data = await res.json();
                    if (!res.ok) {
                        const msg = data?.errors?.part_number?.[0] || data?.error || data?.message || 'Failed to create unit';
                        throw new Error(msg);
                    }

                    // add new option to Unit select & select it (PN + manual для различения одинаковых PN в разных manuals)
                    const label = `${data.part_number} (Manual pending)`;
                    const option = new Option(label, data.id, true, true);
                    option.setAttribute('data-name', data.name || '');

                    if (hasSelect2) {
                        $('#unit_id').append(option).trigger('change');
                    } else {
                        unitSelect.add(option);
                        unitSelect.value = data.id;
                    }

                    // close modal
                    bootstrap.Modal.getInstance(document.getElementById('addUnitModal')).hide();

                    // clear modal inputs
                    document.getElementById('unitPn').value = '';

                } catch (e) {
                    window.notifyError('Error: ' + e.message);
                } finally {
                    if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                }
            });

        });
    </script>
@endsection
