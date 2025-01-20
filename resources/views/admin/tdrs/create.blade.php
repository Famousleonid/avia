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
{{--                <h4 class="text-primary"> WorkOrder {{$current_wo->number}}</h4>--}}
{{--                <h4 class="text-primary"> Unit {{$current_wo->unit->part_number}}</h4>--}}
{{--                <h4 class="text-primary"> Manual {{$current_wo->unit->manuals->number}}</h4>--}}

                <h4 class="text-primary">{{__('Add Unit Inspection')}}</h4>
            </div>

        <div class="card-body" id="create_div_inputs">
            <form id="createForm" class="createForm" role="form" method="POST" action="{{route('admin.tdrs.store')
                }}" enctype="multipart/form-data" id="createComponentForm">
                @csrf
                <!-- Скрытое поле для передачи workorder_id -->
                <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">

                <div class="">
                    <div class="d-flex m-1 justify-content-between">
                        <div>
                            <label for="" class="p-1">{{__('Component')}}</label>
                            <div class="d-flex">
                                <select name="component_id" id="component_id" class="form-control" style="width: 200px">
                                    <option disabled selected value="">---</option>
                                    @foreach($components as $component)
                                        <option
                                            value="{{ $component->id }}"
                                            data-title="{{$component->part_number}}">
                                            {{$component->part_number}}
                                            ( {{ $component->name }} )
                                        </option>
                                    @endforeach
                                </select>

                            </div>
                            <button type="button" class="btn btn-link" data-bs-toggle="modal"
                                    data-bs-target="#addComponentModal">{{ __('Add Component') }}
                            </button>

                    </div>


                        <div class="form-group">
                            <div class="m-1">
                                <label for="serial_number" class="pb-1">{{ __('Serial Number') }} </label>
                                <input id='serial_number' type="text" class="form-control" name="serial_number"
                                       required>
                            </div>
                        </div>
                        <div class="">
                            <div class="m-1">
                                <label for="assy_serial_number" class="pb-1">{{ __('Assy Serial Number') }}</label>
                                <input id='assy_serial_number' type="text" class="form-control"
                                       name="assy_serial_number" >
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="name">{{ __('Condition') }}</label>

                        <select name="conditions_id" id="conditions_id" class="form-control">
                            <option disabled selected value="">---</option>
                            @foreach($conditions as $condition)
                                <option
                                    value="{{ $condition->id }}"
                                    data-title="{{$condition->name}}">
                                    {{ $condition->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex justify-content-center">
                        <div class="form-group m-3">
                            <label for="name">{{ __('Necessaries') }}</label>

                            <select name="necessaries_id" id="necessaries_id" class="form-control" style="width: 250px">
                                <option  selected value="">---</option>
                                @foreach($necessaries as $necessary)
                                    <option
                                        value="{{ $necessary->id }}"
                                        data-title="{{$necessary->name}}">
                                        {{ $necessary->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group m-3">
                            <label for="codes_id">{{ __('Codes') }}</label>

                            <select name="codes_id" id="codes_id" class="form-control" style="width: 250px">
                                <option  selected value="">---</option>
                                @foreach($codes as $code)
                                    <option
                                        value="{{ $code->id }}"
                                        data-title="{{$code->name}}">
                                        {{ $code->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <div class="form-check ">
                            <label class="form-check-label" for="use_tdr">Use TDR</label>
                            <input class="form-check-input" type="checkbox" name="use_tdr" id="use_tdr">
                        </div>
                        <div class="form-check ">
                            <label class="form-check-label" for="use_process_form">Use Process Form</label>
                            <input class="form-check-input" type="checkbox" name="use_process_form" id="use_process_form">
                        </div>
                        <div class="form-check ">
                            <label class="form-check-label" for="use_log_card">Use Log Card</label>
                            <input class="form-check-input" type="checkbox" name="use_log_card" id="use_log_card">
                        </div>
                        <div class="form-check ">
                            <label class="form-check-label" for="Use_extra_process_form">Use Extra Process Form</label>
                            <input class="form-check-input" type="checkbox" name="Use_extra_process_form"
                                   id="Use_extra_process_form">
                        </div>
                    </div>


                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-outline-primary
                        mt-3 ">{{ __('Save') }}</button>
                    <a href="{{ route('admin.tdrs.show', ['tdr' => $current_wo->id]) }}"
                       class="btn btn-outline-secondary mt-3">{{ __('Cancel') }} </a>
                </div>


            </form>
        </div>

        <!-- Modal - Add component -->
        <div class="modal fade" id="addComponentModal" tabindex="-1" aria-labelledby="addComponentModalLabel"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-gradient">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addComponentModalLabel">{{ __('Add Component') }}</h5>
                        <h5 class="" id="">{{$component->manuals->id}}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <form action="" method="POST" id="addComponentForm">
                        @csrf
                        <div class="modal-body">
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
                    </form>
                </div>
            </div>
        </div>
    </div></div>


    <script>
        window.addEventListener('load', function () {


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
                $('#condition_id').select2({
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
    </script>
@endsection
