@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 650px;
        }

        /* ----------------------------------- Select 2 Dark Theme -------------------------------------*/



        html[data-bs-theme="dark"]  .select2-selection--single {
            background-color: #121212 !important;
            color: gray !important;
            height: 38px !important;
            border: 1px solid #495057 !important;
            align-items: center !important;
            border-radius: 8px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
            color: #999999;
            line-height: 2.2 !important;
        }

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field  {
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


    </style>
    <div class="container mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <h4 class="text-primary">{{__('Add Inspection')}}</h4>
                <h4 class="text-primary"> {{__('Work Order')}}
                    {{$current_wo->number}}</h4>

            </div>

        <div class="card-body" id="create_div_inputs">
            <form id="createForm" class="createForm" role="form" method="POST" action="{{route('admin.tdrs.store')}}"
                  enctype="multipart/form-data" >
                @csrf

                <input type="hidden" name="workorder_id" value="{{$current_wo->id }}">


                <div class="">

                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="RadioInspection" id="Component">
                        <label class="form-check-label" for="Component">
                            Add Component Inspection
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="RadioInspection" id="Unit">
                        <label class="form-check-label" for="Unit">
                            Add Unit Inspection
                        </label>
                    </div>



                        <!-- Группа элементов для Component Inspection -->

                        <div id="componentGroup" style="display:none;">
{{--                            <p>Here are the fields for component inspection...</p>--}}

                            <div class="mt-3">
                                <div class=" form-group  d-flex">
                                    <label for="component_id" class="form-label me-2">Component</label>
                                    <select name="component_id" id="component_id" class="form-control" style="width: 300px">
                                        @if (isset($selectedComponent))
                                            <option value="{{ $selectedComponent->id }}" selected>
                                                {{ $selectedComponent->part_number }} ({{ $selectedComponent->name }})
                                            </option>
                                        @else
                                            <option selected value="">---</option>
                                        @endif
                                        @foreach($components as $component)
                                            <option
                                                value="{{ $component->id }}"
                                                data-has_assy_part_number="{{ $component->assy_part_number ? 'true' : 'false' }}"

                                                data-title="{{$component->name}}">

                                                {{$component->part_number}} ( {{ $component->name }} )

                                            </option>
                                        @endforeach

                                    </select>
                                    <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                            data-bs-target="#addComponentModal">{{ __('Add Component') }}
                                    </button>
                                </div>

                                <div class="  ms-2 me-2"  >

                                    <div class="form-group ms-4 d-flex justify-content-between "  id="sns-group" style="display: block;">
                                       <div></div>
                                        <div class="">
                                            <label class="" for="serial_number">{{ __('Serial Number')}}</label>
                                            <input id='serial_number' type="text"
                                                   class="form-control "name="serial_number" >
                                        </div>
                                        <div class="" >
                                            <div id="assy_serial_number_container" style="display: none;">
                                                <label for="assy_serial_number">Assy Serial Number</label>
                                                <input id="assy_serial_number" type="text" class="form-control" name="assy_serial_number">
                                            </div>

                                        </div>
                                    </div>

                                </div>

                                <div class="d-flex justify-content-center">
                                    <div class=" form-group m-2">
                                        <label for="codes_id" class="form-label pe-2">Code Inspection</label>
                                        <select name="codes_id" id="codes_id" class="form-control" style="width:
                                        250px">
                                            <option  selected value="">---</option>
                                            @foreach($codes as $code)
                                                <option
                                                    value="{{ $code->id }}"
                                                    data-title="{{$code->name}}">
                                                    {{$code->name}}

                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class=" form-group m-2" id="necessary">
                                        <label for="necessaries_id" class="form-label pe-2">Necessary to Do</label>
                                        <select name="necessaries_id" id="necessaries_id" class="form-control"
                                                style="width: 250px">
                                            <option  selected value="">---</option>
                                            @foreach($necessaries as $necessary)
                                                <option
                                                    value="{{ $necessary->id }}"
                                                    data-title="{{$necessary->name}}">
                                                    {{$necessary->name}}

                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>
                                <div class="form-group m-2" id="conditions">
                                    <label for="conditions_id" class="form-label pe-2">Conditions</label>
                                    <select name="conditions_id" id="c_conditions_id" class="form-control"
                                            style="width: 350px">
                                        <option value="" disabled selected>---</option> <!-- Пустое значение по умолчанию -->
                                        @foreach($component_conditions as $component_condition)
                                            <option value="{{ $component_condition->id }}" data-title="{{ $component_condition->name }}">
                                                {{ $component_condition->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                </div>



                            </div>
                        </div>

                        <!-- Группа элементов для Unit Inspection -->
                        <div id="unitGroup" style="display:none;">

                            <p>Here are the fields for unit inspection...</p>

                            <div class=" form-group m-2">
                                <label for="conditions_id"
                                       class="form-label pe-2">Condition</label>
                                <select name="conditions_id" id="u_conditions_id" class="form-control" style="width:
                                575px">
                                    <option  selected value="">---</option>
                                    @foreach($unit_conditions as $unit_condition)
                                        <option
                                            value="{{ $unit_condition->id }}"
                                            data-title="{{$unit_condition->name}}">
                                            {{$unit_condition->name}}

                                        </option>
                                    @endforeach

                                </select>


                            </div>


                        </div>





                    <div class="d-flex">





                    </div>



                <div class="text-end">
                    <button type="submit" class="btn btn-outline-primary
                        mt-3 ">{{ __('Save') }}</button>

                    <a href="{{ route('admin.tdrs.show',
                    ['tdr'=>$current_wo->id]) }}"
                       class="btn btn-outline-secondary mt-3">{{ __('Cancel') }} </a>
                </div>
        </div>
            </form>
        </div>

        </div>
    </div>

    <!-- Modal - Add component -->
    <div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addComponentModalLabel">{{ __('Add Component') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form action="{{ route('admin.components.storeFromInspection') }}" method="POST" id="addComponentForm">
                    @csrf

                    <div class="modal-body">
                        <input type="hidden" name="manual_id" value="{{$current_wo->unit->manual_id}}">
                        <div class="form-group">
                            <label for="name">{{ __('Name') }}</label>
                            <input id='name' type="text" class="form-control" name="name" required>
                        </div>
                        <div class="d-flex">

                            <div class="d-flex">
                                <div class="m-3">
                                    <div class="">
                                        <label for="ipl_num">{{ __('IPL Number') }}</label>
                                        <input id='ipl_num' type="text" class="form-control" name="ipl_num" required>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                        <div class="form-group">
                                            <strong>{{__('Image:')}}</strong>
                                            <input type="file" name="img" class="form-control" placeholder="Image">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label for="part_number">{{ __('Part Number') }}</label>
                                        <input id='part_number' type="text" class="form-control"
                                               name="part_number" required>
                                    </div>
                                    <div class="mt-2">
                                        <label for="eff_code">{{ __('EFF Code') }}</label>
                                        <input id='eff_code' type="text" class="form-control"
                                               name="eff_code" placeholder="Enter EFF code (optional)">
                                    </div>

                                </div>

                                <div class="m-3">
                                    <div class="">
                                        <label for="assy_ipl_num">{{ __('Assembly IPL Number') }}</label>
                                        <input id='assy_ipl_num' type="text" class="form-control" name="assy_ipl_num" >
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                        <div class="form-group">
                                            <strong>{{__(' Assy Image:')}}</strong>
                                            <input type="file" name="assy_img" class="form-control" placeholder="Image">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label for="assy_part_number">{{ __(' Assembly Part Number') }}</label>
                                        <input id='assy_part_number' type="text" class="form-control"
                                               name="assy_part_number" >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="d-flex">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"  id="log_card" name="log_card">
                                    <label class="form-check-label" for="log_card">
                                        Log Card
                                    </label>
                                </div>
                                <div class="form-check ms-3">
                                    <input class="form-check-input" type="checkbox"  id="repair" name="repair">
                                    <label class="form-check-label" for="repair">
                                        Repair
                                    </label>
                                </div>
                                <div class="form-check ms-3">
                                    <input class="form-check-input" type="checkbox"  id="is_bush" name="is_bush" onchange="toggleBushIPL()">
                                    <label class="form-check-label" for="is_bush">
                                        Is Bush
                                    </label>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Save Component</button>
                            </div>
                        </div>
                        
                        <!-- Bush IPL Number field - показывается только когда Is Bush отмечен -->
                        <div class="form-group mt-3" id="bush_ipl_container" style="display: none;">
                            <div class="d-flex">
                                <label for="bush_ipl_num">{{ __('Initial Bushing IPL Number') }}</label>
                                <input id='bush_ipl_num' type="text" class="form-control" name="bush_ipl_num"
                                       pattern="^\d+-\d+[A-Za-z]?$"
                                       title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B)">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>




    <script>


        document.addEventListener('DOMContentLoaded', function () {
            var codesSelect = document.getElementById('codes_id');
            var conditionsSelect = document.getElementById('c_conditions_id');
            var necessariesSelect = document.getElementById('necessaries_id');
            var form = document.getElementById('createForm');

            // Массив данных для каждого типа "necessaries"
            var necessariesData = {
                'Missing': {
                    hiddenFields: [
                        { name: 'conditions_id', value: '1' },
                        { name: 'necessaries_id', value: '2' },
                        { name: 'use_tdr', value: 'false' },
                        { name: 'use_process_forms', value: 'false' }
                    ]
                },
                'Life': {
                    lifeFields: [
                        { name: 'necessaries_id', value: '2' },
                        { name: 'conditions_id', value: '35' },
                        { name: 'use_tdr', value: 'true' },
                        { name: 'use_process_forms', value: 'true' }
                    ]
                },
                'Kit': {
                    kitFields: [
                        { name: 'necessaries_id', value: '2' },
                        { name: 'conditions_id', value: conditionsSelect.value },  // Изначальное значение
                        { name: 'use_tdr', value: 'true' },
                        { name: 'use_process_forms', value: 'false' }
                    ]
                },
                'Damage': {
                    'Repair': [
                        { name: 'conditions_id', value: '3' },
                        { name: 'use_tdr', value: 'true' },
                        { name: 'use_process_forms', value: 'true' }
                    ]
                }
            };

            // Функция для добавления скрытых полей
            function addHiddenFields(fields) {
                fields.forEach(function (field) {
                    var inputField = document.querySelector(`input[name="${field.name}"]`);
                    if (!inputField) {
                        inputField = document.createElement('input');
                        inputField.type = 'hidden';
                        inputField.name = field.name;
                        inputField.value = field.value;
                        form.appendChild(inputField);
                    } else {
                        inputField.value = field.value; // Обновляем значение, если уже существует
                    }
                });
            }

            // Функция для удаления всех скрытых полей
            function removeHiddenFields() {
                var hiddenFields = form.querySelectorAll('input[type="hidden"]');
                hiddenFields.forEach(function (field) {
                    field.remove();
                });
            }

            // Функция для обновления скрытых полей в зависимости от выбранного кода и necessaries
            function updateHiddenFields() {
                var selectedCodeName = codesSelect.options[codesSelect.selectedIndex].getAttribute('data-title');
                var data;

                // Очищаем старые скрытые поля
                removeHiddenFields();

                // Обновляем данные в зависимости от выбранного кода
                if (selectedCodeName === 'Missing') {
                    data = necessariesData['Missing'].hiddenFields;
                } else if (selectedCodeName === 'Damage') {
                    data = necessariesData['Damage']['Repair']; // Пример для 'Repair'
                } else if (selectedCodeName === 'Life') {
                    data = necessariesData['Life'].lifeFields;
                } else if (selectedCodeName === 'Kit') {
                    data = necessariesData['Kit'].kitFields;
                } else {
                    data = [];
                }

                // Добавляем новые скрытые поля
                addHiddenFields(data);
            }

            // Обработчик изменения для conditionsSelect
            conditionsSelect.addEventListener('change', function () {
                var conditionsValue = conditionsSelect.value; // Обновляем значение при изменении выбора
                console.log('Selected conditions_id:', conditionsValue); // Лог для отладки

                // Обновляем объект necessariesData для Kit с новым значением conditions_id
                necessariesData['Kit'].kitFields[1].value = conditionsValue; // Обновляем conditions_id для Kit
                console.log('Updated Kit data:', necessariesData['Kit']); // Логируем обновленные данные

                // Обновляем скрытые поля после изменения значений
                updateHiddenFields();
            });

            // Обработчик изменения для кодов
            codesSelect.addEventListener('change', function () {
                updateHiddenFields(); // Обновляем скрытые поля при изменении кода
            });

            // Инициализация значений при загрузке страницы
            updateHiddenFields(); // Убедитесь, что данные обновляются при загрузке страницы

            // Функция для скрытия/показа div sns в зависимости от selectedCode и necessariesValue
            function toggleSnsDiv(necessariesName) {
                console.log('Toggle snsDiv called with:', necessariesName); // Лог для отладки

                if (necessariesName === 'Order New' || necessariesName === null) {
                    snsDiv.style.visibility = 'hidden';
                    console.log('snsDiv is now hidden');
                } else {
                    snsDiv.style.visibility = 'visible';
                    console.log('snsDiv is now visible');
                }
            }

            // Функция для скрытия/показа div necessary в зависимости от selectedCode
            function toggleNecessaryDiv(codeName) {
                if (codeName === 'Missing' || codeName === 'Life' || codeName === 'Kit' || codeName === 'Service Bulletin' || codeName === 'Incorrect Part') {
                    necessaryDiv.style.display = 'none';  // Скрываем necessaryDiv, если код = 'Missing'
                } else {
                    necessaryDiv.style.display = 'block'; // Показываем necessaryDiv в остальных случаях
                }
            }

            // Функция для скрытия/показа div conditions в зависимости от selectedCode
            function toggleConditionsDiv(codeName) {
                if (codeName === 'Missing' || codeName === 'Life' || codeName === 'Service Bulletin' || codeName === 'Incorrect Part') {
                    conditionsDiv.style.display = 'none';  // Скрываем necessaryDiv, если код = 'Missing'
                } else {
                    conditionsDiv.style.display = 'block'; // Показываем necessaryDiv в остальных случаях
                }
            }

            // Обработчики изменения для кодов и necessaries
            codesSelect.addEventListener('change', function () {
                var selectedCode = this.options[this.selectedIndex].value; // Получаем id
                var selectedCodeName = this.options[this.selectedIndex].getAttribute('data-title'); // Получаем name
                var selectedNecessariesName = necessariesSelect.options[necessariesSelect.selectedIndex].getAttribute('data-title');

                updateHiddenFields();
                toggleNecessaryDiv(selectedCodeName);
                toggleConditionsDiv(selectedCodeName);
                toggleSnsDiv(selectedNecessariesName);
            });

            necessariesSelect.addEventListener('change', function () {
                var selectedNecessariesName = this.options[this.selectedIndex].getAttribute('data-title'); // Получаем name
                var selectedCodeName = codesSelect.options[codesSelect.selectedIndex].getAttribute('data-title');

                toggleSnsDiv(selectedNecessariesName);
            });

            // Проверяем значение при загрузке страницы
            var selectedCodeName = codesSelect.options[codesSelect.selectedIndex].getAttribute('data-title');
            var selectedNecessariesName = necessariesSelect.options[necessariesSelect.selectedIndex].getAttribute('data-title');

            updateHiddenFields();
            toggleNecessaryDiv(selectedCodeName);
            toggleSnsDiv(selectedNecessariesName);

            // Функция для отображения нужной группы
            function showSelectedGroup() {
                var selectedOption = document.querySelector('input[name="RadioInspection"]:checked');

                // Если радиокнопка не выбрана, скрываем обе группы
                if (!selectedOption) {
                    document.getElementById('componentGroup').style.display = 'none';
                    document.getElementById('unitGroup').style.display = 'none';
                    return;
                }

                // Скрываем обе группы
                document.getElementById('componentGroup').style.display = 'none';
                document.getElementById('unitGroup').style.display = 'none';

                // Отображаем нужную группу в зависимости от выбранной радиокнопки
                if (selectedOption.id === 'Component') {
                    document.getElementById('componentGroup').style.display = 'block';
                } else if (selectedOption.id === 'Unit') {
                    document.getElementById('unitGroup').style.display = 'block';
                }
            }

            // Слушаем изменения выбора радиокнопок
            document.querySelectorAll('input[name="RadioInspection"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    showSelectedGroup();
                });
            });

            // Вызов функции при загрузке страницы, чтобы скрыть обе группы (так как нет выбранной радиокнопки)
            window.onload = function () {
                showSelectedGroup();
            };
        });

        // Обработка отправки формы
        window.addEventListener('load', function () {
            $('#addComponentForm').submit(function(e) {
                e.preventDefault();  // предотвращаем стандартную отправку формы

                var formData = new FormData(this);  // собираем данные из формы

                $.ajax({
                    url: $(this).attr('action'),  // URL маршрута
                    type: 'POST',  // метод отправки данных
                    data: formData,  // данные из формы
                    processData: false,  // не обрабатывать данные как обычную строку
                    contentType: false,  // не устанавливать заголовок типа контента
                    success: function(response) {
                        if (response.success) {
                            $('#addComponentModal').modal('hide');
                            $('#component_id').append(new Option(response.component.part_number + ' (' + response.component.name + ')', response.component.id))
                                .val(response.component.id)
                                .trigger('change');
                        }
                    },
                    error: function(response) {
                        alert('Error occurred while adding the component');
                    }
                });
            });
        });



        // --------------------------------- Select 2 --------------------------------------------------------

                $(document).ready(function () {
                    // Инициализация Select2 для элемента conditions_id
                    $('#conditions_id').select2({
                        placeholder: '---',
                        theme: 'bootstrap-5',  // Тема Bootstrap 5
                        allowClear: true        // Разрешаем очистку выбора
                    });
                });

                $(document).ready(function () {
                    $('#component_id').select2({
                        placeholder: '---',
                        theme: 'bootstrap-5',
                        allowClear: true
                    });

                });


            // Функция для отображения/скрытия div с полем для Assy Serial Number
            function toggleAssySerialNumberField(selectedComponentId) {
                // Находим выбранную опцию по id
                const selectedOption = $('#component_id option[value="' + selectedComponentId + '"]');
                // Получаем атрибут data-has_assy_part_number с помощью метода .attr()
                const hasAssyPartNumber = selectedOption.attr('data-has_assy_part_number');

                console.log('hasAssyPartNumber:', hasAssyPartNumber);  // Для отладки

                // Показываем или скрываем div в зависимости от наличия assy_part_number
                if (hasAssyPartNumber === 'true') {
                    $('#assy_serial_number_container').show();  // Показываем
                } else {
                    $('#assy_serial_number_container').hide();   // Скрываем
                }
            }

            // Обработчик события при изменении выбора компонента
            $('#component_id').on('change', function () {
                const selectedComponentId = $(this).val();
                console.log("Selected Component ID:", selectedComponentId);  // Выводим id в лог

                // Вызываем функцию для отображения/скрытия assy_serial_number
                toggleAssySerialNumberField(selectedComponentId);
            });

            // При загрузке страницы, проверяем выбранный компонент
            const defaultSelectedId = $('#component_id').val();
            if (defaultSelectedId) {
                console.log("Initial Selected Component ID:", defaultSelectedId);
                toggleAssySerialNumberField(defaultSelectedId);
            }

//----------------------------- Theme ----------------------------------


                $(function() {
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


        });


    </script>

@endsection
