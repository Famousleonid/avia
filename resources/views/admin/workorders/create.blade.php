@extends('admin.master')

@section('style')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"/>

    <style>

        /* ----------------------------------- Select 2 Dark Theme -------------------------------------*/

        html[data-bs-theme="dark"] .select2-selection--single {
            background-color: var(--avia-input) !important;
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

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: var(--avia-surface-raised) !important;
            color: #999999;
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
            background-color: var(--avia-input) !important;
        }

        html[data-bs-theme="light"] .select2-container .select2-dropdown {
            max-height: 40vh !important;
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

        /* -------------------------------------------------------------------------------------------*/

        .checkbox-wo {
            font-size: 1rem;
        }

        .checkbox-wo input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 1px;
        }

        .is-invalid-shadow {
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.5) !important; /* Bootstrap danger */
        }

        /* ----------------------------------- Select2 White Text After Selection ----------------------------------- */

        /* Светлая тема */
        html[data-bs-theme="light"] .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            color: #000 !important; /* чёрный текст в светлой теме */
        }

        /* Тёмная тема */
        html[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            color: #fff !important; /* белый текст в тёмной теме */
        }

        /* Чтобы не перекрашивался placeholder */
        .select2-container--bootstrap-5 .select2-selection__placeholder {
            color: #6c757d !important;
        }

        .draft-match-table-wrap {
            max-height: 48vh;
            overflow: auto;
            border: 1px solid var(--avia-border, var(--bs-border-color));
            border-radius: 0.5rem;
        }

        .draft-match-table {
            margin-bottom: 0;
            min-width: 1080px;
        }

        .draft-match-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--avia-surface-raised, var(--bs-body-bg));
        }

        .draft-match-value {
            white-space: nowrap;
        }

        .draft-match-gallery {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            min-width: 245px;
        }

        .draft-match-gallery-link {
            display: inline-flex;
            flex: 0 0 auto;
        }

        .draft-match-thumb {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border: 1px solid var(--avia-border, var(--bs-border-color));
            border-radius: 0.35rem;
        }


    </style>

@endsection

