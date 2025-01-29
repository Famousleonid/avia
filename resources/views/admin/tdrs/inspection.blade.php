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

                            <div class="">
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
                                                   class="form-control " name="serial_number" >
                                        </div>
                                        <div class="" >
                                            <div class="" id="assy_serial_number_container" >
                                                <label class="" for="assy_serial_number">{{__('Assy Serial Number')}}</label>
                                                <input id='assy_serial_number' type="text"
                                                       class="form-control " name="assy_serial_number" >
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="d-flex">
                                    <div class=" form-group m-2">
                                        <label for="codes_id" class="form-label pe-2">Code Inspection</label>
                                        <select name="codes_id" id="codes_id" class="form-control" style="width: 278px">
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
                                    <div class="form-group m-2" id="qty" style="display: none">
                                        <label class="" for="qty">{{__('QTY')}}</label>
                                        <input id="qty" type="number" class="form-control" name="qty" value="1">
                                    </div>
                                    <div class=" form-group m-2" id="necessary">
                                        <label for="necessaries_id" class="form-label pe-2">Necessary to Do</label>
                                        <select name="necessaries_id" id="necessaries_id" class="form-control"
                                                style="width: 278px">
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
                                    <label for="c_conditions_id" class="form-label pe-2">Conditions</label>
                                    <select name="conditions_id" id="c_conditions_id" class="form-control"
                                            style="width: 278px">
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
                            <input type="hidden" name="use_tdr" value="true"> <!-- Этот инпут будет использоваться только в unitGroup -->

                            <div class="form-group m-2">
                                <label for="u_conditions_id" class="form-label pe-2">Condition</label>
                                <select name="conditions_id" id="u_conditions_id" class="form-control" style="width:575px">
                                    <option selected value="">---</option>
                                    @foreach($unit_conditions as $unit_condition)
                                        @if($unit_condition->name != 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')
                                            <option value="{{ $unit_condition->id }}" data-title="{{$unit_condition->name}}">
                                                {{$unit_condition->name}}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-outline-primary mt-3 ">{{ __('Save') }}</button>
                            <a href="{{ route('admin.tdrs.show', ['tdr'=>$current_wo->id]) }}"
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
                        <button type="submit" class="btn btn-primary">Save Component</button>
                    </div>
                </form>
            </div>
        </div>
    </div>




    <script>

        // Ожидаем изменения в поле "codes_id"
        document.getElementById('codes_id').addEventListener('change', function() {
            // Получаем выбранное значение из выпадающего списка
            var selectedCode = this.options[this.selectedIndex].text;

            // Если выбранный код "Missing", показываем инпут "qty", иначе скрываем его
            if (selectedCode === "Missing") {
                document.getElementById('qty').style.display = 'block';
            } else {
                document.getElementById('qty').style.display = 'none';
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            var codesSelect = document.getElementById('codes_id');
            var necessaryDiv = document.getElementById('necessary');
            var conditionsDiv = document.getElementById('conditions');
            var snsDiv = document.getElementById('sns-group');
            var form = document.getElementById('createForm'); // Получаем форму

            var necessariesSelect = document.getElementById('necessaries_id');

            codesSelect.addEventListener('change', function() {
                necessariesSelect.value = ""; // Сбрасываем значение necessaries_id при изменении codes_id
            });

// Массив скрытых полей
            var hiddenFields = [];

            // Получаем ссылку на элемент select
            var selectCondition = document.getElementById('c_conditions_id');

            /// Функция для обновления значения condition_id
            function updateConditionsId() {
                var conditionsId = selectCondition.value;
                console.log(conditionsId);  // Выводим новое значение в консоль для проверки

                // Обновляем объект Kit
                var skits = {
                    kFields: [
                        { name: 'conditions_id', value: conditionsId },
                    ]
                };

                // Добавляем скрытые поля в форму
                addHiddenFields(skits.kFields);
            }

            // Слушаем изменение значения в select для условий
            selectCondition.addEventListener('change', updateConditionsId);

            // Пример данных для necessaries в зависимости от выбранного кода
            var necessariesData = {
                'Missing': {
                    // necessaries_id: 'Order New',
                    hiddenFields: [
                        { name: 'conditions_id', value: '1' },
                        { name: 'necessaries_id', value: '2' },
                        // { name: 'use_tdr', value: 'false' },
                    ]
                },
                'Life': {
                    // necessaries_id: 'Order New',
                    LifeFields: [
                        { name: 'necessaries_id', value: '2' },
                        { name: 'conditions_id', value: '35' },
                        // { name: 'use_tdr', value: 'false' },
                    ]
                },
                'Incorrect Part': {
                    // necessaries_id: 'Order New',
                    ipFields: [
                        { name: 'necessaries_id', value: '2' },
                        { name: 'conditions_id', value: '39' },
                        { name: 'use_tdr', value: 'true' },
                    ]
                },
                'Kit': {
                    // necessaries_id: 'Order New',
                    kFields: [
                        { name: 'necessaries_id', value: '2' },
                        { name: 'conditions_id', value: '38' },
                        { name: 'use_tdr', value: 'false' },
                        { name: 'use_process_forms', value: 'false' }
                    ]
                },
                'Worn': {
                    // necessaries_id: 'Order New',
                    wFields: [
                        { name: 'necessaries_id', value: '2' },
                        { name: 'conditions_id', value: '6' },
                        { name: 'use_tdr', value: 'true' },

                    ]
                },
                'Cracked': {
                    // necessaries_id: 'Order New',
                    crackFields: [
                        { name: 'necessaries_id', value: '2' },
                        { name: 'conditions_id', value: '3' },
                        { name: 'use_tdr', value: 'true' },

                    ]
                },
                'Corroded': {
                    'Repair': [
                        { name: 'conditions_id', value: '5' },
                        {name: 'use_tdr', value: 'true'},
                        {name: 'use_process_forms', value: 'true'}
                    ],
                    'Order New': [
                        { name: 'conditions_id', value: '5' },
                        { name: 'necessaries_id', value: '2' },
                        {name: 'use_tdr', value: 'true'},
                        // {name: 'use_process_forms', value: 'true'}
                    ],
                    'Safran Inspection': [

                        {name: 'use_tdr', value: 'true'},
                        {name: 'use_process_forms', value: 'true'}
                    ],
                    'Etch Inspection': [
                        {name: 'use_tdr', value: 'true'},
                        {name: 'use_process_forms', value: 'true'}
                    ],
                    'EC': [
                        {name: 'use_tdr', value: 'true'},
                        {name: 'use_process_forms', value: 'true'}
                    ]
                },

                'Damage': {
                    'Repair': [
                        {name: 'use_tdr', value: 'true'},
                        {name: 'use_process_forms', value: 'true'}
                    ],
                    'Order New': [
                        {name: 'conditions_id', value: '3'},
                        { name: 'necessaries_id', value: '2' },
                        {name: 'use_tdr', value: 'true'},
                        // {name: 'use_process_forms', value: 'false'}
                    ],
                    'Safran Inspection': [
                        {name: 'use_tdr', value: 'true'},
                        {name: 'use_process_forms', value: 'true'}
                    ],
                    'Etch Inspection': [
                        {name: 'use_tdr', value: 'true'},
                        {name: 'use_process_forms', value: 'true'}
                    ],
                    'EC': [
                        {name: 'use_tdr', value: 'true'},
                        {name: 'use_process_forms', value: 'true'}
                    ]
                }

            };

            // Функция для добавления скрытых полей
            function addHiddenFields(fields) {
                fields.forEach(function (field) {
                    // Проверка, чтобы избежать дублирования
                    if (!document.querySelector(`input[name="${field.name}"]`)) {
                        var inputField = document.createElement('input');
                        inputField.type = 'hidden';
                        inputField.name = field.name;
                        inputField.value = field.value;
                        form.appendChild(inputField);
                    }
                });
            }

            // Функция для удаления скрытых полей
            function removeHiddenFields() {
                hiddenFields.forEach(function (field) {
                    var inputField = document.querySelector(`input[name="${field.name}"]`);
                    if (inputField) {
                        inputField.remove();
                    }
                });
            }

            // Функция для обновления скрытых полей в зависимости от выбранного кода и necessaries
            function updateHiddenFields(codeName, necessariesValue) {
                // Очищаем старые скрытые поля
                removeHiddenFields();

                var data;

                if (codeName === 'Missing') {
                    // Для кода 'Missing' всегда использует 'Order New' с дополнительными скрытыми полями
                    data = necessariesData['Missing'].hiddenFields;
                } else if (codeName === 'Damage' && necessariesData['Damage'][necessariesValue]) {
                    // Для кода 'Damage' и выбранного значения в necessaries
                    data = necessariesData['Damage'][necessariesValue];
                } else if (codeName === 'Corroded' && necessariesData['Corroded'][necessariesValue]) {
                    // Для кода 'Damage' и выбранного значения в necessaries
                    data = necessariesData['Corroded'][necessariesValue];
                } else if (codeName === 'Life' ) {
                    // Для кода 'Life' и выбранного значения в necessaries
                    data = necessariesData['Life'].LifeFields;
                } else if (codeName === 'Incorrect Part' ) {
                    // Для кода 'Incorrect Part' и выбранного значения в necessaries
                    data = necessariesData['Incorrect Part'].ipFields;
                } else if (codeName === 'Kit' ) {
                    // Для кода 'Kit' и выбранного значения в necessaries
                    data = necessariesData['Kit'].kFields;
                } else if (codeName === 'Worn' ) {
                    // Для кода 'Kit' и выбранного значения в necessaries
                    data = necessariesData['Worn'].wFields;
                } else if (codeName === 'Cracked' ) {
                    // Для кода 'Kit' и выбранного значения в necessaries
                    data = necessariesData['Cracked'].crackFields;
                } else {
                    data = [];
                }

                // Добавляем новые скрытые поля
                addHiddenFields(data);
            }


            // Функция для скрытия/показа div sns в зависимости от selectedCode и necessariesValue
            function toggleSnsDiv(necessariesName) {
                console.log('Toggle snsDiv called with:', necessariesName); // Проверяем вызов функции

                if (necessariesName === 'Order New' || necessariesName === null) {
                    snsDiv.style.visibility = 'hidden';
                    conditionsDiv.style.display = 'none';
                    // snsDiv.style.display = 'none'; // Скрываем snsDiv
                    console.log('snsDiv is now hidden');
                } else {
                    snsDiv.style.visibility = 'visible';
                    // snsDiv.style.display = 'block'; // Показываем snsDiv
                    console.log('snsDiv is now visible');
                    conditionsDiv.style.display = 'block';
                }
            }


            // Функция для скрытия/показа div necessary в зависимости от selectedCode
            function toggleNecessaryDiv(codeName) {

                if (codeName === 'Missing' || codeName === 'Life'  || codeName === 'Service Bulletin' || codeName ===
                    'Incorrect Part' || codeName === 'Kit' || codeName === 'Worn') {
                    necessaryDiv.style.display = 'none';  // Скрываем necessaryDiv, если код = 'Missing'
                } else {
                    necessaryDiv.style.display = 'block'; // Показываем necessaryDiv в остальных случаях
                }
            }

            // Функция для скрытия/показа div conditions в зависимости от selectedCode
            function toggleConditionsDiv(codeName) {
                if (codeName === 'Missing' || codeName === 'Life' || codeName === 'Service Bulletin' || codeName === 'Incorrect Part'
                    || codeName === 'Kit' || codeName === 'Worn') {
                    conditionsDiv.style.display = 'none';  // Скрываем necessaryDiv, если код = 'Missing'
                } else {
                    conditionsDiv.style.display = 'block'; // Показываем necessaryDiv в остальных случаях
                }
            }
            function toggleConditionsDivExtra(codeName, necessariesName){
                if (codeName === 'Corroded' && necessariesName === 'Repair') {
                    conditionsDiv.style.display = 'none';
                    console.log(codeName, necessariesName)
                } else {
                    conditionsDiv.style.visibility = 'block'; // Показываем necessaryDiv в остальных случаях
                }
            }


            codesSelect.addEventListener('change', function () {
                // var selectedCode = this.options[this.selectedIndex].value; // Получаем id
                var selectedCodeName = this.options[this.selectedIndex].getAttribute('data-title'); // Получаем name
                var selectedNecessariesName = necessariesSelect.options[necessariesSelect.selectedIndex].getAttribute('data-title');

                updateHiddenFields(selectedCodeName, selectedNecessariesName);
                toggleNecessaryDiv(selectedCodeName);
                toggleSnsDiv(selectedNecessariesName);
                toggleConditionsDiv(selectedCodeName);
                toggleConditionsDivExtra(selectedCodeName, selectedNecessariesName);
            });
            necessariesSelect.addEventListener('change', function () {
                // var selectedNecessaries = this.value; // Получаем id
                var selectedNecessariesName = this.options[this.selectedIndex].getAttribute('data-title'); // Получаем name
                var selectedCodeName = codesSelect.options[codesSelect.selectedIndex].getAttribute('data-title');

                updateHiddenFields(selectedCodeName, selectedNecessariesName);
                toggleSnsDiv(selectedNecessariesName);
                toggleConditionsDivExtra(selectedCodeName, selectedNecessariesName);
            });



            // Проверяем значение при загрузке страницы
            var selectedCodeName = codesSelect.options[codesSelect.selectedIndex].getAttribute('data-title');
            var selectedNecessariesName = necessariesSelect.options[necessariesSelect.selectedIndex].getAttribute('data-title');

            updateHiddenFields(selectedCodeName, selectedNecessariesName);
            toggleNecessaryDiv(selectedCodeName);
            toggleSnsDiv(selectedNecessariesName);

            // var selectedCode = codesSelect.options[codesSelect.selectedIndex].text;
            // var selectedNecessaries = necessariesSelect.value;
            // updateHiddenFields(selectedCode, selectedNecessaries); // Добавляем скрытые поля при первой загрузке

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
                    removeUseTdrInput();
                } else if (selectedOption.id === 'Unit') {
                    document.getElementById('unitGroup').style.display = 'block';
                    addUseTdrInput();
                }
            }
        // Функция для добавления скрытого инпута use_tdr
            function addUseTdrInput() {
                var hiddenInput = document.querySelector('input[name="use_tdr"]');
                if (!hiddenInput) {
                    var inputField = document.createElement('input');
                    inputField.type = 'hidden';
                    inputField.name = 'use_tdr';
                    inputField.value = 'true';
                    document.getElementById('unitGroup').appendChild(inputField); // Добавляем инпут в unitGroup
                }
            }

            // Функция для удаления скрытого инпута use_tdr
            function removeUseTdrInput() {
                var hiddenInput = document.querySelector('input[name="use_tdr"]');
                if (hiddenInput) {
                    hiddenInput.remove(); // Удаляем инпут из unitGroup
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

        window.addEventListener('load', function () {

            // Обработка отправки формы для добавления компонента
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
                        // Проверяем, успешен ли ответ
                        if (response.success) {
                            // Закрываем модальное окно
                            $('#addComponentModal').modal('hide');

                            // Добавляем новый компонент в select
                            $('#component_id').append(new Option(response.component.part_number + ' (' + response.component.name + ')', response.component.id))
                                .val(response.component.id)  // устанавливаем выбранное значение
                                .trigger('change');  // обновляем select2
                        }
                    },
                    error: function(response) {
                        // Если что-то пошло не так, выводим сообщение об ошибке
                        alert('Error occurred while adding the component');
                    }
                });
            });

            // --------------------------------- Select 2 --------------------------------------------------------

            $(document).ready(function () {
                $('#component_id').select2({
                    placeholder: '---',
                    theme: 'bootstrap-5',
                    allowClear: true
                });
            });

            $(function() {
                applyTheme();
            });

            $(document).ready(function () {
                $('#conditions_id').select2({
                    placeholder: '---',
                    theme: 'bootstrap-5',
                    allowClear: true
                });
            });

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

            // Функция для отображения/скрытия div assy_serial_number
            function toggleAssySerialNumberField(selectedComponentId) {
                const selectedOption = $('#component_id option[value="' + selectedComponentId + '"]');
                const hasAssyPartNumber = selectedOption.data('has_assy_part_number');

                // Показываем или скрываем div в зависимости от наличия assy_part_number
                if (hasAssyPartNumber) {
                    $('#assy_serial_number_container').show();
                } else {
                    $('#assy_serial_number_container').hide();
                }
            }

            // Обработчик события изменения выбора компонента
            $('#component_id').on('change', function () {
                const selectedComponentId = $(this).val();
                console.log("Selected Component ID:", selectedComponentId);

                // Вызываем функцию для отображения/скрытия assy_serial_number
                toggleAssySerialNumberField(selectedComponentId);
            });

            // При загрузке страницы, проверяем выбранный компонент
            const defaultSelectedId = $('#component_id').val();
            if (defaultSelectedId) {
                console.log("Initial Selected Component ID:", defaultSelectedId);
                toggleAssySerialNumberField(defaultSelectedId);
            }
        });


    </script>

@endsection
