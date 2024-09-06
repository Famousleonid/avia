@extends('cabinet.master')

@section('content')

    <div class="container pl-3 pr-3 pt-2">
        <div class="card shadow firm-border bg-white mt-2">
            <div class="card-header">
                <h3 class="card-title text-bold">list of techniks ( {{count($users)}} )</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
                        <i class="fas fa-minus"></i></button>
                </div>
            </div>
            @php
                $teams = [
                    0 => "Management",
                    1 => "Akimov's team",
                    2 => "Blinov's team",
                    3 => "Steblyk's team",
                    4 => "Tchalyi's team",
                    5 => "Barysevich's team",
                    6 => "Volker's team",
                    7 => "Never stop's team",
                    8 => "Lipikhin's team"
                ];
            @endphp
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
                                <th style="width: 100px" class="text-center" data-orderable="false">Team Leader</th>
                                <th style="width: 100px" class="text-center" data-orderable="false">Create Date</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td class="text-center">{{$loop->iteration}}</td>
                                    <td @if(!$user->email_verified_at) style="color:red" @endif>{{$user->name}}</td>
                                    <td>{{$user->email}}</td>
                                    <td class="text-center">{{$user->stamp}}</td>
                                    <td class="text-center">{{ $teams[$user->team] ?? 'Unknown team' }}</td>
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
                                                : asset('img/noimage2.png');
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
                                        @if($user->role)<img src="{{asset('img/icons/team-leader.png')}}" width="60" alt=""></i>@endif
                                    </td>
                                    <td class="text-center"><span style="display: none">{{$user->created_at}}</span>{{$user->created_at->format('d.m.Y')}}</td>
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
            "searching": false,
            "info": false,
            "columnDefs": [
                {"width": "2%", "targets": 0},
                {"width": "10%", "targets": 1},
                {"width": "17%", "targets": 2},
                {"width": "5%", "targets": 3}, // -- stamp
                {"width": "15%", "targets": 4},
                {"width": "10%", "targets": 5}, //-- avatar
                {"width": "8%", "targets": 6},
                {"width": "5%", "targets": 7},
                {"width": "10%", "targets": 8},
                {"width": "10%", "targets": 9},
            ],
        });

    </script>
@endsection
