@extends('admin.master')

@section('content')

    <style>
        .card {
            max-width: 650px;
        }

        /* ----------------------------------- Select 2 Dark Theme -------------------------------------*/



        html[data-bs-theme="dark"]  .select2-selection--single {
            background-color: var(--avia-input) !important;
            color: gray !important;
            height: 38px !important;
            border: 1px solid var(--avia-border) !important;
            align-items: center !important;
            border-radius: 8px;
        }

        html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
            color: #999999;
            line-height: 2.2 !important;
        }

        html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field  {
            background-color: var(--avia-surface-raised) !important;
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
            background-color: var(--avia-input) !important;
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
                <h4 class="mb-0">{{ __('Select Unit') }}</h4>
                {{-- Кому добавляется тренинг — особенно важно для Admin/Manager/TL --}}
                <div class="mt-1 {{ $userId != auth()->id() ? 'text-warning' : 'text-muted' }}">
                    {{ __('Trainee') }}:
                    <strong>{{ $targetUser->stamp }} — {{ $targetUser->selection_name }}</strong>
                    @if($userId == auth()->id())
                        ({{ __('you') }})
                    @endif
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('trainings.store') }}" id="training_create_form">
                    @csrf
                    <input type="hidden" name="return_url" value="{{ request()->get('return_url', url()->previous()) }}">
                    @if(isset($userId) && $userId != auth()->id())
                        <input type="hidden" name="user_id" value="{{ $userId }}">
                    @endif
                    @php $canMarkLegacy = auth()->user()->canManageTrainingMatrix(); @endphp
                    @if($canMarkLegacy)
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="is_legacy" name="is_legacy" value="1">
                            <label class="form-check-label" for="is_legacy">
                                {{ __('Old training') }}
                                <small class="text-muted d-block">{{ __('Trained in the past, no dated records. Shown as «X» in the matrix; no Form 112/132.') }}</small>
                            </label>
                        </div>
                    @endif

                    <div class="form-group mt-2" id="single_manual_group">
                        <label for="manuals_id">{{ __('Componenr PN') }}</label>
                        <select id="manuals_id" name="manuals_id" class="form-control" required>
                            <option value="">{{ __('Select Component PN') }}</option>
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

                    @if($canMarkLegacy)
                        <div class="form-group mt-2" id="legacy_manuals_group" style="display: none;">
                            <label for="legacy_manuals_ids">{{ __('Units (multiple selection)') }}</label>
                            <select id="legacy_manuals_ids" name="legacy_manuals_ids[]" class="form-control" multiple size="12">
                                @foreach ($manuals as $manual)
                                    @if(!empty($manual->unit_name_training))
                                        <option value="{{ $manual->id }}">
                                            {{ $manual->unit_name_training }} ({{ $manual->title }})
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="form-text text-muted">{{ __('Hold Ctrl to select multiple units.') }}</small>

                            {{-- Бланк 132 потерян / нужен перевыпуск: создать 132 вместе с отметкой --}}
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="legacy_create_132" name="create_form_132" value="1">
                                <label class="form-check-label" for="legacy_create_132">
                                    {{ __('Also create Form 132 (lost / needs reissue)') }}
                                </label>
                            </div>
                            <div class="mt-1" id="legacy_132_date_group" style="display: none;">
                                <label for="legacy_132_date" class="form-label">{{ __('Form 132 date') }}</label>
                                <input type="date" id="legacy_132_date" name="form_132_date" class="form-control"
                                       value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                                <small class="form-text text-muted">{{ __('The date is normalized to the Friday of its week.') }}</small>
                            </div>
                        </div>
                    @endif

                    <div class="form-group mt-3" id="date_training_group">
                        <label for="date_training">{{ __('First Training Date') }}</label>
                        {{-- data-project-date: проектный flatpickr, формат 24/Aug/2026 --}}
                        <input type="text" id="date_training" name="date_training" class="form-control"
                               data-project-date autocomplete="off" placeholder="{{ __('dd/Mon/yyyy') }}" required>
                    </div>

                    <div class="form-group mt-3">
                        <label>{{ __('Subsequent Training Dates') }}</label>
                        <small class="form-text text-muted d-block mb-1">
                            <i class="bi bi-info-circle"></i>
                            {{ __('Add all past training dates for this unit (after the first date).') }}
                        </small>
                        <div id="training_dates_list"></div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-1" id="add_training_date_btn">
                            <i class="bi bi-plus"></i> {{ __('Add Date') }}
                        </button>
                        <div id="training_dates_error" class="text-danger mt-1" style="display: none;"></div>
                    </div>

                    <div class="form-group mt-3" id="additional_training_date_group" style="display: none;">
                        <label for="additional_training_date">{{ __('Additional Training Date') }}</label>
                        <input type="text" id="additional_training_date" name="additional_training_date" class="form-control"
                               data-project-date autocomplete="off" placeholder="{{ __('dd/Mon/yyyy') }}">
                        <small class="form-text text-muted">
                            <i class="bi bi-info-circle"></i>
                            {{ __('Last training was more than 360 days ago. You can add a training on the date of adding the unit or choose another date.') }}
                        </small>
                        <div id="additional_training_date_error" class="text-danger mt-1" style="display: none;"></div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary mt-3">{{ __('Add Component') }}</button>
                        @php
                            // «Back to TDR» — только если реально пришли из TDR/Main
                            // (из матрицы тоже приходит manual_id, но там возврат делает Cancel)
                            $cameFromTdr = str_contains(request('return_url', ''), '/tdrs/')
                                || str_contains(request('return_url', ''), '/mains/');
                        @endphp
                        @if(request('manual_id') && $cameFromTdr)
                            <a href="{{ request()->get('return_url', url()->previous()) }}" class="btn btn-outline-info mt-3">
                                <i class="bi bi-arrow-left"></i> Back to TDR
                            </a>
                        @endif
                        @php
                            // Cancel возвращает туда, откуда пришли (матрица/TDR), но только внутри приложения
                            $cancelUrl = request('return_url');
                            if (!$cancelUrl || !\Illuminate\Support\Str::startsWith($cancelUrl, url('/'))) {
                                $cancelUrl = route('trainings.index', isset($userId) && $userId != auth()->id() ? ['user_id' => $userId] : []);
                            }
                        @endphp
                        <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary mt-3">{{ __('Cancel') }} </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: add additional training when last > 360 days -->
    <div class="modal fade" id="additionalTrainingModal" tabindex="-1" aria-labelledby="additionalTrainingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="additionalTrainingModalLabel">{{ __('Additional Training') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('Last training was more than 360 days ago. Add an additional training on the date of adding the unit?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="additionalModalNo">{{ __('No') }}</button>
                    <button type="button" class="btn btn-primary" id="additionalModalYes">{{ __('Yes') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            const DAYS_THRESHOLD = 360;
            let additionalTrainingAsked = false;
            let formPendingSubmit = false;

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

            // Значение проектного date-инпута (24/Aug/2026) → 'Y-m-d'
            function projToYmd(input) {
                if (!input) return '';
                const picked = input._projectDatePicker?.selectedDates?.[0];
                if (picked) {
                    return picked.getFullYear() + '-'
                        + String(picked.getMonth() + 1).padStart(2, '0') + '-'
                        + String(picked.getDate()).padStart(2, '0');
                }
                const m = String(input.value || '').trim().match(/^(\d{1,2})[\/.]([a-z]{3})[\/.](\d{4})$/i);
                if (!m) return '';
                const months = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
                const month = months.indexOf(m[2].toLowerCase());
                if (month < 0) return '';
                return m[3] + '-' + String(month + 1).padStart(2, '0') + '-' + String(Number(m[1])).padStart(2, '0');
            }

            function getFirstDate() {
                const v = projToYmd(document.getElementById('date_training'));
                return v ? new Date(v + 'T00:00:00') : null;
            }

            function getTodayStart() {
                const t = new Date();
                t.setHours(0, 0, 0, 0);
                return t;
            }

            function getTrainingDatesEntered() {
                const dates = [];
                document.querySelectorAll('input[name="training_dates[]"]').forEach(function(inp) {
                    const ymd = projToYmd(inp);
                    if (ymd) dates.push(ymd);
                });
                return dates.sort();
            }

            function getLastEnteredDate() {
                const dates = getTrainingDatesEntered();
                if (dates.length === 0) return null;
                return new Date(dates[dates.length - 1] + 'T00:00:00');
            }

            function daysBetween(d1, d2) {
                const a = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate());
                const b = new Date(d2.getFullYear(), d2.getMonth(), d2.getDate());
                return Math.floor((b - a) / (24 * 60 * 60 * 1000));
            }

            function addTrainingDateRow(value) {
                const id = 'td_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6);
                const row = document.createElement('div');
                row.className = 'input-group input-group-sm mb-1';
                row.id = id;
                row.innerHTML =
                    '<input type="text" name="training_dates[]" class="form-control" data-project-date autocomplete="off" placeholder="{{ __('dd/Mon/yyyy') }}" value="' + (value || '') + '">' +
                    '<button type="button" class="btn btn-outline-danger remove-training-date" data-row-id="' + id + '" aria-label="Remove"><i class="bi bi-dash"></i></button>';
                document.getElementById('training_dates_list').appendChild(row);
                if (window.initProjectDatePickers) {
                    window.initProjectDatePickers(row);
                }
                $(row).find('.remove-training-date').on('click', function() {
                    document.getElementById(id).remove();
                });
            }

            document.getElementById('add_training_date_btn').addEventListener('click', function() {
                addTrainingDateRow('');
            });

            function validateSubsequentDates() {
                const firstDate = getFirstDate();
                const today = getTodayStart();
                $('#training_dates_error').hide();
                let valid = true;
                document.querySelectorAll('input[name="training_dates[]"]').forEach(function(inp) {
                    inp.classList.remove('is-invalid');
                    const ymd = projToYmd(inp);
                    if (!ymd) return;
                    const d = new Date(ymd + 'T00:00:00');
                    if (firstDate && d <= firstDate) {
                        inp.classList.add('is-invalid');
                        valid = false;
                    }
                    if (d > today) {
                        inp.classList.add('is-invalid');
                        valid = false;
                    }
                });
                if (!valid) {
                    $('#training_dates_error').text('{{ __("Each date must be after First Training Date and not in the future.") }}').show();
                }
                return valid;
            }

            function validateAdditionalDate() {
                const v = projToYmd(document.getElementById('additional_training_date'));
                if (!v) return true;
                const firstDate = getFirstDate();
                const today = getTodayStart();
                const d = new Date(v + 'T00:00:00');
                $('#additional_training_date_error').hide();
                $('#additional_training_date').removeClass('is-invalid');
                if (firstDate && d < firstDate) {
                    $('#additional_training_date_error').text('{{ __("Additional training date must be on or after First Training Date.") }}').show();
                    $('#additional_training_date').addClass('is-invalid');
                    return false;
                }
                if (d > today) {
                    $('#additional_training_date_error').text('{{ __("Additional training date cannot be in the future.") }}').show();
                    $('#additional_training_date').addClass('is-invalid');
                    return false;
                }
                return true;
            }

            $('#additional_training_date').on('change', function() {
                validateAdditionalDate();
            });

            function validateTrainingDate() {
                if (!validateSubsequentDates()) return false;
                if (!validateAdditionalDate()) return false;

                const subsequentDates = getTrainingDatesEntered();
                const lastEntered = subsequentDates.length ? getLastEnteredDate() : getFirstDate();
                const today = getTodayStart();
                const needAsk = lastEntered && daysBetween(lastEntered, today) >= DAYS_THRESHOLD;
                const hasAdditional = $('#additional_training_date').val() && $('#additional_training_date_group').is(':visible');

                if (needAsk && !additionalTrainingAsked && !hasAdditional) {
                    formPendingSubmit = true;
                    const modal = new bootstrap.Modal(document.getElementById('additionalTrainingModal'));
                    modal.show();
                    return false;
                }

                return true;
            }

            document.getElementById('additionalModalNo').addEventListener('click', function() {
                additionalTrainingAsked = true;
                bootstrap.Modal.getInstance(document.getElementById('additionalTrainingModal')).hide();
                if (formPendingSubmit) {
                    formPendingSubmit = false;
                    document.getElementById('training_create_form').submit();
                }
            });

            document.getElementById('additionalModalYes').addEventListener('click', function() {
                additionalTrainingAsked = true;
                bootstrap.Modal.getInstance(document.getElementById('additionalTrainingModal')).hide();
                $('#additional_training_date_group').show();
                const addInput = document.getElementById('additional_training_date');
                if (window.initProjectDatePickers) {
                    window.initProjectDatePickers(document);
                }
                if (!addInput.value) {
                    addInput._projectDatePicker?.setDate(new Date(), true);
                }
                $('#additional_training_date').focus();
                formPendingSubmit = false;
                // User confirms or changes the date and clicks "Add Component" again
            });

            function isLegacyMode() {
                const cb = document.getElementById('is_legacy');
                return !!(cb && cb.checked);
            }

            // «Old training»: вместо одной пары юнит+даты — мультивыбор юнитов без дат.
            const legacy132Checkbox = document.getElementById('legacy_create_132');
            if (legacy132Checkbox) {
                legacy132Checkbox.addEventListener('change', function () {
                    document.getElementById('legacy_132_date_group').style.display = this.checked ? '' : 'none';
                });
            }

            const legacyCheckbox = document.getElementById('is_legacy');
            if (legacyCheckbox) {
                legacyCheckbox.addEventListener('change', function () {
                    const legacy = isLegacyMode();
                    document.getElementById('single_manual_group').style.display = legacy ? 'none' : '';
                    document.getElementById('legacy_manuals_group').style.display = legacy ? '' : 'none';
                    document.getElementById('date_training_group').style.display = legacy ? 'none' : '';
                    document.getElementById('additional_training_date_group').style.display = 'none';
                    document.querySelectorAll('#training_dates_list input').forEach(i => i.closest('.input-group').remove());
                    document.getElementById('add_training_date_btn').parentElement.style.display = legacy ? 'none' : '';
                    document.getElementById('manuals_id').required = !legacy;
                    document.getElementById('date_training').required = !legacy;
                });
            }

            document.getElementById('training_create_form').addEventListener('submit', function(e) {
                if (isLegacyMode()) {
                    const selected = document.querySelectorAll('#legacy_manuals_ids option:checked').length;
                    if (selected === 0) {
                        e.preventDefault();
                        alert(@json(__('Select at least one unit for Old training.')));
                        return false;
                    }
                    return; // даты не валидируются — их нет
                }
                if (formPendingSubmit) return;
                if (!validateTrainingDate()) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        });
    </script>
    @include('admin.trainings.partials.friday-snap')
@endsection
