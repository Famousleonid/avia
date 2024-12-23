@extends('cabinet.master')

@section('content')

    <style>
        .parent {
            position: relative;

        }

        .winPhoto {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 270px;
            height: 10px;
        }
    </style>

    <div class="container ">
        <div class="row">
            <div class="col-5">
                <div class="">
                    <div class="card card-primary card-outline shadow  p-3">
                        <div class="card-body box-profile">
                            @php $mediaName = 'avatar' @endphp

                            <div class="parent text-center">
                                @php
                                    $avatarUrl = $avatar ? route('image.show.thumb', ['mediaId' => $avatar->id, 'modelId' => $user->id, 'mediaName' => $mediaName]) : asset('img/avatar.jpeg');
                                @endphp
                                <img class="rounded-circle" src="{{ $avatarUrl }}" width="70">

                                <div class="winPhoto">
                                    <form action="{{ route('avatar.media.store', ['id' => $user->id]) }}" method="post" enctype="multipart/form-data" id="uploadAvatarForm">
                                        @csrf
                                        <input type="file" id="avatarfileInput" name="avatar" style="display: none;">
                                        <button type="button" class="rounded-circle" style="border: none;" title="Change avatar" id="triggerAvatarButton">
                                            <img class="rounded-circle" src="{{ asset('img/photo.jpeg') }}" width="30">
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <h3 class="profile-username text-center">{{$user->name}}</h3>
                            @if(Auth()->user()->getRole() == 1)
                                <p class="text-muted text-center">Aviation team lieder</p>
                            @else
                                <p class="text-muted text-center">Aviation technician</p>
                            @endif
                            <div class="col-12 text-center">
                                <b class="text-blue">Email:</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b class="">{{$user->email}}</b>
                            </div>
                            <form action="{{route('cabinet.users.update',['user' => $user->id])}}" class="createForm" method="POST">
                                @method('PUT')
                                @csrf
                                <div class="row">
                                    <div class="col-12 mb-2">
                                        <b>Name:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input name="name" class="form-control" value="{{$user->name}}">
                                    </div>
                                    <div class="col-12 mb-2">
                                        <b>Phone:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input name="phone" class="form-control" value="{{$user->phone}}" placeholder="000 000 00 00">
                                    </div>


                                    <div class="col-12 row ">
                                        <div class="col-6 pt-2">
                                            <b>Stamp:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input name="stamp" class="form-control" value="{{$user->stamp}}">
                                        </div>
                                        <div class="col-6">
                                            <label class="sf" for="select_team">
                                                Team <span style="color:red; font-size: x-small">(required)</span>
                                            </label>
                                            <select name="team_id" id="select_team" class="form-control">
                                                @if($user->team)
                                                    <option value="{{ $user->team->id }}" selected>{{ $user->team->name }}</option>
                                                @else
                                                    <option value="" disabled selected>Select your team</option>
                                                @endif

                                                @foreach($teams as $team)
                                                    @if(!$user->team || $team->id != $user->team->id)
                                                        <option value="{{ $team->id }}" {{ old('team_id') == $team->id ? 'selected' : '' }}>
                                                            {{ $team->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group container-fluid mt-2 ">
                                    <div class="card-body row">
                                        <div class="col-5 m-auto">
                                            <button id="ntSaveFormsSubmit" type="submit" class="btn btn-info btn-block ntSaveFormsSubmit">Save</button>
                                        </div>
                                        <div class="col-5 m-auto">
                                            <a href="{{ route('cabinet.users.index')}}" class="btn btn-secondary btn-block">Cancel</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div class="form-group container-fluid mb-3 row">
                                <div class="col-lg-12 m-auto">
                                    <form action="{{route('cabinet.profile.changePassword', ['id' => $user->id])}}" method="post"
                                    @method('POST')
                                    @csrf
                                    @include('components.updatepassword')

                                    <button class="btn btn-primary btn-block" type="button" data-toggle="modal" data-target="#updatePasswordModal" data-title="Change Password">Change password</button>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script>
        document.addEventListener('DOMContentLoaded', function () {

            $('#updatePasswordModal').on('show.bs.modal', function (e) {
                let title = $(e.relatedTarget).attr('data-title');
                $(this).find('.modal-title').text(title);
                let form = $(e.relatedTarget).closest('form');
                $(this).find('.modal-footer #btn_confirm_change_pass').data('form', form);
            });
            $('#updatePasswordModal').find('.modal-footer #btn_confirm_change_pass').on('click', function () {
                $(this).data('form').submit();
            });

            // ------------------------avatar --------------------------------------------------------
            const avatarButton = document.getElementById('triggerAvatarButton');
            const fileInput = document.getElementById('avatarfileInput');
            avatarButton.addEventListener('click', function () {
                fileInput.click();
            });
            fileInput.addEventListener('change', function () {
                showLoadingSpinner()
                if (this.files.length > 0) {
                    this.form.submit();
                }
            });


        });
    </script>
@endsection