@section('content')
    @php
        $canCreateCustomer = auth()->check() && auth()->user()->roleIs('Admin');
    @endphp

    <div class="container pl-3 pr-3 mt-5">
        <div class="card  p-2 shadow bg-gradient">
            <form id="createForm" class="createForm" role="form" method="post" action="{{route('workorders.store')}}" enctype="multipart/form-data" data-no-spinner>
                @csrf
                <input type="hidden" id="draftDuplicateAcknowledged" name="draft_duplicate_acknowledged" value="{{ old('draft_duplicate_acknowledged', 0) ? 1 : 0 }}">
                <input type="text" hidden name="user_id" value="{{auth()->user()->id}}">
                <div class="tab-content">
                    <div class="active tab-pane" id="create_firms">
                        <div class="col-md-12">
                            <div class="card-header row">
                                <p class="text-bold">Create workorder for user: ( &nbsp;&nbsp;
                                    <span class="text-info" style="font-size: 1.2rem">{{auth()->user()->selection_name}}</span>
                                    <span>&nbsp;&nbsp; ) email: {{auth()->user()->email}}</span>
                                </p>
                            </div>
                            <div class="card-body row" id="create_div_inputs">
                                <div class="col-lg-9 row">
                                    <div class="row ">
                                        <div class="form-group col-lg-4 mb-1">
                                            <label for="number_id">Workorder № <span style="color:red; font-size: x-small">(required)</span></label>
                                            <input type="text" name="number" id="number_id" value="{{ old('number') }}" class="form-control  @error('number') is-invalid @enderror" placeholder="Enter workorder number ">
                                            <div class="invalid-feedback" id="numberError">@error('number'){{ $message }}@enderror</div>
                                        </div>

                                        <div class="form-group col-lg-4 mb-1">
                                            <label for="unit_id">Unit
                                                <span style="color:red; font-size: x-small">(required)</span>
                                                <a id="new_unit_create" class="ms-3" data-bs-toggle="modal" data-bs-target="#addUnitModal">
                                                    <img class="mb-1" src="{{asset('img/plus.png')}}" width="20px" alt="" data-toggle="tooltip" data-placement="top" title="Add new unit">
                                                </a>
                                            </label>
                                            <select name="unit_id" id="unit_id" class="form-control">
                                                <option disabled {{ old('unit_id') ? '' : 'selected' }} value="">---</option>
                                                @foreach ($units as $unit)
                                                    <option
                                                        value="{{$unit->id}}"
                                                        data-name="{{ $unit->name }}"
                                                        {{ (string) old('unit_id') === (string) $unit->id ? 'selected' : '' }}>
                                                        {{ $unit->part_number }}@if($unit->manual) ({{ $unit->manual->number }})@endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-lg-4 mb-1">
                                            <label for="customer_id">Customer <span style="color:red; font-size: x-small">(required)</span>
                                                @if($canCreateCustomer)
                                                    <a id="new_customer_create" class="ms-3" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                                        <img class="mb-1" src="{{asset('img/plus.png')}}" width="20px" alt="" data-toggle="tooltip" data-placement="top" title="Add new customer">
                                                    </a>
                                                @endif
                                            </label>
                                            <select name="customer_id" id="customer_id" class="form-select">
                                                <option disabled {{ old('customer_id') ? '' : 'selected' }} value>---</option>
                                                @foreach ($customers as $customer)
                                                    <option value="{{$customer->id}}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>{{$customer->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row ">
                                        <div class="form-group col-lg-4 mb-1">
                                            <label for="instruction_id">Instruction <span style="color:red; font-size: x-small">(required)</span></label>
                                            <select name="instruction_id" id="instruction_id" class="form-select">
                                                <option disabled {{ old('instruction_id') ? '' : 'selected' }} value>---</option>
                                                @foreach ($instructions as $instruction)
                                                    <option value="{{$instruction->id}}" {{ (string) old('instruction_id') === (string) $instruction->id ? 'selected' : '' }}>{{$instruction->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="number_id">Serial number</label>
                                            <input type="text" name="serial_number" id="serial_number" value="{{ old('serial_number') }}" class="form-control @error('serial_number') is-invalid @enderror" placeholder="s/n">
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="unit_description">Description</label>
                                            <input type="text" name="description" id="description" value="{{ old('description') }}" class="form-control @error('description') is-invalid @enderror" placeholder="">
                                        </div>
                                    </div>

                                    <div class="row ">
                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="unit_amdt">Amdt</label>
                                            <input type="text" name="amdt" id="wo_amdt" maxlength="30" value="{{ old('amdt') }}" class="form-control @error('amdt') is-invalid @enderror" placeholder="">
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="unit_place">Place</label>
                                            <input type="text" name="place" id="wo_place" maxlength="30" value="{{ old('place') }}" class="form-control @error ('place') is-invalid @enderror" placeholder="">
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="unit_open_at">Open date</label>
                                            <input type="text" name="open_at" id="open_at" maxlength="11" value="{{ old('open_at') }}" class="form-control @error('open_at') is-invalid @enderror" placeholder=".... /.... /......" data-project-date autocomplete="off">
                                            @error('open_at')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row ">

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="customer_po">Customer PO</label>
                                            <input type="text" name="customer_po" id="customer_po" maxlength="30" value="{{ old('customer_po') }}" class="form-control @error ('customer_po') is-invalid @enderror" placeholder="">
                                        </div>

                                        <div class="form-group col-lg-4  mt-2">
                                            <label for="instruction_id">Technician</label>
                                            <select name="user_id" id="user_id" class="form-select">
                                                <option disabled {{ old('user_id', auth()->user()->id) ? '' : 'selected' }} value style="color: gray;"> -- select an option --</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}"
                                                            @if((string) old('user_id', $currentUser->id ?? auth()->user()->id) === (string) $user->id) selected @endif>
                                                        {{ $user->selection_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="customer_po">Modified</label>
                                            <input type="text" name="modified" id="modified" maxlength="30" value="{{ old('modified') }}" class="form-control @error ('modified') is-invalid @enderror" placeholder="">
                                        </div>

                                    </div>

                                </div>

                                <div class="col-lg-3 row">

                                    <label class="checkbox-wo mb-2"><input type="checkbox" name="external_damage" {{ old('external_damage') ? 'checked' : '' }}>___ External Damage</label><br>
                                    <label class="checkbox-wo mb-2"><input type="checkbox" name="received_disassembly" {{ old('received_disassembly') ? 'checked' : '' }}>___ Received Disassembly</label><br>
                                    <label class="checkbox-wo mb-2"><input type="checkbox" name="disassembly_upon_arrival" {{ old('disassembly_upon_arrival') ? 'checked' : '' }}>___ Disassembly Upon Arrival</label><br>
                                    <label class="checkbox-wo mb-2"><input type="checkbox" name="nameplate_missing" {{ old('nameplate_missing') ? 'checked' : '' }}>___ Name Plate Missing</label><br>
                                    <label class="checkbox-wo mb-2"><input type="checkbox" name="preliminary_test_false" {{ old('preliminary_test_false') ? 'checked' : '' }}>___ Preliminary Test</label><br>
                                    <label class="checkbox-wo mb-2"><input type="checkbox" name="extra_parts" {{ old('extra_parts') ? 'checked' : '' }}>___ Extra Parts</label><br>
                                </div>

                            </div>

                            <div class="form-group container-fluid ">
                                <div class="card-body row ">
                                    <div class="col col-lg-1  mb-1">
                                        <button id="ntSaveFormsSubmit" type="submit" class="btn btn-outline-primary btn-block ntSaveFormsSubmit"> Save</button>
                                    </div>
                                    <div class="col col-lg-1 offset-6 mb-1 ">
                                        <a href="{{ route('workorders.index') }}" class="btn btn-outline-secondary btn-block"> Cancel </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно add Unit -->
    <div class="modal fade" id="draftMatchModal" tabindex="-1" aria-labelledby="draftMatchModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="draftMatchModalLabel">Possible Shipping Draft found</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        A Draft with the same P/N or S/N may already contain Shipping inspection photos.
                        Open the Draft and continue it, or explicitly continue creating a separate Workorder.
                    </div>
                    <div class="draft-match-table-wrap">
                        <table class="table table-sm table-hover align-middle draft-match-table">
                            <thead>
                            <tr>
                                <th>Draft</th>
                                <th>Match</th>
                                <th>P/N</th>
                                <th>S/N</th>
                                <th>Description</th>
                                <th>Customer</th>
                                <th>Open date</th>
                                <th>Photos</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody id="draftMatchRows"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Review entered data</button>
                    <button type="button" class="btn btn-warning" id="continueNewWorkorderBtn">Continue creating new WO</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUnitLabel">Add Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cmmSelect" class="form-label">CMM</label>
                        <select class="form-select" id="cmmSelect" name="manual_id">
                            <option value="">{{ __('Select CMM') }}</option>
                            @foreach($manuals as $manual)
                                <option value="{{ $manual->id }}" data-title="{{ $manual->title }}">
                                    {{ $manual->number }}
                                    {{ $manual->title }}
                                    <span class="text-secondary">({{$manual->lib }})</span>

                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="pnInputs">
                        <div class="input-group mb-2 pn-field">
                            <input type="text" class="form-control" placeholder="Enter PN" style="width: 200px;" name="part_number" id="partNumberInput">
                        </div>
                    </div>
                    <div id="pnInputs">
                        <div class="input-group mb-2 pn-field">
                            <input type="text" class="form-control" placeholder="Name" style="width: 200px;"
                                   name="name" id="unitNameInput">
                        </div>
                    </div>
                    <div id="pnInputs">
                        <div class="input-group mb-2 pn-field">
                            <input type="text" class="form-control" placeholder="Description" style="width: 200px;"
                                   name="description" id="unitDescriptionInput">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="createUnitBtn" class="btn btn-outline-primary">Add Unit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно add Customer -->
    @if($canCreateCustomer)
        <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerLabel">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="customerNameInput" class="form-label">Customer name</label>
                        <input type="text" class="form-control" placeholder="Enter customer name" name="customer_name" id="customerNameInput">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="createCustomerBtn" class="btn btn-outline-primary">Add Customer</button>
                </div>
            </div>
        </div>
    </div>

    @endif

@endsection()

@section('scripts')

    <script>
        window.addEventListener('load', function () {

            const form = document.getElementById("createForm");
            const saveBtn = document.getElementById("ntSaveFormsSubmit");
            const unitSelect = document.getElementById('unit_id');
            const descriptionInput = document.getElementById('description');
            const instructionSelect = document.getElementById('instruction_id');
            const numberInput = document.getElementById('number_id');
            const serialInput = document.getElementById('serial_number');
            const draftDuplicateAcknowledged = document.getElementById('draftDuplicateAcknowledged');
            const draftMatchModalElement = document.getElementById('draftMatchModal');
            const draftMatchRows = document.getElementById('draftMatchRows');
            const continueNewWorkorderBtn = document.getElementById('continueNewWorkorderBtn');
            const cmmSelect = document.getElementById('cmmSelect');
            const unitNameInput = document.getElementById('unitNameInput');

            const DRAFT_INSTRUCTION_ID = {{ $draftInstructionId ?? 0 }};
            const DRAFT_MATCHES_URL = @json(route('workorders.draft-matches'));
            const INITIAL_DRAFT_MATCHES = @json(session('draft_matches', []));
            let draftCheckPassed = false;
            let draftCheckInProgress = false;

            function isDraftSelected() {
                return String(instructionSelect.value) === String(DRAFT_INSTRUCTION_ID);
            }
            function toggleNumberField() {
                if (!numberInput) return;
                if (isDraftSelected()) {
                    numberInput.value = '';
                    numberInput.setAttribute('readonly', 'readonly');
                    numberInput.setAttribute('placeholder', 'Auto number for Draft');
                    numberInput.classList.remove('is-invalid-shadow', 'is-invalid');
                    const numberError = document.getElementById('numberError');
                    if (numberError) numberError.textContent = '';
                } else {
                    numberInput.removeAttribute('readonly');
                    numberInput.setAttribute('placeholder', 'Enter workorder number');
                }
            }

            if (instructionSelect) {
                instructionSelect.addEventListener('change', function () {
                    toggleNumberField();
                    resetDraftDuplicateDecision();
                });
                toggleNumberField();
            }


            unitSelect.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const unitName = selectedOption.getAttribute('data-name');
                descriptionInput.value = unitName || '';
                resetDraftDuplicateDecision();
            });

            serialInput?.addEventListener('input', resetDraftDuplicateDecision);

            function resetDraftDuplicateDecision() {
                draftCheckPassed = false;
                if (draftDuplicateAcknowledged) {
                    draftDuplicateAcknowledged.value = '0';
                }
            }

            function syncUnitNameFromSelectedCmm() {
                if (!cmmSelect || !unitNameInput) return;
                const selectedOption = cmmSelect.options[cmmSelect.selectedIndex];
                const manualTitle = selectedOption?.getAttribute('data-title') || '';
                if (unitNameInput.dataset.userEdited === '1') return;
                unitNameInput.value = manualTitle;
            }

            function check1() {
                if (isDraftSelected()) return true; // ✅ draft — номер не обязателен

                const el = $('#number_id');
                const numberError = document.getElementById('numberError');
                const val = (el.val() || '').trim();
                numberError.textContent = '';
                el.removeClass('is-invalid');

                if (!val) {
                    el.addClass('is-invalid-shadow');
                    numberError.textContent = 'Number is required.';
                    setTimeout(() => el.removeClass('is-invalid-shadow'), 3000);
                    return false;
                }
                if (!/^\d+$/.test(val)) {
                    el.addClass('is-invalid is-invalid-shadow');
                    numberError.textContent = 'Workorder number must contain digits only.';
                    setTimeout(() => el.removeClass('is-invalid-shadow'), 3000);
                    return false;
                }
                if (Number(val) < 100000) {
                    el.addClass('is-invalid is-invalid-shadow');
                    numberError.textContent = 'Workorder number must be 100000 or greater.';
                    setTimeout(() => el.removeClass('is-invalid-shadow'), 3000);
                    return false;
                }
                if (Number(val) > 999999) {
                    el.addClass('is-invalid is-invalid-shadow');
                    numberError.textContent = 'Workorder number must be 999999 or less.';
                    setTimeout(() => el.removeClass('is-invalid-shadow'), 3000);
                    return false;
                }
                return true;
            }

            function check2() {
                const el = $('#unit_id');
                const selection = el.next('.select2-container').find('.select2-selection');
                if (!el.val()) {
                    selection.addClass('is-invalid-shadow');
                    setTimeout(() => selection.removeClass('is-invalid-shadow'), 3000);
                    return false;
                }
                return true;
            }

            function check3() {
                const el = $('#customer_id');
                const selection = el.next('.select2-container').find('.select2-selection');
                if (!el.val()) {
                    selection.addClass('is-invalid-shadow');
                    setTimeout(() => selection.removeClass('is-invalid-shadow'), 3000);
                    return false;
                }
                return true;
            }

            function check4() {
                const el = $('#instruction_id');
                if (!el.val()) {
                    el.addClass('is-invalid-shadow');
                    setTimeout(() => el.removeClass('is-invalid-shadow'), 3000);
                    return false;
                }
                return true;
            }

            function scrollToInvalid() {
                const firstInvalid = document.querySelector('.is-invalid-shadow');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({behavior: 'smooth', block: 'center'});
                }
            }

            function validateCreateForm() {
                const valid1 = check1();
                const valid2 = check2();
                const valid3 = check3();
                const valid4 = check4();

                if (!(valid1 && valid2 && valid3 && valid4)) {
                    scrollToInvalid();
                    return false;
                }

                return true;
            }

            function appendDraftMatchCell(row, value, className = '') {
                const cell = row.insertCell();
                cell.textContent = value || '—';
                if (className) cell.className = className;
                return cell;
            }

            function appendDraftPhotosCell(row, match) {
                const cell = row.insertCell();
                const photos = Array.isArray(match.photos) ? match.photos : [];

                if (photos.length === 0) {
                    cell.textContent = 'No photos';
                    cell.className = 'text-muted text-nowrap';
                    return;
                }

                const gallery = document.createElement('div');
                gallery.className = 'draft-match-gallery';
                const galleryName = `draft-match-${match.id}`;
                let firstLink = null;

                photos.forEach((photo, index) => {
                    const link = document.createElement('a');
                    link.href = photo.big_url;
                    link.target = '_blank';
                    link.rel = 'noopener';
                    link.dataset.fancybox = galleryName;
                    link.dataset.caption = `Draft ${match.number} — ${photo.label || 'Photo'}`;
                    link.className = 'draft-match-gallery-link';
                    link.setAttribute('aria-label', `Open photo ${index + 1} of Draft ${match.number}`);

                    if (index < 3) {
                        const image = document.createElement('img');
                        image.src = photo.thumb_url;
                        image.alt = `Draft ${match.number} photo ${index + 1}`;
                        image.className = 'draft-match-thumb';
                        image.loading = 'lazy';
                        link.appendChild(image);
                    } else {
                        link.classList.add('d-none');
                    }

                    if (!firstLink) firstLink = link;
                    gallery.appendChild(link);
                });

                const viewAllButton = document.createElement('button');
                viewAllButton.type = 'button';
                viewAllButton.className = 'btn btn-sm btn-outline-info text-nowrap';
                viewAllButton.textContent = `View all (${photos.length})`;
                viewAllButton.addEventListener('click', () => firstLink?.click());
                gallery.appendChild(viewAllButton);
                cell.appendChild(gallery);
            }

            function showDraftMatches(matches) {
                if (typeof window.safeHideSpinner === 'function') {
                    window.safeHideSpinner();
                } else if (typeof window.hideLoadingSpinner === 'function') {
                    window.hideLoadingSpinner();
                }

                draftMatchRows.replaceChildren();

                matches.forEach(match => {
                    const row = draftMatchRows.insertRow();
                    appendDraftMatchCell(row, `Draft ${match.number}`, 'draft-match-value');
                    appendDraftMatchCell(row, Array.isArray(match.matched_by) ? match.matched_by.join(' + ') : '');
                    appendDraftMatchCell(row, match.part_number, 'draft-match-value');
                    appendDraftMatchCell(row, match.serial_number, 'draft-match-value');
                    appendDraftMatchCell(row, match.description);
                    appendDraftMatchCell(row, match.customer);
                    appendDraftMatchCell(row, match.open_date, 'draft-match-value');
                    appendDraftPhotosCell(row, match);

                    const actionCell = row.insertCell();
                    const openLink = document.createElement('a');
                    openLink.className = 'btn btn-sm btn-primary text-nowrap';
                    openLink.href = match.edit_url;
                    openLink.textContent = 'Open Draft';
                    actionCell.appendChild(openLink);
                });

                if (window.jQuery?.fn?.fancybox) {
                    window.jQuery(draftMatchRows).find('[data-fancybox]').fancybox({
                        loop: false,
                        buttons: ['zoom', 'slideShow', 'fullScreen', 'close']
                    });
                }

                bootstrap.Modal.getOrCreateInstance(draftMatchModalElement).show();
            }

            continueNewWorkorderBtn?.addEventListener('click', function () {
                draftDuplicateAcknowledged.value = '1';
                draftCheckPassed = true;
                bootstrap.Modal.getOrCreateInstance(draftMatchModalElement).hide();
                form.requestSubmit();
            });

            form.addEventListener('submit', async function (event) {
                if (!validateCreateForm()) {
                    event.preventDefault();
                    return;
                }

                if (
                    isDraftSelected()
                    || draftCheckPassed
                    || draftDuplicateAcknowledged.value === '1'
                ) {
                    showLoadingSpinner();
                    return;
                }

                event.preventDefault();
                if (draftCheckInProgress) return;

                draftCheckInProgress = true;
                saveBtn.disabled = true;
                let submitAfterCheck = false;

                try {
                    const url = new URL(DRAFT_MATCHES_URL, window.location.origin);
                    url.searchParams.set('unit_id', unitSelect.value);
                    if (serialInput.value.trim() !== '') {
                        url.searchParams.set('serial_number', serialInput.value.trim());
                    }

                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`Draft lookup failed (${response.status})`);
                    }

                    const payload = await response.json();
                    const matches = Array.isArray(payload.matches) ? payload.matches : [];

                    if (matches.length > 0) {
                        showDraftMatches(matches);
                    } else {
                        draftCheckPassed = true;
                        submitAfterCheck = true;
                    }
                } catch (error) {
                    // The store action repeats the lookup, so submission remains safe if this pre-check fails.
                    draftCheckPassed = true;
                    submitAfterCheck = true;
                } finally {
                    draftCheckInProgress = false;
                    saveBtn.disabled = false;
                }

                if (submitAfterCheck) {
                    form.requestSubmit();
                }
            });

            if (Array.isArray(INITIAL_DRAFT_MATCHES) && INITIAL_DRAFT_MATCHES.length > 0) {
                showDraftMatches(INITIAL_DRAFT_MATCHES);
            }

            // --------------------------------- Select2 Initialization ---------------------------------
            $(document).ready(function () {
                $('#unit_id, #customer_id, #user_id').select2({
                    placeholder: '---',
                    theme: 'bootstrap-5',
                    allowClear: true
                });

                $('#cmmSelect').select2({
                    placeholder: '---',
                    theme: 'bootstrap-5',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#addUnitModal'),
                    dropdownAutoWidth: true
                });

                $('#cmmSelect').on('change', syncUnitNameFromSelectedCmm);
                $('#cmmSelect').on('change', function () {
                    unitNameInput.dataset.userEdited = '';
                    syncUnitNameFromSelectedCmm();
                });

                applyTheme();
            });

            function applyTheme() {
                const isDark = document.documentElement.getAttribute('data-bs-theme');
                const selectContainer = $('.select2-container');
                const dropdown = $('.select2-container .select2-dropdown');

                if (isDark === 'dark') {
                    selectContainer.addClass('select2-dark').removeClass('select2-light');
                    dropdown.addClass('select2-dark').removeClass('select2-light');
                } else {
                    selectContainer.addClass('select2-light').removeClass('select2-dark');
                    dropdown.addClass('select2-light').removeClass('select2-dark');
                }
            }
            // ---------------------   Save Unit ------------------------------------------------------------------
            document.getElementById('createUnitBtn').addEventListener('click', function () {
                const manualId = document.getElementById('cmmSelect').value;
                const pnInput = document.getElementById('partNumberInput');
                const nameInput = document.getElementById('unitNameInput');
                const descriptionInput = document.getElementById('unitDescriptionInput');
                const partNumber = pnInput.value.trim();
                const unitName = nameInput.value.trim();
                const unitDescription = descriptionInput.value.trim();

                if (!manualId || !partNumber) {
                    showNotification("Please select a CMM and enter a Part Number.", 'warning');
                    return;
                }

                showLoadingSpinner();

                const requestBody = {
                    manual_id: manualId,
                    part_number: partNumber
                };

                // Добавляем name и description если они заполнены
                if (unitName) {
                    requestBody.name = unitName;
                }
                if (unitDescription) {
                    requestBody.description = unitDescription;
                }

                fetch("{{ route('units.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(requestBody)
                })
                    .then(async res => {
                        hideLoadingSpinner();
                        const data = await res.json();
                        if (!res.ok) {
                            const msg = data?.errors?.part_number?.[0] || data?.error || data?.message || 'Failed to create unit';
                            throw new Error(msg);
                        }
                        return data;
                    })
                    .then(data => {
                        // Добавить новую опцию в селект (PN + manual для различения одинаковых PN в разных manuals)
                        const label = data.manual_number
                            ? `${data.part_number} (${data.manual_number})`
                            : data.part_number;
                        const option = new Option(label, data.id, true, true);
                        option.setAttribute('data-name', data.name || '');
                        $('#unit_id').append(option).trigger('change');

                        // Подставить name в description workorder, если name заполнено
                        const workorderDescriptionInput = document.getElementById('description');
                        if (data.name) {
                            workorderDescriptionInput.value = data.name;
                        }

                        // Закрыть модалку
                        bootstrap.Modal.getInstance(document.getElementById('addUnitModal')).hide();

                        // Очистить поля
                        pnInput.value = '';
                        nameInput.value = '';
                        delete nameInput.dataset.userEdited;
                        descriptionInput.value = '';
                        document.getElementById('cmmSelect').value = '';
                        $('#cmmSelect').trigger('change');
                    })
                    .catch(error => {
                        hideLoadingSpinner();
                        showNotification("Error: " + error.message, 'error');
                    });
            });

            unitNameInput?.addEventListener('input', function () {
                this.dataset.userEdited = '1';
            });

            // ---------------------   Save Customer --------------------------------------------------------------
            document.getElementById('createCustomerBtn')?.addEventListener('click', function () {
                const nameInput = document.getElementById('customerNameInput');
                const name = nameInput.value.trim();

                if (!name) {
                    showNotification("Please enter a Customer name.", 'warning');
                    return;
                }

                showLoadingSpinner();

                fetch("{{ route('customers.store') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        name: name
                    })
                })
                    .then(res => {
                        hideLoadingSpinner();
                        if (!res.ok) throw new Error("Failed to create customer");
                        return res.json();
                    })
                    .then(data => {
                        const option = new Option(data.name, data.id, true, true);
                        $('#customer_id').append(option).trigger('change');

                        bootstrap.Modal.getInstance(document.getElementById('addCustomerModal')).hide();
                        nameInput.value = '';
                    })
                    .catch(error => {
                        hideLoadingSpinner();
                        showNotification("Error: " + error.message, 'error');
                    });
            });


        });
    </script>
@endsection

