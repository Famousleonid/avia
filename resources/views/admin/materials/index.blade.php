{{--materials--}}

@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 120px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-left: 10px;
        }

        .table th:nth-child(1), .table td:nth-child(1) { min-width: 50px; max-width: 70px; }
        .table th:nth-child(2), .table td:nth-child(2) { min-width: 50px; max-width: 80px; }
        .table th:nth-child(3), .table td:nth-child(3) { min-width: 50px; max-width: 150px; }
        .table th:nth-child(5), .table td:nth-child(5) { min-width: 50px; max-width: 70px; }

        .table thead th {
            position: sticky;
            height: 50px;
            top: 0;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        @media (max-width: 1200px) {
            .table th:nth-child(5), .table td:nth-child(5),
            .table th:nth-child(2), .table td:nth-child(2) { display: none; }
        }

        .table th.sortable { cursor: pointer; }

        /* Search in header */
        .header-tools {
            display: flex;
            gap: .75rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .clearable-input {
            position: relative;
            width: 420px;
            max-width: 100%;
        }

        .clearable-input .form-control {
            padding-right: 2.25rem;
        }

        .clearable-input .btn-clear {
            position: absolute;
            right: .45rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        .search-hint {
            font-size: .85rem;
            opacity: .75;
        }

        td.editable-cell {
            position: relative;
        }

        /* текст в ячейке не убираем — просто скрываем во время редактирования, чтобы сохранить высоту */
        td.editable-cell.is-editing .cell-text {
            visibility: hidden;
        }

        /* оверлей поверх ячейки */
        .inline-editor {
            position: absolute;
            inset: 2px;              /* чуть отступ, чтобы влезла рамка */
            z-index: 10;
            display: flex;
            gap: 6px;
            align-items: center;
        }

        /* поле ввода ровно высотой строки, без ресайза */
        .inline-editor .inline-input {
            height: 100%;
            width: 100%;
            min-height: 28px;        /* под table-sm */
            line-height: 1.2;
            resize: none;
            overflow: hidden;        /* чтобы не раздувало */
            white-space: nowrap;     /* одна строка */
        }

        /* кнопки маленькие, чтобы не расширяли */
        .inline-editor .inline-actions {
            display: flex;
            gap: 6px;
            flex: 0 0 auto;
        }

        .inline-editor .btn {
            padding: 2px 6px;
            line-height: 1;
        }

    </style>

    <div class="card dir-page">

        <div class="card-header my-1 shadow">
            <div class="row g-2 align-items-center">
                {{-- Left: title --}}
                <div class="col-12 col-md-3">
                    <h5 class="text-primary mb-0">
                        {{__('Materials')}} (
                        <span class="text-success">{{$materials->count()}}</span>
                        )
                    </h5>
                </div>

                {{-- Center: search --}}
                <div class="col-12 col-md-6 d-flex justify-content-md-center">
                    <div class="clearable-input w-100" style="max-width:520px;">
                        <input id="searchInput" type="text" class="form-control form-control-sm w-100"
                               placeholder="Search...">
                        <button type="button" class="btn-clear text-secondary" id="clearSearchBtn" title="Clear">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>

                {{-- Right: actions --}}
                <div class="col-12 col-md-3 d-flex justify-content-md-end">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                        {{ __('Add materials') }}
                    </button>
                </div>
            </div>
        </div>

        @if(count($materials))

            <div class="table-wrapper me-3 p-2 pt-0">
                <table id="cmmTable" class="display table table-sm table-hover table-bordered dir-table">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary sortable bg-gradient" data-direction="asc">
                            {{__('Code')}}<i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary sortable bg-gradient">
                            {{__('Material')}}<i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary sortable bg-gradient">
                            {{__('Specification')}}<i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary sortable bg-gradient">
                            {{__('Description')}}<i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-primary text-center bg-gradient">{{__('Action')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($materials as $material)
                        <tr>
                            <td class="">{{$material->code}}</td>
                            <td class="">{{$material->material}}</td>
                            <td class="">{{$material->specification}}</td>
                            <td class="editable-cell"
                                data-id="{{ $material->id }}"
                                data-field="description"
                                data-can-edit="1"
                                data-value="{{ e($material->description) }}">
                                <span class="cell-text">{{ $material->description }}</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal"
                                        data-bs-target="#editModal"
                                        onclick="populateEditModal({{ $material->id }},
                                        @js($material->code),
                                        @js($material->material),
                                        @js($material->specification),
                                        @js($material->description)
                                            )">
                                    <i class="bi bi-pencil-square" title="Edit"></i>
                                </button>

                                @roles('Admin|Manager')
                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#deleteModal"
                                        onclick="populateDeleteModal({{ $material->id }}, @js($material->code))">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endroles
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

        @else
            <p class="p-3 mb-0">Materials not created</p>
        @endif
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createForm" method="POST" action="{{ route('materials.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="createCode" class="form-label">Code</label>
                            <input type="text" id="createCode" name="code" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="createMaterial" class="form-label">Material</label>
                            <input type="text" id="createMaterial" name="material" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="createSpecification" class="form-label">Specification</label>
                            <input type="text" id="createSpecification" name="specification" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="createDescription" class="form-label">Description</label>
                            <textarea id="createDescription" name="description" class="form-control"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST" action="{{ route('materials.update', ':id') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="editId" name="id">
                        <div class="mb-3">
                            <label for="editCode" class="form-label">Code</label>
                            <input type="text" id="editCode" name="code" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editMaterial" class="form-label">Material</label>
                            <input type="text" id="editMaterial" name="material" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSpecification" class="form-label">Specification</label>
                            <input type="text" id="editSpecification" name="specification" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea id="editDescription" name="description" class="form-control"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="deleteModalTitle" class="modal-title">Delete Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this material?</p>
                    <form id="deleteForm" method="POST" action="{{ route('materials.destroy', ':id') }}">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" id="deleteId" name="id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function spinOn() {
            if (typeof window.safeShowSpinner === 'function') return window.safeShowSpinner();
            if (typeof window.showLoadingSpinner === 'function') return window.showLoadingSpinner();
        }
        function spinOff() {
            if (typeof window.safeHideSpinner === 'function') return window.safeHideSpinner();
            if (typeof window.hideLoadingSpinner === 'function') return window.hideLoadingSpinner();
        }

        function debounce(fn, ms) {
            let t;
            return function (...args) {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), ms);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('cmmTable');
            const tbody = table.querySelector('tbody');

            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearchBtn');
            const headers = document.querySelectorAll('.sortable');

            // -------------------------------
            // Sorting (как у тебя)
            // -------------------------------
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header) + 1;
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;

                    headers.forEach(h => {
                        const icon = h.querySelector('i');
                        if (icon) icon.className = 'bi bi-chevron-expand ms-1';
                    });
                    const currentIcon = header.querySelector('i');
                    if (currentIcon) currentIcon.className = direction === 'asc'
                        ? 'bi bi-arrow-up ms-1'
                        : 'bi bi-arrow-down ms-1';

                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    rows.sort((a, b) => {
                        const aText = a.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        const bText = b.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                    });
                    rows.forEach(row => tbody.appendChild(row));

                    // после сортировки применим фильтр
                    applyFilter(searchInput.value);
                });
            });

            // -------------------------------
            // Search: обычный (1+ символ), debounce
            // -------------------------------
            let rowCache = [];

            function buildCache() {
                rowCache = Array.from(tbody.querySelectorAll('tr')).map(tr => ({
                    tr,
                    text: tr.innerText.toLowerCase()
                }));
            }

            function updateCacheForRow(tr) {
                const item = rowCache.find(x => x.tr === tr);
                if (item) item.text = tr.innerText.toLowerCase();
            }

            function applyFilter(raw) {
                const q = (raw || '').trim().toLowerCase();

                if (!q) {
                    rowCache.forEach(r => r.tr.style.display = '');
                    spinOff();
                    return;
                }

                spinOn();
                requestAnimationFrame(() => {
                    rowCache.forEach(r => {
                        r.tr.style.display = r.text.includes(q) ? '' : 'none';
                    });
                    spinOff();
                });
            }

            const debouncedFilter = debounce(applyFilter, 200);

            buildCache();

            searchInput.addEventListener('input', (e) => debouncedFilter(e.target.value));

            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                applyFilter('');
                searchInput.focus();
            });

            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    searchInput.value = '';
                    applyFilter('');
                }
            });

            // -------------------------------
            // Inline edit on double click (Description)
            // -------------------------------
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function startInlineEdit(td) {
                if (!td || td.dataset.editing === '1') return;
                if (td.dataset.canEdit !== '1') return;

                td.dataset.editing = '1';
                td.classList.add('is-editing');

                const id = td.dataset.id;
                const field = td.dataset.field;

                const textEl = td.querySelector('.cell-text');
                const oldValue = (td.dataset.value ?? '').toString();

                // создаём оверлей, не трогаем разметку строки
                const overlay = document.createElement('div');
                overlay.className = 'inline-editor';

                overlay.innerHTML = `
        <input type="text" class="form-control form-control-sm inline-input" value="${escapeHtmlAttr(oldValue)}">
        <div class="inline-actions">
            <button type="button" class="btn btn-warning btn-sm" title="Save">💾</button>
            <button type="button" class="btn btn-secondary btn-sm" title="Cancel">✖</button>
        </div>
    `;

                td.appendChild(overlay);

                const input = overlay.querySelector('.inline-input');
                const btnSave = overlay.querySelector('.btn-warning');
                const btnCancel = overlay.querySelector('.btn-secondary');

                input.focus();
                input.setSelectionRange(input.value.length, input.value.length);

                function cleanup() {
                    overlay.remove();
                    td.dataset.editing = '0';
                    td.classList.remove('is-editing');
                }

                function cancel() {
                    cleanup();
                }

                async function save() {
                    const newValue = input.value;

                    btnSave.disabled = true;
                    btnCancel.disabled = true;

                    try {
                        const url = `{{ route('materials.inline', ['material' => '___ID___']) }}`.replace('___ID___', id);

                        const res = await fetch(url, {
                            method: 'PATCH',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ field, value: newValue })
                        });

                        if (!res.ok) {
                            const txt = await res.text();
                            console.error('INLINE SAVE ERROR', res.status, txt);
                            showNotification(`Save failed (${res.status}). See console.`, 'error');
                            throw new Error('HTTP ' + res.status);
                        }

                        const data = await res.json();
                        const newText = (data.value ?? '').toString();

                        if (textEl) textEl.innerText = newText;
                        td.dataset.value = newText;

                        cleanup();
                        updateCacheForRow(td.closest('tr'));

                    } catch (e) {
                        console.error(e);
                        showNotification('Save failed. Check console.', 'error');
                        btnSave.disabled = false;
                        btnCancel.disabled = false;
                    }
                }


                btnCancel.addEventListener('click', cancel);
                btnSave.addEventListener('click', save);

                // Enter = save, Esc = cancel
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        cancel();
                    }
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        save();
                    }
                });

                // клик вне ячейки — отмена (чтобы не зависало)
                const onDocClick = (e) => {
                    if (!td.contains(e.target)) {
                        document.removeEventListener('mousedown', onDocClick, true);
                        cancel();
                    }
                };
                document.addEventListener('mousedown', onDocClick, true);
            }

            function escapeHtmlAttr(str) {
                return (str ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }


            // dblclick handler
            tbody.addEventListener('dblclick', (e) => {
                const td = e.target.closest('td.editable-cell');
                if (!td) return;
                startInlineEdit(td);
            });

            function escapeHtml(str) {
                return (str ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }
        });

        // твои модалки оставляем как есть
        function populateEditModal(id, code, material, specification, description) {
            document.getElementById('editId').value = id;
            document.getElementById('editCode').value = code ?? '';
            document.getElementById('editMaterial').value = material ?? '';
            document.getElementById('editSpecification').value = specification ?? '';
            document.getElementById('editDescription').value = description ?? '';
            document.getElementById('editForm').action = `{{ route('materials.update', ':id') }}`.replace(':id', id);
        }

        function populateDeleteModal(id, code) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').action = `{{ route('materials.destroy', ':id') }}`.replace(':id', id);
            document.getElementById('deleteModalTitle').innerText = `Delete Material (Code: ${code})`;
        }
    </script>

@endsection
