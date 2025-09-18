@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 850px;
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
                <h4 class="text-primary">{{__('Add Component Inspection')}}</h4>
                <h4 class="text-primary"> {{__('Work Order')}} {{$current_wo->number}}</h4>
            </div>
            <div class="card-body">
                <form id="createForm" class="createForm" role="form" method="POST"
                      action="{{route('tdrs.store')}}" enctype="multipart/form-data">
                    @csrf
                    {{--                    <input type="hidden" name="use_tdr" value="true">--}}

                    <input type="hidden" name="workorder_id" value="{{$current_wo->id }}">

                    <div class="form-group  d-flex">
                        <label for="i_component_id" class="form-label pe-2">Component</label>

                        <select name="component_id" id="i_component_id" class="form-control" style="width: 550px">
                            <option selected value="">---</option>
                            @foreach($components as $component)
                                <option value="{{ $component->id }}"
                                        data-has_assy="{{ $component->assy_part_number ? 'true' : 'false' }}"
                                        data-title="{{ $component->name }}">
                                    {{ $component->ipl_num }} : {{ $component->part_number }} - {{ $component->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                data-bs-target="#addComponentModal">{{ __('Add Component') }}
                        </button>
                    </div>
                    <div class="row">
                        <div class="col">
                            <!-- Code -->
                            <div class=" form-group m-2">
                                <label for="codes_id" class="form-label pe-2">Code Inspection</label>
                                <select name="codes_id" id="codes_id" class="form-control" style="width: 300px">
                                    <option  selected value="">---</option>
                                    @foreach($codes as $code)
                                        <option value="{{ $code->id }}" data-title="{{$code->name}}">
                                            {{$code->name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Necessaries -->
                            <div class=" form-group m-2" id="necessary" style="display: none">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <label for="necessaries_id" class="form-label pe-2">Necessary to Do</label>
                                        <select name="necessaries_id" id="necessaries_id" class="form-control"
                                                style="width: 230px">
                                            <option  selected value="">---</option>
                                            @foreach($necessaries as $necessary)
                                                <option value="{{ $necessary->id }}" data-title="{{$necessary->name}}">
                                                    {{$necessary->name}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!-- Новый select для выбора компонента заказа -->
                                    <div id="order_component_group" style="display: none; margin-left: 20px;">
                                        <label for="order_component_id" class="form-label pe-2">{{ __('Order Component') }}</label>
                                        <select name="order_component_id" id="order_component_id" class="form-control" style="width: 350px">
                                            <option selected value="">---</option>
                                            @foreach($components as $component)
                                                <option value="{{ $component->id }}">
                                                    {{ $component->part_number }} - {{ $component->name }} ({{ $component->ipl_num }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="m-2">
                                    <label class="" for="description">{{ __('Description ')}}</label>
                                    <input id='description' type="text"
                                           class="form-control " name="description" >
                                </div>
                            </div>
                            <!-- QTY -->
                            <div class="form-group m-2" id="qty" style="display: none">
                                <label class="" for="qty">{{__('QTY')}}</label>
                                <input id="qty" type="number" class="form-control" name="qty" value="1" style="width: 60px">
                            </div>

                            <div class="form-group m-2" id="conditions" style="display: none">
                                <label for="c_conditions_id" class="form-label pe-2" >Conditions</label>
                                <select name="conditions_id" id="c_conditions_id" class="form-control">
                                    <option value=""  selected>---</option> <!-- Пустое значение по умолчанию -->
                                    @foreach($component_conditions as $component_condition)
                                        <option value="{{ $component_condition->id }}" data-title="{{ $component_condition->name }}">
                                            {{ $component_condition->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="m-3"  >
                                <div class="form-group ms-4  "  id="sns-group" style="display: none">
                                    <div class="m-2">
                                        <label class="" for="serial_number">{{ __('Serial Number')}}</label>
                                        <input id='serial_number' type="text"
                                               class="form-control " name="serial_number" >
                                    </div>
                                    <div class="m-2" >
                                        <div class="" id="assy_serial_number_container" >
                                            <label class="" for="assy_serial_number">{{__('Assy Serial Number')}}</label>
                                            <input id='assy_serial_number' type="text"
                                                   class="form-control " name="assy_serial_number" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-primary mt-3">{{ __('Save') }}</button>
                        <a href="{{ route('tdrs.show', ['tdr'=>$current_wo->id]) }}"
                           class="btn btn-outline-secondary mt-3">{{ __('Cancel') }} </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальные окна -->
    <!-- Modal - Add component -->
    <div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addComponentModalLabel">{{ __('Add Component') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form action="{{ route('components.storeFromInspection') }}" method="POST" id="addComponentForm">
                    @csrf

                    <div class="modal-body">
                        <input type="hidden" name="manual_id" value="{{$current_wo->unit->manual_id}}">
                        <input type="hidden" name="current_wo" value="{{$current_wo->id}}">
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
                        <div class="d-flex justify-content-between">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"  id="log_card" name="log_card">
                                <label class="form-check-label" for="log_card">
                                    Log Card
                                </label>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Save Component</button>
                            </div>


                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Добавьте перед вашим скриптом -->
    {{--    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>--}}



@endsection
@push('scripts')
    <script>
        function initComponentInspectionSelects() {
            // Инициализация Select2
            // Сначала удаляем предыдущую инициализацию (если была)
            ['#i_component_id', '#codes_id', '#necessaries_id', '#c_conditions_id', '#order_component_id'].forEach(function(sel){
                try { if ($(sel).data('select2')) { $(sel).select2('destroy'); } } catch(e) {}
            });

            $('#i_component_id, #codes_id, #necessaries_id, #c_conditions_id').select2({
                placeholder: '---',
                theme: 'bootstrap-5',
                allowClear: true,
                minimumResultsForSearch: 0,
                width: 'resolve',
                dropdownAutoWidth: true,
                dropdownParent: $('body'),
                sorter: function(data) {
                    return data.sort(function(a, b) {
                        // Извлекаем IPL номер из текста (всё до первого ":")
                        const aIpl = a.text.split(':')[0].trim();
                        const bIpl = b.text.split(':')[0].trim();

                        // Разбиваем IPL номер на части (например, "1-40" -> ["1", "40"])
                        const aParts = aIpl.split('-');
                        const bParts = bIpl.split('-');

                        // Сравниваем первую часть (до дефиса)
                        const aFirst = parseInt(aParts[0]);
                        const bFirst = parseInt(bParts[0]);
                        if (aFirst !== bFirst) {
                            return aFirst - bFirst;
                        }

                        // Если первые части равны, сравниваем вторую часть
                        const aSecond = aParts[1].replace(/[^0-9]/g, ''); // Убираем буквы
                        const bSecond = bParts[1].replace(/[^0-9]/g, '');
                        const aSecondNum = parseInt(aSecond);
                        const bSecondNum = parseInt(bSecond);

                        if (aSecondNum !== bSecondNum) {
                            return aSecondNum - bSecondNum;
                        }

                        // Если числовые части равны, сравниваем буквенные суффиксы
                        const aSuffix = aParts[1].replace(/[0-9]/g, '');
                        const bSuffix = bParts[1].replace(/[0-9]/g, '');
                        return aSuffix.localeCompare(bSuffix);
                    });
                }
            });

            applyTheme();

            // Инициализация Select2 для нового select
            $('#order_component_id').select2({
                placeholder: '---',
                theme: 'bootstrap-5',
                allowClear: true,
                minimumResultsForSearch: 0,
                width: 'resolve',
                dropdownAutoWidth: true,
                dropdownParent: $('body')
            });

            // Функция скрытия всех дополнительных групп
            function hideAllGroups() {
                $('#necessary').hide();
                $('#qty').hide();
                $('#sns-group').hide();
                $('#conditions').hide(); // Условия всегда скрыты
            }

            // Основная функция обновления видимости полей
            function updateFieldVisibility() {
                const selectedCode = $('#codes_id').find('option:selected');
                const codeName = (selectedCode.attr('data-title') || '').toString();
                const selectedNecessary = $('#necessaries_id').find('option:selected');
                const necessaryName = (selectedNecessary.attr('data-title') || '').toString();
                const selectedComponent = $('#i_component_id').find('option:selected');
                const hasAssy = String(selectedComponent.attr('data-has_assy')) === 'true';

                const codeKey = codeName.trim().toLowerCase();
                const necKey = necessaryName.trim().toLowerCase();

                // Ничего не показываем до выбора кода
                if (!codeName) {
                    hideAllGroups();
                    return;
                }

                // 1. Поле количества (qty)
                $('#qty').toggle(codeKey === 'missing' || necKey === 'order new');

                // 2. Группа необходимых действий (necessary)
                // Для 'Missing' скрывать блок Necessary to Do и Description
                if (codeKey === 'missing') {
                    $('#necessary').hide();
                } else {
                    $('#necessary').toggle(codeKey !== '');
                }

                // 3. Группа серийных номеров (sns-group)
                if (codeKey !== '' && codeKey !== 'missing' && necKey !== '' && necKey !== 'order new') {
                    $('#sns-group').show();

                    if (hasAssy) {
                        $('#serial_number').parent().show();
                        $('#assy_serial_number').parent().show();
                    } else {
                        $('#serial_number').parent().show();
                        $('#assy_serial_number').parent().hide();
                    }
                } else {
                    $('#sns-group').hide();
                }

                // Показать/скрыть select для заказа компонента
                if (necKey === 'order new') {
                    $('#order_component_group').show();
                    // По умолчанию выбрать текущий компонент
                    const currentComponentId = $('#i_component_id').val();
                    $('#order_component_id').val(currentComponentId).trigger('change');
                } else {
                    $('#order_component_group').hide();
                    $('#order_component_id').val('').trigger('change');
                }
            }

            // Обработчики изменений
            $('#i_component_id').on('change', function() {
                // При изменении компонента только сбрасываем другие поля
                $('#codes_id').val(null).trigger('change');
                $('#necessaries_id').val(null).trigger('change');
                hideAllGroups();
            });

            $('#codes_id').on('change', function() {
                // Сначала сбрасываем necessaries, затем обновляем видимость полей
                $('#necessaries_id').val(null).trigger('change');
                updateFieldVisibility();
            });

            $('#necessaries_id').on('change', updateFieldVisibility);

            // Инициализация при загрузке - скрываем все группы кроме основных
            hideAllGroups();
        }

        // Инициализация, полагаемся на подключение jQuery/Select2 через master layout

        $(document).ready(function () {
            initComponentInspectionSelects();
        });

        $('#createForm').on('submit', function(e) {
            // Удаляем все поля с именами use_tdr и use_process_forms, чтобы избежать дублирования
            $('#createForm').find('input[name="use_tdr"]').remove();
            $('#createForm').find('input[name="use_process_forms"]').remove();

            // Получаем значения data-title выбранных опций через attr()
            var codeName = $('#codes_id option:selected').attr('data-title') || '';
            var necessaryName = $('#necessaries_id option:selected').attr('data-title') || '';

            // Приводим значения к нижнему регистру и убираем лишние пробелы
            codeName = codeName.toString().trim().toLowerCase();
            necessaryName = necessaryName.toString().trim().toLowerCase();

            console.log("codeName:", codeName, "necessaryName:", necessaryName);

            // Функция для установки значения поля (создает новое, если поле отсутствует)
            function setHiddenInput(name, value) {
                var $input = $('#createForm').find('input[name="' + name + '"]');
                if ($input.length) {
                    $input.val(value);
                } else {
                    $('<input>').attr({
                        type: 'hidden',
                        name: name,
                        value: value
                    }).appendTo('#createForm');
                }
            }

            // Всегда сохраняем component_id из начального select'а
            setHiddenInput('component_id', $('#i_component_id').val());

            if (codeName === 'missing') {
                setHiddenInput('use_tdr', '0');
                setHiddenInput('use_process_forms', '0');

                // Найти ID "Order New" динамически
                var orderNewId = null;
                $('#necessaries_id option').each(function() {
                    var title = ($(this).attr('data-title') || '').toString().trim().toLowerCase();
                    if (title === 'order new') {
                        orderNewId = $(this).val();
                        return false;
                    }
                });
                if (orderNewId) {
                    setHiddenInput('necessaries_id', orderNewId);
                }

                // Найти ID условия "PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST" динамически
                var missingCondId = null;
                $('#c_conditions_id option').each(function() {
                    var title = ($(this).attr('data-title') || '').toString().trim().toLowerCase();
                    if (title === 'parts missing upon arrival as indicated on parts list') {
                        missingCondId = $(this).val();
                        return false;
                    }
                });
                if (missingCondId) {
                    setHiddenInput('conditions_id', missingCondId);
                }
            } else if (codeName !== 'missing' && necessaryName === 'order new') {
                setHiddenInput('use_tdr', '1');
                setHiddenInput('use_process_forms', '0');

                // Сохраняем order_component_id из Select2
                setHiddenInput('order_component_id', $('#order_component_id').val());

                var conditionId = null;
                var normalizedCodeName = codeName.toString().trim().toLowerCase();

                $('#c_conditions_id option').each(function() {
                    var condName = $(this).attr('data-title');
                    var condValue = $(this).val();
                    var normalizedCondName = condName ? condName.toString().trim().toLowerCase() : null;

                    if (normalizedCondName && normalizedCondName === normalizedCodeName) {
                        conditionId = condValue;
                        return false;
                    }
                });

                if (conditionId) {
                    setHiddenInput('conditions_id', conditionId);
                }
            } else if (codeName !== 'missing' && necessaryName !== 'order new') {
                setHiddenInput('use_tdr', '1');
                setHiddenInput('use_process_forms', '1');
            }
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
    </script>
    {{--    <script>--}}
    {{--        // Выводим сохраненные логи при загрузке страницы--}}
    {{--        $(document).ready(function() {--}}
    {{--            var savedLogs = localStorage.getItem('debugLogs');--}}
    {{--            if (savedLogs) {--}}
    {{--                console.log('Сохраненные логи отладки:');--}}
    {{--                JSON.parse(savedLogs).forEach(function(log) {--}}
    {{--                    console.log(log);--}}
    {{--                });--}}
    {{--                // Очищаем логи после вывода--}}
    {{--                localStorage.removeItem('debugLogs');--}}
    {{--            }--}}
    {{--        });--}}
    {{--    </script>--}}
@endpush
