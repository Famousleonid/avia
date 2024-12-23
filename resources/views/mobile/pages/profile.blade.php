@extends('mobile.master')

@section('content')

    <style>
        .parent {
            position: relative;

        }

        .winPhoto {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 220px;
            height: 20px;
        }
    </style>


    <div class="card card-primary card-outline shadow">
        <div class="card-body box-profile">
            @php $mediaName = 'avatar' @endphp

            <div class="parent text-center">
                @php
                    $avatarUrl = $avatar ? route('image.show.thumb', ['mediaId' => $avatar->id, 'modelId' => $user->id, 'mediaName' => $mediaName]) : asset('img/avatar.jpeg');
                @endphp
                <img class="rounded-circle" src="{{ $avatarUrl }}" width="70">

                <div class="winPhoto">
                    <form action="{{ route('mobile.avatar.media.store', ['id' => $user->id]) }}" method="post" enctype="multipart/form-data" id="uploadAvatarForm">
                        @csrf
                        <input type="file" id="avatarfileInput" name="avatar" style="display: none;" accept="image/*" capture="user">
                        <button type="button" class="rounded-circle" style="border: none;" title="Change avatar" id="triggerAvatarButton">
                            <img class="rounded-circle" src="{{ asset('img/photo.jpeg') }}" width="30">
                        </button>
                    </form>
                </div>
            </div>

            <h3 class="profile-username text-center">{{$user->name}}</h3>
            <p class="text-muted text-center">Aviation technician</p>
            <form action="{{route('users.update',['users' => $user->id])}}" class="createForm" method="POST">
                @method('PUT')
                @csrf
                <div class="row">
                    <div class="col-12">
                        <b>Name:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input name="name" class="form-control" value="{{$user->name}}">
                    </div>
                    <div class="col-12">
                        <b>Phone:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input name="phone" class="form-control" value="{{$user->phone}}" placeholder="000 000 00 00">
                    </div>
                    <div class="col-12">
                        <b>Email:</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b class="">{{$user->email}}</b>
                    </div>
                    <div class="col-12">
                        <b>Name:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input name="name" class="form-control" value="{{$user->name}}">
                    </div>
                    <div class="col-12 row">
                        <div class="col-6">
                            <b>Stamp:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input name="stamp" class="form-control" value="{{$user->stamp}}">
                        </div>
                        <div class="col-6">
                            <b>Stamp:</b>&nbsp;&nbsp;&nbsp;&nbsp;<input name="stamp" class="form-control" value="{{$user->stamp}}">
                        </div>
                    </div>


                </div>

                <div class="form-group container-fluid ">
                    <div class="card-body row ">
                        <div class="col-5 mb-1">
                            <button id="ntSaveFormsSubmit" type="submit" class="btn btn-info btn-block ntSaveFormsSubmit">Save</button>
                        </div>
                        <div class="col-5 mb-1 ml-auto">
                            <a href="{{ route('mobile.index')}}" class="btn btn-secondary btn-block">Cancel</a>
                        </div>
                    </div>
                </div>

            </form>
            <div class="form-group ">
                <div class="row">
                    <form action="{{route('profile.changePassword', ['id' => $user->id])}}" method="post"
                    @method('POST')
                    @csrf
                    @include('components.updatepassword')
                    <div class="col-12">
                        <button class="btn btn-primary btn-block" type="button" data-toggle="modal" data-target="#updatePasswordModal" data-title="Change Password">Change password</button>
                    </div>
                    </form>
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

                if (this.files.length > 0) {
                    showSpinner();
                    this.form.submit();
                }
            });


        });
    </script>
@endsection

