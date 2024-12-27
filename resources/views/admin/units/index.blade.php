{{--@extends('layouts.main_dlb')--}}
@extends('admin.master')

@section('content')

    <div class="container">
        <div class="card shadow">

            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <div style="width: 450px">
                        <h3>{{__('Manage Units')}}</h3>
                    </div>
                    <!-- Кнопка Добавить юнит -->
                    <button class="btn btn-primary mb-1"
                            data-bs-toggle="modal"
                            data-bs-target="#addUnitModal">{{__('Add Unit')
                            }}</button>
                </div>
            </div>
            <div class="card-body">
                @if($groupedUnits->isEmpty())
                    <div class="alert alert-info text-center">
                        {{ __('No units available.') }}
                    </div>
                @else
                    <table id="cmmTable" data-toggle="table"
                           data-search="true"
                           data-pagination="false"
                           data-page-size="5"
                           class="table table-bordered data-table">
                        <thead>
                        <tr>
                            <th data-field="#" data-visible="true" data-priority="1"
                                class="text-center">{{__('#')}}</th>
                            <th data-field="part_title" data-visible="true"
                                data-priority="2" class="text-center">{{__('Unit
                            Description')
                            }}</th>
                            <th data-field="part_number" data-visible="true"
                                data-priority="2" class="text-center">{{__('Unit
                            PN')
                            }}</th>
                            <th data-field="number" data-visible="true"
                                data-priority="3" class="text-center">{{__('CMM
                            Unit')}}</th>
                            <th data-field="img" data-visible="true"
                                data-priority="4" class="text-center">{{__('Image')
                            }}</th>
                            <th data-field="action" data-visible="true" data-priority="5"
                                class="text-center">{{__('Action')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php $pp = 1; @endphp
                        @foreach($groupedUnits as $manualNumber => $units)
                            <tr>
                                <td class="text-center">{{ $pp++ }}</td>

                                <td class="text-center">
                                    @if ($units->isNotEmpty() && $units->first()->manuals)

                                        {{ $units->first()->manuals->title }}
                                    @else
                                        <span>No data on CMM</span>
                                    @endif
                                </td>
                                <td>
                                    <select class="form-select">
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

                                {{--                            <td>--}}
                                {{--                                <select class="forms-select">--}}
                                {{--                                    @foreach($units as $unit)--}}
                                {{--                                        <!-- Итерируем по $units, а не $groupedUnits -->--}}
                                {{--                                        @if ($unit->manuals)--}}
                                {{--                                            <option value="{{ $unit->part_number }}">{{ $unit->part_number }}</option>--}}
                                {{--                                        @else--}}
                                {{--                                            <option value="" disabled>No data--}}
                                {{--                                                on CMM</option>--}}
                                {{--                                        @endif--}}
                                {{--                                    @endforeach--}}
                                {{--                                </select>--}}
                                {{--                            </td>--}}

                                <td class="text-center">
                                    @if ($units->isNotEmpty() && $units->first()->manuals)
                                        <a href="#"
                                           {{--                                       class="view-cmm-btn" --}}
                                           {{--                                       data-cmm-id="{{ $units->first()->manuals->id }}">--}}
                                           data-bs-toggle="modal"
                                           data-bs-target="#cmmModal{{
                                       $units->first()->manuals->id }}">
                                            {{ $manualNumber }}
                                        </a>
                                    @else
                                        <span>No data on CMM</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if ($units->isNotEmpty() && $units->first()->manuals)
                                        <a href="#" data-bs-toggle="modal"
                                           data-bs-target="#imageModal{{ $units->first()->manuals->id }}">
                                            <img src="{{ asset('storage/image/cmm/' . $units->first()->manuals->img) }}"
                                                 style="max-width: 50px;
                                             border:1px" alt="Image">
                                        </a>
                                    @else
                                        <img src="{{ asset
                                    ('storage/image/No_image.svg') }}"
                                             style="max-width: 50px; border:1px"
                                             alt="Image">
                                    @endif
                                </td>

                                <td class="text-center">
                                    @foreach($units as $unit)
                                        @php
                                            $partNumbers = is_array($unit->part_numbers) ? $unit->part_numbers : explode(',', $unit->part_numbers);
                                        @endphp
                                    @endforeach
                                    <div class="d-inline-block mb-2">

                                        <button class="edit-unit-btn btn btn-primary btn-sm"
                                                data-id="{{ $unit->id }}"
                                                data-manuals-id="{{ $unit->manuals_id }}"
                                                data-manual="{{ $unit->manuals->title }}"
                                                data-manual-number="{{ $unit->manuals->number }}"
                                                data-manual-image="{{
                                           $units->first()->manuals->img}}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editUnitModal">

                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        {{--                                    <button id="testButton" class="edit-unit-btn btn btn-primary btn-sm">--}}
                                        {{--                                        <i class="bi bi-pencil"></i>--}}
                                        {{--                                    </button>--}}

                                        <form action="{{ route('admin.units.destroy', $manualNumber) }}" method="post"
                                              style="display: inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit"
                                                    onclick="return confirm('Are you sure you want to delete all units in this group?');">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                    </div>
                                    <br>

                                </td>
                            </tr>

                            @if ($units->first()->manuals && $units->first()->manuals->img)
                                <!-- Модальное окно для показа большого изображения -->
                                <div class="modal fade" id="imageModal{{ $units->first()->manuals->id }}" tabindex="-1"
                                     role="dialog" aria-labelledby="imageModalLabel{{ $units->first()->manuals->id }}"
                                     aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"
                                                    id="imageModalLabel{{ $units->first()->manuals->id }}">{{ $units->first()->manuals->title }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center">
                                                <img src="{{ asset('storage/image/cmm/' . $units->first()->manuals->img) }}"
                                                     style="max-width: 100%; max-height: 100%;"
                                                     alt="{{ $units->first()->manuals->title }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{--    <!-- Модальное окно для просмотра деталей CMM -->--}}

                                <div class="modal fade" id="cmmModal{{
                            $units->first()->manuals->id }}" tabindex="-1"
                                     role="dialog" aria-labelledby="cmmModalLabel{{
                                  $units->first()->manuals->id }}"
                                     aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <div>
                                                    <h5 class="modal-title"
                                                        id="imageModalLabel{{ $units->first()->manuals->id }}">
                                                        {{ $units->first()->manuals->title }}{{__(': ')}}
                                                    </h5 >
                                                    <h6>{{ $units->first()->manuals->units_pn }}</h6>
                                                </div>
                                                <button type="button"
                                                        class="btn-close pb-2"
                                                        data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="d-flex">
                                                    <div class="me-2">
                                                        <img src="{{ asset('storage/image/cmm/' . $units->first()->manuals->img) }}"
                                                             style="max-width: 200px;"
                                                             alt="{{ $units->first()->manuals->title }}">
                                                    </div>
                                                    <div>
                                                        <p><strong>{{ __('CMM:') }}</strong> {{ $units->first()->manuals->number }}</p>
                                                        <p><strong>{{ __('Description:') }}</strong>
                                                            {{ $units->first()->manuals->title }}</p>
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
                @endif
            </div>
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

                    <!-- Выпадающий список для выбора CMM -->
                    <div class="mb-3">
                        <label for="cmmSelect" class="form-label">Select CMM</label>
                        <select class="form-select" id="cmmSelect">
                            {{--                            @foreach($restManuals as $restManual)--}}
                            {{--                                <option value="{{ $restManual->id }}">{{ $restManual->title }}--}}
                            {{--                                    ({{ $restManual->number }})--}}
                            {{--                                </option>--}}
                            {{--                            @endforeach--}}
                            @foreach($manuals as $manual)
                                <option value="{{ $manual->id }}">{{ $manual->title }}
                                    ({{ $manual->number }})
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <!-- Поле для ввода PN -->
                    <div id="pnInputs">
                        <div class="input-group mb-2 pn-field">
                            <input type="text" class="form-control"
                                   placeholder="Enter PN" style="width: 200px;"
                                   name="pn[]">
                            <button class="btn btn-secondary" type="button"
                                    id="addPnField">Add PN
                            </button>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">Close
                    </button>
                    <button type="button" id="createUnitBtn" class="btn
                    btn-primary"> Add Unit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно Edit Unit  -->

    <div class="modal fade" id="editUnitModal" tabindex="-1" role="dialog" aria-labelledby="editUnitModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header justify-content-between">
                    <h5 class="modal-title" id="editUnitModalLabel"></h5>
                    <button type="button" class="btn btn-primary"
                            id="addUnitButton">
                        {{__('Add PN')}}
                    </button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col">
                            <div class="modal-body text-center">
                                <img id="cmmImage" src="" style="max-width: 150px;" alt="Image CMM">

                            </div>
                        </div>
                        <div class="col">
                            @if(isset($units) && $units->isNotEmpty())
                                <p id="editUnitModalNumber"></p>
                                <div id="partNumbersList"></div>
                            @else
                                <p>No part numbers found for this manual.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateUnitButton">Update</button>
                </div>
            </div>
        </div>
    </div>



    <script>


        // Добавление нового поля ввода PN
        document.getElementById('addPnField').addEventListener('click', function () {
            const newPnField = document.createElement('div');
            newPnField.className = 'input-group mb-2 pn-field';
            newPnField.innerHTML = ` <input type="text" class="form-control"
                                    placeholder="Enter PN"
                                     style="width: 200px;" name="pn[]">
                <button class="btn btn-danger removePnField" type="button">
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

        // Add Unit
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




        // Логика для Edit Unit
        document.addEventListener('click', function (event) {
            // Проверяем, нажали ли на элемент с классом .edit-unit-btn или на дочерний элемент кнопки
            if (event.target.matches('.edit-unit-btn') || event.target.closest('.edit-unit-btn')) {
                const button = event.target.closest('.edit-unit-btn'); // Находим нужную кнопку, если был клик по дочернему элементу
                const manualId = button.getAttribute('data-manuals-id');
                const manualTitle = button.getAttribute('data-manual');
                const manualImage = button.getAttribute('data-manual-image');
                const manualNumber = button.getAttribute('data-manual-number');

                // console.log('Кнопка нажата');
                console.log('Manual ID:', manualId);
                console.log('Manual Title:', manualTitle);
                console.log('Manual Number:', manualNumber);
                console.log('Manual Image:', manualImage);

                // Установка данных в модальное окно
                document.getElementById('editUnitModalLabel').innerText = `${manualTitle}`;
                document.getElementById('editUnitModalNumber').innerText = `CMM: ${manualNumber}`;

                // Установка изображения
                const cmmImage = document.getElementById('cmmImage');
                if (manualImage) {
                    cmmImage.src = `/storage/image/cmm/${manualImage}`;
                } else {
                    cmmImage.src = `/storage/image/Noimage.svg`; // Путь к изображению по умолчанию
                }

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
            deleteButton.className = 'btn btn-danger btn-sm ms-1';
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
            const partNumbers = Array.from(document.querySelectorAll('#partNumbersList .d-flex.align-items-center')).map(listItem => {
                const input = listItem.querySelector('.forms-control');
                const checkbox = listItem.querySelector('.forms-check-input');
                return {
                    part_number: input.value,
                    verified: checkbox.checked
                };
            });
            const manualId = document.querySelector('.edit-unit-btn').getAttribute('data-manuals-id');

            console.log("Part Numbers:", partNumbers);  // Для проверки
            console.log("Manual ID:", manualId);  // Для проверки

            // Отправляем запрос на обновление part_numbers
            fetch(`units/update/${manualId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    part_numbers: partNumbers
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Units updated successfully');
                        $('#editUnitModal').modal('hide');
                        // Обновляем страницу после закрытия модального окна
                        window.location.href = '/admin/units';
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
