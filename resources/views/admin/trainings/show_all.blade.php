@extends('admin.master')

@section('content')
    <style>
        .table-container {
            overflow-x: auto;
            overflow-y: auto;
            /* экран минус шапка карточки, легенда и футер — низ карточки всегда виден */
            max-height: calc(100vh - 240px);
            position: relative;
        }

        .training-table {
            /* Ширина по контенту: при одной-двух колонках (техник/TL)
               таблица не растягивается на всю карточку. margin:auto центрирует
               узкую таблицу; у широкой авто-отступы схлопываются в 0 и скролл
               работает как обычно */
            width: max-content;
            margin-left: auto;
            margin-right: auto;
            border-collapse: separate;
            border-spacing: 0;
        }

        .training-table th,
        .training-table td {
            white-space: nowrap;
            vertical-align: middle;
            padding: 6px 12px; /* воздух */
            border: 1px solid var(--avia-border);
            background: var(--avia-panel);
            color: var(--avia-text);
        }

        .training-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background: linear-gradient(180deg, var(--avia-surface-raised) 0%, var(--avia-surface) 100%);
            color: #8fc4f0;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 8px;
        }

        /* Замороженные левые колонки: ширина по контенту (+пределы),
           сдвиг второй колонки вычисляется JS по фактической ширине первой */
        .training-table th.col-unit,
        .training-table td.col-unit {
            position: sticky !important;
            left: 0;
            min-width: 150px;
            max-width: 360px;
            z-index: 30;
            background: var(--avia-panel) !important;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 12px;
        }

        .training-table th.col-part,
        .training-table td.col-part {
            position: sticky !important;
            left: var(--col-part-left, 220px);
            min-width: 190px;
            max-width: 420px;
            z-index: 30;
            background: var(--avia-surface-raised) !important;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.28);
            white-space: normal;
            line-height: 1.3;
            font-size: 12px;
        }

        .training-table thead th.col-unit,
        .training-table thead th.col-part {
            z-index: 50;
            background: linear-gradient(180deg, var(--avia-surface-raised) 0%, var(--avia-surface) 100%) !important;
            color: #8fc4f0 !important;
        }

        .training-table th.user-column,
        .training-table td.user-column {
            min-width: 90px;
            text-align: center;
        }

        .training-table th.user-column .stamp-no {
            display: block;
            font-size: 10px;
            color: var(--avia-text-muted);
            font-weight: 400;
        }

        /* Строка-заголовок группы — как зелёные секции в Excel.
           Ячейка шириной во всю таблицу, поэтому текст прижат влево
           и прилипает к левому краю видимой области при скролле. */
        .training-table td.group-row {
            background: rgba(93, 158, 91, 0.28) !important;
            color: var(--avia-text) !important;
            font-weight: 700;
            font-size: 12.5px;
            letter-spacing: .04em;
            text-align: left;
        }

        .training-table td.group-row .group-label {
            position: sticky;
            left: 12px;
            display: inline-block;
        }

        .training-date-old {
            color: #fff !important;
            background: #b3423a;
            border-radius: 4px;
            padding: 1px 6px;
            font-weight: 700;
            font-size: 12px;
        }

        .training-date-fresh {
            color: var(--avia-text) !important;
            font-size: 12px;
        }

        .training-x {
            color: var(--avia-text-muted);
            font-weight: 700;
            cursor: default;
        }

        .row-no-cmm td.col-unit,
        .row-no-cmm td.col-part {
            opacity: .55;
        }

        .badge-no-cmm {
            font-size: 10px;
            background: rgba(255, 193, 7, .18);
            color: #e0a800;
            border: 1px solid rgba(255, 193, 7, .4);
            border-radius: 4px;
            padding: 0 4px;
            white-space: nowrap;
        }

        .row-tools {
            float: right;
            white-space: nowrap;
            visibility: hidden;
        }

        .training-table tr:hover .row-tools {
            visibility: visible;
        }

        .row-tools button {
            background: none;
            border: none;
            color: var(--avia-text-muted);
            padding: 0 2px;
            font-size: 12px;
            cursor: pointer;
        }

        .row-tools button:hover { color: var(--avia-text); }

        /* Узкий режим (мало колонок): карточка по ширине таблицы, по центру */
        .matrix-narrow {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .matrix-narrow .card {
            /* min-content: ширину диктует таблица (nowrap), а легенда и шапка
               переносятся, не распирая карточку */
            width: min-content;
            min-width: 420px;
            max-width: 100%;
        }

        .matrix-narrow .matrix-legend {
            min-width: 0;
        }

        .matrix-legend {
            font-size: 12px;
            color: var(--avia-text-muted);
            display: flex;
            gap: 2px 18px; /* строки легенды плотно */
            flex-wrap: wrap;
            margin-top: 6px;
        }

        /* Колонка Active (только Admin/Manager) — левее замороженных колонок */
        .training-table th.col-active,
        .training-table td.col-active {
            position: sticky !important;
            left: 0;
            min-width: 36px;
            max-width: 36px;
            z-index: 35;
            background: var(--avia-panel) !important;
            text-align: center;
            padding: 5px 4px;
        }

        .training-table thead th.col-active {
            z-index: 50;
            background: linear-gradient(180deg, var(--avia-surface-raised) 0%, var(--avia-surface) 100%) !important;
        }

        .training-table td.col-active input[type="checkbox"] {
            accent-color: #5f7f99; /* спокойный серо-синий вместо кричащего дефолта */
            opacity: .8;
        }

        .training-table.has-active-col th.col-unit,
        .training-table.has-active-col td.col-unit {
            left: 36px;
        }

        /* Неактивный юнит («с ним пока не работаем») — штриховка как в Excel */
        .row-inactive td {
            color: var(--avia-text-muted);
        }

        .row-inactive td.col-unit,
        .row-inactive td.col-part {
            background-image: repeating-linear-gradient(45deg, transparent 0 6px, rgba(140, 140, 140, 0.18) 6px 9px);
        }

        /* Последний тренинг пары принят (approved) — зелёная рамка ячейки */
        td.user-column.cell-approved {
            box-shadow: inset 0 0 0 2px #2e7d4f;
        }

        /* Кликабельная ячейка: клик = добавить тренинг этой паре */
        td.user-column.cell-click {
            cursor: pointer;
        }

        td.user-column.cell-click:hover {
            background: rgba(110, 168, 254, 0.14) !important;
            outline: 1px solid rgba(110, 168, 254, 0.45);
            outline-offset: -1px;
        }
    </style>

    {{-- Карточка всегда по ширине таблицы и по центру: узкая — компактное окно,
         широкая — до краёв экрана со скроллом внутри --}}
    <div class="container-fluid px-4 mt-3 matrix-narrow">
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <h6 class="mb-0">{{ $scaMode ? 'SCA PERSONNEL TRAINING' : 'PART NUMBER APPROVED PERSONNEL' }}</h6>
                        @if($canManage)
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('trainings.showAll') }}"
                                   class="btn py-0 {{ $scaMode ? 'btn-outline-secondary' : 'btn-info' }}">{{ __('Production') }}</a>
                                <a href="{{ route('trainings.showAll', ['sca' => 1]) }}"
                                   class="btn py-0 {{ $scaMode ? 'btn-info' : 'btn-outline-secondary' }}">SCA</a>
                            </div>
                        @endif
                        <input type="search" id="matrixSearch" class="form-control form-control-sm"
                               style="width: 220px;" placeholder="{{ __('Search unit / P/N…') }}"
                               autocomplete="off">
                    </div>
                    @if($canManage)
                        <div class="d-flex align-items-center gap-3">
                            @php $modeParams = $scaMode ? ['sca' => 1] : []; @endphp
                            @if(!$scaMode && $uncategorizedCount > 0)
                                <span class="badge-no-cmm" title="{{ __('CMMs with training PN that are not linked to any matrix row') }}">
                                    {{ __('CMM not in matrix:') }} {{ $uncategorizedCount }}
                                </span>
                            @endif
                            @if($showInactive)
                                <a href="{{ route('trainings.showAll', $modeParams) }}" class="btn btn-outline-secondary btn-sm py-0">
                                    {{ __('Hide inactive') }}
                                </a>
                            @elseif($inactiveCount > 0)
                                <a href="{{ route('trainings.showAll', $modeParams + ['show_inactive' => 1]) }}" class="btn btn-outline-secondary btn-sm py-0">
                                    {{ __('Show inactive') }} ({{ $inactiveCount }})
                                </a>
                            @endif
                            @unless($scaMode)
                                <button class="btn btn-outline-info btn-sm py-0" data-bs-toggle="modal" data-bs-target="#matrixPersonnelModal">
                                    {{ __('Personnel') }}
                                </button>
                            @endunless
                            <button class="btn btn-outline-info btn-sm py-0" data-bs-toggle="modal" data-bs-target="#matrixRowModal"
                                    onclick="matrixRowModalReset()">
                                + {{ __('Add row') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success py-1 mb-2">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger py-1 mb-2">{{ $errors->first() }}</div>
                @endif

                @if($categories->isEmpty())
                    <div class="alert alert-info text-center mb-0">
                        {{ __('Matrix is empty. Import the structure or add rows manually.') }}
                    </div>
                @else
                    @php $colspan = ($canManage ? 3 : 2) + $users->count(); @endphp
                    <div class="table-container">
                        <table class="table training-table table-bordered align-middle mb-0 {{ $canManage ? 'has-active-col' : '' }}">
                            <thead>
                            <tr>
                                @if($canManage)
                                    <th class="col-active" title="{{ __('Unit is currently worked on; uncheck to hide the row from the matrix') }}">✓</th>
                                @endif
                                <th class="col-unit">{{ __('Unit Description') }}</th>
                                <th class="col-part text-center">PART NUMBER APPROVED</th>
                                @foreach($users as $user)
                                    <th class="user-column">
                                        {{ $user->selection_name }}
                                        <span class="stamp-no">{{ $user->stamp }}</span>
                                    </th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($categories as $category)
                                <tr>
                                    <td class="group-row" colspan="{{ $colspan }}">
                                        <span class="group-label">
                                            {{ $category->name }}
                                            @if($canManage)
                                                <span class="row-tools">
                                                    <button type="button" title="{{ __('Move group up') }}" onclick="matrixCategoryMove({{ $category->id }}, 'up')">▲</button>
                                                    <button type="button" title="{{ __('Move group down') }}" onclick="matrixCategoryMove({{ $category->id }}, 'down')">▼</button>
                                                </span>
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                                @foreach($category->rows as $row)
                                    <tr class="{{ $row->manual_id ? '' : 'row-no-cmm' }} {{ $row->is_active ? '' : 'row-inactive' }}">
                                        @if($canManage)
                                            <td class="col-active">
                                                <input type="checkbox" {{ $row->is_active ? 'checked' : '' }}
                                                       onchange="matrixRowToggleActive({{ $row->id }})"
                                                       title="{{ $row->is_active ? __('Uncheck: unit not currently worked on — hide row (not deleted)') : __('Check: return unit to the matrix') }}">
                                            </td>
                                        @endif
                                        <td class="col-unit">
                                            {{ $row->description ?? '' }}
                                        </td>
                                        <td class="col-part">
                                            {{ $row->part_number }}
                                            @if(!$category->is_sca && !$row->manual_id)
                                                <span class="badge-no-cmm" title="{{ __('No CMM registered in avia for this unit') }}">no CMM</span>
                                            @endif
                                            @if($canManage)
                                                @php
                                                    $rowPayload = [
                                                        'id' => $row->id,
                                                        'category' => $row->training_category_id,
                                                        'description' => $row->description,
                                                        'part_number' => $row->part_number,
                                                        'manual_id' => $row->manual_id,
                                                        'manual_label' => $row->manual
                                                            ? $row->manual->unit_name_training . ' (' . $row->manual->title . ')'
                                                            : null,
                                                    ];
                                                @endphp
                                                <span class="row-tools">
                                                    <button type="button" title="{{ __('Edit row') }}"
                                                            data-bs-toggle="modal" data-bs-target="#matrixRowModal"
                                                            onclick='matrixRowModalEdit(@json($rowPayload))'>✎</button>
                                                </span>
                                            @endif
                                        </td>
                                        @foreach($users as $user)
                                            @php
                                                $cell = $cells[$row->id][$user->id] ?? null;
                                                // Клик: управляющие — любая колонка; TL — своя team; техник — своя.
                                                // Курсы (SCA-секции) — только в колонках людей с SCA-квалификацией.
                                                $clickable = $category->is_sca
                                                    ? ($canManage && $user->can_sign_certificates)
                                                    : ($row->manual_id && ($canManage || auth()->user()->canManageTrainingsFor($user)));
                                                $clickAttrs = '';
                                                if ($clickable) {
                                                    if ($category->is_sca) {
                                                        // Курс: дата пишется на строку напрямую, модалка для любой ячейки
                                                        $clickAttrs = 'data-course-row="' . $row->id . '" data-train-user="' . $user->id . '"'
                                                            . ' data-train-user-name="' . e($user->selection_name) . '"'
                                                            . ' data-train-pn="' . e($row->part_number) . '"';
                                                    } elseif ($cell === null) {
                                                        // Пары ещё нет — первичное обучение через create-форму;
                                                        // return_url возвращает в матрицу (Cancel и после сохранения)
                                                        $clickAttrs = 'data-create-url="' . e(route('trainings.create', [
                                                            'user_id' => $user->id,
                                                            'manual_id' => $row->manual_id,
                                                            'return_url' => route('trainings.showAll', $scaMode ? ['sca' => 1] : []),
                                                        ])) . '"';
                                                    } else {
                                                        $clickAttrs = 'data-train-manual="' . $row->manual_id . '" data-train-user="' . $user->id . '"'
                                                            . ' data-train-user-name="' . e($user->selection_name) . '"'
                                                            . ' data-train-pn="' . e($row->part_number) . '"'
                                                            . ' data-train-need132="' . (!empty($cell['need132']) ? 1 : 0) . '"';
                                                    }
                                                }
                                            @endphp
                                            <td class="user-column {{ $clickable ? 'cell-click' : '' }} {{ !empty($cell['approved']) ? 'cell-approved' : '' }}" {!! $clickAttrs !!}
                                                @if($clickable) title="{{ $cell === null ? __('Add unit for this user') : __('New Training Date') }}" @endif>
                                                @if($cell === null)
                                                    <span class="text-muted">-</span>
                                                @elseif($cell['kind'] === 'x')
                                                    <span class="training-x"
                                                          @if($cell['date']) title="{{ __('Last training:') }} {{ $cell['date']->format('d/M/Y') }} — {{ __('refresh required (MP-20)') }}"
                                                          @else title="{{ __('Old training (no date on record)') }}" @endif>X</span>
                                                @else
                                                    <span class="{{ $cell['red'] ? 'training-date-old' : 'training-date-fresh' }}">
                                                        {{ $cell['date']->format('d/M/Y') }}
                                                    </span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="matrix-legend">
                        <span><span class="training-date-fresh">01/Jan/2026</span> — {{ __('training up to date') }}</span>
                        <span><span class="training-date-old">01/Jan/2025</span> — {{ __('older than :days days, refresh required', ['days' => config('trainings.matrix_red_after_days', 350)]) }}</span>
                        <span><span class="training-x">X</span> — {{ __('trained in the past; unit not currently worked on (older than :years years or old training)', ['years' => config('trainings.matrix_legacy_after_years', 3)]) }}</span>
                        <span><span class="text-muted">-</span> — {{ __('never trained') }}</span>
                        <span><span style="box-shadow: inset 0 0 0 2px #2e7d4f; padding: 1px 8px; border-radius: 3px;">{{ __('date') }}</span> — {{ __('last training approved') }}</span>
                        <span><span class="badge-no-cmm">no CMM</span> — {{ __('unit not registered in avia') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Модалка «добавить дату тренинга» по клику на ячейку --}}
    <div class="modal fade" id="matrixTrainModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gradient">
                <div class="modal-header">
                    <h6 class="modal-title">{{ __('New Training Date') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="small mb-2" id="matrixTrainInfo"></div>
                    {{-- data-project-date: проектный flatpickr с форматом 24/Aug/2026 --}}
                    <input type="text" id="matrixTrainDate" class="form-control" data-project-date autocomplete="off">
                    <small class="text-muted">{{ __('The date is normalized to the Friday of its week.') }}</small>
                    @if($canManage)
                        {{-- Только для legacy-пары без 132: бланк потерян / нужен перевыпуск --}}
                        <div class="form-check mt-2" id="matrixTrain132Wrap" style="display: none;">
                            <input class="form-check-input" type="checkbox" id="matrixTrain132">
                            <label class="form-check-label small" for="matrixTrain132">
                                {{ __('Also create Form 132 (lost / needs reissue)') }}
                            </label>
                        </div>
                    @endif
                    <div class="text-danger small mt-1" id="matrixTrainError" style="display: none;"></div>
                    <div class="mt-2">
                        <div class="d-flex align-items-center gap-3">
                            <button type="button" class="btn btn-link btn-sm p-0" id="matrixTrainHistoryBtn">{{ __('Show history') }}</button>
                            <button type="button" class="btn btn-outline-success btn-sm py-0" id="matrixTrainApproveAll" style="display: none;">✓ {{ __('Approve all') }}</button>
                        </div>
                        <div id="matrixTrainHistory" class="mt-2 small" style="display: none;">
                            {{-- 132/legacy закреплены, скроллятся только 112-е --}}
                            <div id="matrixTrainHistoryPinned"></div>
                            <div id="matrixTrainHistoryScroll" style="max-height: 200px; overflow-y: auto; overflow-x: hidden;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-1">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="matrixTrainSave">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            // Сдвиг второй замороженной колонки = фактическая граница первой
            // (ширины теперь по контенту, фиксированного офсета нет)
            function syncStickyOffsets() {
                const table = document.querySelector('.training-table');
                const unitTh = document.querySelector('.training-table th.col-unit');
                if (table && unitTh) {
                    table.style.setProperty('--col-part-left', (unitTh.offsetLeft + unitTh.offsetWidth) + 'px');
                }
            }
            window.addEventListener('load', syncStickyOffsets);
            window.addEventListener('resize', syncStickyOffsets);
            syncStickyOffsets();

            // Поиск: по строкам (описание + парт-номер/курс) И по колонкам
            // (имя/штамп в шапке). Если запрос совпал с сотрудником — фильтруются
            // колонки; иначе — строки (группы без совпадений скрываются).
            const searchInput = document.getElementById('matrixSearch');
            if (searchInput) {
                const headerRow = document.querySelector('.training-table thead tr');
                const userThs = headerRow ? Array.from(headerRow.querySelectorAll('th.user-column')) : [];
                const userColBase = headerRow && userThs.length
                    ? Array.from(headerRow.children).indexOf(userThs[0])
                    : -1;

                function setColumnVisibility(visibleFlags) {
                    if (userColBase < 0) return;
                    userThs.forEach(function (th, i) {
                        th.style.display = visibleFlags[i] ? '' : 'none';
                    });
                    document.querySelectorAll('.training-table tbody tr').forEach(function (tr) {
                        if (tr.querySelector('td.group-row')) return; // colspan-строка группы
                        userThs.forEach(function (_, i) {
                            const cell = tr.children[userColBase + i];
                            if (cell) cell.style.display = visibleFlags[i] ? '' : 'none';
                        });
                    });
                }

                function filterRows(q) {
                    const rows = document.querySelectorAll('.training-table tbody tr');
                    let currentGroup = null;
                    let groupHasVisible = false;
                    rows.forEach(function (tr) {
                        if (tr.querySelector('td.group-row')) {
                            if (currentGroup) currentGroup.style.display = groupHasVisible ? '' : 'none';
                            currentGroup = tr;
                            groupHasVisible = false;
                            return;
                        }
                        const unit = tr.querySelector('td.col-unit')?.textContent.toLowerCase() ?? '';
                        const pn = tr.querySelector('td.col-part')?.textContent.toLowerCase() ?? '';
                        const match = !q || unit.includes(q) || pn.includes(q);
                        tr.style.display = match ? '' : 'none';
                        if (match) groupHasVisible = true;
                    });
                    if (currentGroup) currentGroup.style.display = groupHasVisible ? '' : 'none';
                }

                searchInput.addEventListener('input', function () {
                    const q = this.value.trim().toLowerCase();
                    const colFlags = userThs.map(th => th.textContent.toLowerCase().includes(q));
                    const anyColMatch = q && colFlags.some(Boolean);

                    if (anyColMatch) {
                        setColumnVisibility(colFlags);
                        filterRows(''); // все строки видимы
                    } else {
                        setColumnVisibility(userThs.map(() => true));
                        filterRows(q);
                    }
                    syncStickyOffsets(); // ширины колонок могли измениться
                });
            }

            let trainCtx = null;
            const modalEl = document.getElementById('matrixTrainModal');

            document.querySelectorAll('td.cell-click').forEach(function (td) {
                td.addEventListener('click', function () {
                    if (td.dataset.createUrl) {
                        window.location.href = td.dataset.createUrl;
                        return;
                    }
                    trainCtx = { manual: td.dataset.trainManual, user: td.dataset.trainUser, courseRow: td.dataset.courseRow, td: td };
                    document.getElementById('matrixTrainInfo').textContent =
                        td.dataset.trainPn + ' — ' + td.dataset.trainUserName;
                    const dateInput = document.getElementById('matrixTrainDate');
                    dateInput.value = '{{ now()->format('d/M/Y') }}';
                    if (window.initProjectDatePickers) {
                        window.initProjectDatePickers(modalEl);
                    }
                    dateInput._projectDatePicker?.setDate(new Date(), false);
                    document.getElementById('matrixTrainError').style.display = 'none';
                    const wrap132 = document.getElementById('matrixTrain132Wrap');
                    if (wrap132) {
                        // Для SCA-курсов форм 112/132 нет
                        wrap132.style.display = (!trainCtx.courseRow && td.dataset.trainNeed132 === '1') ? '' : 'none';
                        document.getElementById('matrixTrain132').checked = false;
                    }
                    document.getElementById('matrixTrainHistory').style.display = 'none';
                    document.getElementById('matrixTrainHistoryPinned').innerHTML = '';
                    document.getElementById('matrixTrainHistoryScroll').innerHTML = '';
                    document.getElementById('matrixTrainApproveAll').style.display = 'none';
                    new bootstrap.Modal(modalEl).show();
                });
            });

            // История пары + выборочный/общий апрув (кнопки — только can_approve).
            // Страница НЕ перезагружается: ячейка матрицы обновляется на месте.
            const MATRIX_RED_DAYS = @json((int) config('trainings.matrix_red_after_days', 350));
            const MATRIX_LEGACY_YEARS = @json((int) config('trainings.matrix_legacy_after_years', 3));

            function parseHistoryDate(str) {
                const m = String(str || '').match(/^(\d{1,2})\/([A-Za-z]{3})\/(\d{4})$/);
                if (!m) return null;
                const months = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
                const month = months.indexOf(m[2].toLowerCase());
                return month < 0 ? null : new Date(Number(m[3]), month, Number(m[1]));
            }

            // Перерисовка ячейки по свежей истории (та же логика, что на сервере)
            function updateCellFromRecords(records) {
                const td = trainCtx?.td;
                if (!td) return;

                const hasLegacy = records.some(r => r.label.indexOf('Old training') !== -1);
                const dated = records
                    .map(r => ({ rec: r, date: parseHistoryDate(r.date) }))
                    .filter(x => x.date && x.rec.label.indexOf('Old training') === -1)
                    // перевыпущенная 132 у legacy-пары — не тренинг
                    .filter(x => !(hasLegacy && x.rec.label.indexOf('132') !== -1))
                    .sort((a, b) => b.date - a.date);

                const last = dated[0] || null;
                td.classList.toggle('cell-approved', !!last?.rec.approved);

                if (!last) {
                    td.innerHTML = hasLegacy
                        ? '<span class="training-x">X</span>'
                        : '<span class="text-muted">-</span>';
                    return;
                }

                const days = (Date.now() - last.date.getTime()) / 86400000;
                if (days > MATRIX_LEGACY_YEARS * 365) {
                    const x = document.createElement('span');
                    x.className = 'training-x';
                    x.textContent = 'X';
                    x.title = @json(__('Last training:')) + ' ' + last.rec.date + ' — ' + @json(__('refresh required (MP-20)'));
                    td.innerHTML = '';
                    td.appendChild(x);
                    return;
                }

                const span = document.createElement('span');
                span.className = days > MATRIX_RED_DAYS ? 'training-date-old' : 'training-date-fresh';
                span.textContent = last.rec.date;
                td.innerHTML = '';
                td.appendChild(span);
            }

            function pairParams() {
                const p = new URLSearchParams({ user_id: trainCtx.user });
                if (trainCtx.courseRow) {
                    p.append('matrix_row_id', trainCtx.courseRow);
                } else {
                    p.append('manual_id', trainCtx.manual);
                }
                return p;
            }

            async function approvalPost(url, body) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' },
                    body: body,
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Error');
                }
                await loadPairHistory();
            }

            async function loadPairHistory() {
                const box = document.getElementById('matrixTrainHistory');
                const pinned = document.getElementById('matrixTrainHistoryPinned');
                const scroll = document.getElementById('matrixTrainHistoryScroll');
                const approveAllBtn = document.getElementById('matrixTrainApproveAll');
                box.style.display = '';
                pinned.textContent = '…';
                scroll.innerHTML = '';
                approveAllBtn.style.display = 'none';
                try {
                    const response = await fetch(@json(route('trainings.matrixPairHistory')) + '?' + pairParams(), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Error');
                    }
                    pinned.innerHTML = '';
                    if (!data.records.length) {
                        pinned.textContent = @json(__('No trainings yet.'));
                        return;
                    }
                    let hasUnapproved = false;
                    data.records.forEach(function (rec) {
                        const line = document.createElement('div');
                        line.className = 'd-flex justify-content-between align-items-center mb-1 gap-2 flex-wrap';

                        const left = document.createElement('span');
                        left.textContent = rec.label + ' — ' + rec.date;
                        left.style.whiteSpace = 'nowrap';
                        line.appendChild(left);

                        if (rec.approved) {
                            const badge = document.createElement('span');
                            badge.textContent = '✓ ' + (rec.approved_by || '') + (rec.approved_at ? ' · ' + rec.approved_at : '');
                            badge.style.cssText = 'color:#4caf7d;border:1px solid #2e7d4f;border-radius:4px;padding:0 6px;white-space:nowrap;font-size:.9em;';
                            line.appendChild(badge);
                        } else {
                            hasUnapproved = true;
                            if (data.can_approve) {
                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'btn btn-outline-success btn-sm py-0';
                                btn.textContent = '✓ ' + @json(__('Approve'));
                                btn.addEventListener('click', function () {
                                    approvalPost(@json(route('trainings.approve', ['id' => '__ID__'])).replace('__ID__', rec.id), null)
                                        .catch(function (error) { pinned.textContent = error.message; });
                                });
                                line.appendChild(btn);
                            }
                        }
                        if (rec.editable) {
                            const editBtn = document.createElement('button');
                            editBtn.type = 'button';
                            editBtn.className = 'btn btn-link btn-sm p-0';
                            editBtn.textContent = '✎';
                            editBtn.addEventListener('click', function () {
                                startEditHistoryDate(rec, line);
                            });
                            line.appendChild(editBtn);
                        }

                        // 132 и legacy закреплены над скроллом; 112/курсы скроллятся
                        (rec.label.indexOf('112') === -1 ? pinned : scroll).appendChild(line);
                    });

                    approveAllBtn.style.display = (data.can_approve && hasUnapproved) ? '' : 'none';
                    updateCellFromRecords(data.records);
                } catch (error) {
                    pinned.textContent = error.message;
                }
            }

            // Инлайн-правка даты записи истории (право проверяет сервер)
            function startEditHistoryDate(rec, line) {
                line.innerHTML = '';

                const label = document.createElement('span');
                label.textContent = rec.label;
                label.style.whiteSpace = 'nowrap';

                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.style.maxWidth = '140px';
                input.setAttribute('data-project-date', '');
                input.autocomplete = 'off';
                input.value = rec.date;

                const saveBtn = document.createElement('button');
                saveBtn.type = 'button';
                saveBtn.className = 'btn btn-outline-primary btn-sm py-0';
                saveBtn.textContent = @json(__('Save'));

                const cancelBtn = document.createElement('button');
                cancelBtn.type = 'button';
                cancelBtn.className = 'btn btn-outline-secondary btn-sm py-0';
                cancelBtn.textContent = '✕';

                line.append(label, input, saveBtn, cancelBtn);
                if (window.initProjectDatePickers) {
                    window.initProjectDatePickers(line);
                }

                cancelBtn.addEventListener('click', function () { loadPairHistory(); });
                saveBtn.addEventListener('click', async function () {
                    const picked = input._projectDatePicker?.selectedDates?.[0];
                    if (!picked) return;
                    const ymd = picked.getFullYear() + '-'
                        + String(picked.getMonth() + 1).padStart(2, '0') + '-'
                        + String(picked.getDate()).padStart(2, '0');
                    try {
                        const response = await fetch(@json(url('trainings')) + '/' + rec.id, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': @json(csrf_token()),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ date_training: ymd }),
                        });
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Error');
                        }
                        await loadPairHistory(); // история + ячейка обновятся без reload
                    } catch (error) {
                        document.getElementById('matrixTrainHistoryPinned').textContent = error.message;
                    }
                });
            }

            document.getElementById('matrixTrainApproveAll').addEventListener('click', function () {
                approvalPost(@json(route('trainings.approveUnit')), pairParams())
                    .catch(function (error) {
                        document.getElementById('matrixTrainHistoryPinned').textContent = error.message;
                    });
            });

            document.getElementById('matrixTrainHistoryBtn').addEventListener('click', function () {
                // Повторный клик — скрыть историю (и Approve all вместе с ней)
                const box = document.getElementById('matrixTrainHistory');
                if (box.style.display !== 'none') {
                    box.style.display = 'none';
                    document.getElementById('matrixTrainHistoryPinned').innerHTML = '';
                    document.getElementById('matrixTrainHistoryScroll').innerHTML = '';
                    document.getElementById('matrixTrainApproveAll').style.display = 'none';
                    return;
                }
                loadPairHistory();
            });

            document.getElementById('matrixTrainSave').addEventListener('click', async function () {
                if (!trainCtx) return;
                const errEl = document.getElementById('matrixTrainError');
                // flatpickr показывает 24/Aug/2026; на сервер шлём Y-m-d
                const dateInput = document.getElementById('matrixTrainDate');
                const picked = dateInput._projectDatePicker?.selectedDates?.[0];
                if (!picked) {
                    errEl.textContent = @json(__('Select a date.'));
                    errEl.style.display = '';
                    return;
                }
                const date = picked.getFullYear() + '-'
                    + String(picked.getMonth() + 1).padStart(2, '0') + '-'
                    + String(picked.getDate()).padStart(2, '0');
                try {
                    const body = new FormData();
                    let endpoint;
                    if (trainCtx.courseRow) {
                        // SCA-курс: дата на строку, без форм 112/132
                        endpoint = @json(route('trainings.matrixCourseDate.store'));
                        body.append('matrix_row_id', trainCtx.courseRow);
                        body.append('date_training', date);
                        body.append('user_id', trainCtx.user);
                    } else {
                        endpoint = @json(route('trainings.createTraining'));
                        body.append('manuals_id[]', trainCtx.manual);
                        body.append('date_training[]', date);
                        body.append('form_type[]', '112');
                        body.append('user_id', trainCtx.user);
                        const cb132 = document.getElementById('matrixTrain132');
                        if (cb132 && cb132.checked) {
                            body.append('create_form_132', '1');
                        }
                    }
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json' },
                        body: body,
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Error');
                    }
                    // Без перезагрузки: обновить ячейку из свежей истории и закрыть модалку
                    try {
                        const histResp = await fetch(@json(route('trainings.matrixPairHistory')) + '?' + pairParams(), {
                            headers: { 'Accept': 'application/json' },
                        });
                        const hist = await histResp.json();
                        if (histResp.ok && hist.success) {
                            updateCellFromRecords(hist.records);
                        }
                    } catch (ignored) {}
                    bootstrap.Modal.getInstance(modalEl)?.hide();
                } catch (error) {
                    errEl.textContent = error.message;
                    errEl.style.display = '';
                }
            });
        })();
    </script>

    @if($canManage)
        {{-- Модалка Personnel: кто показан колонками матрицы --}}
        <div class="modal fade" id="matrixPersonnelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content bg-gradient">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Matrix personnel') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('trainings.matrixPersonnel.update') }}">
                        @csrf
                        <div class="modal-body">
                            <small class="text-muted d-block mb-2">{{ __('Checked people are shown as matrix columns. Trainings are kept either way.') }}</small>
                            <input type="search" class="form-control form-control-sm mb-2" id="personnelSearch"
                                   placeholder="{{ __('Search by name / stamp / role…') }}" autocomplete="off">
                            <div id="personnelList" style="max-height: 55vh; overflow-y: auto;">
                                @foreach($personnel as $person)
                                    <label class="d-flex align-items-center gap-2 mb-1 personnel-row" style="cursor: pointer;">
                                        <input type="checkbox" name="user_ids[]" value="{{ $person->id }}"
                                               {{ $person->show_in_training_matrix ? 'checked' : '' }}>
                                        <span class="text-truncate">
                                            <span class="text-muted" style="font-variant-numeric: tabular-nums;">{{ $person->stamp }}</span>
                                            {{ $person->selection_name }}
                                            <span class="text-muted small">— {{ $person->role->name ?? '' }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="modal-footer py-1">
                            <button type="submit" class="btn btn-outline-primary btn-sm">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Модалка добавления/редактирования строки матрицы --}}
        <div class="modal fade" id="matrixRowModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content bg-gradient">
                    <div class="modal-header">
                        <h5 class="modal-title" id="matrixRowModalTitle">{{ __('Add matrix row') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" id="matrixRowForm" action="{{ route('trainings.matrixRows.store') }}">
                        @csrf
                        <input type="hidden" name="_method" value="POST" id="matrixRowMethod">
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="form-label">{{ __('Group') }}</label>
                                <select name="training_category_id" id="matrixRowCategory" class="form-select">
                                    <option value="">{{ __('— new group —') }}</option>
                                    @foreach($allCategories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mt-2" id="matrixRowNewCategoryWrap">
                                <label class="form-label">{{ __('New group name') }}</label>
                                <input type="text" name="new_category_name" id="matrixRowNewCategory" class="form-control">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="matrixRowNewCategorySca" name="is_sca" value="1" {{ $scaMode ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="matrixRowNewCategorySca">
                                        {{ __('SCA group (courses; cells only for SCA-qualified people)') }}
                                    </label>
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label class="form-label">{{ __('Unit Description (column 1)') }}</label>
                                <input type="text" name="description" id="matrixRowDescription" class="form-control">
                            </div>
                            <div class="form-group mt-2">
                                <label class="form-label">{{ __('Part Number / Course name') }}</label>
                                <input type="text" name="part_number" id="matrixRowPartNumber" class="form-control" required>
                            </div>
                            <div class="form-group mt-2">
                                <label class="form-label">{{ __('Linked CMM') }}</label>
                                <select name="manual_id" id="matrixRowManual" class="form-select">
                                    <option value="">{{ __('— not registered —') }}</option>
                                    @foreach($unlinkedManuals as $m)
                                        <option value="{{ $m->id }}">{{ $m->unit_name_training }} ({{ $m->title }})</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('Leave empty for SCA courses.') }}</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <form method="POST" id="matrixRowMoveForm" class="d-none">@csrf<input type="hidden" name="direction" id="matrixRowMoveDirection"></form>
        <form method="POST" id="matrixRowDeleteForm" class="d-none">@csrf @method('DELETE')</form>

        <script>
            const matrixRowRoutes = {
                store: @json(route('trainings.matrixRows.store')),
                update: @json(route('trainings.matrixRows.update', ['row' => '__ID__'])),
                move: @json(route('trainings.matrixRows.move', ['row' => '__ID__'])),
                destroy: @json(route('trainings.matrixRows.destroy', ['row' => '__ID__'])),
                categoryMove: @json(route('trainings.matrixCategories.move', ['category' => '__ID__'])),
                toggleActive: @json(route('trainings.matrixRows.toggleActive', ['row' => '__ID__'])),
            };

            function matrixCategoryMove(id, direction) {
                const form = document.getElementById('matrixRowMoveForm');
                form.action = matrixRowRoutes.categoryMove.replace('__ID__', id);
                document.getElementById('matrixRowMoveDirection').value = direction;
                form.submit();
            }

            function matrixRowToggleActive(id) {
                const form = document.getElementById('matrixRowMoveForm');
                form.action = matrixRowRoutes.toggleActive.replace('__ID__', id);
                document.getElementById('matrixRowMoveDirection').value = '';
                form.submit();
            }

            // Поиск в модалке Personnel (имя / stamp / роль)
            const personnelSearch = document.getElementById('personnelSearch');
            if (personnelSearch) {
                personnelSearch.addEventListener('input', function () {
                    const q = this.value.trim().toLowerCase();
                    document.querySelectorAll('#personnelList .personnel-row').forEach(function (row) {
                        row.style.display = (!q || row.textContent.toLowerCase().includes(q)) ? '' : 'none';
                    });
                });
            }

            function matrixRowToggleNewCategory() {
                const select = document.getElementById('matrixRowCategory');
                document.getElementById('matrixRowNewCategoryWrap').style.display = select.value === '' ? '' : 'none';
            }
            document.getElementById('matrixRowCategory').addEventListener('change', matrixRowToggleNewCategory);

            function matrixRowModalReset() {
                document.getElementById('matrixRowModalTitle').textContent = @json(__('Add matrix row'));
                const form = document.getElementById('matrixRowForm');
                form.action = matrixRowRoutes.store;
                document.getElementById('matrixRowMethod').value = 'POST';
                document.getElementById('matrixRowCategory').value = document.querySelector('#matrixRowCategory option:nth-child(2)')?.value ?? '';
                document.getElementById('matrixRowNewCategory').value = '';
                document.getElementById('matrixRowDescription').value = '';
                document.getElementById('matrixRowPartNumber').value = '';
                matrixRowSetManualOptions(null, null);
                matrixRowToggleNewCategory();
            }

            function matrixRowModalEdit(row) {
                document.getElementById('matrixRowModalTitle').textContent = @json(__('Edit matrix row')) + ' — ' + row.part_number;
                const form = document.getElementById('matrixRowForm');
                form.action = matrixRowRoutes.update.replace('__ID__', row.id);
                document.getElementById('matrixRowMethod').value = 'PATCH';
                document.getElementById('matrixRowCategory').value = row.category;
                document.getElementById('matrixRowNewCategory').value = '';
                document.getElementById('matrixRowDescription').value = row.description ?? '';
                document.getElementById('matrixRowPartNumber').value = row.part_number;
                matrixRowSetManualOptions(row.manual_id, row.manual_label);
                matrixRowToggleNewCategory();
            }

            // В edit-режиме к списку несвязанных CMM добавляется текущий привязанный.
            function matrixRowSetManualOptions(currentId, currentLabel) {
                const select = document.getElementById('matrixRowManual');
                if (!select) return; // SCA-режим: курсы без CMM
                select.querySelector('option[data-current]')?.remove();
                if (currentId) {
                    const opt = document.createElement('option');
                    opt.value = currentId;
                    opt.textContent = currentLabel ?? ('#' + currentId);
                    opt.dataset.current = '1';
                    select.appendChild(opt);
                }
                select.value = currentId ?? '';
            }

            function matrixRowMove(id, direction) {
                const form = document.getElementById('matrixRowMoveForm');
                form.action = matrixRowRoutes.move.replace('__ID__', id);
                document.getElementById('matrixRowMoveDirection').value = direction;
                form.submit();
            }

            function matrixRowDelete(id, pn) {
                window.appConfirm(@json(__('Delete matrix row')) + ' ' + pn + '?', { okText: @json(__('Delete')), okClass: 'btn-danger' })
                    .then(function (ok) {
                        if (!ok) return;
                        const form = document.getElementById('matrixRowDeleteForm');
                        form.action = matrixRowRoutes.destroy.replace('__ID__', id);
                        form.submit();
                    });
            }
        </script>
    @endif
@endsection
