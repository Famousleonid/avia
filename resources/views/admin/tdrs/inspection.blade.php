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
                                                data-title="{{$component->name}}">
                                                {{$component->part_number}} ( {{ $component->name }} )

                                            </option>
                                        @endforeach

                                    </select>
                                    <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                            data-bs-target="#addComponentModal">{{ __('Add Component') }}
                                    </button>
                                </div>
                                <div class="d-flex justify-content-center ms-2 me-2" >


                                    <div class="m-2" id="sns">
                                        <div class="">
                                            <label class="pb-1" for="serial_number">{{ __('Serial Number')}}</label>
                                            <input id='serial_number' type="text"
                                                   class="form-control "
                                                   name="serial_number"
                                            >
                                        </div>\
                                        <div class="m-2" id="sns">
                                            <div class="">
                                                <label class="pb-1" for="assy_serial_number">{{__('Assy Serial Number')}}</label>
                                                <input id='assy_serial_number' type="text"
                                                       class="form-control " name="assy_serial_number"
                                                >
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="d-flex">
                                    <div class=" form-group m-2">
                                        <label for="codes_id"
                                               class="form-label pe-2">Code Inspection</label>
                                        <select name="codes_id" id="codes_id"
                                                class="form-control" style="width: 278px">
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
                                        <label for="necessaries_id"
                                               class="form-label pe-2">Necessary to Do</label>
                                        <select name="necessaries_id" id="necessaries_id"
                                                class="form-control" style="width: 278px">
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



                                <input type="hidden" name="use_tdr" value=true>
                                <input type="hidden" name="use_process_forms" value=true>

                            </div>
                        </div>

                        <!-- Группа элементов для Unit Inspection -->
                        <div id="unitGroup" style="display:none;">

                            <p>Here are the fields for unit inspection...</p>

                            <div class=" form-group m-2">
                                <label for="conditions_id"
                                       class="form-label pe-2">Condition</label>
                                <select name="conditions_id" id="conditions_id"
                                        class="form-control" style="width: 575px">
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

                            <input type="hidden" name="use_tdr" value=true>

                        </div>





                    <div class="d-flex">





                    </div>

{{--                    <div class="d-flex justify-content-between mt-3">--}}
{{--                        <div class="form-check ">--}}
{{--                            <label class="form-check-label" for="use_tdr">Use TDR</label>--}}
{{--                            <input class="form-check-input" type="checkbox" name="use_tdr" id="use_tdr">--}}
{{--                        </div>--}}
{{--                        <div class="form-check ">--}}
{{--                            <label class="form-check-label"--}}
{{--                                   for="use_process_forms">Use Process Form</label>--}}
{{--                            <input class="form-check-input" type="checkbox"--}}
{{--                                   name="use_process_forms"--}}
{{--                                   id="use_process_forms">--}}
{{--                        </div>--}}
{{--                        <div class="form-check ">--}}
{{--                            <label class="form-check-label" for="use_log_card">Use Log Card</label>--}}
{{--                            <input class="form-check-input" type="checkbox" name="use_log_card" id="use_log_card">--}}
{{--                        </div>--}}
{{--                        <div class="form-check ">--}}
{{--                            <label class="form-check-label"--}}
{{--                                   for="use_extra_forms">Use Extra--}}
{{--                                Process Form</label>--}}
{{--                            <input class="form-check-input" type="checkbox"--}}
{{--                                   name="use_extra_forms"--}}
{{--                                   id="use_extra_forms">--}}
{{--                        </div>--}}
{{--                    </div>--}}


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
        document.addEventListener('DOMContentLoaded', function() {
            var codesSelect = document.getElementById('codes_id');
            var necessaryDiv = document.getElementById('necessary');
            var snsDiv = document.getElementById('sns');
            var form = document.getElementById('createForm'); // Получаем форму

            // Массив скрытых полей
            var hiddenFields = [
                { name: 'necessaries_id', value: '2' },
                { name: 'conditions_id', value: '1' },
                { name: 'serial_number', value: 'NSN' }
            ];

            // Функция для добавления скрытых полей
            function addHiddenFields() {
                hiddenFields.forEach(function(field) {
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
                hiddenFields.forEach(function(field) {
                    var inputField = document.querySelector(`input[name="${field.name}"]`);
                    if (inputField) {
                        inputField.remove();
                    }
                });
            }

            // Обработчик изменения значения в поле select (коды)
            codesSelect.addEventListener('change', function() {
                if (this.value === '7') {
                    necessaryDiv.style.display = 'none';
                    snsDiv.style.display = 'none';

                    // Добавляем скрытые поля при выборе кода 7
                    addHiddenFields();
                } else {
                    necessaryDiv.style.display = 'block';
                    snsDiv.style.display = 'flex';

                    // Удаляем скрытые поля, если код не 7
                    removeHiddenFields();
                }
            });

            // Проверяем значение при загрузке страницы
            if (codesSelect.value === '7') {
                necessaryDiv.style.display = 'none';
                snsDiv.style.display = 'none';
                addHiddenFields(); // Добавляем скрытые поля при первой загрузке
            } else {
                snsDiv.style.display = 'flex';
            }

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
                radio.addEventListener('change', showSelectedGroup);
            });

            // Вызов функции при загрузке страницы, чтобы скрыть обе группы (так как нет выбранной радиокнопки)
            window.onload = function() {
                showSelectedGroup();
            }

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

            });
        });


    </script>

@endsection
