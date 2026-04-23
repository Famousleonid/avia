@extends('admin.master')

@section('content')

    <div class="container mt-1" style="max-width:800px;">
        <div class="card bg-gradient">
            <div class="card-header">
                <h5 class="text-primary mb-0">Create new USER</h5>
            </div>

            <div class="card-body">
                <form method="POST"
                      action="{{ route('users.store') }}"
                      enctype="multipart/form-data"
                      id="createUserForm">
                    @csrf

                    {{-- Name / Email --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Name</label>
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   value="{{ old('name') }}"
                                   required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Email <span class="text-danger-emphasis" style="font-size: 12px"> requared</span></label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   value="{{ old('email') }}"
                                   required>
                        </div>
                    </div>

                    {{-- Birthday / Password --}}
                    <div class="row g-3 mt-0">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Birthday</label>
                            <input type="date"
                                   name="birthday"
                                   class="form-control"
                                   value="{{ old('birthday') }}">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Temporary Password</label>
                            <input type="password"
                                   name="password"
                                   class="form-control"
                                   required>
                        </div>
                    </div>

                    {{-- Avatar --}}
                    <div class="row g-3 mt-0">
                        <div class="col-12">
                            <label class="form-label">Avatar</label>
                            <input type="file"
                                   name="img"
                                   class="form-control">
                        </div>
                    </div>

                    {{-- Role / Team --}}
                    <div class="row g-3 mt-0">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role_id"
                                    class="form-select"
                                    required>
                                <option value="">Select Role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ (string) old('role_id') === (string) $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Team</label>
                            <select name="team_id"
                                    class="form-select"
                                    required>
                                <option value="">Select Team</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" {{ (string) old('team_id') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Phone / Stamp --}}
                    <div class="row g-3 mt-0">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text"
                                   name="phone"
                                   class="form-control"
                                   value="{{ old('phone') }}">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Stamp</label>
                            <input type="text"
                                   name="stamp"
                                   class="form-control"
                                   value="{{ old('stamp') }}">
                        </div>
                    </div>

                    {{-- Buttons --}}
                    <div class="d-flex gap-2 mt-2">
                        <button type="submit"
                                class="btn btn-outline-primary">
                            Create
                        </button>

                        <a href="{{ route('users.index') }}"
                           class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

@endsection

