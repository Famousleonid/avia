@extends('admin.master')

@section('contend')
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
                <h4 class="text-primary">{{__('Add Unit Inspection')}}</h4>
            </div>
        </div>
        <div class="card-body" id="create_div_inputs">
            <form id="createForm" class="createForm" role="form" method="POST" action="{{route('tdrs.store')}}"
                  enctype="multipart/form-data" id="createComponentForm">
                @csrf

                <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">

                <div class="">
                    <div class=" form-group mb-3">
                        <label for="manual_id" class="form-label">CMM</label>
                        <select name="manual_id" id="manual_id" class="form-control">
                            <option disabled selected value="">---</option>
                            @foreach($manuals as $manual)
                                <option
                                    value="{{ $manual->id }}"
                                    data-title="{{$manual->title}}">
                                    {{$manual->number}}
                                    ( {{ $manual->title }} -
                                    {{$manual->unit_name_training}} )

                                </option>
                            @endforeach

                        </select>

                    </div>
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

                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-outline-primary
                        mt-3 ">{{ __('Save') }}</button>
                    <a href="{{ route('tdrs.index') }}"
                       class="btn btn-outline-secondary mt-3">{{ __('Cancel') }} </a>
                </div>
            </form>
        </div>

    </div>
@endsection

<script>
    // Выводим сохраненные логи при загрузке страницы
    $(document).ready(function() {
        var savedLogs = localStorage.getItem('debugLogs');
        if (savedLogs) {
            console.log('Сохраненные логи отладки:');
            JSON.parse(savedLogs).forEach(function(log) {
                console.log(log);
            });
            // Очищаем логи после вывода
            localStorage.removeItem('debugLogs');
        }
    });

    function setHiddenInput(name, value) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        document.getElementById('createForm').appendChild(input);
    }

    function handleOrderNew() {
        var codeName = $('#manual_id').val();
        var necessaryName = 'order new';

        if (codeName === null || codeName === '') {
            // Сохраняем начальные данные
            var debugLogs = [];
            debugLogs.push('Выполнение ветки: order new');
            debugLogs.push('Начальный codeName: ' + codeName + ' Тип: ' + typeof codeName);

            setHiddenInput('use_tdr', '1');
            setHiddenInput('use_process_forms', '0');

            var conditionId = null;

            // Нормализация codeName для сравнения
            var normalizedCodeName = codeName.toString().trim().toLowerCase();
            debugLogs.push('Нормализованный codeName: ' + normalizedCodeName + ' Длина: ' + normalizedCodeName.length);

            // Проверка структуры select
            debugLogs.push('Структура select c_conditions_id:');
            debugLogs.push($('#c_conditions_id').html());

            // Вывод всех доступных опций для отладки
            debugLogs.push('Все доступные опции в select:');
            var options = $('#c_conditions_id option');
            debugLogs.push('Количество опций: ' + options.length);

            options.each(function(index) {
                var condName = $(this).attr('data-title');
                var condValue = $(this).val();
                var optionText = $(this).text();
                debugLogs.push('Опция #' + (index + 1) + ':');
                debugLogs.push('  - Значение: ' + condValue);
                debugLogs.push('  - Название: ' + condName);
                debugLogs.push('  - Текст: ' + optionText);
                debugLogs.push('  - Нормализованное название: ' + (condName ? condName.toString().trim().toLowerCase() : 'null'));
            });

            $('#c_conditions_id option').each(function() {
                var condName = $(this).attr('data-title');
                var condValue = $(this).val();

                // Нормализация condName для сравнения
                var normalizedCondName = condName ? condName.toString().trim().toLowerCase() : null;

                debugLogs.push('Сравнение: ' + JSON.stringify({
                    codeName: normalizedCodeName,
                    condName: normalizedCondName,
                    совпадают: normalizedCondName === normalizedCodeName,
                    codeNameТип: typeof normalizedCodeName,
                    condNameТип: typeof normalizedCondName
                }));

                if (normalizedCondName && normalizedCondName === normalizedCodeName) {
                    conditionId = condValue;
                    debugLogs.push('Найдено точное соответствие: ' + JSON.stringify({
                        id: conditionId,
                        название: condName,
                        нормализованноеНазвание: normalizedCondName
                    }));
                    return false;
                }
            });

            if (conditionId) {
                setHiddenInput('conditions_id', conditionId);
                debugLogs.push('Установлен conditions_id: ' + conditionId);
            } else {
                debugLogs.push("Не найдено точное соответствие для codeName: " + normalizedCodeName);
                debugLogs.push("Проверьте значения в логах");
                setHiddenInput('conditions_id', '1');
            }

            // Сохраняем логи в localStorage с уникальным ключом
            var timestamp = new Date().getTime();
            localStorage.setItem('debugLogs_' + timestamp, JSON.stringify(debugLogs));

            // Добавляем кнопку для просмотра логов
            if (!$('#viewLogsBtn').length) {
                $('body').append('<button id="viewLogsBtn" style="position: fixed; top: 10px; right: 10px; z-index: 9999;">Показать логи</button>');
                $('#viewLogsBtn').click(function() {
                    var allLogs = [];
                    for (var i = 0; i < localStorage.length; i++) {
                        var key = localStorage.key(i);
                        if (key.startsWith('debugLogs_')) {
                            var logs = JSON.parse(localStorage.getItem(key));
                            allLogs = allLogs.concat(logs);
                        }
                    }
                    console.log('Все сохраненные логи:');
                    allLogs.forEach(function(log) {
                        console.log(log);
                    });
                });
            }
        } else if (codeName !== 'missing' && necessaryName === 'order new') {
            // Сохраняем начальные данные
            var debugLogs = [];
            debugLogs.push('Выполнение ветки: order new');
            debugLogs.push('Начальный codeName: ' + codeName + ' Тип: ' + typeof codeName);

            setHiddenInput('use_tdr', '1');
            setHiddenInput('use_process_forms', '0');

            var conditionId = null;

            // Нормализация codeName для сравнения
            var normalizedCodeName = codeName.toString().trim().toLowerCase();
            debugLogs.push('Нормализованный codeName: ' + normalizedCodeName + ' Длина: ' + normalizedCodeName.length);

            // Проверка структуры select
            debugLogs.push('Структура select c_conditions_id:');
            debugLogs.push($('#c_conditions_id').html());

            // Вывод всех доступных опций для отладки
            debugLogs.push('Все доступные опции в select:');
            var options = $('#c_conditions_id option');
            debugLogs.push('Количество опций: ' + options.length);

            options.each(function(index) {
                var condName = $(this).attr('data-title');
                var condValue = $(this).val();
                var optionText = $(this).text();
                debugLogs.push('Опция #' + (index + 1) + ':');
                debugLogs.push('  - Значение: ' + condValue);
                debugLogs.push('  - Название: ' + condName);
                debugLogs.push('  - Текст: ' + optionText);
                debugLogs.push('  - Нормализованное название: ' + (condName ? condName.toString().trim().toLowerCase() : 'null'));
            });

            $('#c_conditions_id option').each(function() {
                var condName = $(this).attr('data-title');
                var condValue = $(this).val();

                // Нормализация condName для сравнения
                var normalizedCondName = condName ? condName.toString().trim().toLowerCase() : null;

                debugLogs.push('Сравнение: ' + JSON.stringify({
                    codeName: normalizedCodeName,
                    condName: normalizedCondName,
                    совпадают: normalizedCondName === normalizedCodeName,
                    codeNameТип: typeof normalizedCodeName,
                    condNameТип: typeof normalizedCondName
                }));

                if (normalizedCondName && normalizedCondName === normalizedCodeName) {
                    conditionId = condValue;
                    debugLogs.push('Найдено точное соответствие: ' + JSON.stringify({
                        id: conditionId,
                        название: condName,
                        нормализованноеНазвание: normalizedCondName
                    }));
                    return false;
                }
            });

            if (conditionId) {
                setHiddenInput('conditions_id', conditionId);
                debugLogs.push('Установлен conditions_id: ' + conditionId);
            } else {
                debugLogs.push("Не найдено точное соответствие для codeName: " + normalizedCodeName);
                debugLogs.push("Проверьте значения в логах");
                setHiddenInput('conditions_id', '1');
            }

            // Сохраняем логи в localStorage с уникальным ключом
            var timestamp = new Date().getTime();
            localStorage.setItem('debugLogs_' + timestamp, JSON.stringify(debugLogs));

            // Добавляем кнопку для просмотра логов
            if (!$('#viewLogsBtn').length) {
                $('body').append('<button id="viewLogsBtn" style="position: fixed; top: 10px; right: 10px; z-index: 9999;">Показать логи</button>');
                $('#viewLogsBtn').click(function() {
                    var allLogs = [];
                    for (var i = 0; i < localStorage.length; i++) {
                        var key = localStorage.key(i);
                        if (key.startsWith('debugLogs_')) {
                            var logs = JSON.parse(localStorage.getItem(key));
                            allLogs = allLogs.concat(logs);
                        }
                    }
                    console.log('Все сохраненные логи:');
                    allLogs.forEach(function(log) {
                        console.log(log);
                    });
                });
            }
        }
    }
</script>
