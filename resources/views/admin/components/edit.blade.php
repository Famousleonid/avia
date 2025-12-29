@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 700px;
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

    <div class="container  mt-3">
        <div class="card bg-gradient">
            <div class="card-header">
                <h4 class="text-primary">{{__('Edit Component')}}</h4>
            </div>
            <div class="card-body" id="edit_div_inputs">
                <form id="editForm" class="editForm" role="form" method="POST"
                      action="{{route('components.update',
                  ['component'=>$current_component->id])}}"
                      enctype="multipart/form-data" id="editComponentForm">
                    @csrf
                    @method('PUT')

                    <div class="">
                        <div class=" form-group mb-3">
                            <label for="manual_id" class="form-label">CMM</label>
                            <select name="manual_id" id="manual_id"
                                    class="form-control mt-2">
                                <option  selected
                                        value="{{$current_component->manual_id}}">
                                    {{$current_component->manuals->number}}
                                    ( {{ $current_component->manuals->title }} -
                                    {{$current_component->manuals->unit_name_training}} )</option>
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
                        <div class="form-group mt-3">
                            <label for="name">{{ __('Name') }}</label>
                            <input id='name' type="text" class="form-control mt-2"
                                   name="name"
                                   value="{{$current_component->name}}" required>
                        </div>
                        <div class="d-flex">

                            <div class="d-flex">
                                <div class="m-3">
                                    <div class="">
                                        <label for="ipl_num">{{ __('IPL Number') }}</label>
                                        <input id='ipl_num' type="text"
                                               class="form-control mt-2"
                                               name="ipl_num"
                                               value="{{$current_component->ipl_num}}" required>
                                    </div>
                                    <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                        <div class="form-group mt-3">
                                            <strong>{{__('Image:')}}</strong>
                                            <div class="d-flex mt-2">
                                                <a href="{{ $current_component->getFirstMediaBigUrl('component') }}" data-fancybox="gallery">
                                                    <img class="me-1" src="{{$current_component->getFirstMediaThumbnailUrl('component') }}"
                                                         width="40" height="40" alt="IMG"/>
                                                </a>
                                                <input type="file" name="img" class="form-control" placeholder="Image">

                                            </div>
                                             </div>
                                    </div>
                                    <div class="mt-3">
                                        <label for="part_number">{{ __('Part Number') }}</label>
                                        <input id='part_number' type="text"
                                               class="form-control mt-2"
                                               name="part_number"
                                               value="{{$current_component->part_number}}"
                                               required>
                                    </div >

                                        <div class="mt-3">
                                            <label for="eff_code">{{ __('EFF Code') }}</label>
                                            <input id='eff_code' type="text"
                                                   class="form-control mt-2"
                                                   name="eff_code" placeholder="Enter EFF code (optional)"
                                                   value="{{$current_component->eff_code}}">
                                        </div>


                                    </div>

                                <div class="m-3">
                                    <div class="">
                                        <label for="assy_ipl_num">{{ __('Assembly IPL Number') }}</label>

                                            <input id='assy_ipl_num' type="text"
                                                   class="form-control mt-2"
                                                   name="assy_ipl_num"
                                                   pattern="^$|^\d+-\d+[A-Za-z]?$"
                                                   title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B) or leave empty"
                                                   value="{{$current_component->assy_ipl_num}}">


                                    </div>

                                    <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
                                        <div class="form-group mt-3">
                                            <strong>{{__(' Assy Image:')}}</strong>
                                            <div class="d-flex mt-2">
                                                <a href="{{ $current_component->getFirstMediaBigUrl('assy_component') }}" data-fancybox="gallery">
                                                    <img class="me-1" src="{{
                                                      $current_component->getFirstMediaThumbnailUrl('assy_component') }}" width="40"
                                                         height="40" alt="IMG"/>
                                                </a>
                                            <input type="file"
                                                   name="assy_img"
                                                   class="form-control "
                                                   placeholder="Image">
                                        </div> </div>
                                    </div>
                                    <div class="mt-3">
                                        <label for="assy_part_number">{{ __(' Assembly Part Number') }}</label>
                                        <input id='assy_part_number'
                                               type="text"
                                               class="form-control mt-2"
                                               name="assy_part_number"
                                               value="{{$current_component->assy_part_number}}">
                                    </div>
                                    <div class="mt-3">
                                        <label for="units_assy">{{ __('Units per Assy') }}</label>
                                        <input id='units_assy' type="text"
                                               class="form-control mt-2"
                                               name="units_assy" placeholder="Enter units per assembly"
                                               value="{{$current_component->units_assy}}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bush IPL Number field - показывается только когда Is Bush отмечен -->
                        <div class="form-group mt-3" id="bush_ipl_container"
                             style="display: {{ $current_component->is_bush ? 'block' : 'none' }};">
                            <label for="bush_ipl_num">{{ __('Initial Bushing IPL Number') }}</label>
                            <input id='bush_ipl_num' type="text" class="form-control" name="bush_ipl_num"
                                   pattern="^\d+-\d+[A-Za-z]?$"
                                   title="The format should be: number-number (for example: 1-200A, 1001-100, 5-398B)"
                                   value="{{ $current_component->bush_ipl_num }}">
                        </div>

                    </div>
                    <div class="justify-content-between d-flex">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"  id="log_card" name="log_card"
                                   value="1" {{ $current_component->log_card ? 'checked' : '' }}>
                            <label class="form-check-label" for="log_card">
                                Log Card
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"  id="repair" name="repair"
                                   value="1" {{ $current_component->repair ? 'checked' : '' }}>
                            <label class="form-check-label" for="repair">
                                Repair
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"  id="is_bush" name="is_bush"
                                   value="1" {{ $current_component->is_bush ? 'checked' : '' }} onchange="toggleBushIPL()">
                            <label class="form-check-label" for="is_bush">
                                Is Bush
                            </label>
                        </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-primary
                        mt-3 ">{{ __('Update') }}</button>
                        <a href="{{ route('components.show', $current_component->manual_id) }}"
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
