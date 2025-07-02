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
                <h4 class="text-primary">{{__('Add Component')}}</h4>
            </div>
            <div class="card-body" id="create_div_inputs">
                <form id="createForm" class="createForm" role="form" method="POST" action="{{route('components.store')
                }}" enctype="multipart/form-data" id="createComponentForm">
                    @csrf

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

                                </div>

                                <div class="m-3">
                                    <div class="">
                                        <label for="assy_ipl_num">{{ __('Assembly IPL Number') }}</label>
                                        <input id='assy_ipl_num' type="text" class="form-control" name="assy_ipl_num"
                                               pattern="^$|^\d+-\d+[A-Za-z]?$"
                                               title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B) or leave empty">
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
                    <div class="justify-content-between d-flex">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"  id="log_card" name="log_card">
                            <label class="form-check-label" for="log_card">
                                Log Card
                            </label>
                        </div>
                        <div class="text-end">

                            <button type="submit" class="btn btn-outline-primary
                        mt-3 ">{{ __('Save') }}</button>
                            <a href="{{ route('components.index') }}"
                               class="btn btn-outline-secondary mt-3">{{ __('Cancel') }} </a>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>


    <script>

        window.addEventListener('load', function () {


            // --------------------------------- Select 2 --------------------------------------------------------

            $(document).ready(function () {
                $('#manual_id').select2({
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

