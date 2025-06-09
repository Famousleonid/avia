@extends('admin.master')

@section('content')
    <style>
        .container {
            max-width: 700px;
        }
    </style>
    <div class="container mt-4">
        <div class="card bg-gradient">
            <div class="card-header text-center">
                @if($user->hasMedia('avatar'))
                    <a href="{{ $user->getFirstMediaBigUrl('avatar') }}" data-fancybox="gallery">
                        <img class="rounded-circle mb-3" src="{{ $user->getFirstMediaThumbnailUrl('avatar') }}" width="150" height="150" alt="Avatar"/>
                    </a>
                @else
                    <img src="https://via.placeholder.com/150" class="rounded-circle mb-3" width="150" height="150" alt="Default Avatar">
                @endif
            </div>
            <div class="card-body">
                <form action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data" onsubmit="return validateForm();">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label small">name</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ $user->name }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label small">email</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ $user->email }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label small">phone</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="{{ $user->phone }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="stamp" class="form-label small">stamp</label>
                            <input type="text" name="stamp" id="stamp" class="form-control" value="{{ $user->stamp }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="role_id" class="form-label small">role</label>
                            <select name="role_id" id="role_id" class="form-select">
                                <option value="" {{ $user->role_id ? '' : 'selected' }}>Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="team_id" class="form-label small" id="team_label">team</label>
                            <select name="team_id" id="team_id" class="form-select" >
                                <option value="" disabled {{ $user->team_id ? '' : 'selected' }}>Select Team</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}" {{ $user->team_id == $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_admin" id="is_admin" class="form-check-input" {{ $user->is_admin ? 'checked' : '' }}>
                        <label for="is_admin" class="form-check-label small">admin</label>
                    </div>

                    <div class="mb-3">
                        <label for="img" class="form-label small">avatar</label>
                        <input type="file" name="img" id="img" class="form-control">
                        <small>Upload a new avatar to replace the current one.</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary" onclick="showLoadingSpinner()">Save</button>
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function validateForm() {
            const teamSelect = document.getElementById('team_id');
            const teamLabel = document.getElementById('team_label');
            if (!teamSelect.value) {
                hideLoadingSpinner();
                const originalText = teamLabel.textContent;
                teamLabel.textContent = 'Please select a valid team.';
                teamLabel.classList.add('text-danger');
                setTimeout(() => {
                    teamLabel.textContent = originalText;
                    teamLabel.classList.remove('text-danger');
                }, 5000);
                teamSelect.focus();
                return false;
            }
            return true;
        }
    </script>
@endsection
