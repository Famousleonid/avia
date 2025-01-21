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
                <h4 class="text-primary">{{__('Add Unit Inspection')}}</h4>
                <h4 class="text-primary"> {{__('Work Order')}}
                    {{$current_wo->number}}</h4>

            </div>

        <div class="card-body" id="create_div_inputs">
            <form id="createForm" class="createForm" role="form" method="POST" action="{{route('admin.tdrs.store')}}"
                  enctype="multipart/form-data" id="createComponentForm">
                @csrf

                <input type="hidden" name="workorder_id" value="{{
                $current_wo->id }}">


                <div class="">

                    <div class="d-flex">
                        <div class=" form-group m-2">
                            <label for="component_id"
                                   class="form-label">Component</label>
                            <select name="component_id" id="component_id"
                                    class="form-control" style="width: 230px">
                                <option disabled selected value="">---</option>
                                @foreach($components as $component)
                                    <option
                                        value="{{ $component->id }}"
                                        data-title="{{$component->name}}">
                                        {{$component->part_number}}
                                        ( {{ $component->name }} )

                                    </option>
                                @endforeach

                            </select>

                        </div>
                        <div class="m-2">
                            <div class="">
                                <label class="pb-1" for="serial_number">{{ __
                                ('Serial
                                 Number')
                                }}</label>
                                <input id='serial_number' type="text"
                                       class="form-control m-1"
                                       name="serial_number"
                                       required>
                            </div>
                        </div>
                        <div class="m-2">
                            <div class="">
                                <label class="pb-1" for="assy_serial_number">{{
                                __('Assy
                                Serial Number')
                                }}</label>
                                <input id='assy_serial_number' type="text"
                                       class="form-control m-1"
                                       name="assy_serial_number"
                                       required>
                            </div>
                        </div>


                </div>
                    <div class=" form-group m-2">
                        <label for="conditions_id"
                               class="form-label pe-2">Condition</label>
                        <select name="conditions_id" id="conditions_id"
                                class="form-control" style="width: 494px">
                            <option disabled selected value="">---</option>
                            @foreach($conditions as $condition)
                                <option
                                    value="{{ $condition->id }}"
                                    data-title="{{$condition->name}}">
                                    {{$condition->name}}

                                </option>
                            @endforeach

                        </select>


                    </div>


                <div class="text-end">
                    <button type="submit" class="btn btn-outline-primary
                        mt-3 ">{{ __('Save') }}</button>
                    <a href="{{ route('admin.tdrs.show',
                    ['tdr'=>$current_wo->id]) }}"
                       class="btn btn-outline-secondary mt-3">{{ __('Cancel') }} </a>
                </div>
            </form>
        </div>

        </div>
    </div>

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




    </script>

@endsection
