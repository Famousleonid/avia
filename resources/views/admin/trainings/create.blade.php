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
                @include('admin.trainings.partials.unit-form')
            </div>
        </div>
    </div>


    <script>
        window.addEventListener('load', function () {

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
                if (!validateSubsequentDates()) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        });
    </script>
    @include('admin.trainings.partials.friday-snap')
@endsection
