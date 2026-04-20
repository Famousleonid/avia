@extends('admin.master')

@section('content')
    <div class="container mt-3" style="max-width: 760px;">
        <div class="card bg-gradient">
            <div class="card-header text-center">
                <a href="{{ $user->getFirstMediaBigUrl('avatar') }}" data-fancybox="gallery">
                    <img class="rounded-circle mb-2"
                         src="{{ $user->getFirstMediaThumbnailUrl('avatar') }}"
                         width="80"
                         height="80"
                         alt="Avatar">
                </a>
                <h5 class="mb-0">My profile</h5>
            </div>

            <div class="card-body">
                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Birthday</label>
                            <input type="date" name="birthday" class="form-control" value="{{ old('birthday', optional($user->birthday)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Stamp</label>
                            <input type="text" name="stamp" class="form-control" value="{{ old('stamp', $user->stamp) }}" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Team</label>
                            <select name="team_id" class="form-select" required>
                                <option value="">Select Team</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}" {{ (string) old('team_id', $user->team_id) === (string) $team->id ? 'selected' : '' }}>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Avatar</label>
                        <input type="file" name="file" class="form-control" accept="image/*">
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-outline-primary">Save</button>
                        <a href="{{ route('cabinet.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

                <hr>

                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    <h5>Change password</h5>

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Old Password</label>
                            <input type="password" name="old_pass" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-outline-primary mt-3">Change password</button>
                </form>
            </div>
        </div>
    </div>
@endsection
