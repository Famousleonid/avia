@extends('cabinet.master')


@section('add-menu')

    <div class="ml-5">
        <a href="{{route('technik.create')}}"><img src="{{asset('img/adduser.png')}}" width="30" alt=""></a>
        <span class="ml-2">Add technik</span>
    </div>

@endsection



@section('content')

    <div class="container-fluid pl-3 pr-3 pt-2">

        <div class="card shadow firm-border  mt-2">

            <div class="card-header">
                <h3 class="card-title text-bold">list of techniks ( {{count($users)}} )</h3>
                <span class="text-danger">&nbsp;&nbsp;&nbsp; RED name </span><span>this is an unconfirmed email</span>


                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                        <i class="fas fa-minus"></i></button>
                </div>
            </div>

            <div class="card-body">
                <div class="box-body table-responsive">

                    @if(count($users))

                        <table id="user-list" class="table-sm table-bordered table-striped table-hover " style="width:100%;">

                            <thead>
                            <tr>
                                <th class="text-center text-sm" data-orderable="false">№</th>
                                <th>Name</th>
                                <th data-orderable="false">Email</th>
                                <th style="width: 100px" class="text-center" data-orderable="false">Stamp</th>
                                <th style="width: 100px" class="text-center" data-orderable="false">Team</th>
                                <th style="width: 400px" class="text-center" data-orderable="false">Avatar</th>
                                <th style="width: 100px" data-orderable="false">Phone</th>
                                <th style="width: 100px" class="text-center" data-orderable="false">Admin</th>
                                <th style="width: 100px" class="text-center" data-orderable="false">Role</th>
                                <th style="width: 100px" class="text-center" data-orderable="false">Create Date</th>
                                <th class="text-center" data-orderable="false">Edit</th>
                                <th class="text-center" data-orderable="false">Delete</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td class="text-center">{{$loop->iteration}}</td>

                                    <td @if(!$user->email_verified_at) style="color:red" @endif>{{$user->name}}</td>
                                    <td>{{$user->email}}</td>
                                    <td class="text-center">{{$user->stamp}}</td>
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
                                    <td>{{$user->phone}}</td>
                                    <td class="text-center">
                                        @if($user->is_admin)<i class="fas fa-lg fa-crown text-primary"></i>@endif
                                    </td>
                                    <td class="text-center">
                                        @if($user->role)<i class="fas fa-lg fa-people-carry text-primary"></i>@endif
                                    </td>

                                    <td class="text-center"><span style="display: none">{{$user->created_at}}</span>{{$user->created_at->format('d.m.Y')}}</td>
                                    <td class="text-center">
                                        <a href="{{route('technik.edit', ['technik' => $user->id]) }}"><img src="{{asset('img/set.png')}}" width="25" alt=""></a>
                                    </td>
                                    <td class="text-center">
                                        <div>
                                            <form action="{{route('technik.destroy', ['technik' => $user->id])}}" method="post">
                                                @csrf
                                                @method('DELETE')

                                                <button class="btn btn-xs btn-danger" type="button" data-toggle="modal" data-target="#confirmDelete" data-title="Delete User" data-message="Are you sure you want to delete technik: {{$user->name}} ?">
                                                    <i class="fa fa-trash-o"></i>
                                                </button>

                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            </tbody>
                        </table>
                    @else
                        <p>No user created</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('components.delete')

@endsection

@section('scripts')
    <script>
        let userTable = $('#user-list').DataTable({
            "AutoWidth": true,
            "scrollY": "600px",
            "scrollCollapse": true,
            "paging": false,
            "ordering": false,
            "info": false,
            "columnDefs": [
                {"width": "2%", "targets": 0},
                {"width": "10%", "targets": 1},
                {"width": "15%", "targets": 2},
                {"width": "5%", "targets": 3}, // -- stamp
                {"width": "15%", "targets": 4},
                {"width": "10%", "targets": 5}, //-- avatar
                {"width": "8%", "targets": 6},
                {"width": "5%", "targets": 7},
                {"width": "5%", "targets": 8},
                {"width": "5%", "targets": 9},
                {"width": "5%", "targets": 10},
                {"width": "5%", "targets": 11},
            ],
        });
        // delete form confirm

        $('#confirmDelete').on('show.bs.modal', function (e) {

            let message = $(e.relatedTarget).attr('data-message');
            $(this).find('.modal-body p').text(message);
            $title = $(e.relatedTarget).attr('data-title');
            $(this).find('.modal-title').text($title);
            let form = $(e.relatedTarget).closest('form');
            $(this).find('.modal-footer #buttonConfirm').data('form', form);
        });

        $('#confirmDelete').find('.modal-footer #buttonConfirm').on('click', function () {
            $(this).data('form').submit();
        });

    </script>
@endsection
