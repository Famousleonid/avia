@extends('admin.master')


@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 140px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 80px;
            max-width: 190px;
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            min-width: 40px;
            max-width: 50px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 50px;
            max-width: 150px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            min-width: 50px;
            max-width: 150px;
        }
        .table th:nth-child(4), .table td:nth-child(4) {
            min-width: 80px;
            max-width: 100px;
        }

        .table th:nth-child(5), .table td:nth-child(5) {
            min-width: 50px;
            max-width: 70px;
        }

        .table thead th {
            position: sticky;
            height: 50px;
            top: 0;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
        }

        /*@media (max-width: 1200px) {*/
        /*    .table th:nth-child(5), .table td:nth-child(5),*/
        /*    .table th:nth-child(2), .table td:nth-child(2),*/
        /*    .table th:nth-child(3), .table td:nth-child(3) {*/
        /*        display: none;*/
        /*    }*/
        /*}*/

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
    </style>

    <div class="card shadow">

        <div class="card-header my-1 shadow">
            <div class="d-flex justify-content-between">
                <h5 class="text-primary">{{__('Manage Users')}}</h5>
                <a href="{{ route('user.create') }}" class="btn
                btn-outline-primary
                btn-sm ">{{ __('Add User') }}</a>
            </div>

            <div class="d-flex my-2">
                <div class="clearable-input ps-2">
                    <input id="searchInput" type="text" class="form-control w-100" placeholder="Search...">
                    <button class="btn-clear text-secondary" onclick="document.getElementById('searchInput').value = '';
                        document.getElementById('searchInput').dispatchEvent(new Event('input'))">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>
        </div>

        @if(count($users))
            <div class="table-wrapper me3 p-2 pt-0">
                <table id="userTable" class="display table table-sm table-hover table-striped">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary bg-gradient text-center" >{{__('NN')
                        }}</th>
                        <th class="text-primary bg-gradient " >{{__('Name')
                        }}</th>
                        <th class="text-primary bg-gradient text-center" >{{__('Email')
                        }}</th>
                        <th class="text-primary bg-gradient text-center" >{{__('Team')
                        }}</th>
                        <th class="text-primary bg-gradient text-center" >{{__('Avatar')
                        }}</th>
                        <th class="text-primary bg-gradient text-center" >{{__('Role')
                        }}</th>
                        <th class="text-primary bg-gradient text-center" >{{__('Create
                        Date')}}</th>
                        <th class="text-primary bg-gradient text-center" >{{__('Edit')
                        }}</th>

                    </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td class="text-center">{{$loop->iteration}}</td>

                            <td  @if(!$user->email_verified_at)
                                     style="color:red" @endif>{{$user->name}}</td>
                            <td class="text-center">{{$user->email}}</td>
                            <td class="text-center">{{$user->team->name ?? 'Unknown team' }}</td>
                            <td>
                                <div class="text-center">

                                        <?php
                                        $avatar = $user->getMedia('avatar')->first();
                                        $avatarThumbUrl = $avatar
                                            ? route('image.show.thumb', [
                                                'mediaId' => $avatar->id,
                                                'modelId' => $user->id,
                                                'mediaName' => 'avatar'
                                            ])
                                            : asset('img/noimage.png');
                                        $avatarBigUrl = $avatar
                                            ? route('image.show.big', [
                                                'mediaId' => $avatar->id,
                                                'modelId' => $user->id,
                                                'mediaName' => 'avatar'
                                            ])
                                            : asset('img/noimage2.png');
                                        ?>
                                    <a href="{{ $avatarBigUrl }}" data-fancybox="gallery">
                                        <img class="rounded-circle" src="{{ $avatarThumbUrl }}" width="50" height="50" alt="User Avatar"/>
                                    </a>


                                </div>
                            </td>
                            <td class="text-center">

                                @if($user->role)<i class="fas fa-lg fa-people-carry text-primary"></i>
                                @else
                                    {{__('Unknown role')}}
                                @endif
                            </td>

                            <td class="text-center"><span style="display: none">{{$user->created_at}}</span>{{$user->created_at->format('d.m.Y')}}</td>
                            <td class="text-center">
                                <a href="{{route('user.edit', ['user' =>
                                $user->id]) }}"><img src="{{asset('img/set.png')}}" width="25" alt=""></a>
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


@endsection
@section('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('userTable');
            const searchInput = document.getElementById('searchInput');
            const headers = document.querySelectorAll('.sortable');

            // Sorting
            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const columnIndex = Array.from(header.parentNode.children).indexOf(header) + 1;
                    const direction = header.dataset.direction === 'asc' ? 'desc' : 'asc';
                    header.dataset.direction = direction;

                    // Icon
                    headers.forEach(h => {
                        const icon = h.querySelector('i');
                        if (icon) icon.className = 'bi bi-chevron-expand';
                    });
                    const currentIcon = header.querySelector('i');
                    if (currentIcon) currentIcon.className = direction === 'asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down';

                    // Sorting row
                    const rows = Array.from(table.querySelectorAll('tbody tr'));
                    rows.sort((a, b) => {
                        const aText = a.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        const bText = b.querySelector(`td:nth-child(${columnIndex})`).innerText.trim();
                        return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
                    });

                    // Updating the table
                    rows.forEach(row => table.querySelector('tbody').appendChild(row));
                });
            });

            // Search
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

