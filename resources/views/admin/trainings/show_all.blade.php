@extends('admin.master')

@section('content')
    <style>
        .table-container {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 82vh;
            position: relative;
        }

        .training-table {
            width: max-content;
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .training-table th,
        .training-table td {
            white-space: nowrap;
            vertical-align: middle;
            padding: 5px 8px;
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

        /* Замороженные левые колонки: описание (0) + парт-номер (220px) */
        .training-table th.col-unit,
        .training-table td.col-unit {
            position: sticky !important;
            left: 0;
            min-width: 220px;
            max-width: 220px;
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
            left: 220px;
            min-width: 250px;
            max-width: 250px;
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
            min-width: 96px;
            width: 96px;
            text-align: center;
        }

        .training-table th.user-column .stamp-no {
            display: block;
            font-size: 10px;
            color: var(--avia-text-muted);
            font-weight: 400;
        }

        /* Строка-заголовок группы — как зелёные секции в Excel */
        .training-table td.group-row {
            background: rgba(93, 158, 91, 0.28) !important;
            color: var(--avia-text) !important;
            font-weight: 700;
            font-size: 12.5px;
            letter-spacing: .04em;
            text-align: center;
            position: sticky;
            left: 0;
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

        .matrix-legend {
            font-size: 12px;
            color: var(--avia-text-muted);
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
    </style>

    <div class="container-fluid px-4 px-xl-5">
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0">PART NUMBER APPROVED PERSONNEL</h6>
                    @if($canManage)
                        <div class="d-flex align-items-center gap-3">
                            @if($uncategorizedCount > 0)
                                <span class="badge-no-cmm" title="{{ __('CMMs with training PN that are not linked to any matrix row') }}">
                                    {{ __('CMM not in matrix:') }} {{ $uncategorizedCount }}
                                </span>
                            @endif
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
                    @php $colspan = 2 + $users->count(); @endphp
                    <div class="table-container">
                        <table class="table training-table table-bordered align-middle mb-0">
                            <thead>
                            <tr>
                                <th class="col-unit">{{ __('Unit Description') }}</th>
                                <th class="col-part text-center">PART NUMBER APPROVED</th>
                                @foreach($users as $user)
                                    <th class="user-column" title="Stamp {{ $user->stamp }}">
                                        {{ $user->selection_name }}
                                        <span class="stamp-no">{{ $user->stamp }}</span>
                                    </th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($categories as $category)
                                <tr>
                                    <td class="group-row" colspan="{{ $colspan }}">{{ $category->name }}</td>
                                </tr>
                                @foreach($category->rows as $row)
                                    <tr class="{{ $row->manual_id ? '' : 'row-no-cmm' }}">
                                        <td class="col-unit" title="{{ $row->description }}">
                                            {{ $row->description ?? '' }}
                                        </td>
                                        <td class="col-part">
                                            {{ $row->part_number }}
                                            @unless($row->manual_id)
                                                <span class="badge-no-cmm" title="{{ __('No CMM registered in avia for this unit') }}">no CMM</span>
                                            @endunless
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
                                                    <button type="button" title="{{ __('Move up') }}" onclick="matrixRowMove({{ $row->id }}, 'up')">▲</button>
                                                    <button type="button" title="{{ __('Move down') }}" onclick="matrixRowMove({{ $row->id }}, 'down')">▼</button>
                                                    <button type="button" title="{{ __('Delete row') }}" onclick="matrixRowDelete({{ $row->id }}, @json($row->part_number))">✕</button>
                                                </span>
                                            @endif
                                        </td>
                                        @foreach($users as $user)
                                            <td class="user-column">
                                                @php $cell = $row->manual_id ? ($cells[$row->manual_id][$user->id] ?? null) : null; @endphp
                                                @if($cell === null)
                                                    <span class="text-muted">-</span>
                                                @elseif($cell['kind'] === 'x')
                                                    <span class="training-x"
                                                          @if($cell['date']) title="{{ __('Last training:') }} {{ $cell['date']->format('M-d-Y') }} — {{ __('refresh required (MP-20)') }}"
                                                          @else title="{{ __('Old training (no date on record)') }}" @endif>X</span>
                                                @else
                                                    <span class="{{ $cell['red'] ? 'training-date-old' : 'training-date-fresh' }}">
                                                        {{ $cell['date']->format('M-d-Y') }}
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
                        <span><span class="training-date-fresh">Jan-01-2026</span> — {{ __('training up to date') }}</span>
                        <span><span class="training-date-old">Jan-01-2025</span> — {{ __('older than :days days, refresh required', ['days' => config('trainings.matrix_red_after_days', 350)]) }}</span>
                        <span><span class="training-x">X</span> — {{ __('trained in the past; unit not currently worked on (older than :years years or old training)', ['years' => config('trainings.matrix_legacy_after_years', 3)]) }}</span>
                        <span><span class="text-muted">-</span> — {{ __('never trained') }}</span>
                        <span><span class="badge-no-cmm">no CMM</span> — {{ __('unit not registered in avia') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($canManage)
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
                            </div>
                            <div class="form-group mt-2">
                                <label class="form-label">{{ __('Unit Description (column 1)') }}</label>
                                <input type="text" name="description" id="matrixRowDescription" class="form-control">
                            </div>
                            <div class="form-group mt-2">
                                <label class="form-label">{{ __('Part Number (as in matrix)') }}</label>
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
            };

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
                if (!confirm(@json(__('Delete matrix row')) + ' ' + pn + '?')) return;
                const form = document.getElementById('matrixRowDeleteForm');
                form.action = matrixRowRoutes.destroy.replace('__ID__', id);
                form.submit();
            }
        </script>
    @endif
@endsection
