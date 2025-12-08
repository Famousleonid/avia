@extends('admin.master')

@section('style')
    <style>
        .card {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 70px);
        }

        .card-header {
            flex-shrink: 0;
        }

        .table-wrapper {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: auto;

            opacity: 0;
            transition: opacity .15s;

        }

        .table-wrapper.ready {
            opacity: 1;
        }

        #show-workorder {
            table-layout: fixed;
            width: 100%;
        }

        .table th,
        .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-left: 10px;
            vertical-align: middle;
        }

        .col-number { width: 100px; font-size: 0.9rem; }
        .col-approve { width: 60px; font-size: 0.7rem; font-weight: normal; }
        .col-edit { width: 60px; font-size: 0.8rem; font-weight: normal; }
        .col-delete { width: 60px; font-size: 0.8rem; font-weight: normal; }
        .col-date { width: 100px; font-size: 0.8rem; font-weight: normal; }

        .table thead th {
            position: sticky;
            height: 50px;
            top: 0;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        .table th.sortable {
            cursor: pointer;
        }

        /* Search такого же размера, как селекты */
        .clearable-input {
            position: relative;
            max-width: 260px;
            height: 32px;
        }

        .clearable-input .form-control {
            padding-right: 2.5rem;
            width: 100%;
            height: 32px;
            line-height: 32px;
            font-size: .8rem;
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .clearable-input .btn-clear {
            position: absolute;
            top: 50%;
            right: 0.25rem;
            transform: translateY(-50%);
            height: 32px;
            width: 32px;
            background: none;
            border: none;
            padding: 0;
            font-size: 1.15rem;
            color: #ccc;
            z-index: 10;
        }

        #currentUserCheckbox,
        #woDone,
        #approvedCheckbox {
            cursor: pointer;
        }

        [data-bs-theme="dark"] #show-workorder {
            background: linear-gradient(to bottom, #131313, #2E2E2E) !important;
        }

        [data-bs-theme="dark"] #show-workorder thead th {
            background: linear-gradient(to bottom, #131313, #2E2E2E) !important;
        }

        [data-bs-theme="dark"] .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

        /* Фильтры customer / technik */
        .filter-select-wrapper {
            min-width: 210px;
        }

        .filter-select-wrapper .form-label {
            font-size: .75rem;
            margin-bottom: .2rem;
        }

        .filter-select-wrapper .form-control {
            height: 32px;
            padding-top: 2px;
            padding-bottom: 2px;
            font-size: .8rem;
        }

        .btn-clear-select {
            height: 32px;
            width: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 991.98px) {
            .filter-select-wrapper {
                min-width: 150px;
            }
        }

        .checkbox-group {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            position: relative;
            padding-bottom: 4px; /* чтобы линия не прилипала */
        }

        /* Чекбокс */
        .checkbox-group input[type="checkbox"] {
            width: 22px;
            height: 22px;
            cursor: pointer;
        }

        /* Линия, соединяющая чекбокс и текст */
        .checkbox-group::after {
            content: "";
            position: absolute;

            left: 0;                 /* начинаем ровно от края чекбокса */
            right: 0;                /* тянем до конца текста */
            bottom: 0;               /* под всем блоком */
            height: 3px;

            background: #0d6efd;     /* синий */
            border-radius: 2px;
            opacity: 0.45;
        }



    </style>

@endsection

@section('content')

    <div class="card shadow">

        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">

            <div class="d-flex align-items-center gap-3">
                <h5 class="text-primary mb-0">
                    {{ __('Workorders') }}
                    (<span class="text-success">{{ $workorders->count() }}</span>)
                </h5>

                <a id="admin_new_firm_create" href="{{ route('workorders.create') }}">
                    <img src="{{ asset('img/plus.png') }}" width="30" alt="Add" data-bs-toggle="tooltip"
                         title="Add new workorder">
                </a>
            </div>

            {{-- Search --}}
            <div class="clearable-input">
                <input id="searchInput" type="text" class="form-control" placeholder="Search...">
                <button id="clearSearch" type="button" class="btn-clear">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>

            {{-- Фильтры справа --}}
            <div class="d-flex flex-wrap align-items-center gap-5">

                {{-- Customer filter --}}
                <div class="d-flex align-items-end  filter-select-wrapper">
                    <div class="flex-grow-1">
                        <select id="customerFilter" class="form-control form-control-sm">
                            <option value="">— All customers —</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="clearCustomerFilter"
                            class="btn btn-outline-secondary btn-sm btn-clear-select"
                            title="Clear customer filter">
                        <i class="bi bi-x"></i>
                    </button>
                </div>

                {{-- Technician filter --}}
                <div class="d-flex align-items-end filter-select-wrapper">
                    <div class="flex-grow-1">
                        <select id="technikFilter" class="form-control form-control-sm">
                            <option value="">— All technicians —</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="clearTechnikFilter"
                            class="btn btn-outline-secondary btn-sm btn-clear-select"
                            title="Clear technician filter">
                        <i class="bi bi-x"></i>
                    </button>
                </div>

                <label class="checkbox-group">
                    <input type="checkbox" id="woDone">
                    <span>WO active</span>
                </label>

                <label class="checkbox-group">
                    <input type="checkbox" id="currentUserCheckbox">
                    <span>My workorders</span>
                </label>

                <label class="checkbox-group">
                    <input type="checkbox" id="approvedCheckbox">
                    <span>Approved</span>
                </label>

{{--                <div class="form-check d-flex align-items-center mb-0">--}}
{{--                    <input class="form-check-input" type="checkbox" id="currentUserCheckbox" checked--}}
{{--                           style="width: 1.2em; height: 1.2em;">--}}
{{--                    <label class="form-check-label ms-2 checkbox-group" for="currentUserCheckbox">My workorders</label>--}}
{{--                </div>--}}

{{--                <div class="form-check d-flex align-items-center mb-0">--}}
{{--                    <input class="form-check-input" type="checkbox" id="approvedCheckbox"--}}
{{--                           style="width: 1.2em; height: 1.2em;">--}}
{{--                    <label class="form-check-label ms-2 checkbox-group" for="approvedCheckbox">Approved</label>--}}
{{--                </div>--}}

            </div>
        </div>

        @if(count($workorders))

            <div class="table-wrapper p-2 pt-0">
                <table id="show-workorder" class="table table-sm table-bordered table-striped table-hover w-100">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-center text-primary sortable col-number">
                            Number <i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-center text-primary col-approve">Approve</th>
                        <th class="text-center text-primary">Unit</th>
                        <th class="text-center text-primary">Description</th>
                        <th class="text-center text-primary">Serial number</th>
                        <th class="text-center text-primary">Manual</th>
                        <th class="text-center text-primary sortable">
                            Customer <i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-center text-primary sortable">
                            Instruction <i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-center text-primary sortable">
                            Technik <i class="bi bi-chevron-expand ms-1"></i>
                        </th>
                        <th class="text-center text-primary col-edit">Edit</th>
                        <th class="text-center text-primary col-date">Open Date</th>
                        @role('Admin')
                        <th class="text-center text-primary col-delete">Delete</th>
                        @endrole
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($workorders as $workorder)
                        <tr
                            data-tech-id="{{ $workorder->user_id }}"
                            data-customer-id="{{ $workorder->customer_id }}"
                            data-status="{{ $workorder->main->whereIn('task.name', ['Submitted Wo Assembly', 'Done'])->isNotEmpty() ? 'done' : 'active' }}"
                            data-approved="{{ $workorder->approve_at ? '1' : '0' }}"
                        >
                            <td class="text-center">
                                @if($workorder->isDone())
                                    <a href="{{ route('mains.show', $workorder->id) }}" class="text-decoration-none">
                                        <span class="text-muted">{{ $workorder->number }}</span>
                                    </a>
                                @else
                                    <a href="{{ route('mains.show', $workorder->id) }}" class="text-decoration-none">
                                        <span style="font-size: 16px; color: #0DDDFD;">
                                            w&nbsp;{{ $workorder->number }}
                                        </span>
                                    </a>
                                @endif
                            </td>

                            <td class="text-center">
                                <a href="{{ route('workorders.approve', $workorder->id) }}"
                                   class="change_approve"
                                   onclick="showLoadingSpinner()">
                                    @if($workorder->approve_at)
                                        <img src="{{ asset('img/ok.png') }}" width="20" alt=""
                                             title="{{ $workorder->approve_at->format('d.m.Y') }} {{ $workorder->approve_name }}">
                                    @else
                                        <img src="{{ asset('img/icon_no.png') }}" width="12" alt="">
                                    @endif
                                </a>
                            </td>

                            <td class="text-center">{{ $workorder->unit->part_number }}</td>

                            <td class="text-center"
                                data-bs-toggle="tooltip"
                                title="{{ $workorder->description }}">
                                {{ $workorder->description }}
                            </td>

                            <td class="text-center">
                                {{ $workorder->serial_number }}
                                @if($workorder->amdt > 0)
                                    Amdt {{ $workorder->amdt }}
                                @endif
                            </td>

                            <td class="text-center">
                                {{ $workorder->unit->manuals->number }}
                                &nbsp;
                                <span class="text-white-50">
                                    ({{ $workorder->unit->manuals->lib }})
                                </span>
                            </td>

                            <td class="text-center"
                                data-bs-toggle="tooltip"
                                title="{{ $workorder->customer->name }}">
                                {{ $workorder->customer->name }}
                            </td>

                            <td class="text-center">
                                {{ $workorder->instruction->name }}
                            </td>

                            <td class="text-center td-technik">
                                {{ $workorder->user->name }}
                            </td>

                            <td class="text-center">
                                <a href="{{ route('workorders.edit', $workorder->id) }}">
                                    <img src="{{ asset('img/set.png') }}" width="30" alt="Edit">
                                </a>
                            </td>

                            <td class="text-center">
                                @if($workorder->open_at)
                                    <span style="display: none">
                                        {{ $workorder->open_at->format('Ymd') }}
                                    </span>
                                    {{ $workorder->open_at->format('d.m.Y') }}
                                @else
                                    <span style="display: none">{{ $workorder->open_at }}</span>
                                    {{ $workorder->open_at }}
                                @endif
                            </td>

                            @role('Admin')
                            <td class="text-center">
                                <form id="deleteForm_{{ $workorder->id }}"
                                      action="{{ route('workorders.destroy', $workorder->id) }}"
                                      method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"
                                            type="button" name="btn_delete"
                                            data-bs-toggle="modal"
                                            data-bs-target="#useConfirmDelete"
                                            data-form-id="deleteForm_{{ $workorder->id }}"
                                            data-title="Delete Confirmation WO {{ $workorder->number }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                            @endrole
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

        @else
            <p class="ms-2">Workorders not created</p>
        @endif
    </div>

    @include('components.delete')

@endsection


@section('scripts')
    <script>
        const currentUserId   = {{ auth()->id() }};
        const currentUserName = @json(trim(auth()->user()->name));
        const currentUserNameLC = currentUserName.toLowerCase();

        document.addEventListener('DOMContentLoaded', function () {

            const searchInput    = document.getElementById('searchInput');
            const clearSearchBtn = document.getElementById('clearSearch');

            const checkboxMy       = document.getElementById('currentUserCheckbox');
            const checkboxDone     = document.getElementById('woDone');
            const checkboxApproved = document.getElementById('approvedCheckbox');

            const customerFilter   = document.getElementById('customerFilter');
            const technikFilter    = document.getElementById('technikFilter');
            const clearCustomerBtn = document.getElementById('clearCustomerFilter');
            const clearTechnikBtn  = document.getElementById('clearTechnikFilter');

            const table        = document.getElementById('show-workorder');
            const tableWrapper = document.querySelector('.table-wrapper');
            const headers      = document.querySelectorAll('.sortable');

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // показать/скрыть крестик у search
            searchInput.addEventListener('input', function () {
                clearSearchBtn.style.display = this.value ? 'block' : 'none';
            });
            clearSearchBtn.style.display = searchInput.value ? 'block' : 'none';

            // --- восстановление состояний чекбоксов из localStorage ---
            const savedMy       = localStorage.getItem('myWorkordersCheckbox');
            const savedDone     = localStorage.getItem('doneCheckbox');
            const savedApproved = localStorage.getItem('approvedCheckbox');

            checkboxMy.checked       = savedMy       !== null ? savedMy       === 'true' : true;
            checkboxDone.checked     = savedDone     !== null ? savedDone     === 'true' : false;
            checkboxApproved.checked = savedApproved !== null ? savedApproved === 'true' : false;

            localStorage.setItem('myWorkordersCheckbox', checkboxMy.checked);
            localStorage.setItem('doneCheckbox', checkboxDone.checked);
            localStorage.setItem('approvedCheckbox', checkboxApproved.checked);

            // восстановление фильтров select из localStorage
            const savedCustomer = localStorage.getItem('woCustomerFilter') || '';
            const savedTechnik  = localStorage.getItem('woTechnikFilter')  || '';

            if (customerFilter) customerFilter.value = savedCustomer;
            if (technikFilter)  technikFilter.value  = savedTechnik;

            function updateSelectClearButton(selectEl, buttonEl) {
                if (!selectEl || !buttonEl) return;
                if (selectEl.value) {
                    buttonEl.classList.remove('btn-outline-secondary');
                    buttonEl.classList.add('btn-primary', 'text-white');
                } else {
                    buttonEl.classList.add('btn-outline-secondary');
                    buttonEl.classList.remove('btn-primary', 'text-white');
                }
            }

            updateSelectClearButton(customerFilter, clearCustomerBtn);
            updateSelectClearButton(technikFilter, clearTechnikBtn);
            let firstFilterDone = false;

            function filterTable() {
                const filterText   = searchInput.value.toLowerCase();
                const onlyMy       = checkboxMy.checked;
                const onlyActive   = checkboxDone.checked;
                const onlyApproved = checkboxApproved.checked;

                const selectedCustomer = customerFilter ? customerFilter.value : '';
                const selectedTechnik  = technikFilter  ? technikFilter.value  : '';

                const rows = table.querySelectorAll('tbody tr');

                if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

                rows.forEach(row => {
                    const rowText       = row.innerText.toLowerCase();
                    const rowTechId     = row.getAttribute('data-tech-id');
                    const rowCustomerId = row.getAttribute('data-customer-id');
                    const rowStatus     = row.getAttribute('data-status') || 'active';
                    const rowApproved   = row.getAttribute('data-approved') === '1';

                    const matchesSearch   = !filterText || rowText.includes(filterText);
                    const matchesUser     = onlyMy ? String(rowTechId) === String(currentUserId) : true;
                    const matchesStatus   = onlyActive ? rowStatus === 'active' : true;
                    const matchesApproved = onlyApproved ? rowApproved : true;
                    const matchesCustomer = selectedCustomer ? String(rowCustomerId) === String(selectedCustomer) : true;
                    const matchesTechnik  = selectedTechnik  ? String(rowTechId)     === String(selectedTechnik)   : true;

                    row.style.display =
                        (matchesSearch && matchesUser && matchesStatus &&
                            matchesApproved && matchesCustomer && matchesTechnik)
                            ? ''
                            : 'none';
                });

                if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();

                if (!firstFilterDone) {
                    firstFilterDone = true;
                    tableWrapper.classList.add('ready');
                }

            }

            // сортировка по клику на заголовок
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header) + 1;
                    const direction   = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;

                    headers.forEach(h => {
                        const icon = h.querySelector('i');
                        if (icon) icon.className = 'bi bi-chevron-expand';
                    });
                    const currentIcon = header.querySelector('i');
                    if (currentIcon) {
                        currentIcon.className = direction === 'asc'
                            ? 'bi bi-arrow-up'
                            : 'bi bi-arrow-down';
                    }

                    const rows = Array.from(table.querySelectorAll('tbody tr'));
                    rows.sort((a, b) => {
                        const aText = a.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        const bText = b.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        return direction === 'asc'
                            ? aText.localeCompare(bText)
                            : bText.localeCompare(aText);
                    });
                    rows.forEach(row => table.querySelector('tbody').appendChild(row));
                    filterTable();
                });
            });

            // события
            searchInput.addEventListener('input', filterTable);

            checkboxMy.addEventListener('change', () => {
                localStorage.setItem('myWorkordersCheckbox', checkboxMy.checked);
                filterTable();
            });

            checkboxDone.addEventListener('change', () => {
                localStorage.setItem('doneCheckbox', checkboxDone.checked);
                filterTable();
            });

            checkboxApproved.addEventListener('change', () => {
                localStorage.setItem('approvedCheckbox', checkboxApproved.checked);
                filterTable();
            });

            customerFilter?.addEventListener('change', () => {
                localStorage.setItem('woCustomerFilter', customerFilter.value);
                updateSelectClearButton(customerFilter, clearCustomerBtn);
                filterTable();
            });

            technikFilter?.addEventListener('change', () => {
                localStorage.setItem('woTechnikFilter', technikFilter.value);
                updateSelectClearButton(technikFilter, clearTechnikBtn);
                filterTable();
            });

            clearCustomerBtn?.addEventListener('click', () => {
                if (!customerFilter) return;
                customerFilter.value = '';
                localStorage.setItem('woCustomerFilter', '');
                updateSelectClearButton(customerFilter, clearCustomerBtn);
                filterTable();
            });

            clearTechnikBtn?.addEventListener('click', () => {
                if (!technikFilter) return;
                technikFilter.value = '';
                localStorage.setItem('woTechnikFilter', '');
                updateSelectClearButton(technikFilter, clearTechnikBtn);
                filterTable();
            });

            clearSearchBtn.addEventListener('click', function () {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
            });

            // delete workorder (модалка)
            let currentFormId = null;
            const deleteModal = document.getElementById('useConfirmDelete');
            const confirmBtn  = document.getElementById('confirmDeleteBtn');

            deleteModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                currentFormId = button.getAttribute('data-form-id');

                const title = button.getAttribute('data-title') || 'Delete Confirmation';
                document.getElementById('confirmDeleteLabel').textContent = title;
            });

            confirmBtn.addEventListener('click', () => {
                if (!currentFormId) return;
                const form = document.getElementById(currentFormId);
                if (!form) return;
                if (typeof showLoadingSpinner === 'function') showLoadingSpinner();
                form.submit();
            });

            filterTable();
        });
    </script>
@endsection
