@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 80px;
            max-width: 190px;
            padding-left: 10px;
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            min-width: 80px;
            max-width: 90px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 50px;
            max-width: 250px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 50px;
            max-width: 250px;
        }

        .table th:nth-child(4), .table td:nth-child(4) {
            min-width: 100px;
            max-width: 150px;
        }

        .table th:nth-child(7), .table td:nth-child(7) {
            min-width: 50px;
            max-width: 70px;
        }

        .table thead th {
            position: sticky;
            height: 50px;
            top: 0;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        @media (max-width: 1200px) {
            .table th:nth-child(6), .table td:nth-child(6),
            .table th:nth-child(2), .table td:nth-child(2),
            .table th:nth-child(3), .table td:nth-child(3),
            .table th:nth-child(4), .table td:nth-child(4) {
                display: none;
            }
        }

        .table th.sortable { cursor: pointer; }

        .clearable-input { position: relative; width: 400px; }
        .clearable-input .form-control { padding-right: 2.5rem; }
        .clearable-input .btn-clear {
            position: absolute; right: .5rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
        }
    </style>

    <div class="card shadow">

        <div class="card-header my-1 shadow">
            <div class="d-flex justify-content-between">
                <h5 class="text-primary">{{ __('Manage Units') }}</h5>

                <div class="d-flex my-2">
                    <div class="clearable-input ps-2">
                        <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                        <button class="btn-clear text-secondary"
                                onclick="document.getElementById('searchInput').value='';document.getElementById('searchInput').dispatchEvent(new Event('input'))">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>

                <button class="btn btn-outline-primary mb-1"
                        data-bs-toggle="modal"
                        data-bs-target="#addUnitModal">{{ __('Add Unit') }}
                </button>
            </div>
        </div>

        @if($groupedUnits->isEmpty())
            <div class="alert alert-info text-center">
                {{ __('No units available.') }}
            </div>
        @else
            <div class="table-wrapper me-3 p-2 pt-0">
                <table id="unitTable" class="display table table-sm table-hover table-striped align-middle table-bordered">
                    <thead class="bg-gradient">
                    <tr>
                        </th>
                        <th class="text-primary sortable bg-gradient text-center">
                            {{ __('Units Description') }} <i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary sortable bg-gradient text-center">
                            {{ __('Units PN') }} <i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary text-center bg-gradient text-center">{{ __('CMM Unit ') }}</th>
                        <th class="text-primary text-center bg-gradient text-center">{{ __('Image') }}</th>
                        <th class="text-primary text-center bg-gradient text-center">{{ __('Edit') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($groupedUnits as $manualNumber => $units)
                        @php
                            /** @var \App\Models\Unit $firstUnit */
                            $firstUnit = $units->first();
                            $manual    = $firstUnit?->manuals;

                            // URL-ы через твои хелперы из трейта (fallback внутри ниже)
                            $thumbUrl  = $manual?->getFirstMediaThumbnailUrl('manuals');
                            $bigUrl    = $manual?->getFirstMediaBigUrl('manuals');

                            // страховка, если хелпер вернул пустую строку (не ожидается, но пусть будет)
                            $thumbUrl  = $thumbUrl ?: asset('img/no-image.png');
                            $bigUrl    = $bigUrl   ?: asset('img/no-image.png');
                        @endphp

                        <tr>

                            <td class="p-3">
                                @if ($manual)
                                    {{ $manual->title }}
                                @else
                                    <span>No data on CMM</span>
                                @endif
                            </td>

                            <td>
                                <select class="form-select">
                                    @foreach($units as $u)
                                        @if ($u->manuals)
                                            <option value="{{ $u->part_number }}" @if(!$u->verified) class="text-danger" @endif>
                                                {{ $u->part_number }}
                                            </option>
                                        @else
                                            <option value="" disabled>No data on CMM</option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>

                            <td class="text-center">
                                @if ($manual)
                                    <a href="#"
                                       data-bs-toggle="modal"
                                       data-bs-target="#cmmModal{{ $manual->id }}">
                                        {{ $manualNumber }}
                                    </a>
                                @else
                                    <span>No data on CMM</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <a href="{{ $bigUrl }}">
                                    <img class="rounded-circle" src="{{ $thumbUrl }}" width="40" height="40" alt="Image">
                                </a>
                            </td>

                            <td class="text-center">
                                <div class="d-inline-block mb-2">
                                    <button class="edit-unit-btn btn btn-outline-primary btn-sm"
                                            data-id="{{ $firstUnit?->id ?? '' }}"
                                            data-manuals-id="{{ $manual?->id ?? '' }}"
                                            data-manual="{{ $manual?->title ?? '' }}"
                                            data-manual-number="{{ $manual?->number ?? '' }}"
                                            data-manual-image="{{ $bigUrl }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editUnitModal">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </div>
                                <br>
                            </td>
                        </tr>

                        @if($manual)
                            <div class="modal fade" id="cmmModal{{ $manual->id }}" tabindex="-1"
                                 role="dialog" aria-labelledby="cmmModalLabel{{ $manual->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content bg-gradient">
                                        <div class="modal-header">
                                            <div>
                                                <h5 class="modal-title" id="imageModalLabel{{ $manual->id }}">
                                                    {{ $manual->title }}{{ __(': ') }}
                                                </h5>
                                                <h6>{{ $manual->unit_name_training }}</h6>
                                            </div>
                                            <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="d-flex">
                                                <div class="me-2">
                                                    <img src="{{ $bigUrl }}" width="200" alt="Image"/>
                                                </div>
                                                <div>
                                                    <p><strong>{{ __('CMM:') }}</strong> {{ $manual->number }}</p>
                                                    <p><strong>{{ __('Description:') }}</strong> {{ $manual->title }}</p>
                                                    <p><strong>{{ __('Revision Date:') }}</strong> {{ $manual->revision_date }}</p>
                                                    <p><strong>{{ __('AirCraft Type:') }}</strong> {{ $planes[$manual->planes_id] ?? 'N/A' }}</p>
                                                    <p><strong>{{ __('MFR:') }}</strong> {{ $builders[$manual->builders_id] ?? 'N/A' }}</p>
                                                    <p><strong>{{ __('Scope:') }}</strong> {{ $scopes[$manual->scopes_id] ?? 'N/A' }}</p>
                                                    <p><strong>{{ __('Library:') }}</strong> {{ $manual->lib }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
    @endif

    <!-- Модальное окно Add Unit -->
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
                            <select class="form-select" id="cmmSelect" name="cmmSelect" >
                                <option value="">{{ __('Select CMM') }}</option>
                                @foreach($manuals as $m)
                                    <option value="{{ $m->id }}">{{ $m->title }} ({{ $m->number }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="pnInputs">
                            <div class="input-group mb-2 pn-field">
                                <input type="text" class="form-control" placeholder="Enter PN" style="width:200px;" name="pn[]">
                                <input type="text" class="form-control ms-2" placeholder="Enter EFF Code" style="width:150px;" name="eff_code[]">
                                <button class="btn btn-outline-primary" type="button" id="addPnField">Add PN</button>
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

        <!-- Модальное окно Edit Unit -->
        <div class="modal fade" id="editUnitModal" tabindex="-1" role="dialog" aria-labelledby="editUnitModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header justify-content-between">
                        <h5 class="modal-title" id="editUnitModalLabel">Edit Unit</h5>
                        <button type="button" class="btn btn-outline-primary" id="addUnitButton">{{ __('Add PN') }}</button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col text-center">
                                <img id="cmmImage" src="" alt="Image CMM" style="width: 180px;">
                            </div>
                            <div class="col">
                                <p id="editUnitModalNumber"></p>
                                <div id="partNumbersList"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-outline-primary" id="updateUnitButton">Update</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Sorting
            const table = document.getElementById('unitTable');
            const headers = document.querySelectorAll('.sortable');
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                    const rows = Array.from(table.querySelectorAll('tbody tr'));
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;
                    rows.sort((a, b) => {
                        const aText = a.cells[columnIndex].innerText.trim();
                        const bText = b.cells[columnIndex].innerText.trim();
                        return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                    });
                    rows.forEach(row => table.querySelector('tbody').appendChild(row));
                });
            });

            // Search
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', () => {
                const filter = searchInput.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });

            // Добавление нового поля PN
            document.getElementById('addPnField').addEventListener('click', function () {
                const newPnField = document.createElement('div');
                newPnField.className = 'input-group mb-2 pn-field';
                newPnField.innerHTML = `
                    <input type="text" class="form-control" placeholder="Enter PN" style="width:200px;" name="pn[]">
                    <input type="text" class="form-control ms-2" placeholder="Enter EFF Code" style="width:150px;" name="eff_code[]">
                    <button class="btn btn-outline-danger removePnField" type="button">Delete</button>`;
                document.getElementById('pnInputs').appendChild(newPnField);
            });

            // Удаление поля PN
            document.addEventListener('click', function (event) {
                if (event.target.classList.contains('removePnField')) {
                    event.target.parentElement.remove();
                }
            });

            // Создание юнитов
            document.getElementById('createUnitBtn').addEventListener('click', function () {
                const cmmId = document.getElementById('cmmSelect').value;
                const pnInputs = document.querySelectorAll('.pn-field');
                const unitData = [];

                pnInputs.forEach(field => {
                    const pnInput = field.querySelector('input[name="pn[]"]');
                    const effCodeInput = field.querySelector('input[name="eff_code[]"]');
                    if (pnInput && pnInput.value.trim()) {
                        unitData.push({
                            part_number: pnInput.value.trim(),
                            eff_code: effCodeInput ? effCodeInput.value.trim() : ''
                        });
                    }
                });

                if (cmmId && unitData.length > 0) {
                    $.ajax({
                        url: '{{ route('units.store') }}',
                        type: 'POST',
                        data: {
                            cmm_id: cmmId,
                            units: unitData,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.success) {
                                alert(response.message);
                                location.reload();
                            } else {
                                alert('Error: ' + (response.error || 'Unknown error'));
                            }
                        },
                        error: function (xhr) {
                            console.error(xhr.responseText);
                            alert('An error occurred while creating the unit. Please try again.');
                        }
                    });
                } else {
                    alert('Please select CMM and enter at least one PN.');
                }
            });

            // ---- Edit Unit (open modal, load units) ----
            document.addEventListener('click', function (event) {
                if (event.target.matches('.edit-unit-btn') || event.target.closest('.edit-unit-btn')) {
                    const button = event.target.closest('.edit-unit-btn');
                    const manualId     = button.getAttribute('data-manuals-id');
                    const manualTitle  = button.getAttribute('data-manual');
                    const manualImage  = button.getAttribute('data-manual-image');
                    const manualNumber = button.getAttribute('data-manual-number');

                    const editModal = document.getElementById('editUnitModal');
                    editModal.setAttribute('data-manual-id', manualId);

                    document.getElementById('editUnitModalLabel').innerText  = manualTitle || 'Edit Unit';
                    document.getElementById('editUnitModalNumber').innerText = manualNumber ? `CMM: ${manualNumber}` : '';
                    document.getElementById('cmmImage').src                  = manualImage || '';

                    const partNumbersList = document.getElementById('partNumbersList');
                    partNumbersList.innerHTML = '';

                    fetch(`units/${manualId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.units && data.units.length > 0) {
                                data.units.forEach(function (unit) {
                                    addPartNumberRow(unit.part_number, unit.verified, unit.eff_code || '');
                                });
                            } else {
                                const noUnitsItem = document.createElement('div');
                                noUnitsItem.className = 'mb-2';
                                noUnitsItem.innerText = 'No part numbers found for this manual.';
                                partNumbersList.appendChild(noUnitsItem);
                            }
                            $('#editUnitModal').modal('show');
                        })
                        .catch(error => console.error('Error loading units:', error));
                }
            });

            document.addEventListener('click', function (event) {
                if (event.target.matches('.edit-unit-btn') || event.target.closest('.edit-unit-btn')) {
                    $('#editUnitModal').on('shown.bs.modal', function () {
                        const addUnitButton = document.getElementById('addUnitButton');
                        if (addUnitButton) {
                            addUnitButton.addEventListener('click', handleAddUnitClick, { once: true });
                        }
                    });
                }
            });
        });

        function handleAddUnitClick() { addPartNumberRow('', true, ''); }

        // Добавление/удаление строк PN внутри Edit
        function addPartNumberRow(partNumber = '', verified = true, effCode = '') {
            const partNumbersList = document.getElementById('partNumbersList');
            if (!partNumbersList) { console.error('Error: partNumbersList element not found'); return; }

            const listItem = document.createElement('div');
            listItem.className = 'mb-2 d-flex align-items-center';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input me-2';
            checkbox.checked = verified;

            const pnInput = document.createElement('input');
            pnInput.type = 'text';
            pnInput.className = 'form-control me-2';
            pnInput.style.width = '150px';
            pnInput.value = partNumber;
            pnInput.placeholder = 'Part Number';

            const effCodeInput = document.createElement('input');
            effCodeInput.type = 'text';
            effCodeInput.className = 'form-control me-2';
            effCodeInput.style.width = '120px';
            effCodeInput.value = effCode;
            effCodeInput.placeholder = 'EFF Code';

            const deleteButton = document.createElement('button');
            deleteButton.className = 'btn btn-danger btn-sm ms-1';
            deleteButton.innerText = 'Del';
            deleteButton.onclick = function () { listItem.remove(); };

            listItem.appendChild(checkbox);
            listItem.appendChild(pnInput);
            listItem.appendChild(effCodeInput);
            listItem.appendChild(deleteButton);
            partNumbersList.appendChild(listItem);
        }

        // Update
        document.getElementById('updateUnitButton').addEventListener('click', function () {
            const editModal = document.getElementById('editUnitModal');
            const manualId = editModal.getAttribute('data-manual-id');

            const listItems = document.querySelectorAll('#partNumbersList .d-flex.align-items-center');
            const partNumbers = Array.from(listItems).map((listItem) => {
                const inputs = listItem.querySelectorAll('.form-control');
                const checkbox = listItem.querySelector('.form-check-input');
                return {
                    part_number: inputs[0] ? inputs[0].value : '',
                    eff_code: inputs[1] ? inputs[1].value : '',
                    verified: !!(checkbox && checkbox.checked)
                };
            });

            if (!manualId) { alert('Error: Manual ID not found'); return; }
            if (partNumbers.length === 0) { alert('Error: No part numbers to update'); return; }
            const invalidItems = partNumbers.filter(item => !item.part_number.trim());
            if (invalidItems.length > 0) { alert('Error: All part numbers must be filled'); return; }

            const requestData = { part_numbers: partNumbers };
            const updateUrl = '{{ route("units.update", ":id") }}'.replace(':id', manualId);

            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(requestData)
            })
                .then(response => {
                    if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Units updated successfully');
                        $('#editUnitModal').modal('hide');
                        window.location.reload();
                    } else {
                        alert('Error updating units: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error updating units:', error);
                    if (error.message.includes('404')) {
                        alert('Error: Route not found. Please check the server configuration.');
                    } else {
                        alert('Error updating units: ' + error.message);
                    }
                });
        });
    </script>
@endsection
