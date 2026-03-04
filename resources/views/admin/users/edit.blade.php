@extends('admin.master')

@section('content')

    <style>
        .page-wrap { max-width: 800px; margin: 0 auto; }
        .form-actions{
            position: sticky;
            bottom: 0;
            z-index: 10;
            background: rgba(0,0,0,.35);
            backdrop-filter: blur(6px);
            border-top: 1px solid rgba(255,255,255,.12);
            padding-top: .75rem;
            padding-bottom: .5rem;
            margin-top: 1rem;
        }
    </style>

    <div class="container mt-0 page-wrap">
        <div class="card bg-gradient">

            {{-- Header with avatar --}}
            <div class="card-header text-center">
                @if($user->hasMedia('avatar'))
                    <a href="{{ $user->getFirstMediaBigUrl('avatar') }}" data-fancybox="gallery">
                        <img class="rounded-circle mb-2"
                             src="{{ $user->getFirstMediaThumbnailUrl('avatar') }}"
                             width="80" height="80" alt="Avatar"/>
                    </a>
                @else
                    <img src="https://via.placeholder.com/140"
                         class="rounded-circle mb-2"
                         width="140" height="140"
                         alt="Default Avatar">
                @endif

                <h6 class="text-primary mb-0">Edit technik</h6>
            </div>

            <div class="card-body p-2">
                <form action="{{ route('users.update', $user->id) }}"
                      method="POST"
                      enctype="multipart/form-data"
                      onsubmit="return validateForm();">
                    @csrf
                    @method('PUT')

                    {{-- Name / Email --}}
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   value="{{ old('name', $user->name) }}"
                                   required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Email</label>

                            <div class="input-group">
                                @role('Admin')
                                <input type="email"
                                       name="email"
                                       class="form-control"
                                       value="{{ old('email', $user->email) }}"
                                       required>
                                @else
                                    <input type="email"
                                           class="form-control"
                                           value="{{ $user->email }}"
                                           readonly>

                                    <span class="input-group-text text-warning"
                                          data-tippy-content="Only Admin can edit email"
                                          style="cursor: help;">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                    @endrole
                            </div>
                        </div>
                    </div>

                    {{-- Birthday / Phone --}}
                    <div class="row g-3 mt-0">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Birthday</label>
                            <input type="date"
                                   name="birthday"
                                   class="form-control"
                                   value="{{ old('birthday', optional($user->birthday)->format('Y-m-d')) }}">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text"
                                   name="phone"
                                   class="form-control"
                                   value="{{ old('phone', $user->phone) }}">
                        </div>
                    </div>

                    {{-- Stamp / Team --}}
                    <div class="row g-3 mt-0">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Stamp</label>
                            <input type="text"
                                   name="stamp"
                                   class="form-control"
                                   value="{{ old('stamp', $user->stamp) }}">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" id="team_label">Team</label>
                            <select name="team_id" id="team_id" class="form-select" required>
                                <option value="">Select Team</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}"
                                        {{ (string)old('team_id', $user->team_id) === (string)$team->id ? 'selected' : '' }}>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Role (Admin only) --}}
                    @role('Admin')
                    <div class="row g-3 mt-0">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role_id" class="form-select">
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}"
                                        {{ (string)old('role_id', $user->role_id) === (string)$role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox"
                                       name="is_admin"
                                       id="is_admin"
                                       class="form-check-input"
                                    {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
                                <label for="is_admin" class="form-check-label">admin</label>
                            </div>
                        </div>
                    </div>
                    @endrole

                    {{-- Avatar / Sign --}}
                    <div class="row g-3 mt-0">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Avatar</label>
                            <input type="file" name="img" class="form-control">
                            <div class="form-text">Upload a new avatar to replace the current one.</div>
                        </div>

                        @if(Auth::user()->role !== null && Auth::user()->role->name !== 'Component Technician')
                            <div class="col-12 col-md-6">
                                <label class="form-label">Sign</label>
                                <input type="file" name="sign" id="sign" class="form-control">
                            </div>
                        @endif
                    </div>

                    {{-- Sticky Buttons --}}
                    <div class="form-actions">
                        <div class="d-flex gap-2">
                            <button type="submit"
                                    class="btn btn-outline-primary"
                                    onclick="showLoadingSpinner()">
                                Save
                            </button>

                            <a href="{{ route('users.index') }}"
                               class="btn btn-outline-secondary"
                               onclick="hideLoadingSpinner()">
                                Cancel
                            </a>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script>
        function validateForm() {
            const teamSelect = document.getElementById('team_id');
            const teamLabel  = document.getElementById('team_label');

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
