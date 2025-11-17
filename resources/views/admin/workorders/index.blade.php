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
        }
        #show-workorder {
            table-layout: fixed;
            width: 100%;
        }
        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis; /* Это будет обрезать длинный текст многоточием "..." */
            padding-left: 10px;
            vertical-align: middle; /* Выравнивание по центру по вертикали */
        }
        .col-number { width: 100px; font-size: 0.9rem; }
        .col-approve { width: 60px; font-size: 0.7rem; font-weight: normal; }
        .col-tdr { width: 70px; font-size: 0.8rem; font-weight: normal;}
        .col-photos { width: 60px; font-size: 0.8rem; font-weight: normal;}
        .col-edit { width: 60px;font-size: 0.8rem; font-weight: normal; }
        .col-delete { width: 60px; font-size: 0.8rem; font-weight: normal;}
        .col-date { width: 100px; font-size: 0.8rem; font-weight: normal;}


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

        .clearable-input {
            position: relative;
            max-width: 400px;
            height: 38px;
        }

        .clearable-input .form-control {
            padding-right: 2.5rem;
            width: 100%;
            height: 100%;
            line-height: 38px;
        }

        .clearable-input .btn-clear {
            position: absolute;
            top: 50%;
            right: 0.75rem;
            transform: translateY(-50%);
            height: 24px;
            width: 24px;
            background: none;
            border: none;
            padding: 0;
            font-size: 1.25rem;
            color: #ccc;
            z-index: 10;
        }

        #currentUserCheckbox, #woDone {
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
        .photo-thumbnail {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
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
                    <img src="{{ asset('img/plus.png') }}" width="30" alt="Add" data-bs-toggle="tooltip" title="Add new workorder">
                </a>
            </div>

            <div class="clearable-input">
                <input id="searchInput" type="text" class="form-control" placeholder="Search...">
                <button id="clearSearch" type="button" class="btn-clear">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>

            <div class="form-check d-flex align-items-center mb-0">
                <input class="form-check-input" type="checkbox" id="woDone" style="width: 1.2em; height: 1.2em;">
                <label class="form-check-label ms-2" for="woDone">WO active</label>
            </div>

            <div class="form-check d-flex align-items-center mb-0">
                <input class="form-check-input" type="checkbox" id="currentUserCheckbox" checked style="width: 1.2em; height: 1.2em;">
                <label class="form-check-label ms-2" for="currentUserCheckbox">My workorders</label>
            </div>
        </div>

        @if(count($workorders))

            <div class="table-wrapper p-2 pt-0">
                <table id="show-workorder" class="table table-sm table-bordered table-striped table-hover w-100">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-center text-primary sortable col-number">Number <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary col-approve">Approve</th>
                        <th class="text-center text-primary">Unit</th>
                        <th class="text-center text-primary">Description</th>
                        <th class="text-center text-primary">Serial number</th>
                        <th class="text-center text-primary col-tdr">WO TDR</th>
                        <th class="text-center text-primary">Manual</th>
                        <th class="text-center text-primary sortable">Customer <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary sortable">Instruction <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary col-photos">Photos</th>
                        <th class="text-center text-primary sortable">Technik <i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-center text-primary col-edit">Edit</th>
                        <th class="text-center text-primary col-date">Open Date</th>
                        @if (is_admin())
                            <th class="text-center text-primary col-delete">Delete</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($workorders as $workorder)
                        <tr
                            data-tech-id="{{ $workorder->user_id }}"
                            data-done="{{ $workorder->main->whereIn('task.name', ['Submitted Wo Assembly', 'Done'])->isNotEmpty() ? '1' : '0' }}">
                            <td class="text-center">

                                @if ($workorder->main->whereIn('task.name', ['Submitted Wo Assembly', 'Done'])->isNotEmpty())
                                    <a href="{{ route('mains.show', $workorder->id) }}" class="text-decoration-none">
                                        <span class="text-muted">{{ $workorder->number }}</span>
                                    </a>
                                @else
                                    <a href="{{ route('mains.show', $workorder->id) }}" class="text-decoration-none">
                                        <span style="font-size: 16px; color: #0DDDFD;">w&nbsp;{{ $workorder->number }}</span>
                                    </a>
                                @endif

                            </td>
                            <td class="text-center">
                                <a href="{{ route('workorders.approve', $workorder->id) }}" class="change_approve" onclick="showLoadingSpinner()">
                                    @if($workorder->approve_at)
                                        <img src="{{ asset('img/ok.png') }}" width="20" alt="" title="{{ $workorder->approve_at->format('d.m.Y') }} {{ $workorder->approve_name }}">
                                    @else
                                        <img src="{{ asset('img/icon_no.png') }}" width="12" alt="">
                                    @endif
                                </a>
                            </td>
                            <td class="text-center">{{ $workorder->unit->part_number }}</td>
                            <td class="text-center" data-bs-toggle="tooltip" title="{{ $workorder->description }}">{{
                            $workorder->description }} </td>

                            <td class="text-center">{{ $workorder->serial_number }} @if($workorder->amdt > 0) Amdt {{ $workorder->amdt }} @endif </td>

                            <td class="text-center">
                                <a href="{{ route('tdrs.show', $workorder->id) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-journal-richtext"></i>
                                </a>
                            </td>
                            <td class="text-center">{{ $workorder->unit->manuals->number }}  &nbsp; <span class="text-white-50">({{$workorder->unit->manuals->lib}})</span></td>
                            <td class="text-center" data-bs-toggle="tooltip" title="{{ $workorder->customer->name }}">{{ $workorder->customer->name }}</td>
                            <td class="text-center">{{ $workorder->instruction->name }}</td>

                            <td class="text-center">
                                <button class="btn btn-outline-info btn-sm open-photo-modal" data-id="{{ $workorder->id }}" data-number="{{ $workorder->number }}">
                                    <i class="bi bi-images text-decoration-none"></i>
                                </button>
                            </td>
                            <td class="text-center td-technik">{{ $workorder->user->name }}</td>

                            <td class="text-center">
                                <a href="{{ route('workorders.edit', $workorder->id) }}">
                                    <img src="{{ asset('img/set.png') }}" width="30" alt="Edit">
                                </a>
                            </td>
                            <td class="text-center">
                                @if($workorder->open_at)
                                    <span style="display: none">{{ $workorder->open_at->format('Ymd') }}</span>{{ $workorder->open_at->format('d.m.Y') }}
                                @else
                                    <span style="display: none">{{ $workorder->open_at }}</span>{{ $workorder->open_at }}
                                @endif
                            </td>
                            @can('workorders.delete', $workorder)
                                <td class="text-center">
                                    <form id="deleteForm_{{ $workorder->id }}" action="{{ route('workorders.destroy', $workorder->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="button" name="btn_delete"
                                                data-bs-toggle="modal" data-bs-target="#useConfirmDelete"
                                                data-title="Delete Confirmation WO {{ $workorder->number }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            @endcan
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

    <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content" style="background-color: #343A40">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">Photos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="photoModalContent" class="row g-3"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" id="saveAllPhotos">Download All</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeletePhotoModal" tabindex="-1" aria-labelledby="confirmDeletePhotoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeletePhotoLabel">Confirm Deletion</h5>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this photo?
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button id="confirmPhotoDeleteBtn" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
        <div id="photoDeletedToast" class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body">
                Photo deleted successfully.
            </div>
        </div>
    </div>

@endsection


@section('scripts')
    <script>
        const currentUserId = {{ auth()->id() }};
        const currentUserName = @json(trim(auth()->user()->name));
        const currentUserNameLC = currentUserName.toLowerCase();

        document.addEventListener('DOMContentLoaded', function () {

            const searchInput = document.getElementById('searchInput');
            const clearSearchBtn = document.getElementById('clearSearch');

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });


            // показать/скрыть крестик в зависимости от наличия текста
            searchInput.addEventListener('input', function () {
                clearSearchBtn.style.display = this.value ? 'block' : 'none';
            });

            // при загрузке — проверить сразу
            clearSearchBtn.style.display = searchInput.value ? 'block' : 'none';


            const table = document.getElementById('show-workorder');
            const tableWrapper = document.querySelector('.table-wrapper');
            const headers = document.querySelectorAll('.sortable');

            const checkboxMy = document.getElementById('currentUserCheckbox');
            const checkboxDone = document.getElementById('woDone');

            // --- Восстановление состояний из localStorage ---
            const savedMy = localStorage.getItem('myWorkordersCheckbox');
            checkboxMy.checked = savedMy !== null ? savedMy === 'true' : true;
            localStorage.setItem('myWorkordersCheckbox', checkboxMy.checked);

            const savedDone = localStorage.getItem('doneCheckbox');
            checkboxDone.checked = savedDone !== null ? savedDone === 'true' : false; // по умолчанию выключен
            localStorage.setItem('doneCheckbox', checkboxDone.checked);

            function filterTable() {
                const filter = searchInput.value.toLowerCase();
                const onlyMy = checkboxMy.checked;
                const onlyDone = checkboxDone.checked;

                const rows = table.querySelectorAll('tbody tr');

                if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

                rows.forEach(row => {

                    const rowText = row.innerText.toLowerCase();
                    const technikId = row.getAttribute('data-tech-id'); // ← берем ID из <tr>
                    const isDone = row.getAttribute('data-done') === '0';

                    const matchesSearch = rowText.includes(filter);
                    const matchesUser = onlyMy ? String(technikId) === String(currentUserId) : true;
                    const matchesDone = onlyDone ? isDone : true;

                    row.style.display = (matchesSearch && matchesUser && matchesDone) ? '' : 'none';
                });

                if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
            }

            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header) + 1;
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;

                    headers.forEach(h => {
                        const icon = h.querySelector('i');
                        if (icon) icon.className = 'bi bi-chevron-expand';
                    });
                    const currentIcon = header.querySelector('i');
                    if (currentIcon) currentIcon.className = direction === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down';

                    const rows = Array.from(table.querySelectorAll('tbody tr'));
                    rows.sort((a, b) => {
                        const aText = a.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        const bText = b.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                    });
                    rows.forEach(row => table.querySelector('tbody').appendChild(row));
                    filterTable();
                });
            });

            searchInput.addEventListener('input', filterTable);

            checkboxMy.addEventListener('change', () => {
                localStorage.setItem('myWorkordersCheckbox', checkboxMy.checked);
                filterTable();
            });

            checkboxDone.addEventListener('change', () => {
                localStorage.setItem('doneCheckbox', checkboxDone.checked);
                filterTable();
            });


            tableWrapper.style.display = 'block';
            filterTable();

            // ----------------- Удаление через модалку -----------------
            document.getElementById('confirmPhotoDeleteBtn').addEventListener('click', async function () {
                const {mediaId, photoBlock} = window.pendingDelete || {};
                if (!mediaId) return;

                showLoadingSpinner();

                try {
                    const response = await fetch(`/workorders/photo/delete/${mediaId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        photoBlock.style.transition = 'opacity 0.3s ease';
                        photoBlock.style.opacity = '0';
                        setTimeout(() => {
                            photoBlock.remove();
                            loadPhotoModal(window.currentWorkorderId);
                        }, 300);

                        const toast = new bootstrap.Toast(document.getElementById('photoDeletedToast'));
                        toast.show();
                    } else {
                        alert('Failed to delete photo');
                    }
                } catch (err) {
                    console.error('Delete error:', err);
                    alert('Server error');
                } finally {
                    hideLoadingSpinner();
                    bootstrap.Modal.getInstance(document.getElementById('confirmDeletePhotoModal')).hide();
                    window.pendingDelete = null;
                }
            });

            // ----------------- Открытие модалки фото -----------------
            document.querySelectorAll('.open-photo-modal').forEach(button => {
                button.addEventListener('click', async function () {

                    const workorderId = this.dataset.id;
                    const workorderNumber = this.dataset.number;
                    window.currentWorkorderId = workorderId;
                    window.currentWorkorderNumber = workorderNumber;

                    await loadPhotoModal(workorderId);
                    new bootstrap.Modal(document.getElementById('photoModal')).show();
                });
            });

            // ----------------- Загрузка фото -----------------
            async function loadPhotoModal(workorderId) {
                const modalContent = document.getElementById('photoModalContent');
                showLoadingSpinner();

                try {
                    const response = await fetch(`/workorders/${workorderId}/photos`);

                    if (!response.ok) throw new Error('Response not ok');
                    const data = await response.json();

                    let html = '';
                    ['photos', 'damages', 'logs'].forEach(group => {
                        html += `
            <div class="col-12">
                <h6 class="text-primary text-uppercase mt-2">${group}</h6>
                <div class="row g-2">  {{-- ИЗМЕНЕНИЕ 1: Уменьшен отступ --}}
                        `;

                        data[group].forEach(media => {
                            html += `
                <div class="col-4 col-md-2 col-lg-1"> {{-- ИЗМЕНЕНИЕ 2: Убран внутренний отступ p-1 --}}
                            <div class="position-relative d-inline-block w-100">
                                <a data-fancybox="${group}" href="${media.big}" data-caption="${group}">
                            {{-- ИЗМЕНЕНИЕ 3: Класс img-fluid заменен на photo-thumbnail --}}
                            <img src="${media.thumb}" class="photo-thumbnail border border-primary rounded" />
                        </a>
                        <button class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center position-absolute delete-photo-btn"
                              style="top: -6px; right: -6px; width: 20px; height: 20px; z-index: 10;"
                                data-id="${media.id}" title="Delete">
                                <i class="bi bi-x" style="font-size: 12px;"></i>
                        </button>
                    </div>
                </div>
            `;
                        });

                        html += `</div></div>`;
                    });

                    modalContent.innerHTML = html;
                    bindDeleteButtons();
                } catch (e) {
                    console.error('Load photo error', e);
                    modalContent.innerHTML = '<div class="text-danger">Failed to load photos</div>';
                } finally {
                    hideLoadingSpinner();
                }
            }


            // ----------------- Назначение обработчиков на кнопки удаления -----------------
            function bindDeleteButtons() {
                document.querySelectorAll('.delete-photo-btn').forEach(btn => {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const mediaId = this.dataset.id;
                        const photoBlock = this.closest('.col-4, .col-md-2');

                        window.pendingDelete = {mediaId, photoBlock};
                        new bootstrap.Modal(document.getElementById('confirmDeletePhotoModal')).show();
                    });
                });
            }

            // ----------------- Скачивание ZIP -----------------
            document.getElementById('saveAllPhotos').addEventListener('click', function () {
                const workorderId = window.currentWorkorderId;
                const workorderNumber = window.currentWorkorderNumber || 'workorder';
                if (!workorderId) return alert('Workorder ID missing');

                showLoadingSpinner();

                fetch(`/workorders/download/${workorderId}/all`)
                    .then(response => {
                        if (!response.ok) throw new Error('Download failed');
                        return response.blob();
                    })
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `workorder_${workorderNumber}_images.zip`;
                        a.click();
                        window.URL.revokeObjectURL(url);
                    })
                    .catch(err => {
                        console.error('Error downloading ZIP:', err);
                        alert('Download failed');
                    })
                    .finally(() => {
                        hideLoadingSpinner();
                    });
            });

            document.getElementById('clearSearch').addEventListener('click', function () {
                const input = document.getElementById('searchInput');
                input.value = '';
                input.dispatchEvent(new Event('input'));
            });

        });
    </script>
@endsection



