@extends('admin.master')


@section('content')

    <style>
        .table-wrapper{
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 80px;
            max-width: 190px;
            padding-left: 10px;
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            min-width: 50px;
            max-width: 150px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 50px;
            max-width: 150px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 50px;
            max-width: 150px;
        }

        .table thead th {
            position: sticky;
            height: 32px;
            top: 0;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        @media (max-width: 1200px) {
            .table th:nth-child(2), .table td:nth-child(2),
            .table th:nth-child(4), .table td:nth-child(4),
            .table th:nth-child(6), .table td:nth-child(6),
            .table th:nth-child(7), .table td:nth-child(7) {
                display: none;
            }
        }

        .table th.sortable {
            cursor: pointer;
        }

        .clearable-input {
            position: relative;
            width: 400px;
        }

        .clearable-input .form-control {
            padding-right: 2.5rem;
        }

        .clearable-input .btn-clear {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }


        .table th.sortable.active {
            color: white;
        }

        .table th.sortable.active .bi-chevron-up {
            color: white !important;
            display: inline;
        }

        .table th.sortable.active .bi-chevron-down {
            color: white;
            display: inline;
        }


    </style>

    <div class="card dir-page mt-1 pt-2 " >
        @role('Admin')
        <div class="card-header my-1">
            <div class="d-flex align-items-center gap-3 flex-wrap">

                {{-- Title --}}
                <h5 class="mb-0 text-primary flex-shrink-0">
                    {{ __('Manage Users') }}
                    (<span class="text-success">{{ $users->count() }}</span>)
                </h5>

                {{-- Search (центр, растягивается) --}}
                <div class="flex-grow-1 d-flex justify-content-center">
                    <div class="clearable-input w-100" style="max-width: 420px;">
                        <input id="searchUserInput"
                               type="text"
                               class="form-control"
                               placeholder="Search...">
                        <button class="btn-clear text-secondary"
                                onclick="const i=document.getElementById('searchUserInput'); i.value=''; i.dispatchEvent(new Event('input')); i.focus();">
                                <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>

                {{-- Button --}}
                <div class="flex-shrink-0">
                    <a href="{{ route('users.create') }}"
                       class="btn btn-outline-primary btn-sm">
                        {{ __('Add User') }}
                    </a>
                </div>

            </div>
        </div>
        @endrole

        @if(count($users))
            <div class="table-wrapper me-3 p-2 pt-0 mb-3">
                <table id="userTable"
                       class="display table table-sm table-hover  align-middle table-bordered dir-table">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary bg-gradient sortable">{{__('Name') }}<i
                                class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary bg-gradient ">{{__('Email') }}</th>
                        <th class="text-primary bg-gradient sortable text-center">{{__('Team') }}<i
                                class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary bg-gradient text-center">{{__('Avatar') }}</th>
                        <th class="text-primary bg-gradient text-center">{{__('Role') }}</th>
                        <th class="text-primary bg-gradient text-center">{{__('Stamp') }}</th>
                        <th class="text-primary bg-gradient text-center">{{__('Create Date')}}</th>
                        <th class="text-primary bg-gradient text-center">{{__('Action') }}</th>

                    </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td @if(!$user->email_verified_at) style="color:red" @endif>{{$user->name}}</td>
                            <td class="">{{$user->email}}</td>
                            <td class="text-center"
                                style="color: {{ $user->team ? '#ffffff' : '#808080' }};">{{ $user->team->name ?? 'Unknown team' }}</td>
                            <td class="text-center">
                                <a href="{{ $user->getFirstMediaBigUrl('avatar') }}" data-fancybox="gallery">
                                    <img class="rounded-circle" src="{{ $user->getFirstMediaThumbnailUrl('avatar') }}"
                                         width="40" height="40" alt="Image"/>
                                </a>
                            </td>
                            <td class="text-center"
                                style="color: {{ $user->role? '#ffffff' : '#808080' }};">{{ $user->role->name ?? 'Unknown role' }}</td>
                            <td class="text-center"
                                style="color: {{ $user->role? '#ffffff' : '#808080' }};">{{ $user->stamp }}</td>
                            <td class="text-center"><span
                                    style="display: none">{{$user->created_at}}</span>{{$user->created_at->format('d.m.Y')}}
                            </td>
                            <td class="text-center">
                                @if(auth()->user()->roleIs('Admin'))
                                    {{-- Admin: edit + delete everyone --}}
                                    <a href="{{ route('users.edit', ['user' => $user->id]) }}"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <form id="deleteForm_{{$user->id}}"
                                          action="{{ route('users.destroy', ['user' => $user->id]) }}"
                                          method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="button"
                                                data-bs-toggle="modal" data-bs-target="#useConfirmDelete"
                                                data-title="Delete Confirmation row {{$user->name}}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>

                                @else
                                    {{-- Others: can edit only themselves, no delete --}}
                                    @if(auth()->id() === $user->id)
                                        <a href="{{ route('users.edit', ['user' => $user->id]) }}"
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @else
                    <p>Users not Created</p>
                @endif
            </div>
    </div>

    @include('components.delete')

@endsection
@section('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const table = document.getElementById('userTable');
            if (!table) return;

            // ---------------- Sorting ----------------
            const headers = table.querySelectorAll('thead th.sortable');

            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                    const tbody = table.querySelector('tbody');
                    if (!tbody) return;

                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;

                    headers.forEach(h => h.classList.remove('active'));
                    header.classList.add('active');

                    rows.sort((a, b) => {
                        const aText = (a.cells[columnIndex]?.innerText || '').trim();
                        const bText = (b.cells[columnIndex]?.innerText || '').trim();
                        return direction === 'asc'
                            ? aText.localeCompare(bText, undefined, {numeric: true, sensitivity: 'base'})
                            : bText.localeCompare(aText, undefined, {numeric: true, sensitivity: 'base'});
                    });

                    rows.forEach(row => tbody.appendChild(row));
                });
            });

            // ---------------- Search ----------------
            const searchInput = document.getElementById('searchUserInput');
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const filter = searchInput.value.toLowerCase();
                    const rows = table.querySelectorAll('tbody tr');

                    rows.forEach(row => {
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(filter) ? '' : 'none';
                    });
                });
            }

            // ---------------- Delete modal ----------------
            const modal = document.getElementById('useConfirmDelete');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

            if (modal && confirmDeleteBtn) {
                let deleteForm = null;

                modal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    deleteForm = button?.closest('form') || null;

                    const title = button?.getAttribute('data-title');
                    const modalTitle = modal.querySelector('#confirmDeleteLabel');
                    if (modalTitle) modalTitle.textContent = title || 'Delete Confirmation';
                });

                confirmDeleteBtn.addEventListener('click', function () {
                    if (deleteForm) deleteForm.submit();
                });
            }

        });
    </script>


@endsection

