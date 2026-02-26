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

                    {{-- Draft number preview (not sent) --}}
                    <div class="mb-2">
                        <label class="form-label small text-white-50 mb-1">Draft Number</label>
                        <input type="text" class="form-control bg-black text-info border-secondary" value="{{ $draftNumber }}" readonly>
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
                                    {{ $u->part_number }}
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

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small text-white-50 mb-1">Serial number</label>
                            <input name="serial_number"
                                   class="form-control bg-black text-light border-secondary @error('serial_number') is-invalid @enderror"
                                   value="{{ old('serial_number') }}" placeholder="s/n">
                            @error('serial_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-white-50 mb-1">Open date</label>
                            <input type="date" name="open_at"
                                   class="form-control bg-black text-light border-secondary @error('open_at') is-invalid @enderror"
                                   value="{{ old('open_at') }}">
                            @error('open_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-2">
                        <label class="form-label small text-white-50 mb-1">Description</label>
                        <input name="description" id="description"
                               class="form-control bg-black text-light border-secondary @error('description') is-invalid @enderror"
                               value="{{ old('description') }}">
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mt-2">
                        <label class="form-label small text-white-50 mb-1">Customer PO</label>
                        <input name="customer_po"
                               class="form-control bg-black text-light border-secondary @error('customer_po') is-invalid @enderror"
                               value="{{ old('customer_po') }}">
                        @error('customer_po') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                                <input name="storage_2"
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
                        <div class="row g-2 small">
                            <div class="col-6">
                                <label class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" name="external_damage" value="1" @checked(old('external_damage'))>
                                    <span>External Damage</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" name="received_disassembly" value="1" @checked(old('received_disassembly'))>
                                    <span>Received Disassembly</span>
                                </label>
                            </div>
{{--                            <div class="col-6">--}}
{{--                                <label class="form-check d-flex align-items-center gap-2">--}}
{{--                                    <input class="form-check-input" type="checkbox" name="disassembly_upon_arrival" value="1" @checked(old('disassembly_upon_arrival'))>--}}
{{--                                    <span>Disassembly Upon Arrival</span>--}}
{{--                                </label>--}}
{{--                            </div>--}}
                            <div class="col-6">
                                <label class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" name="nameplate_missing" value="1" @checked(old('nameplate_missing'))>
                                    <span>Name Plate Missing</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input" type="checkbox" name="extra_parts" value="1" @checked(old('extra_parts'))>
                                    <span>Extra Parts</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-outline-info flex-fill">Save Draft</button>
                        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary flex-fill">Cancel</a>
                    </div>
                </form>



            </div>
        </div>
    </div>

    {{-- MODAL: Add Unit (manual_id required) --}}
    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header border-secondary">
                    <h6 class="modal-title">Add Unit</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small text-white-50 mb-1">Manual (CMM) <span class="text-danger">*</span></label>
                        <select id="manual_id" class="form-select bg-black text-light border-secondary" required>
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
            if (hasSelect2 && document.getElementById('manual_id')) {
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

            // --- Auto fill description from selected unit (only if empty)
            const unitSelect = document.getElementById('unit_id');
            const desc = document.getElementById('description');
            if (unitSelect && desc) {
                unitSelect.addEventListener('change', function () {
                    const opt = this.options[this.selectedIndex];
                    const name = opt?.getAttribute('data-name') || '';
                    if (!desc.value) desc.value = name;
                });
            }

            // --- Create Unit (manual_id + part_number required)
            const btnCreateUnit = document.getElementById('btnCreateUnit');
            btnCreateUnit?.addEventListener('click', async () => {
                const manualId = (document.getElementById('manual_id').value || '').trim();
                const pn = (document.getElementById('unitPn').value || '').trim();

                if (!manualId) { alert('Manual is required'); return; }
                if (!pn) { alert('Part Number is required'); return; }

                try {
                    if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

                    const res = await fetch("{{ route('units.store') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            manual_id: manualId,
                            part_number: pn
                        })
                    });

                    if (!res.ok) {
                        const txt = await res.text();
                        throw new Error(txt || 'Failed to create unit');
                    }

                    const data = await res.json();

                    // add new option to Unit select & select it
                    const option = new Option(data.part_number, data.id, true, true);
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
                    if (hasSelect2) $('#manual_id').val('').trigger('change');
                    else document.getElementById('manual_id').value = '';

                } catch (e) {
                    alert('Error: ' + e.message);
                } finally {
                    if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
                }
            });

        });
    </script>
@endsection
