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
            <div class="card-hrader">
                <h4 class="text-primary">{{__('Add Extra Component Processes')}}</h4>
                <h4 class="text-primary"> {{__('Work Order')}} {{$current_wo->number}}</h4>
            </div>
            <div class="card-body">
                <form id="createForm" role="form" method="POST"
                      action="{{route('extra_processes.store')}}" class="createForm">
                    @csrf
                    <input type="hidden" name="workorder_id" value="{{$current_wo->id }}">

                    <div class="form-group d-flex">
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
                    <div id="processes-container" data-manual-id="{{ $manual_id }}">
                        <!-- Начальная строка -->
                        <div class="process-row mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="process_names">Process Name:</label>
                                    <select name="processes[0][process_names_id]" class="form-control select2-process" required>
                                        <option value="">Select Process Name</option>
                                        @foreach ($processNames as $processName)
                                            <option value="{{ $processName->id }}">{{ $processName->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="process">Processes (Specification):</label>

                                    <button type="button" class="btn btn-link mb-1" data-bs-toggle="modal"
                                            data-bs-target="#addProcessModal">

                                        <img src="{{ asset('img/plus.png')}}" alt="arrow"
                                             style="width: 20px;" class="" >
                                    </button>


                                    <div class="process-options">
                                        <!-- Здесь будут чекбоксы для выбранного имени процесса -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-primary mt-3">{{ __('Save') }}</button>
                        <a href="{{ route('extra_processes.show_all',['id'=>$current_wo->id]) }}"
                           class="btn btn-outline-secondary mt-3">{{ __('Cancel') }}</a>
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
                                    {{--                                    <div class="">--}}
                                    {{--                                        <label for="ipl_num">{{ __('IPL Number') }}</label>--}}
                                    {{--                                        <input id='ipl_num' type="text" class="form-control" name="ipl_num" required>--}}
                                    {{--                                    </div>--}}
                                    <div class="">
                                        <label for="ipl_num">{{ __('IPL Number') }}</label>
                                        <input id='ipl_num' type="text" class="form-control" name="ipl_num"
                                               pattern="^\d+-\d+[A-Za-z]?$"
                                               title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B)"
                                               required>
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
                                        <input id='assy_ipl_num' type="text" class="form-control" name="assy_ipl_num"
                                               pattern="^\d+-\d+[A-Za-z]?$"
                                               title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B)"
                                        >
                                    </div>

                                    <div class=" col-xs-12 col-sm-12 col-md-12 mt-2" >
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
    <!-- Модальное окно для добавления процесса -->
    <div class="modal fade" id="addProcessModal" tabindex="-1" aria-labelledby="addProcessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Заголовок модального окна -->
                <div class="modal-header">
                    <h5 class="modal-title" id="addProcessModalLabel">
                        Enter Process (<span id="modalProcessName"></span>)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Тело модального окна -->
                <div class="modal-body">
                    <!-- Поле для ввода нового процесса -->
                    <div class="mb-3">
                        <label for="newProcessInput" class="form-label">New Process</label>
                        <input type="text" class="form-control" id="newProcessInput" placeholder="Enter new process">
                    </div>
                    <!-- Секция "Existing Processes" – подгружаются availableProcesses -->
                    <div class="mb-3">
                        <h6>Existing Processes</h6>
                        <div id="existingProcessContainer">
                            Loading processes...
                        </div>
                    </div>
                    <!-- Скрытое поле для хранения выбранного process_name_id -->
                    <input type="hidden" id="modalProcessNameId">
                </div>
                <!-- Футер модального окна -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveProcessModal">Save Process</button>
                </div>
            </div>
        </div>
    </div>




@endsection
@section('scripts')


    <script>





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
    </script>
@endsection
