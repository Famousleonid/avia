@extends('mobile.master')

@section('style')
    <style>
        label {
            font-size: 0.85rem;
            font-weight: 500;
        }

        .form-control {
            font-size: 0.9rem;
        }
    </style>
@endsection

@section('content')
    <div class="card card-primary card-outline shadow mt-2 mb-4">
        <div class="card-body box-profile">

            <div class="text-center mb-3">
                <a href="{{ $user->getBigImageUrl('avatar') }}" class="open-fancybox" data-caption="Avatar">
                    <img src="{{ $user->getThumbnailUrl('avatar') }}" width="60" class="rounded-circle">
                </a>
            </div>

            <h4 class="profile-username text-center mb-1">{{ $user->name }}</h4>
            @if($user->team)
                <p class="text-muted text-center mb-3">{{ $user->team->name }}</p>
            @else
                <p class="text-danger text-center mb-3">No team selected</p>
            @endif

            <form action="{{ route('mobile.update.profile', ['id' => $user->id]) }}" class="createForm" method="POST" enctype="multipart/form-data">
                @method('PUT')
                @csrf

                <div class="form-group mb-3">
                    <label for="name">Name</label>
                    <input name="name" class="form-control" value="{{ $user->name }}">
                </div>

                <div class="form-group mb-3">
                    <label for="phone">Phone</label>
                    <input name="phone" class="form-control" value="{{ $user->phone }}" placeholder="000 000 00 00">
                </div>

                <div class="form-group mb-3">
                    <label>Email</label>
                    <input class="form-control" value="{{ $user->email }}" disabled>
                </div>

                <div class="form-group mb-3">
                    <label>Change Avatar</label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        {{-- Кнопка Upload --}}
                        <label for="avatarUpload" class="btn btn-outline-secondary btn-sm mb-0">
                            📷 Upload
                        </label>
                        <input type="file" id="avatarUpload" name="file" accept="image/*" class="d-none">

                        {{-- Превью изображения (по умолчанию скрыто) --}}

                        <img id="avatarPreviewImg"
                             src="{{ $user->getThumbnailUrl('avatar') }}"
                             alt="Preview"
                             width="40"
                             height="40"
                             class="rounded-circle"
                             style="cursor: zoom-in;"
                             data-default="{{ $user->getBigImageUrl('avatar') }}">


                        {{-- Название файла --}}
{{--                        <span id="file-name" class="small text-muted">No file chosen</span>--}}

                        {{-- Select --}}
                        <select name="team_id"
                                class="form-select form-select-sm {{ is_null($user->team_id) ? 'border-danger text-danger' : '' }}"
                                style="min-width: 160px; flex: 1;">
                            <option value="" {{ is_null($user->team_id) ? 'selected' : '' }} class="text-danger">
                                No team selected
                            </option>
                            @foreach ($teams as $team)
                                <option value="{{ $team->id }}" {{ $user->team_id == $team->id ? 'selected' : '' }}>
                                    {{ $team->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group mb-3">
            <label for="stamp">Stamp</label>
            <input name="stamp" class="form-control" value="{{ $user->stamp }}">
        </div>

        <div class="d-flex justify-content-center mt-4">
            <button type="submit" class="btn btn-info me-5" style="width: 140px;" id="saveButton" onclick="showLoadingSpinner()">Save</button>
            <a href="{{ route('mobile.index') }}" class="btn btn-secondary" style="width: 140px;" id="cancelButton">Cancel</a>
        </div>

        </form>

        <div class="form-group mt-5">
            <button class="btn btn-primary btn-block" type="button"
                    data-bs-toggle="modal"
                    data-bs-target="#updatePasswordModal">
                Change password
            </button>
        </div>
    </div>
    </div>

    <form id="changePassForm" method="POST" action="{{ route('mobile.profile.changePassword', ['id' => $user->id]) }}">
        @csrf
        <div class="modal fade" id="updatePasswordModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h6 class="modal-title text-primary mb-0">Change Password</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body pb-0">
                        <div class="form-group mb-3">
                            <label for="old_pass" class="small">Old Password</label>
                            <input id="old_pass" type="password" name="old_pass" class="form-control" required autofocus>
                        </div>
                        <div class="form-group mb-3">
                            <label for="new_pass" class="small">New Password</label>
                            <input id="new_pass" type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="confirm_pass" class="small">Confirm Password</label>
                            <input id="confirm_pass" type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>

                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary me-auto" data-bs-dismiss="modal" >Close</button>
                        <button type="submit" class="btn btn-primary" onclick="showLoadingSpinner()">Verify</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    <script>
        window.addEventListener('load', function () {

            document.body.classList.add('loaded');
            hideLoadingSpinner();

            document.querySelectorAll('.open-fancybox').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();

                    Fancybox.show([
                        {
                            src: this.getAttribute('href'),
                            type: "image",
                            caption: this.dataset.caption || ""
                        }
                    ], {
                        Toolbar: {
                            display: {
                                left: [],
                                middle: [],
                                right: ['close']
                            }
                        },
                        Thumbs: false
                    });
                });
            });


            const fileInput = document.getElementById('avatarUpload');
            const previewImg = document.getElementById('avatarPreviewImg');
            let currentPreviewSrc = previewImg.dataset.default; // ссылка на старое изображение


            // Открытие изображения в Fancybox
            previewImg.addEventListener('click', function () {
                if (currentPreviewSrc) {
                    Fancybox.show([
                        {
                            src: currentPreviewSrc,
                            type: 'image'
                        }
                    ]);
                }
            });

            // При выборе нового файла
            fileInput.addEventListener('change', function () {
                if (this.files.length > 0) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const base64 = e.target.result;
                        previewImg.src = base64;
                        currentPreviewSrc = base64;
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    // Если файл отменили — вернуть аватар по умолчанию
                    previewImg.src = previewImg.dataset.default;
                    currentPreviewSrc = previewImg.dataset.default;
                }
            });



            function clearAvatarPreview() {
                const previewImg = document.getElementById('avatarPreviewImg');
                const fileInput = document.getElementById('avatarUpload');

                previewImg.classList.add('d-none');
                previewImg.src = '';
                fileInput.value = '';
            }

            document.getElementById('cancelButton')?.addEventListener('click', clearAvatarPreview);


        });
    </script>
@endsection
