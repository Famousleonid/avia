{{--@extends('layouts.main_dlb')--}}
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

        .table th:nth-child(6), .table td:nth-child(6) {
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
            .table th:nth-child(1), .table td:nth-child(1),
            .table th:nth-child(5), .table td:nth-child(5),
            .manage-header {
                display: none;
            }
        }

        .table th.sortable {
            cursor: pointer;
        }

        .clearable-input {
            position: relative;
            width: 400px;
        }

        .clearable-input .form-control {
            padding-right: 2.5rem;
        }

        .clearable-input .btn-clear {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }
    </style>

    <div class="card shadow">

        <div class="card-header my-1 shadow">
            <div class="d-flex justify-content-between">
                <h5 class="text-primary manage-header">{{__('Manage Units')}}( <span class="text-success">{{$units_all->count()}} </span>)</h5>

                <div class="d-flex my-2">
                    <div class="clearable-input ps-2">
                        <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                        <button class="btn-clear text-secondary" onclick="document.getElementById('searchInput').value = '';
                        document.getElementById('searchInput').dispatchEvent(new Event('input'))">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
                <button class="btn btn-outline-primary mb-1" style="height: 40px"
                        data-bs-toggle="modal"
                        data-bs-target="#addUnitModal">{{__('Add Unit')}}
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
                        <th class="text-primary  sortable bg-gradient text-center" >{{__('Units
                        Description')}}<i
                                class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary  sortable bg-gradient text-center" style="width: 220px">{{__('Units
                        PN')
                        }}<i class="bi
                        bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary text-center bg-gradient text-center" style="width: 120px">{{__('CMM
                        Unit ')}}</th>
                        <th class="text-primary text-center bg-gradient text-center" style="width: 120px">{{__('Image')}}</th>
                        <th class="text-primary text-center bg-gradient text-center" style="width: 120px">{{__('Edit')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($groupedUnits as $manualNumber => $units)
                        <tr>

                            <td class="p-3">
                                @if ($units->isNotEmpty() && $units->first()->manuals)
                                    {{ $units->first()->manuals->title }}
                                @else
                                    <span>No data on CMM</span>
                                @endif
                            </td>
                            <td>
                                <select class="form-select ">
                                    @foreach($units as $unit)
                                        <!-- Проверяем наличие manuals -->
                                        @if ($unit->manuals)
                                            <!-- Проверяем verified и добавляем класс text-danger для красного текста -->
                                            <option value="{{ $unit->part_number }}" @if(!$unit->verified) class="text-danger" @endif>
                                                {{ $unit->part_number }}
                                            </option>
                                        @else
                                            <option value="" disabled>No data on CMM</option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>
                            <td class="text-center">
                                @if ($units->isNotEmpty() && $units->first()->manuals)

                                    <a href="#"
                                       data-bs-toggle="modal"
                                       data-bs-target="#cmmModal{{$units->first()->manuals->id }}">
                                        {{ $manualNumber }}
                                    </a>
                                @else
                                    <span>No data on CMM</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ $units->first()->manuals->getBigImageUrl('manuals') }}" data-fancybox="gallery">
                                    <img class="rounded-circle" src="{{ $units->first()->manuals->getThumbnailUrl('manuals') }}" width="40" height="40" alt="Image"/>
                                </a>
                            </td>
                            <td class="text-center">
                                @foreach($units as $unit)
                                    @php
                                        $partNumbers = is_array($unit->part_numbers) ? $unit->part_numbers : explode(',', $unit->part_numbers);
                                    @endphp
                                @endforeach
                                <div class="d-inline-block mb-2">

                                    <button class="edit-unit-btn btn btn-outline-primary btn-sm"
                                            data-id="{{ $unit->id }}"
                                            data-manuals-id="{{ $unit->manual_id }}"
                                            data-manual="{{ $unit->manuals->title }}"
                                            data-manual-number="{{ $unit->manuals->number }}"
{{--                                            data-manual-image="{{$units->first()->manuals->img}}"--}}
                                            data-manual-image="{{$units->first()->manuals->getBigImageUrl('manuals') }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editUnitModal">

                                        <i class="bi bi-pencil-square"></i>
                                    </button>

{{--                                    <form action="{{ route('admin.units.destroy', $manualNumber) }}" method="post"--}}
{{--                                          style="display: inline-block">--}}
{{--                                        @csrf--}}
{{--                                        @method('DELETE')--}}
{{--                                        <button class="btn btn-outline-danger btn-sm" type="submit"--}}
{{--                                                onclick="return confirm('Are you sure you want to delete all units in this group?');">--}}
{{--                                            <i class="bi bi-trash"></i>--}}
{{--                                        </button>--}}
{{--                                    </form>--}}

                                </div>
                                <br>

                            </td>

                        </tr>

                        @if($units->first()->manuals )
                            <div class="modal fade" id="cmmModal{{$units->first()->manuals->id }}" tabindex="-1"
                                 role="dialog" aria-labelledby="cmmModalLabel{{$units->first()->manuals->id }}"
                                 aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content bg-gradient">
                                        <div class="modal-header">
                                            <div>
                                                <h5 class="modal-title"
                                                    id="imageModalLabel{{ $units->first()->manuals->id }}">
                                                    {{ $units->first()->manuals->title }}{{__(': ')}}
                                                </h5 >
                                                <h6>{{ $units->first()->manuals->unit_name_training }}</h6>
                                            </div>
                                            <button type="button"
                                                    class="btn-close pb-2"
                                                    data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="d-flex">
                                                <div class="me-2">
                                                    <img class="" src="{{ $units->first()->manuals->getBigImageUrl('manuals') }}"
                                                         width="200"  alt="Image"/>

                                                </div>
                                                <div>
                                                    <p><strong>{{ __('CMM:') }}</strong> {{ $units->first()->manuals->number }}</p>
                                                    <p><strong>{{ __('Description:') }}</strong>
                                                        {{ $units->first()->manuals->title }} </p>
                                                    <p><strong>{{ __('Revision Date:')}}</strong> {{ $units->first()->manuals->revision_date }}</p>
                                                    <p><strong>{{ __('AirCraft Type:')}}</strong>
                                                        {{ $planes[$units->first()->manuals->planes_id] ?? 'N/A' }}</p>
                                                    <p><strong>{{ __('MFR:') }}</strong> {{$builders[$units->first()->manuals->builders_id] ?? 'N/A' }}</p>
                                                    <p><strong>{{ __('Scope:') }}</strong> {{$scopes[$units->first()->manuals->scopes_id] ?? 'N/A' }}</p>
                                                    <p><strong>{{ __('Library:') }}</strong> {{$units->first()->manuals->lib }}</p>
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






    <!-- Модальное окно add Unit -->
    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUnitLabel">Add Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">

                    <!-- Выпадающий список для выбора CMM -->
                    <div class="mb-3">
                        <label for="cmmSelect" class="form-label">CMM</label>
                        <select class="form-select" id="cmmSelect" name="cmmSelect">
                            <option value="">{{ __('Select CMM') }}</option>
                            @foreach($manuals as $manual)
                                <option value="{{ $manual->id }}">{{ $manual->title }} ({{ $manual->number }})</option>
                            @endforeach
                        </select>
                    </div>


                    <!-- Поле для ввода PN -->
                    <div id="pnInputs">
                        <div class="input-group mb-2 pn-field">
                            <input type="text" class="form-control"
                                   placeholder="Enter PN" style="width: 200px;"
                                   name="pn[]">
                            <button class="btn btn-outline-primary" type="button"
                                    id="addPnField">Add PN
                            </button>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Close
                    </button>
                    <button type="button" id="createUnitBtn" class="btn
                    btn-outline-primary"> Add Unit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно Edit Unit  -->

        <div class="modal fade" id="editUnitModal" tabindex="-1" role="dialog" aria-labelledby="editUnitModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content bg-gradient">
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

// --------------- Delete modal -----------------------------------------------------------------------------------
//
//             const modal = document.getElementById('useConfirmDelete');
//             const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
//             let deleteForm = null;
//             modal.addEventListener('show.bs.modal', function (event) {
//                 const button = event.relatedTarget;
//                 deleteForm = button.closest('form');
//                 const title = button.getAttribute('data-title');
//                 const modalTitle = modal.querySelector('#confirmDeleteLabel');
//                 modalTitle.textContent = title || 'Delete Confirmation';
//             });
//             confirmDeleteBtn.addEventListener('click', function () {
//                 if (deleteForm) {
//                     deleteForm.submit();
//                 }
//             });
// --------------- Delete modal -----------------------------------------------------------------------------------

        });

        // Add Unit

            // Добавление нового поля ввода PN
            document.getElementById('addPnField').addEventListener('click', function () {
                const newPnField = document.createElement('div');
                newPnField.className = 'input-group mb-2 pn-field';
                newPnField.innerHTML = ` <input type="text" class="form-control"
                                    placeholder="Enter PN"
                                     style="width: 200px;" name="pn[]">
                <button class="btn btn-outline-danger removePnField" type="button">
                        Delete
                </button> `;
                document.getElementById('pnInputs').appendChild(newPnField);
            });

            // Удаление поля ввода PN
            document.addEventListener('click', function (event) {
                if (event.target.classList.contains('removePnField')) {
                    event.target.parentElement.remove();
                }
            });





            document.getElementById('createUnitBtn').addEventListener('click', function () {
                const cmmId = document.getElementById('cmmSelect').value;
                const pnValues = Array.from(document.querySelectorAll('input[name="pn[]"]')).map(input => input.value.trim());

                // AJAX-запрос для отправки данных на сервер
                if (cmmId && pnValues.length > 0) {
                    $.ajax({
                        url: '{{ route('admin.units.store') }}', // Обновите с вашим маршрутом для сохранения юнитов
                        type: 'POST',
                        data: {
                            cmm_id: cmmId,
                            part_numbers: pnValues,
                            _token: '{{ csrf_token() }}' // CSRF токен для Laravel
                        },
                        success: function (response) {
                            // Обработка успешного ответа
                            console.log(response);
                            location.reload(); // Перезагрузка страницы, чтобы увидеть новый юнит в таблице
                        },
                        error: function (xhr) {
                            // Обработка ошибок
                            console.error(xhr.responseText);
                            alert('An error occurred while creating the unit. Please try again.');
                        }
                    });
                } else {
                    alert('Please select CMM and enter at least one PN.');
                }
            });


           //   -------------------   Edit Unit (New) -----------------------

        document.addEventListener('click', function (event) {
            // Проверяем, нажали ли на элемент с классом .edit-unit-btn или на дочерний элемент кнопки
            if (event.target.matches('.edit-unit-btn') || event.target.closest('.edit-unit-btn')) {
                const button = event.target.closest('.edit-unit-btn'); // Находим нужную кнопку, если был клик по дочернему элементу
                const manualId = button.getAttribute('data-manuals-id');
                const manualTitle = button.getAttribute('data-manual');
                const manualImage = button.getAttribute('data-manual-image');
                const manualNumber = button.getAttribute('data-manual-number');


                // Установите manualId как атрибут модального окна
                const editModal = document.getElementById('editUnitModal');
                editModal.setAttribute('data-manual-id', manualId);


                // console.log('Кнопка нажата');
                console.log('Manual ID:', manualId);
                console.log('Manual Title:', manualTitle);
                console.log('Manual Number:', manualNumber);
                console.log('Manual Image:', manualImage);

                // Установка данных в модальное окно

                document.getElementById('editUnitModalLabel').innerText = manualTitle;
                document.getElementById('editUnitModalNumber').innerText = `CMM: ${manualNumber}`;

                // Установка изображения
                const cmmImage = document.getElementById('cmmImage');
                    cmmImage.src = `${manualImage}`;

                // Очистить текущий список part_number
                const partNumbersList = document.getElementById('partNumbersList');
                partNumbersList.innerHTML = '';

                // Отправка запроса для получения юнитов, связанных с мануалом
                fetch(`units/${manualId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.units && data.units.length > 0) {
                            data.units.forEach(function (unit) {
                                addPartNumberRow(unit.part_number, unit.verified);
                            });
                        } else {
                            const noUnitsItem = document.createElement('div');
                            noUnitsItem.className = 'mb-2';
                            noUnitsItem.innerText = 'No part numbers found for this manual.';
                            partNumbersList.appendChild(noUnitsItem);
                        }
                        $('#editUnitModal').modal('show'); // Открываем модальное окно после получения данных
                    })
                    .catch(error => {
                        console.error('Error loading units:', error);
                    });
            }
        });
        document.addEventListener('click', function (event) {
            // Проверяем, что кнопка, которая открывает модальное окно, нажата
            if (event.target.matches('.edit-unit-btn') || event.target.closest('.edit-unit-btn')) {
                $('#editUnitModal').on('shown.bs.modal', function () {
                    const addUnitButton = document.getElementById('addUnitButton');
                    if (addUnitButton) {
                        console.log('Кнопка addUnitButton найдена после открытия модального окна');
                        addUnitButton.addEventListener('click', handleAddUnitClick);
                    } else {
                        console.error('Кнопка addUnitButton не найдена после открытия модального окна');
                    }
                });
            }
        });
        function handleAddUnitClick() {
            addPartNumberRow('');
        }


        // Функция для добавления новой строки с part_number
        function addPartNumberRow(partNumber = '', verified = true) {
            const partNumbersList = document.getElementById('partNumbersList');

            if (!partNumbersList) {
                console.error('Error: partNumbersList element not found');
                return;
            }

            // Создаем новый элемент для списка part_numbers
            const listItem = document.createElement('div');
            listItem.className = 'mb-2 d-flex align-items-center';

            // Создаем чекбокс для верификации
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'forms-check-input me-2';

            checkbox.checked = verified;

            // Создаем поле ввода
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'forms-control';
            input.style.width = '180px';
            input.value = partNumber;

            // Создаем кнопку для удаления
            const deleteButton = document.createElement('button');
            deleteButton.className = 'btn btn-outline-danger btn-sm ms-1';
            deleteButton.innerText = 'Del';
            deleteButton.onclick = function () {
                listItem.remove();
            };

            // Добавляем чекбокс, поле ввода и кнопку в список
            listItem.appendChild(checkbox);
            listItem.appendChild(input);
            listItem.appendChild(deleteButton);
            partNumbersList.appendChild(listItem);
        }

        // Обработчик кнопки Update
        document.getElementById('updateUnitButton').addEventListener('click', function () {

            const editModal = document.getElementById('editUnitModal');
            const manualId = editModal.getAttribute('data-manual-id');


            const partNumbers = Array.from(document.querySelectorAll('#partNumbersList .d-flex.align-items-center')).map(listItem => {
                const input = listItem.querySelector('.forms-control');
                const checkbox = listItem.querySelector('.forms-check-input');
                return {
                    part_number: input.value,
                    verified: checkbox.checked
                };
            });


            // const manualId = document.querySelector('.edit-unit-btn').getAttribute('data-manuals-id');

            console.log("Part Numbers to send:", JSON.stringify(partNumbers));
            console.log("Manual ID:", manualId);

            fetch(`/admin/units/${manualId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ part_numbers: partNumbers })
            })
                .then(response => {
                    console.log("Response status:", response.status);
                    return response.json();
                })
                .then(data => {
                    console.log("Response data:", data);
                    if (data.success) {
                        alert('Units updated successfully');
                        $('#editUnitModal').modal('hide');
                        window.location.reload();
                    } else {
                        alert('Error updating units');
                    }
                })
                .catch(error => {
                    console.error('Error updating units:', error);
                });
        });



    </script>

@endsection
