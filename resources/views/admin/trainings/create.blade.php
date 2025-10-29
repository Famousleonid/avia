@extends('admin.master')

@section('content')

    <style>
        .card {
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

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4>{{ __('Select Unit') }}</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('trainings.store') }}" onsubmit="return validateTrainingDate()">
                    @csrf
                    <input type="hidden" name="return_url" value="{{ request()->get('return_url', url()->previous()) }}">
                    <div class="form-group mt-2">
                        <label for="manuals_id">{{ __('Unit PN') }}</label>
                        <select id="manuals_id" name="manuals_id" class="form-control" required>
                            <option value="">{{ __('Select Unit PN') }}</option>
                            @foreach ($manuals as $manual)
                                @if(!empty($manual->unit_name_training))
                                    <option value="{{ $manual->id }}" 
                                            @if(request('manual_id') == $manual->id) selected @endif>
                                        {{ $manual->unit_name_training }}
                                        ({{ $manual->title }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mt-3">
                        <label for="date_training">{{ __('First Training Date') }}</label>
                        <input type="date" id="date_training" name="date_training" class="form-control" required>
                        <small class="form-text text-muted">
                            <i class="bi bi-info-circle"></i> 
                            If the date is more than 2 years ago, you will need to provide the last training date.
                        </small>
                    </div>

                    <div class="form-group mt-3" id="last_training_date_group" style="display: none;">
                        <label for="last_training_date">{{ __('Last Existing Training Date') }} <span class="text-danger">*</span></label>
                        <input type="date" id="last_training_date" name="last_training_date" class="form-control">
                        <small class="form-text text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Please enter the date of the last existing training. Missing yearly trainings between First Training Date and this date will be created, and a new training will be created with today's date.
                        </small>
                        <div id="last_training_date_error" class="text-danger mt-1" style="display: none;"></div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary mt-3">{{ __('Add Unit') }}</button>
                        @if(request('manual_id'))
                            <a href="{{ request()->get('return_url', url()->previous()) }}" class="btn btn-outline-info mt-3">
                                <i class="bi bi-arrow-left"></i> Back to TDR
                            </a>
                        @endif
                        <a href="{{ route('trainings.index') }}"
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
                $('#manuals_id').select2({
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

            // Проверка даты First Training Date и показ поля для даты последнего тренинга
            $('#date_training').on('change', function() {
                const firstTrainingDate = new Date($(this).val());
                const today = new Date();
                const twoYearsAgo = new Date(today.getFullYear() - 2, today.getMonth(), today.getDate());
                
                // Если дата больше чем 2 года назад, показываем поле для последнего тренинга
                if (firstTrainingDate < twoYearsAgo) {
                    $('#last_training_date_group').show();
                    $('#last_training_date').prop('required', true);
                } else {
                    $('#last_training_date_group').hide();
                    $('#last_training_date').prop('required', false);
                    $('#last_training_date').val('');
                    $('#last_training_date_error').hide();
                }
                
                // Проверяем валидность даты последнего тренинга если она уже введена
                if ($('#last_training_date').val()) {
                    validateLastTrainingDate();
                }
            });

            // Валидация даты последнего тренинга
            $('#last_training_date').on('change', function() {
                validateLastTrainingDate();
            });

            function validateLastTrainingDate() {
                const firstDate = new Date($('#date_training').val());
                const lastDate = new Date($('#last_training_date').val());
                const today = new Date();
                today.setHours(0, 0, 0, 0); // Убираем время для корректного сравнения

                $('#last_training_date_error').hide();
                $('#last_training_date').removeClass('is-invalid');

                if (!lastDate || isNaN(lastDate.getTime())) {
                    return;
                }

                if (lastDate < firstDate) {
                    $('#last_training_date_error').text('Last training date must be after First Training Date.').show();
                    $('#last_training_date').addClass('is-invalid');
                    return false;
                }

                if (lastDate >= today) {
                    $('#last_training_date_error').text('Last training date must be before today.').show();
                    $('#last_training_date').addClass('is-invalid');
                    return false;
                }

                return true;
            }

            // Валидация формы перед отправкой
            function validateTrainingDate() {
                const firstDate = new Date($('#date_training').val());
                const today = new Date();
                const twoYearsAgo = new Date(today.getFullYear() - 2, today.getMonth(), today.getDate());

                if (firstDate < twoYearsAgo) {
                    const lastDate = $('#last_training_date').val();
                    if (!lastDate) {
                        alert('Please provide the Last Existing Training Date when First Training Date is more than 2 years ago.');
                        $('#last_training_date').focus();
                        return false;
                    }

                    if (!validateLastTrainingDate()) {
                        return false;
                    }
                }

                return true;
            }

        });




    </script>
@endsection
