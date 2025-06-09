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
                <form method="POST" action="{{ route('trainings.store') }}">
                    @csrf
                    <div class="form-group mt-2">
                        <label for="manuals_id">{{ __('Unit PN') }}</label>
                        <select id="manuals_id" name="manuals_id" class="form-control" required>
                            <option value="">{{ __('Select Unit PN') }}</option>
                            @foreach ($manuals as $manual)
                                @if(!empty($manual->unit_name_training))
                                    <option value="{{ $manual->id }}">
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
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary mt-3">{{ __('Add Unit') }}</button>
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


        });




    </script>
@endsection
