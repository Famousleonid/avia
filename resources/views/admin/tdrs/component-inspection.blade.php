@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 900px;
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

                    <div class="row">
                        <div class="col">
                            <label for="i_component_id" class="form-label pe-2">Component</label>
                            <div class="form-group ">
                                <select name="component_id" id="i_component_id" class="form-control" style="width: 400px">
                                    <option selected value="">---</option>
                                    @foreach($components as $component)
                                        <option value="{{ $component->id }}"
                                                data-has_assy="{{ $component->assy_part_number ? 'true' : 'false' }}"
                                                data-title="{{ $component->name }}">
                                            {{ $component->ipl_num }} : {{ $component->part_number }} - {{$component->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mt-1 d-flex">
                                <button type="button" class="btn btn-link p-0 me-3" data-bs-toggle="modal"
                                        data-bs-target="#addComponentModal">{{ __('Add Component') }}
                                </button>
                                <button type="button" class="btn btn-link p-0" id="editComponentBtn">
                                    {{ __('Edit Component') }}
                                </button>
                            </div>
                        </div>
                        <div class="col">
                            <label for="i_manual_id" class="form-label pe-2">Manual</label>
                            <div class="form-group ">
                                <select name="manual_id" id="i_manual_id" class="form-control" style="width: 400px">
                                    <option value="">---</option>
                                    @foreach($manuals as $manual)
                                        <option value="{{ $manual->id }}"
                                            {{ $manual->id == $manual_id ? 'selected' : '' }}> <!-- Выделить текущий manual -->
                                            {{ $manual->number }} : {{ $manual->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
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
                                                    {{ $component->assy_part_number ?: $component->part_number }} - {{ $component->name }} ({{ $component->ipl_num }})
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
                        <a href="{{ route('tdrs.show', ['id'=>$current_wo->id]) }}"
                           class="btn btn-outline-secondary mt-3">{{ __('Cancel') }} </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальные окна -->
    <!-- Modal - Add component -->
    <div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel"
         aria-hidden="true" >
        <div class="modal-dialog modal-l" >
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addComponentModalLabel">{{ __('Add Component') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form action="{{ route('components.storeFromInspection') }}" method="POST" id="addComponentForm">
                    @csrf

                    <div class="modal-body" >
                        <input type="hidden" name="manual_id" id="addComponentManualId" value="{{$current_wo->unit->manual_id}}">
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
                                    <div class="mt-2">
                                        <label for="units_assy">{{ __('Units per Assy') }}</label>
                                        <input id='units_assy' type="text" class="form-control"
                                               name="units_assy" placeholder="Enter units per assembly">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex ">
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
                                <input class="form-check-input" type="checkbox"  id="is_bush" name="is_bush">
                                <label class="form-check-label" for="is_bush">
                                    Is Bush
                                </label>
                            </div>
                            <!-- Bush IPL Number field - показывается только когда Is Bush отмечен -->
                            <div class="form-group ms-3" id="bush_ipl_container" style="display: none;">
                                <div class="d-flex">
                                    <label for="bush_ipl_num">{{ __('Initial Bushing IPL Number') }}</label>
                                    <input id='bush_ipl_num' type="text" class="form-control" name="bush_ipl_num"
                                           pattern="^\d+-\d+[A-Za-z]?$"
                                           title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B)"
                                    style="width: 100px">
                                </div>
                            </div>
                        </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Save Component</button>
                            </div>



                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal - Edit component -->
    <div class="modal fade" id="editComponentModal" tabindex="-1" aria-labelledby="editComponentModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-l">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="editComponentModalLabel">{{ __('Edit Component') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" id="editComponentForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                    <input type="hidden" name="manual_id" value="{{ $current_wo->unit->manual_id }}">

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_name">{{ __('Name') }}</label>
                            <input id="edit_name" type="text" class="form-control" name="name" required>
                        </div>
                        <div class="d-flex">
                            <div class="d-flex">
                                <div class="m-3">
                                    <div>
                                        <label for="edit_ipl_num">{{ __('IPL Number') }}</label>
                                        <input id="edit_ipl_num" type="text" class="form-control" name="ipl_num" required>
                                    </div>
                                    <div class="mt-2">
                                        <label for="edit_part_number">{{ __('Part Number') }}</label>
                                        <input id="edit_part_number" type="text" class="form-control" name="part_number" required>
                                    </div>
                                    <div class="mt-2">
                                        <label for="edit_eff_code">{{ __('EFF Code') }}</label>
                                        <input id="edit_eff_code" type="text" class="form-control" name="eff_code">
                                    </div>
                                    <div class="mt-2">
                                        <label for="edit_units_assy">{{ __('Units per Assy') }}</label>
                                        <input id="edit_units_assy" type="text" class="form-control" name="units_assy">
                                    </div>
                                    <div class="mt-2">
                                        <label>{{ __('Image') }}</label>
                                        <input type="file" name="img" class="form-control">
                                    </div>
                                </div>

                                <div class="m-3">
                                    <div>
                                        <label for="edit_assy_ipl_num">{{ __('Assembly IPL Number') }}</label>
                                        <input id="edit_assy_ipl_num" type="text" class="form-control" name="assy_ipl_num">
                                    </div>
                                    <div class="mt-2">
                                        <label>{{ __('Assy Image') }}</label>
                                        <input type="file" name="assy_img" class="form-control">
                                    </div>
                                    <div class="mt-2">
                                        <label for="edit_assy_part_number">{{ __('Assembly Part Number') }}</label>
                                        <input id="edit_assy_part_number" type="text" class="form-control" name="assy_part_number">
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="edit_log_card" name="log_card">
                                        <label class="form-check-label" for="edit_log_card">
                                            Log Card
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="edit_repair" name="repair">
                                        <label class="form-check-label" for="edit_repair">
                                            Repair
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="edit_is_bush" name="is_bush">
                                        <label class="form-check-label" for="edit_is_bush">
                                            Is Bush
                                        </label>
                                    </div>
                                    <div class="mt-2" id="edit_bush_ipl_container" style="display: none;">
                                        <label for="edit_bush_ipl_num">{{ __('Initial Bushing IPL Number') }}</label>
                                        <input id="edit_bush_ipl_num" type="text" class="form-control" name="bush_ipl_num"
                                               pattern="^\d+-\d+[A-Za-z]?$"
                                               title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B)"
                                               style="width: 140px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">{{ __('Save changes') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Добавьте перед вашим скриптом -->
    {{--    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>--}}



@endsection
@section('scripts')
    <script>
        $(document).ready(function () {

// Устанавливаем значение по умолчанию для Manual
            const defaultManualId = {{ $manual_id }};
            if (defaultManualId) {
                $('#i_manual_id').val(defaultManualId).trigger('change');
                // Устанавливаем начальное значение manual_id в модальном окне Add Component
                $('#addComponentManualId').val(defaultManualId);
            }




            // Инициализация Select2
            $('#i_component_id, #codes_id, #necessaries_id, #c_conditions_id').select2({
                placeholder: '---',
                theme: 'bootstrap-5',
                allowClear: true,
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
            // Инициализация Select2 для Manual
            $('#i_manual_id').select2({
                placeholder: '---',
                theme: 'bootstrap-5',
                allowClear: true
            });

            applyTheme();

            // Инициализация Select2 для нового select
            $('#order_component_id').select2({
                placeholder: '---',
                theme: 'bootstrap-5',
                allowClear: true
            });

            // Функция для показа/скрытия поля Bush IPL Number
            function toggleBushIPL() {
                const isBushCheckbox = document.getElementById('is_bush');
                const bushIPLContainer = document.getElementById('bush_ipl_container');
                const bushIPLInput = document.getElementById('bush_ipl_num');

                if (isBushCheckbox.checked) {
                    bushIPLContainer.style.display = 'block';
                    bushIPLInput.required = true;
                } else {
                    bushIPLContainer.style.display = 'none';
                    bushIPLInput.required = false;
                    bushIPLInput.value = ''; // Очищаем поле при скрытии
                }
            }

            // Привязываем обработчик события к чекбоксу Is Bush
            $('#is_bush').on('change', toggleBushIPL);


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
                const codeName = selectedCode.data('title');
                const selectedNecessary = $('#necessaries_id').find('option:selected');
                const necessaryName = selectedNecessary.data('title');
                const selectedComponent = $('#i_component_id').find('option:selected');
                const hasAssy = selectedComponent.data('has_assy') === true;

                // Ничего не показываем до выбора кода
                if (!codeName) {
                    hideAllGroups();
                    return;
                }

                // 1. Поле количества (qty)
                $('#qty').toggle(codeName === "Missing" || necessaryName === "Order New");

                // 2. Группа необходимых действий (necessary)
                $('#necessary').toggle(codeName && codeName !== "Missing");

                // 3. Группа серийных номеров (sns-group)
                if (codeName && codeName !== "Missing" && necessaryName && necessaryName !== "Order New") {
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
                if (necessaryName === 'Order New') {
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
                updateFieldVisibility();
                $('#necessaries_id').val(null).trigger('change');
            });

            $('#necessaries_id').on('change', updateFieldVisibility);

            // Инициализация при загрузке - скрываем все группы кроме основных
            hideAllGroups();

            // ----------------- Edit Component Modal -------------------------
            $('#editComponentBtn').on('click', function (e) {
                e.preventDefault();

                var componentId = $('#i_component_id').val();

                if (!componentId) {
                    alert('Select component first.');
                    return;
                }

                var url = '{{ route('components.showJson', ['component' => '__ID__']) }}'.replace('__ID__', componentId);

                $.get(url, function (response) {
                    if (!response.success) {
                        alert('Failed to load component data.');
                        return;
                    }

                    var c = response.component;

                    $('#edit_name').val(c.name);
                    $('#edit_ipl_num').val(c.ipl_num);
                    $('#edit_part_number').val(c.part_number);
                    $('#edit_assy_ipl_num').val(c.assy_ipl_num);
                    $('#edit_assy_part_number').val(c.assy_part_number);
                    $('#edit_eff_code').val(c.eff_code);
                    $('#edit_units_assy').val(c.units_assy);

                    $('#edit_log_card').prop('checked', c.log_card);
                    $('#edit_repair').prop('checked', c.repair);
                    $('#edit_is_bush').prop('checked', c.is_bush);

                    if (c.is_bush) {
                        $('#edit_bush_ipl_container').show();
                        $('#edit_bush_ipl_num').val(c.bush_ipl_num);
                    } else {
                        $('#edit_bush_ipl_container').hide();
                        $('#edit_bush_ipl_num').val('');
                    }

                    var formAction = '{{ route('components.updateFromInspection', ['component' => '__ID__']) }}'
                        .replace('__ID__', componentId);
                    $('#editComponentForm').attr('action', formAction);

                    // Открываем модалку только после успешной загрузки данных
                    $('#editComponentModal').modal('show');
                }).fail(function () {
                    alert('Error loading component.');
                });
            });

            $('#edit_is_bush').on('change', function () {
                if ($(this).is(':checked')) {
                    $('#edit_bush_ipl_container').show();
                } else {
                    $('#edit_bush_ipl_container').hide();
                    $('#edit_bush_ipl_num').val('');
                }
            });
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
                setHiddenInput('necessaries_id', '2');
                setHiddenInput('conditions_id', '1');
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
                } else {
                    setHiddenInput('conditions_id', '39');
                }
            } else if (codeName !== 'missing' && necessaryName !== 'order new') {
                setHiddenInput('use_tdr', '1');
                setHiddenInput('use_process_forms', '1');
            }
        });


        // Функция для загрузки компонентов по manual_id
        function loadComponentsByManual(manualId) {
            const ajaxUrl = '{{ route("api.get-components-by-manual") }}';
            console.log('Loading components for manual_id:', manualId);

            $.ajax({
                url: ajaxUrl,
                method: 'GET',
                data: {
                    manual_id: manualId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // Очищаем текущие опции в основном дропдауне компонентов
                    $('#i_component_id').empty().append('<option value="">---</option>');

                    // Очищаем текущие опции в дропдауне заказа компонентов
                    $('#order_component_id').empty().append('<option value="">---</option>');

                    // Добавляем новые опции в основной дропдаун
                    response.components.forEach(function(component) {
                        $('#i_component_id').append(
                            '<option value="' + component.id + '" ' +
                            'data-has_assy="' + (component.assy_part_number ? 'true' : 'false') + '" ' +
                            'data-title="' + component.name + '">' +
                            component.ipl_num + ' : ' + component.part_number + ' - ' + component.name +
                            '</option>'
                        );

                        // Добавляем те же опции в дропдаун заказа компонентов
                        // Используем assy_part_number если есть, иначе part_number
                        const displayPartNumber = component.assy_part_number || component.part_number;
                        $('#order_component_id').append(
                            '<option value="' + component.id + '">' +
                            displayPartNumber + ' - ' + component.name + ' (' + component.ipl_num + ')' +
                            '</option>'
                        );
                    });

                    // Обновляем Select2 для обоих дропдаунов
                    $('#i_component_id').trigger('change');
                    $('#order_component_id').trigger('change');
                },
                error: function(xhr, status, error) {
                    console.error('Ошибка загрузки компонентов:', error);
                }
            });
        }

        // Обработчик изменения Manual
        $('#i_manual_id').on('change', function() {
            const selectedManualId = $(this).val();

            // Обновляем manual_id в модальном окне Add Component
            $('#addComponentManualId').val(selectedManualId || {{ $manual_id }});

            if (selectedManualId) {
                loadComponentsByManual(selectedManualId);
            } else {
                // Если manual не выбран, загружаем компоненты по начальному manual_id
                const defaultManualId = {{ $manual_id }};
                if (defaultManualId) {
                    loadComponentsByManual(defaultManualId);
                } else {
                    // Если нет начального manual_id, очищаем дропдауны
                    $('#i_component_id').empty().append('<option value="">---</option>').trigger('change');
                    $('#order_component_id').empty().append('<option value="">---</option>').trigger('change');
                }
            }
        });

        // Обновляем manual_id в модальном окне при его открытии
        $('#addComponentModal').on('show.bs.modal', function() {
            const selectedManualId = $('#i_manual_id').val();
            $('#addComponentManualId').val(selectedManualId || {{ $manual_id }});
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
@endsection
