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
                <h4 class="text-primary">{{ __('Add Unit Inspection') }}</h4>
                <h4 class="text-primary">{{ __('Work Order') }} {{$current_wo->number}}</h4>
            </div>
            <div class="card-body" id="create_div_inputs">
                <form id="createForm" method="POST" action="{{ route('tdrs.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                    <input type="hidden" name="qty" value="1">
                    <input type="hidden" name="component_id" value="">
                    <input type="hidden" name="serial_number" value="null">
                    <input type="hidden" name="assy_serial_number" value="null">
                    <input type="hidden" name="codes_id" value="">
                    <input type="hidden" name="necessaries_id" value=" ">
                    <input type="hidden" name="use_tdr" value="true">

                    <!-- Поля, специфичные для Unit Inspection -->
                    <div class="form-group m-2">
                        <label for="u_conditions_id" class="form-label pe-2">Condition</label>
                        <select name="conditions_id" id="u_conditions_id" class="form-control" style="width:575px">
                            <option selected value="">---</option>
                            @foreach($unit_conditions as $unit_condition)
                                @if($unit_condition->name != 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')
                                    <option value="{{ $unit_condition->id }}" data-title="{{ $unit_condition->name }}">
                                        {{ $unit_condition->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#addConditionModal">
                            {{ __('Add Condition') }}
                        </button>
                        <button type="button" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#manageConditionModal">
                            {{ __('Manage Condition') }}
                        </button>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-primary mt-3">{{ __('Save') }}</button>
                        <a href="{{ route('tdrs.show', ['tdr' => $current_wo->id]) }}" class="btn btn-outline-secondary mt-3">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно для добавления условия (можно вынести в отдельный файл, если используется на нескольких страницах) -->
    <div class="modal fade" id="addConditionModal" tabindex="-1" aria-labelledby="addConditionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h5 class="modal-title" id="addConditionModalLabel">{{ __('Add Condition') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form action="{{ route('conditions.store') }}" method="POST" id="addConditionForm">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="unit" value="1">
                        <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                        <div class="form-group">
                            <label for="name">{{ __('Name') }}</label>
                            <input id="name" type="text" class="form-control" name="name" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary m-3">Save Condition</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            $('#u_conditions_id').select2({
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

