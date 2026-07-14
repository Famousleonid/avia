@extends('admin.master')

@section('content')
    <style>
        .library-units-page .card,
        .library-units-page .btn,
        .library-units-page .form-control,
        .library-units-page .form-select {
            border-radius: 6px;
        }

        .library-units-page {
            flex: 1 1 auto;
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        .library-units-page .card {
            flex: 1 1 auto;
            height: 100%;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .library-units-page .card-body {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .library-units-page .unit-filter-form {
            flex: 0 0 auto;
        }

        .library-units-page .table-responsive {
            flex: 1 1 0;
            min-height: 0;
            background: var(--avia-panel);
            border: 1px solid var(--avia-border);
            max-height: none;
            overflow: auto;
        }

        .library-units-page .table {
            --bs-table-bg: var(--avia-panel);
            --bs-table-color: var(--avia-text);
            --bs-table-border-color: var(--avia-border);
            --bs-table-hover-bg: var(--avia-hover);
            --bs-table-hover-color: var(--avia-text);
            --bs-table-striped-bg: var(--avia-surface);
            --bs-table-striped-color: var(--avia-text);
            background: var(--avia-panel) !important;
            color: var(--avia-text);
        }

        .library-units-page .table > :not(caption) > * > * {
            background-color: var(--avia-panel) !important;
            border-color: var(--avia-border);
            color: var(--avia-text);
        }

        .library-units-page .table > :not(caption) > * > .text-warning {
            color: var(--bs-warning) !important;
        }

        .library-units-page .table-hover > tbody > tr:hover > * {
            background-color: var(--avia-hover) !important;
            color: var(--avia-text);
        }

        .library-units-page .library-unit-row {
            cursor: pointer;
        }

        .library-units-page thead th {
            background: var(--avia-surface-raised) !important;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .library-units-page .unit-pn {
            font-weight: 700;
            letter-spacing: 0;
        }

        .library-units-page .unit-muted {
            color: #8c96a3;
        }

        .library-units-page .unit-actions {
            width: 64px;
        }

        .library-units-page .unit-search-wrap {
            position: relative;
        }

        .library-units-page .unit-search-clear {
            position: absolute;
            top: 50%;
            right: .35rem;
            transform: translateY(-50%);
            width: 1.55rem;
            height: 1.55rem;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: #8c96a3;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .library-units-page .unit-search-clear:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, .12);
        }

        .library-units-page #manualFilter + .select2 {
            width: 100% !important;
        }

        .library-units-page #manualFilter + .select2 .select2-selection--single {
            min-height: 31px;
            border-radius: 6px;
        }

        .library-units-page #manualFilter + .select2 .select2-selection__rendered {
            line-height: 29px;
        }

        .library-units-page #manualFilter + .select2 .select2-selection__arrow {
            height: 29px;
        }
    </style>

    <div class="container-fluid library-units-page">
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="mb-0 text-primary">
                        {{ __('Units') }}
                        <span class="text-success">({{ $units->total() }})</span>
                    </h5>

                    <button type="button"
                            class="btn btn-outline-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#createUnitModal">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('Add Unit') }}
                    </button>
                </div>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('library.units.index') }}" class="row g-2 align-items-end mb-3 unit-filter-form">
                    <div class="col-12 col-xl-6">
                        <label for="unitSearch" class="form-label small text-muted mb-1">{{ __('Search') }}</label>
                        <div class="unit-search-wrap">
                            <input id="unitSearch"
                                   type="text"
                                   class="form-control form-control-sm pe-5"
                                   name="q"
                                   value="{{ $q }}"
                                   placeholder="PN, name, description, eff code">
                            <button type="button"
                                    class="unit-search-clear {{ $q === '' ? 'd-none' : '' }}"
                                    id="unitSearchClear"
                                    title="Clear search"
                                    aria-label="Clear search">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <label for="manualFilter" class="form-label small text-muted mb-1">{{ __('CMM') }}</label>
                        <select id="manualFilter" class="form-select form-select-sm" name="manual_id">
                            <option value="">{{ __('All') }}</option>
                            <option value="pending" @selected($manualFilter === 'pending')>{{ __('Manual pending') }}</option>
                            @foreach($manuals as $manual)
                                <option value="{{ $manual->id }}" @selected((string) $manualFilter === (string) $manual->id)>
                                    {{ $manual->number ?: '-' }} {{ $manual->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover align-middle mb-0">
                        <thead class="bg-gradient">
                        <tr>
                            <th class="text-primary">ID</th>
                            <th class="text-primary">{{ __('Part Number') }}</th>
                            <th class="text-primary">{{ __('Name') }}</th>
                            <th class="text-primary">{{ __('Description') }}</th>
                            <th class="text-primary">{{ __('CMM') }}</th>
                            <th class="text-primary text-center">{{ __('Verified') }}</th>
                            <th class="text-primary text-center">{{ __('WO') }}</th>
                            <th class="text-primary">{{ __('Created') }}</th>
                            <th class="text-primary text-center unit-actions">{{ __('Actions') }}</th>
                        </tr>
                        </thead>
                        <tbody id="libraryUnitsRows">
                            @include('admin.library_units.partials.rows', ['units' => $units, 'showEmpty' => true])
                        </tbody>
                    </table>
                </div>

                <div id="libraryUnitsLoadState"
                     class="d-none"
                     data-next-page-url="{{ $units->nextPageUrl() }}"
                     data-has-more="{{ $units->hasMorePages() ? '1' : '0' }}"></div>

                <noscript>
                    <div class="mt-3">
                        {{ $units->links() }}
                    </div>
                </noscript>
            </div>
        </div>
    </div>

    @php
        $indexQ = $q;
        $indexManualId = $manualFilter;
    @endphp

    <div class="modal fade" id="createUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('library.units.store') }}">
                    @csrf
                    <input type="hidden" name="index_q" value="{{ $indexQ }}">
                    <input type="hidden" name="index_manual_id" value="{{ $indexManualId }}">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Add Unit') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.library_units.partials.form', ['prefix' => 'create', 'unit' => null, 'manuals' => $manuals])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-outline-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editUnitForm" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="index_q" value="{{ $indexQ }}">
                    <input type="hidden" name="index_manual_id" value="{{ $indexManualId }}">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Edit Unit') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.library_units.partials.form', ['prefix' => 'edit', 'unit' => null, 'manuals' => $manuals])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-outline-primary">{{ __('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteUnitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="deleteUnitForm" action="">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="index_q" value="{{ $indexQ }}">
                    <input type="hidden" name="index_manual_id" value="{{ $indexManualId }}">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Delete Unit') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-1">{{ __('Delete') }} <strong id="deleteUnitLabel"></strong>?</p>
                        <p class="text-danger small mb-0 d-none" id="deleteUnitBlocked">
                            {{ __('This unit is linked to workorders and cannot be deleted.') }}
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-outline-danger" id="deleteUnitSubmit">{{ __('Delete') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const scrollContainer = document.querySelector('.library-units-page .table-responsive');
            const rowsBody = document.getElementById('libraryUnitsRows');
            const loadState = document.getElementById('libraryUnitsLoadState');
            const filterForm = document.querySelector('.library-units-page .unit-filter-form');
            const searchInput = document.getElementById('unitSearch');
            const searchClear = document.getElementById('unitSearchClear');
            const manualFilter = document.getElementById('manualFilter');
            let isLoadingUnits = false;

            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2 && manualFilter) {
                window.jQuery(manualFilter).select2({
                    width: '100%',
                    placeholder: 'All',
                }).on('change', function () {
                    filterForm?.submit();
                });
            } else {
                manualFilter?.addEventListener('change', function () {
                    filterForm?.submit();
                });
            }

            searchInput?.addEventListener('input', function () {
                searchClear?.classList.toggle('d-none', searchInput.value.trim() === '');
            });

            searchInput?.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    filterForm?.submit();
                }
            });

            searchClear?.addEventListener('click', function () {
                if (!searchInput) return;
                searchInput.value = '';
                searchClear.classList.add('d-none');
                filterForm?.submit();
            });

            function setLoadState(message, isError = false) {
                if (!loadState) return;

                loadState.textContent = '';
                loadState.classList.add('d-none');
                loadState.dataset.lastMessage = message || '';
                loadState.dataset.lastError = isError ? '1' : '0';
            }

            async function loadMoreUnits() {
                if (!scrollContainer || !rowsBody || !loadState || isLoadingUnits || loadState.dataset.hasMore !== '1') {
                    return;
                }

                const url = loadState.dataset.nextPageUrl || '';
                if (!url) return;

                isLoadingUnits = true;
                setLoadState('');

                try {
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data?.message || 'Failed to load units.');
                    }

                    if (data.html) {
                        const template = document.createElement('template');
                        template.innerHTML = data.html.trim();
                        Array.from(template.content.children).forEach(function (row) {
                            rowsBody.appendChild(row);
                        });
                    }

                    loadState.dataset.nextPageUrl = data.next_page_url || '';
                    loadState.dataset.hasMore = data.has_more ? '1' : '0';

                    setLoadState('');
                } catch (error) {
                    loadState.dataset.hasMore = '1';
                    setLoadState(error.message || 'Failed to load units.', true);
                } finally {
                    isLoadingUnits = false;
                }
            }

            scrollContainer?.addEventListener('scroll', function () {
                if (scrollContainer.scrollTop + scrollContainer.clientHeight >= scrollContainer.scrollHeight - 90) {
                    loadMoreUnits();
                }
            });

            if (scrollContainer && scrollContainer.scrollHeight <= scrollContainer.clientHeight) {
                loadMoreUnits();
            }

            const editModal = document.getElementById('editUnitModal');
            const editForm = document.getElementById('editUnitForm');
            const updateUrlTemplate = @json(route('library.units.update', ['unit' => '__unit__']));

            function fillEditForm(unit) {
                editForm.action = updateUrlTemplate.replace('__unit__', encodeURIComponent(unit.id || ''));

                ['part_number', 'name', 'description', 'eff_code'].forEach(function (field) {
                    const input = editForm.querySelector('[name="' + field + '"]');
                    if (input) input.value = unit[field] || '';
                });

                const manualSelect = editForm.querySelector('[name="manual_id"]');
                if (manualSelect) manualSelect.value = unit.manual_id || '';

                const verified = editForm.querySelector('[name="verified"]');
                if (verified) verified.checked = Boolean(unit.verified);
            }

            editModal?.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger?.hasAttribute('data-unit')) return;

                fillEditForm(JSON.parse(trigger.getAttribute('data-unit') || '{}'));
            });

            rowsBody?.addEventListener('click', function (event) {
                if (event.target.closest('button, a, input, select, textarea, label, .select2-container')) {
                    return;
                }

                const row = event.target.closest('.library-unit-row');
                if (!row || !editModal) return;

                fillEditForm(JSON.parse(row.getAttribute('data-unit') || '{}'));
                bootstrap.Modal.getOrCreateInstance(editModal).show(row);
            });

            rowsBody?.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') return;

                const row = event.target.closest('.library-unit-row');
                if (!row) return;

                event.preventDefault();
                fillEditForm(JSON.parse(row.getAttribute('data-unit') || '{}'));
                bootstrap.Modal.getOrCreateInstance(editModal).show(row);
            });

            const deleteModal = document.getElementById('deleteUnitModal');
            const deleteForm = document.getElementById('deleteUnitForm');
            const deleteLabel = document.getElementById('deleteUnitLabel');
            const deleteBlocked = document.getElementById('deleteUnitBlocked');
            const deleteSubmit = document.getElementById('deleteUnitSubmit');
            const deleteUrlTemplate = @json(route('library.units.destroy', ['unit' => '__unit__']));

            deleteModal?.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const unitId = button?.getAttribute('data-unit-id') || '';
                const label = button?.getAttribute('data-unit-label') || '';
                const workordersCount = Number(button?.getAttribute('data-workorders-count') || 0);

                deleteForm.action = deleteUrlTemplate.replace('__unit__', encodeURIComponent(unitId));
                deleteLabel.textContent = label;
                deleteBlocked.classList.toggle('d-none', workordersCount === 0);
                deleteSubmit.disabled = workordersCount > 0;
            });
        });
    </script>
@endsection
