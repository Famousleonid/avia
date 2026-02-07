{{-- resources/views/admin/directories/index.blade.php --}}
@extends('admin.master')

@section('content')

    <div class="card border-0">

        {{-- TOPBAR --}}
        <div class="card-header p-0 mx-3 bg-transparent border-0">
            <div class="dir-topbar px-3 py-1">
                <div class="row g-2 align-items-center">

                    <div class="col-12 col-lg-3">
                        <h5 class="mb-0 text-primary">
                            {{ __($cfg['title']) }}
                            <span class="text-secondary">(</span>
                            <span class="text-success">{{ $items->total() }}</span>
                            <span class="text-secondary">)</span>
                        </h5>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="d-flex justify-content-lg-center">
                            <div class="position-relative w-100" style="max-width:420px;">
                                <input id="dirSearchInput" type="text"
                                       class="form-control pe-5 dir-search"
                                       placeholder="Search..."
                                       value="{{ $q ?? '' }}">
                                <button type="button"
                                        class="btn position-absolute top-50 end-0 translate-middle-y me-2 p-0 border-0 bg-transparent text-secondary"
                                        title="Clear"
                                        onclick="const i=document.getElementById('dirSearchInput'); i.value=''; i.dispatchEvent(new Event('input')); i.focus();">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-3">
                        <div class="d-flex justify-content-lg-end">
                            <button class="btn btn-outline-success btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#dirCreateModal">
                                <i class="bi bi-plus-circle me-1"></i>{{ __('Add') }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- BODY --}}
        <div class="card-body pt-1 m-0">
            @if($items->count())
                <div class="dir-panel ">
                    <div class="dir-table-wrap ">

                        <table id="dirTable" class="table table-sm table-hover table-bordered mb-0 align-middle shadow-lg">
                            <thead>
                            <tr>
                                @foreach($cfg['fields'] as $field => $label)
                                    <th class="text-primary sortable"
                                        data-sort-field="{{ $field }}"
                                        data-direction="asc"
                                        style="min-width:240px;">
                                        {{ __($label) }}
                                        <i class="bi bi-chevron-expand ms-1"></i>
                                    </th>
                                @endforeach
                                <th class="text-primary text-center" style="width:140px;">
                                    {{ __('Action') }}
                                </th>
                            </tr>
                            </thead>

                            <tbody id="dirTbody">
                            @foreach($items as $item)
                                <tr data-row data-id="{{ $item->id }}"
                                    @foreach($cfg['fields'] as $field => $label)
                                        data-{{ $field }}="{{ e((string)($item->{$field} ?? '')) }}"
                                    @endforeach
                                >
                                    @foreach($cfg['fields'] as $field => $label)
                                        <td title="{{ $item->{$field} ?? '' }}">
                                            {{ $item->{$field} ?? '' }}
                                        </td>
                                    @endforeach

                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-outline-primary btn-sm btn-icon me-2"
                                                    data-bs-toggle="modal" data-bs-target="#dirEditModal"
                                                    onclick="dirOpenEdit(this.closest('tr'))"
                                                    title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <button class="btn btn-outline-danger btn-sm btn-icon"
                                                    data-bs-toggle="modal" data-bs-target="#dirDeleteModal"
                                                    onclick="dirOpenDelete(this.closest('tr'))"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        {{-- No results --}}
                        <div id="dirNoResults" class="d-none p-4 text-center mt-2">
                            <div class="text-secondary mb-2">
                                <i class="bi bi-search" style="font-size: 1.6rem;"></i>
                            </div>
                            <div class="fw-semibold">No results</div>
                            <div class="small opacity-75">Try another search term.</div>
                        </div>

                    </div>
                </div>
            @else
                <div class="alert alert-secondary mb-0">
                    No records.
                </div>
            @endif
        </div>
    </div>


    {{-- ===================== MODALS ===================== --}}

    {{-- CREATE --}}
    <div class="modal fade " id="dirCreateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-gray shadow">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add') }} {{ __($cfg['title']) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form id="dirCreateForm" method="POST" action="{{ $cfg['baseUrl'] }}">
                        @csrf

                        @foreach($cfg['fields'] as $field => $label)
                            <div class="mb-3">
                                <label class="form-label" for="dirCreate_{{ $field }}">{{ __($label) }}</label>
                                <input type="text"
                                       id="dirCreate_{{ $field }}"
                                       name="{{ $field }}"
                                       class="form-control"
                                       required>
                            </div>
                        @endforeach

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle me-1"></i>Save
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>


    {{-- EDIT --}}
    <div class="modal fade " id="dirEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-gray shadow">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit') }} {{ __($cfg['title']) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form id="dirEditForm" method="POST" action="">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="dirEditId" name="id">

                        @foreach($cfg['fields'] as $field => $label)
                            <div class="mb-3">
                                <label class="form-label" for="dirEdit_{{ $field }}">{{ __($label) }}</label>
                                <input type="text"
                                       id="dirEdit_{{ $field }}"
                                       name="{{ $field }}"
                                       class="form-control"
                                       required>
                            </div>
                        @endforeach

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save2 me-1"></i>Update
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>


    {{-- DELETE --}}
    <div class="modal fade " id="dirDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-gray shadow">
                <div class="modal-header">
                    <h5 id="dirDeleteTitle" class="modal-title">{{ __('Delete') }} {{ __($cfg['title']) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        Are you sure you want to delete this record?
                    </div>

                    <form id="dirDeleteForm" method="POST" action="">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" id="dirDeleteId" name="id">

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash3 me-1"></i>Delete
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

@endsection


@section('scripts')
    <script>
        (function () {
            // cfg приходит полностью готовым из контроллера:
            // { title, baseUrl, firstField, fields: {name:"Name", code:"Code"} ... }
            const DIR = @json($cfg);

            const table = document.getElementById('dirTable');
            const tbody = document.getElementById('dirTbody');
            const searchInput = document.getElementById('dirSearchInput');
            const noResults = document.getElementById('dirNoResults');

            if (!table || !tbody || !searchInput || !noResults) return;

            // Active row highlight
            tbody.addEventListener('click', (e) => {
                const tr = e.target.closest('tr[data-row]');
                if (!tr) return;
                tbody.querySelectorAll('tr.is-active').forEach(r => r.classList.remove('is-active'));
                tr.classList.add('is-active');
            });

            function applyFilter() {
                const filter = (searchInput.value || '').trim().toLowerCase();
                let visible = 0;

                tbody.querySelectorAll('tr[data-row]').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const show = text.includes(filter);
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                const total = tbody.querySelectorAll('tr[data-row]').length;
                const showNo = total > 0 && visible === 0;

                table.classList.toggle('d-none', showNo);
                noResults.classList.toggle('d-none', !showNo);
            }

            // Sort by any column (string)
            function sortBy(field, th) {
                const dir = (th.dataset.direction === 'asc') ? 'desc' : 'asc';
                th.dataset.direction = dir;

                // reset icons on other headers
                table.querySelectorAll('th.sortable i').forEach(i => i.className = 'bi bi-chevron-expand ms-1');

                // set icon
                const icon = th.querySelector('i');
                if (icon) icon.className = (dir === 'asc') ? 'bi bi-arrow-up ms-1' : 'bi bi-arrow-down ms-1';

                const rows = Array.from(tbody.querySelectorAll('tr[data-row]'));

                rows.sort((a, b) => {
                    const av = (a.dataset[field] || '').trim();
                    const bv = (b.dataset[field] || '').trim();
                    const res = av.localeCompare(bv, undefined, { sensitivity: 'base' });
                    return dir === 'asc' ? res : -res;
                });

                rows.forEach(r => tbody.appendChild(r));
                applyFilter();
            }

            table.querySelectorAll('th.sortable').forEach(th => {
                th.addEventListener('click', () => {
                    const field = th.dataset.sortField || DIR.firstField;
                    sortBy(field, th);
                });
            });

            searchInput.addEventListener('input', applyFilter);

            // expose modal helpers
            window.dirOpenEdit = function (tr) {
                const id = tr?.dataset?.id;
                if (!id) return;

                document.getElementById('dirEditId').value = id;

                // fill inputs from data-*
                for (const field of Object.keys(DIR.fields || {})) {
                    const el = document.getElementById('dirEdit_' + field);
                    if (el) el.value = tr.dataset[field] || '';
                }

                // resource update: PUT /base/{id}
                document.getElementById('dirEditForm').action = DIR.baseUrl + '/' + id;
            }

            window.dirOpenDelete = function (tr) {
                const id = tr?.dataset?.id;
                if (!id) return;

                document.getElementById('dirDeleteId').value = id;

                const name = (tr.dataset[DIR.firstField] || '').trim();
                document.getElementById('dirDeleteTitle').innerText =
                    'Delete ' + (DIR.title || 'Record') + (name ? (' (' + name + ')') : '');

                // resource destroy: DELETE /base/{id}
                document.getElementById('dirDeleteForm').action = DIR.baseUrl + '/' + id;
            }

            applyFilter();
        })();
    </script>
@endsection
