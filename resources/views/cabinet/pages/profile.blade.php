@extends('cabinet.master')

@section('content')

    <div class="container vh-80 pt-3 d-flex justify-content-center">
        <div class="card shadow p-3 w-100 bg-gradient" style="max-width: 700px;">
            <div class="card-body text-center">
                <a href="{{ $user->getBigImageUrl('avatar') }}" data-fancybox="gallery">
                    <img class="rounded-circle mb-3" src="{{ $user->getThumbnailUrl('avatar') }}" width="80" height="80" alt="Image"/>
                </a>

                <h3 class="">{{$user->name}}</h3>
                <h5 class="text-primary">{{ $user->role->name ?? __('Unknown role') }}</h5>

                <p><span class="text-secondary">Email:</span> {{$user->email}}</p>

                <form action="{{route('cabinet.users.update',['user' => $user->id])}}" method="POST">
                    @method('PUT')
                    @csrf
                    <div class="row mb-3 pb-2">
                        <div class="col position-relative">
                            <label for="name" class="form-label text-muted" style="font-size: 0.9rem; position: absolute; top: 5px; left: 15px;">Name:</label>
                            <input id="name" name="name" class="form-control mt-4" value="{{$user->name}}">
                        </div>
                        <div class="col position-relative">
                            <label for="phone" class="form-label text-muted" style="font-size: 0.9rem; position: absolute; top: 5px; left: 15px;">Phone:</label>
                            <input id="phone" name="phone" class="form-control mt-4" value="{{$user->phone}}" placeholder="000 000 00 00">
                        </div>
                    </div>
                    <div class="row pb-4 mb-3 border-bottom ">
                        <div class="col position-relative">
                            <label for="stamp" class="form-label text-muted" style="font-size: 0.9rem; position: absolute; top: 5px; left: 15px;">Stamp:</label>
                            <input id="stamp" name="stamp" class="form-control mt-4" value="{{$user->stamp}}">
                        </div>
                        <div class="col position-relative ">
                            <label for="select_team" class="form-label text-muted" style="font-size: 0.9rem; position: absolute; top: 5px; left: 15px;">Team <span class="text-danger" style="font-size: x-small">(required)</span>:</label>
                            <select name="team_id" id="select_team" class="form-control mt-4">
                                @if($user->team)
                                    <option value="{{ $user->team->id }}" selected>{{ $user->team->name }}</option>
                                @else
                                    <option value="" disabled selected>Select your team</option>
                                @endif
                                @foreach($teams as $team)
                                    @if(!$user->team || $team->id != $user->team->id)
                                        <option value="{{ $team->id }}" {{ old('team_id') == $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col d-flex justify-content-around">
                            <button type="submit" class="btn btn-outline-info " style="width: 40%;" onclick="showLoadingSpinner()">Save</button>
                            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary " style="width: 40%;" onclick="showLoadingSpinner()">Cancel</a>
                        </div>
                    </div>
                </form>

                <div class="row">
                    <div class="col-6 d-flex justify-content-around">
                        <form action="{{route('cabinet.profile.changePassword', ['id' => $user->id])}}" method="post">
                            @csrf
                            <button class="btn btn-outline-primary w-100"  type="button" data-bs-toggle="modal" data-bs-target="#updatePasswordModal">Change Password</button>
                        </form>
                    </div>
                    <div class="col-6 d-flex justify-content-around">
                        <form action="{{ route('avatar.media.store', ['id' => $user->id]) }}" method="post" enctype="multipart/form-data" id="uploadAvatarForm">
                            @csrf
                            <input type="file" id="avatarfileInput" name="avatar" style="display: none;">
                            <button type="button" class="btn btn-outline-secondary w-100" id="triggerAvatarButton">
                                Change Avatar
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @include('components.updatepassword')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('#updatePasswordModal').on('show.bs.modal', function (e) {

                console.log(777)

                let title = $(e.relatedTarget).data('title');
                $(this).find('.modal-title').text(title);
                let form = $(e.relatedTarget).closest('form');
                $(this).find('.modal-footer #btn_confirm_change_pass').data('form', form);
            });
            $('#updatePasswordModal').find('.modal-footer #btn_confirm_change_pass').on('click', function () {
                $(this).data('form').submit();
            });

            const avatarButton = document.getElementById('triggerAvatarButton');
            const fileInput = document.getElementById('avatarfileInput');
            avatarButton.addEventListener('click', function () {
                fileInput.click();
            });
            fileInput.addEventListener('change', function () {
                if (this.files.length > 0) {
                    this.form.submit();
                }
            });
        });
    </script>
@endsection
