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
                <h4 class="text-primary">{{__('Add Component Inspection')}}</h4>
                <h4 class="text-primary"> {{__('Work Order')}} {{$current_wo->number}}</h4>
            </div>
            <div class="card-body">
                <form id="createForm" class="createForm" role="form" method="POST"
                      action="{{route('admin.tdrs.store')}}" enctype="multipart/form-data">
                    @csrf
{{--                    <input type="hidden" name="use_tdr" value="true">--}}

                    <input type="hidden" name="workorder_id" value="{{$current_wo->id }}">

                    <div class="form-group  d-flex">
                        <label for="i_component_id" class="form-label pe-2">Component</label>
                        <select name="component_id" id="i_component_id" class="form-control" style="width: 350px">
                            <option selected value="">---</option>
                            @foreach($components as $component)
                                <option value="{{ $component->id }}"
                                        data-has_assy_part_number="{{ $component->assy_part_number ? 'true' : 'false' }}"
                                        data-title="{{$component->name}}">
                                    {{$component->part_number}} ({{ $component->name }})
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
                            <select name="codes_id" id="codes_id" class="form-control" style="width: 230px">
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
                                    style="width: 230px">
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
                    <div class="form-group m-2" id="conditions" style="visibility: hidden">
                        <label for="c_conditions_id" class="form-label pe-2">Conditions</label>
                        <select name="conditions_id" id="c_conditions_id" class="form-control"
                                style="width: 278px">
                            <option value=""  selected>---</option> <!-- Пустое значение по умолчанию -->
                            @foreach($component_conditions as $component_condition)
                                <option value="{{ $component_condition->id }}" data-title="{{ $component_condition->name }}">
                                    {{ $component_condition->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-primary mt-3">{{ __('Save') }}</button>
                        <a href="{{ route('admin.tdrs.show', ['tdr'=>$current_wo->id]) }}"
                           class="btn btn-outline-secondary mt-3">{{ __('Cancel') }} </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальные окна -->
{{--    @include('admin.tdrs.partials.component-modal')--}}

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

    <!-- Добавьте перед вашим скриптом -->
{{--    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>--}}






@endsection
@section('scripts')
    <script>
        // Получить элемент select
        const selectElement = document.getElementById('i_component_id');


        // Слушатель изменения значения
        selectElement.addEventListener('change', function() {
            const selectedValue = this.value;
            console.log('Selected component_id:', selectedValue);

            // Если нужно получить data-атрибуты выбранного option
            const selectedOption = this.options[this.selectedIndex];
            const hasAssyPartNumber = selectedOption.getAttribute('data-has_assy_part_number');
            const title = selectedOption.getAttribute('data-title');

            console.log('Has assy part number:', hasAssyPartNumber);
            console.log('Title:', title);
        });



        // --------------------------------- Select 2 --------------------------------------------------------

        $(document).ready(function () {
            $('#i_component_id').select2({
                placeholder: '---',

                theme: 'bootstrap-5',
                allowClear: true
            });
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
    </script>

@endsection
