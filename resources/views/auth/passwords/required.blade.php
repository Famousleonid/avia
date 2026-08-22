@extends('front.master')

@section('content')
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h5 mb-0">Password change required</h1>
                    </div>
                    <div class="card-body p-4">
                        <p>
                            Before continuing, replace your temporary or legacy password.
                            Enter your current password and choose a new one.
                        </p>

                        @if($user->hasExpiredTemporaryPassword())
                            <div class="alert alert-danger" role="status">
                                Your temporary password expired on
                                <strong>{{ format_project_date($user->temporary_password_expires_at) }}</strong>.
                                Change it now to continue.
                            </div>
                        @elseif($user->hasActiveTemporaryPassword())
                            <div class="alert alert-info" role="status">
                                Your temporary password is valid until
                                <strong>{{ format_project_date($user->temporary_password_expires_at) }}</strong>.
                            </div>
                        @else
                            <div class="alert alert-warning" role="status">
                                This one-time change is required for your existing password before you can continue.
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.required.update') }}" data-password-submit-loading data-no-spinner>
                            @csrf

                            <div class="mb-3">
                                <x-password-field id="required-current-password"
                                                  name="old_pass"
                                                  label="Current Password"
                                                  autocomplete="current-password"
                                                  :required="true" />
                            </div>

                            <div class="mb-3">
                                <x-password-field id="required-new-password"
                                                  name="password"
                                                  label="New Password"
                                                  autocomplete="new-password"
                                                  :required="true"
                                                  :policy="true" />
                            </div>

                            <div class="mb-4">
                                <x-password-field id="required-password-confirmation"
                                                  name="password_confirmation"
                                                  label="Confirm New Password"
                                                  autocomplete="new-password"
                                                  :required="true" />
                            </div>

                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">Change password and sign in again</button>
                                <button type="submit"
                                        form="required-password-logout"
                                        class="btn btn-outline-secondary">
                                    Sign out
                                </button>
                            </div>
                        </form>

                        <form id="required-password-logout" method="POST" action="{{ route('logout') }}" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
