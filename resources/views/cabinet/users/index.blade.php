@extends('admin.master')


@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 170px);
            overflow-y: auto;
            overflow-x: hidden;
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
            min-width:90px;
            max-width:100px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 50px;
            max-width: 100px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 50px;
            max-width: 100px;
        }

        .table thead th {
            position: sticky;
            height: 50px;
            top: 0;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        @media (max-width: 1200px) {
            .table th:nth-child(2), .table td:nth-child(2) {
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

    <div class="card shadow ">
        <div class="card-header my-1 shadow">
            <div class="d-flex ">
                <h5 class="text-primary">{{__('Manage Users')}}</h5>&nbsp;{{count($users)}}
            </div>

            <div class="d-flex my-2">
                <div class="clearable-input ps-2">
                    <input id="searchUserInput" type="text" class="form-control w-100" placeholder="Search...">
                    <button class="btn-clear text-secondary" onclick="document.getElementById('searchUserInput').value = '';
                    document.getElementById('searchUserInput').dispatchEvent(new Event('input'))">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>
        </div>

        @if(count($users))
            <div class="table-wrapper me3 p-2 pt-0 ">
                <table id="userTable" class="display table table-sm table-hover table-striped align-middle table-bordered">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary bg-gradient sortable">{{__('Name') }}<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary bg-gradient ">{{__('Email') }}</th>
                        <th class="text-primary bg-gradient sortable text-center">{{__('Team') }}<i class="bi bi-chevron-expand ms-1"></i></th>
                        <th class="text-primary bg-gradient text-center">{{__('Avatar') }}</th>
                        <th class="text-primary bg-gradient text-center">{{__('Role') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td @if(!$user->email_verified_at) style="color:red" @endif>{{$user->name}}</td>
                            <td class="">{{$user->email}}</td>
                            <td class="text-center" style="color: {{ $user->team ? '#ffffff' : '#808080' }};">{{ $user->team->name ?? 'Unknown team' }}</td>
                            <td class="text-center">
                                <a href="{{ $user->getBigImageUrl('avatar') }}" data-fancybox="gallery">
                                    <img class="rounded-circle" src="{{ $user->getThumbnailUrl('avatar') }}" width="40" height="40" alt="Image"/>
                                </a>
                            </td>
                            <td class="text-center" style="color: {{ $user->role? '#ffffff' : '#808080' }};">{{ $user->role->name ?? 'Unknown role' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @else
                    <p>Technik not Created</p>
                @endif
            </div>
    </div>

@endsection
@section('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Sorting
            const table = document.getElementById('userTable');
            const headers = document.querySelectorAll('.sortable');
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                    const rows = Array.from(table.querySelectorAll('tbody tr'));
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;
                    headers.forEach(h => h.classList.remove('active'));
                    header.classList.add('active');
                    rows.sort((a, b) => {
                        const aText = a.cells[columnIndex]?.innerText.trim() || '';
                        const bText = b.cells[columnIndex]?.innerText.trim() || '';
                        return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                    });
                    rows.forEach(row => table.querySelector('tbody').appendChild(row));
                });
            });

            // Search
            const searchInput = document.getElementById('searchUserInput');
            searchInput.addEventListener('input', () => {
                const filter = searchInput.value.toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });

        });
    </script>
@endsection

