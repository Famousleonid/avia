@extends('admin.master')

@section('style')
    <style>

        /* ----------------------------------- Select 2 Dark Theme -------------------------------------*/

        html[data-bs-theme="dark"] .select2-selection--single {
            background-color: #121212 !important;
            color: gray !important;
            height: 38px !important;
            border: 1px solid #495057 !important;
            align-items: center !important;
            border-radius: 8px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
            color: white;
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


    </style>
@endsection

@section('content')

    <div class="container pl-3 pr-3 mt-5">
        <div class="card  p-2 shadow bg-gradient">

            <form id="createForm" class="createForm" role="form" method="POST" action="{{route('workorders.update',['workorder' => $current_wo->id])}}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="tab-content">

                    <div class="active tab-pane" id="create_firms">
                        <div class="col-md-12">

                            <div class="card-header row">
                                <p class="text-bold">Edit workorder number: ( &nbsp;&nbsp;
                                    <span class="text-info" style="font-size: 1.2rem">{{$current_wo->number }}</span>&nbsp;&nbsp;&nbsp;)
                                </p>
                            </div>

                            <div class="card-body row" id="create_div_inputs">

                                <div class="col-lg-9 row">

                                    <div class="row ">

                                        <div class="form-group col-lg-4 mb-1">
                                            <label class="mb-1" for="number_id">Workorder №</label>

                                            <div class="position-relative">
                                                <input
                                                    type="text"
                                                    id="number"
                                                    name="number"
                                                    value="{{ old('number', $current_wo->number) }}"
                                                    class="form-control pe-5"
                                                    readonly
                                                    style="padding-right: 44px !important;"
                                                >

                                                {{-- yellow lock --}}
                                                <span id="numberLockIcon"
                                                      class="position-absolute top-50 end-0 translate-middle-y me-2 text-warning d-none"
                                                      title="Number is locked">
                                                      <i class="bi bi-lock-fill"></i>
                                                </span>

                                                <div class="invalid-feedback" id="numberError"></div>
                                            </div>

                                            {{-- Draft → Released подсказка --}}
                                            <div id="draftReleasedHint" class="form-text text-warning d-none">
                                                Draft → Released: enter a NEW unique number
                                            </div>
                                        </div>

                                        <div class="form-group col-lg-4 mb-1">
                                            <label for="unit_id">Unit
                                                <a id="new_unit_create" class="ms-3" data-bs-toggle="modal" data-bs-target="#addUnitModal">
                                                    <img class="mb-1" src="{{asset('img/plus.png')}}" width="20px" alt="" data-toggle="tooltip" data-placement="top" title="Add new unit">
                                                </a>
                                            </label>
                                            <select name="unit_id" id="unit_id" class="form-control">
                                                <option selected value="{{ old('unit_id', $current_wo->unit_id) }}" data-name="{{ old('unit_name', $current_wo->unit->name) }}" data-part-number="{{ old('part_number', $current_wo->unit->part_number) }}" data-manual-id="{{ $current_wo->unit?->manual_id ?? '' }}" data-verified="{{ $current_wo->unit?->verified ? 1 : 0 }}">{{ old('part_number', $current_wo->unit->part_number) }}@if($current_wo->unit->manual) ({{ $current_wo->unit->manual->number }})@else (Manual pending)@endif</option>
                                                @foreach ($units as $unit)
                                                    <option value="{{$unit->id}}" data-name="{{ $unit->name }}" data-part-number="{{ $unit->part_number }}" data-manual-id="{{ $unit->manual_id ?? '' }}" data-verified="{{ $unit->verified ? 1 : 0 }}">{{ $unit->part_number }}@if($unit->manual) ({{ $unit->manual->number }})@else (Manual pending)@endif</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-lg-4 mb-1">
                                            <label class="mb-1" for="customer_id">Customer</label>
                                            <select name="customer_id" id="customer_id" class="form-select">
                                                <option selected value="{{ old('customer_id', $current_wo->customer_id) }}">{{ old('name', $current_wo->customer->name) }}</option>
                                                @foreach ($customers as $customer)
                                                    <option value="{{$customer->id}}">{{$customer->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-lg-4 mb-1">
                                            <label class="mb-1" for="instruction_id">Instruction </label>
                                            <select name="instruction_id" id="instruction_id" class="form-select">
                                                <option selected value="{{ old('instruction_id', $current_wo->instruction_id) }}">{{ old('instruction_id', $current_wo->instruction->name) }}</option>
                                                @foreach ($instructions as $instruction)
                                                    <option value="{{$instruction->id}}">{{$instruction->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="number_id">Serial number</label>
                                            <input type="text" name="serial_number" id="serial_number" value="{{old('serial_number', $current_wo->serial_number)}}" class="form-control">
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="unit_description">Description</label>
                                            <input type="text" name="description" id="description" value="{{old('description', $current_wo->description)}}" class="form-control">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="unit_amdt">Amdt</label>
                                            <input type="text" name="amdt" id="wo_amdt" maxlength="30" value="{{ old('amdt', $current_wo->amdt) }}" class="form-control @error('amdt') is-invalid @enderror">
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="unit_place">Place</label>
                                            <input type="text" name="place" id="wo_place" maxlength="30" value="{{old('place', $current_wo->place)}}" class="form-control @error ('place') is-invalid @enderror">
                                        </div>

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="unit_open_at">Open date</label>
                                            <input type="text" name="open_at" id="open_at" maxlength="11" value="{{ old('open_at', $open_at) }}" class="form-control @error('open_at') is-invalid @enderror" placeholder="10.aug.2026" data-project-date autocomplete="off">
                                            @error('open_at')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                    </div>

                                    <div class="row ">

                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="customer_po">Customer PO</label>
                                            <input type="text" name="customer_po" id="customer_po" maxlength="30" value="{{ old('customer_po', $current_wo->customer_po) }}" class="form-control @error ('place') is-invalid @enderror" placeholder="">
                                        </div>

                                        <div class="form-group col-lg-4  mt-2">
                                            <label for="instruction_id">Technik</label>
                                            <select name="user_id" id="user_id" class="form-select">
                                                <option disabled {{ old('user_id', $current_wo->user_id) ? '' : 'selected' }} value=""> -- select an option --</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}"
                                                            @if((string) old('user_id', $current_wo->user_id) === (string) $user->id) selected @endif>
                                                        {{ $user->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-lg-4 mt-2">
                                            <label for="customer_po">Modified</label>
                                            <input type="text" name="modified" id="modified" maxlength="30" value="{{ old('modified', $current_wo->modified) }}" class="form-control @error ('place') is-invalid @enderror" placeholder="">
                                        </div>

                                    </div>

                                </div>

                                <div class="col-lg-3 row">

                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="part_missing" {{ $current_wo->part_missing ? 'checked' : '' }} disabled>___ Parts Missing</label><br>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="external_damage" {{ $current_wo->external_damage ? 'checked' : '' }}>___ External damage</label><br>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="received_disassembly" {{ $current_wo->received_disassembly ? 'checked' : '' }}>___ Received disassembly</label><br>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="disassembly_upon_arrival" {{ $current_wo->disassembly_upon_arrival ? 'checked' : '' }}>___ Disassembly upon arrival</label><br>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="nameplate_missing" {{ $current_wo->nameplate_missing ? 'checked' : '' }}>___ Nameplate missing</label><br>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="preliminary_test_false" {{ $current_wo->preliminary_test_false ? 'checked' : '' }}>___ Preliminary test false</label><br>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-wo mb-2"><input type="checkbox" name="extra_parts" {{ $current_wo->extra_parts ? 'checked' : '' }}>___ Extra parts</label><br>
                                    </div>

                                </div>

                            </div>


                            <div class="form-group container-fluid ">
                                <div class="card-body row ">
                                    <div class="col col-lg-1  mb-1">
                                        <button id="ntSaveFormsSubmit" type="submit" class="btn btn-outline-primary btn-block ntSaveFormsSubmit"> Update</button>
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close
                    </button>
                    <button type="button" id="createUnitBtn" class="btn btn-outline-primary"> Add Unit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="assignManualModal" tabindex="-1" aria-labelledby="assignManualLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignManualLabel">Assign Manual to Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-warning small mb-3">
                        Confirm or change the CMM before releasing the Workorder.
                    </p>
                    <div class="mb-3">
                        <label for="assignManualPartNumber" class="form-label">Part Number</label>
                        <input type="text" class="form-control" id="assignManualPartNumber" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="assignManualCustomer" class="form-label">Customer</label>
                        <input type="text" class="form-control" id="assignManualCustomer" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="assignManualSelect" class="form-label">CMM</label>
                        <select class="form-select" id="assignManualSelect">
                            <option value="">{{ __('Select CMM') }}</option>
                            @foreach($manuals as $manual)
                                <option value="{{ $manual->id }}">
                                    {{ $manual->number }} {{ $manual->title }} @if($manual->lib) ({{ $manual->lib }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="assignManualError"></div>
                        <div class="form-text text-info d-none" id="assignManualHint"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="assignManualBtn" class="btn btn-outline-primary">Save and Continue</button>
                </div>
            </div>
        </div>
    </div>

@endsection()

@section('scripts')


    <script>

        window.addEventListener('load', function () {

            // --------------------------------- Select 2 --------------------------------------------------------

            const unitSelect = document.getElementById('unit_id');
            const workorderDescriptionInput = document.getElementById('description');
            const cmmSelect = document.getElementById('cmmSelect');
            const unitNameInput = document.getElementById('unitNameInput');

            unitSelect.onchange = function () {
                const selectedOption = this.options[this.selectedIndex];
                const unitName = selectedOption.getAttribute('data-name');
                workorderDescriptionInput.value = unitName || '';
            };

            function syncUnitNameFromSelectedCmm() {
                if (!cmmSelect || !unitNameInput) return;
                const selectedOption = cmmSelect.options[cmmSelect.selectedIndex];
                const manualTitle = selectedOption?.getAttribute('data-title') || '';
                if (unitNameInput.dataset.userEdited === '1') return;
                unitNameInput.value = manualTitle;
            }

            $(document).ready(function () {
                $('#unit_id, #user_id').select2({
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

                $('#cmmSelect').on('change', function () {
                    unitNameInput.dataset.userEdited = '';
                    syncUnitNameFromSelectedCmm();
                });

                $('#assignManualSelect').select2({
                    placeholder: '{{ __('Select CMM') }}',
                    theme: 'bootstrap-5',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#assignManualModal'),
                    dropdownAutoWidth: true
                });

                // Инициализация description при загрузке страницы, если unit уже выбран
                const initialSelectedOption = $('#unit_id option:selected');
                const initialUnitName = initialSelectedOption.attr('data-name');
                if (initialUnitName && !workorderDescriptionInput.value) {
                    workorderDescriptionInput.value = initialUnitName;
                }

                // Обработчик изменения для Select2
                $('#unit_id').on('change', function() {
                    const selectedOption = $(this).find('option:selected');
                    const unitName = selectedOption.attr('data-name');
                    workorderDescriptionInput.value = unitName || '';
                });
            });
            $(function () {
                applyTheme();
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

            // -----------------------------------------------------------------------------------------------------

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
                    window.showNotification("Please select a CMM and enter a Part Number.");
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
                        option.setAttribute('data-manual-id', data.manual_id || manualId);
                        option.setAttribute('data-verified', '0');
                        $('#unit_id').append(option).trigger('change');

                        // Подставить name в description workorder, если name заполнено
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
                        window.notifyError("Error: " + error.message);
                    });
            });

            unitNameInput?.addEventListener('input', function () {
                this.dataset.userEdited = '1';
            });

            const instructionSelect = document.getElementById('instruction_id');
            const numberInput = document.getElementById('number');
            const submitBtn = document.getElementById('ntSaveFormsSubmit');
            const customerSelect = document.getElementById('customer_id');

            const DRAFT_ID = {{ (int)$draftInstructionId }};
            const WAS_DRAFT = {{ $wasDraft ? 'true' : 'false' }};
            const ORIGINAL_NUMBER = numberInput.value;
            const CURRENT_WO_ID = {{ (int)$current_wo->id }};
            const INITIAL_UNIT_ID = {{ (int)($current_wo->unit_id ?? 0) }};
            const HAS_TDRS = {{ ($hasTdrs ?? false) ? 'true' : 'false' }};
            const ASSIGN_MANUAL_URL = "{{ route('units.assignManual', ['unit' => '__unit__']) }}";
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const assignManualModalEl = document.getElementById('assignManualModal');
            const assignManualModal = assignManualModalEl ? bootstrap.Modal.getOrCreateInstance(assignManualModalEl) : null;
            const assignManualSelect = document.getElementById('assignManualSelect');
            const assignManualBtn = document.getElementById('assignManualBtn');
            const assignManualError = document.getElementById('assignManualError');
            const assignManualHint = document.getElementById('assignManualHint');
            const assignManualPartNumber = document.getElementById('assignManualPartNumber');
            const assignManualCustomer = document.getElementById('assignManualCustomer');

            function shouldAllowEditNumber() {
                const selectedInstructionId = parseInt(instructionSelect.value || '0', 10);
                // Разрешаем только если воркордер был Draft и сейчас выбран НЕ Draft
                return WAS_DRAFT && selectedInstructionId !== DRAFT_ID;
            }

            function selectedUnitOption() {
                return unitSelect.options[unitSelect.selectedIndex] || null;
            }

            function selectedUnitManualId() {
                const opt = selectedUnitOption();
                return String(opt?.getAttribute('data-manual-id') || '').trim();
            }

            function selectedUnitPartNumber() {
                const opt = selectedUnitOption();
                return String(opt?.getAttribute('data-part-number') || '').trim();
            }

            function findExistingUnitOptionForManual(manualId) {
                const currentUnitId = String(selectedUnitOption()?.value || '').trim();
                const partNumber = selectedUnitPartNumber();
                if (!manualId || !partNumber) return null;

                return Array.from(unitSelect.options).find((option) => {
                    return String(option.value || '').trim() !== currentUnitId
                        && String(option.getAttribute('data-manual-id') || '').trim() === String(manualId).trim()
                        && String(option.getAttribute('data-part-number') || '').trim() === partNumber;
                }) || null;
            }

            function updateAssignManualHint() {
                if (!assignManualHint) return;

                const manualId = String(assignManualSelect?.value || '').trim();
                const existingOption = findExistingUnitOptionForManual(manualId);

                if (existingOption) {
                    assignManualHint.textContent = 'This CMM already has this unit. The existing unit will be used for the Workorder.';
                    assignManualHint.classList.remove('d-none');
                    return;
                }

                assignManualHint.textContent = '';
                assignManualHint.classList.add('d-none');
            }

            function selectedCustomerText() {
                const opt = customerSelect?.options[customerSelect.selectedIndex] || null;
                return (opt?.textContent || '').trim();
            }

            async function assignManualToSelectedUnit() {
                const opt = selectedUnitOption();
                const unitId = opt?.value || '';
                const manualId = assignManualSelect?.value || '';
                const currentManualId = selectedUnitManualId();

                assignManualError.textContent = '';
                assignManualSelect.classList.remove('is-invalid');

                if (!unitId || !manualId) {
                    assignManualError.textContent = 'Select CMM.';
                    assignManualSelect.classList.add('is-invalid');
                    return false;
                }

                assignManualBtn.disabled = true;

                try {
                    if (currentManualId && currentManualId === String(manualId)) {
                        assignManualModal?.hide();
                        const form = document.getElementById('createForm');
                        form.dataset.readyToSubmit = '1';
                        form.requestSubmit();
                        return true;
                    }

                    const existingOption = findExistingUnitOptionForManual(manualId);
                    if (existingOption) {
                        unitSelect.value = existingOption.value;
                        $('#unit_id').val(existingOption.value).trigger('change');
                        assignManualModal?.hide();
                        const form = document.getElementById('createForm');
                        form.dataset.readyToSubmit = '1';
                        form.requestSubmit();
                        return true;
                    }

                    const res = await fetch(ASSIGN_MANUAL_URL.replace('__unit__', encodeURIComponent(unitId)), {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ manual_id: manualId }),
                    });

                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data?.success) {
                        const msg = data?.errors?.manual_id?.[0] || data?.message || 'Manual assignment failed.';
                        throw new Error(msg);
                    }

                    opt.setAttribute('data-manual-id', data.manual_id || manualId);
                    opt.setAttribute('data-verified', data.verified ? '1' : '0');
                    opt.textContent = data.manual_number
                        ? `${data.part_number} (${data.manual_number})`
                        : data.part_number;
                    $('#unit_id').trigger('change.select2');

                    assignManualModal?.hide();
                    const form = document.getElementById('createForm');
                    form.dataset.readyToSubmit = '1';
                    form.requestSubmit();
                    return true;
                } catch (err) {
                    assignManualError.textContent = err?.message || 'Manual assignment failed.';
                    assignManualSelect.classList.add('is-invalid');
                    return false;
                } finally {
                    assignManualBtn.disabled = false;
                }
            }

            assignManualBtn?.addEventListener('click', assignManualToSelectedUnit);
            assignManualSelect?.addEventListener('change', updateAssignManualHint);
            assignManualModalEl?.addEventListener('hidden.bs.modal', () => {
                if (assignManualSelect) {
                    assignManualSelect.classList.remove('is-invalid');
                    assignManualSelect.value = '';
                    $('#assignManualSelect').val('').trigger('change');
                }
                assignManualError.textContent = '';
                if (assignManualHint) {
                    assignManualHint.textContent = '';
                    assignManualHint.classList.add('d-none');
                }
                if (typeof window.safeHideSpinner === 'function') window.safeHideSpinner();
            });

            function applyNumberState() {
                const allow = shouldAllowEditNumber();

                numberInput.readOnly = !allow;
                if (numberLockIcon) {
                    numberLockIcon.classList.toggle('d-none', allow);
                }
                numberInput.classList.toggle('is-locked', !allow);
                numberInput.classList.toggle('is-editable', allow);

                if (!allow) {
                    numberInput.value = ORIGINAL_NUMBER;
                    numberInput.classList.remove('is-invalid');
                    const numberError = document.getElementById('numberError');
                    if (numberError) numberError.textContent = '';
                    submitBtn.disabled = false;
                }
            }

// Вызов при загрузке и при смене instruction
            applyNumberState();
            instructionSelect.addEventListener('change', applyNumberState);

// Проверка уникальности — только когда номер редактируемый
            async function checkNumberUnique() {
                if (numberInput.readOnly) return true;

                const val = (numberInput.value || '').trim();
                const numberError = document.getElementById('numberError');
                if (!val) {
                    numberInput.classList.add('is-invalid');
                    numberError.textContent = 'Number is required.';
                    return false;
                }

                if (!/^\d+$/.test(val)) {
                    numberInput.classList.add('is-invalid');
                    numberError.textContent = 'Workorder number must contain digits only.';
                    return false;
                }

                if (Number(val) < 100000) {
                    numberInput.classList.add('is-invalid');
                    numberError.textContent = 'Workorder number must be 100000 or greater.';
                    return false;
                }

                if (Number(val) > 999999) {
                    numberInput.classList.add('is-invalid');
                    numberError.textContent = 'Workorder number must be 999999 or less.';
                    return false;
                }

                const url = new URL("{{ route('workorders.checkNumber') }}", window.location.origin);
                url.searchParams.set('number', val);
                url.searchParams.set('ignore_id', CURRENT_WO_ID);

                const res = await fetch(url.toString(), { headers: { "X-Requested-With": "XMLHttpRequest" } });
                const data = await res.json();

                if (!data.ok || data.unique === false) {
                    numberInput.classList.add('is-invalid');
                    numberError.textContent = data.message || 'Workorder number already exists.';
                    return false;
                }

                numberInput.classList.remove('is-invalid');
                numberError.textContent = '';
                return true;
            }

            numberInput.addEventListener('blur', async () => {
                await checkNumberUnique();
                submitBtn.disabled = false;
            });

            numberInput.addEventListener('input', () => {
                submitBtn.disabled = false;
            });

            const workorderForm = document.getElementById('createForm');
            workorderForm.addEventListener('submit', async (e) => {
                if (workorderForm.dataset.readyToSubmit === '1') {
                    workorderForm.dataset.readyToSubmit = '';
                    return;
                }

                const releaseDraft = shouldAllowEditNumber();

                if (releaseDraft) {
                    e.preventDefault();
                    e.stopPropagation();

                    const ok = await checkNumberUnique();
                    if (!ok) {
                        if (typeof window.safeHideSpinner === 'function') window.safeHideSpinner();
                        return;
                    }

                    if (typeof window.safeHideSpinner === 'function') window.safeHideSpinner();
                    assignManualError.textContent = '';
                    assignManualSelect.classList.remove('is-invalid');
                    if (assignManualSelect) {
                        const currentManualId = selectedUnitManualId();
                        const suggestedOption = Array.from(unitSelect.options).find((option) => {
                            return String(option.value || '').trim() !== String(selectedUnitOption()?.value || '').trim()
                                && String(option.getAttribute('data-part-number') || '').trim() === selectedUnitPartNumber()
                                && String(option.getAttribute('data-manual-id') || '').trim() !== '';
                        });
                        const suggestedManualId = suggestedOption?.getAttribute('data-manual-id') || currentManualId;
                        assignManualSelect.value = suggestedManualId;
                        $('#assignManualSelect').val(suggestedManualId || '').trigger('change');
                    }
                    if (assignManualPartNumber) assignManualPartNumber.value = selectedUnitPartNumber();
                    if (assignManualCustomer) assignManualCustomer.value = selectedCustomerText();
                    updateAssignManualHint();
                    assignManualModal?.show();
                    return;
                }

                // Предупреждение при смене unit, если есть TDR
                const newUnitId = parseInt(document.getElementById('unit_id').value || '0', 10);
                if (HAS_TDRS && newUnitId !== INITIAL_UNIT_ID) {
                    const msg = 'Workorder has TDR records. Changing Unit (and thus Manual) may cause data inconsistency. Components in TDR may not match the new Manual. Continue?';
                    if (!confirm(msg)) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (typeof window.safeHideSpinner === 'function') window.safeHideSpinner();
                        return;
                    }
                }

                if (releaseDraft) {
                    workorderForm.dataset.readyToSubmit = '1';
                    workorderForm.requestSubmit();
                }
            });






        });
    </script>
@endsection
