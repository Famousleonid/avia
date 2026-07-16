@extends('admin.master')

@section('style')
    <style>
        .project-settings-page {
            max-width: 1240px;
        }

        .project-settings-layout {
            align-items: flex-start;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(210px, 260px) minmax(0, 1fr);
        }

        .project-settings-menu,
        .project-settings-panel {
            border: 1px solid var(--bs-border-color);
            border-radius: 10px;
            overflow: hidden;
        }

        .project-settings-menu {
            background: var(--bs-body-bg);
            position: sticky;
            top: 12px;
        }

        .project-settings-menu-title {
            border-bottom: 1px solid var(--bs-border-color);
            color: var(--bs-secondary-color);
            font-size: .72rem;
            letter-spacing: .06em;
            padding: 12px 14px 9px;
            text-transform: uppercase;
        }

        .project-settings-menu .list-group-item {
            align-items: center;
            background: transparent;
            border: 0;
            border-left: 3px solid transparent;
            color: var(--bs-body-color);
            display: flex;
            gap: 10px;
            min-height: 46px;
            padding: 10px 14px 10px 11px;
        }

        .project-settings-menu .list-group-item + .list-group-item {
            border-top: 1px solid var(--bs-border-color-translucent);
        }

        .project-settings-menu .list-group-item:hover {
            background: rgba(var(--bs-primary-rgb), .08);
        }

        .project-settings-menu .list-group-item.active {
            background: rgba(var(--bs-primary-rgb), .13);
            border-left-color: var(--bs-primary);
            color: var(--bs-primary-text-emphasis);
        }

        .project-settings-menu .list-group-item i {
            color: var(--bs-info);
            font-size: 1rem;
            width: 18px;
        }

        .project-settings-panel {
            background: var(--bs-body-bg);
            min-width: 0;
        }

        .project-settings-panel-header {
            border-bottom: 1px solid var(--bs-border-color);
            padding: 16px 18px;
        }

        .project-settings-panel-header h5 {
            margin: 0;
        }

        .project-settings-panel-body {
            padding: 18px;
        }

        .project-settings-panel-footer {
            align-items: center;
            background: rgba(var(--bs-secondary-bg-rgb), .55);
            border-top: 1px solid var(--bs-border-color);
            display: flex;
            justify-content: flex-end;
            padding: 12px 18px;
        }

        .project-settings-page .setting-row {
            align-items: center;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            min-height: 64px;
        }

        .project-settings-page .setting-copy {
            min-width: 0;
        }

        .project-settings-page .form-check-input {
            cursor: pointer;
            height: 1.35rem;
            width: 2.55rem;
        }

        .project-settings-page textarea {
            min-height: 110px;
        }

        .user-background-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(260px, .8fr) minmax(320px, 1.2fr);
        }

        .user-background-controls,
        .user-background-preview-wrap {
            min-width: 0;
        }

        .user-background-preview {
            align-items: center;
            background:
                linear-gradient(135deg, rgba(var(--bs-primary-rgb), .08), rgba(var(--bs-info-rgb), .08)),
                var(--bs-tertiary-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 9px;
            display: flex;
            justify-content: center;
            min-height: 260px;
            overflow: hidden;
            position: relative;
        }

        .user-background-preview img {
            display: block;
            height: 100%;
            inset: 0;
            object-fit: cover;
            position: absolute;
            width: 100%;
        }

        .user-background-preview-empty {
            color: var(--bs-secondary-color);
            padding: 32px;
            text-align: center;
        }

        .user-background-preview-empty i {
            display: block;
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .user-background-file-name {
            overflow-wrap: anywhere;
        }

        @media (max-width: 900px) {
            .project-settings-layout,
            .user-background-grid {
                grid-template-columns: 1fr;
            }

            .project-settings-menu {
                position: static;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid project-settings-page py-3 pb-5">
        <div class="mb-3">
            <h4 class="mb-1">System Settings</h4>
            <div class="text-muted small">Printed forms, Marketing and per-user project appearance.</div>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
        @endif

        <div class="project-settings-layout">
            <nav class="project-settings-menu" aria-label="System settings sections">
                <div class="project-settings-menu-title">Settings menu</div>
                <div class="list-group list-group-flush">
                    <a
                        href="{{ route('admin.project-settings.index', ['section' => 'printed-forms']) }}"
                        class="list-group-item list-group-item-action {{ $activeSection === 'printed-forms' ? 'active' : '' }}"
                    >
                        <i class="bi bi-printer"></i>
                        <span>Printed Forms</span>
                    </a>
                    <a
                        href="{{ route('admin.project-settings.index', ['section' => 'marketing']) }}"
                        class="list-group-item list-group-item-action {{ $activeSection === 'marketing' ? 'active' : '' }}"
                    >
                        <i class="bi bi-megaphone"></i>
                        <span>Marketing</span>
                    </a>
                    <a
                        href="{{ route('admin.project-settings.index', ['section' => 'user-background', 'user_id' => $selectedUser?->id]) }}"
                        class="list-group-item list-group-item-action {{ $activeSection === 'user-background' ? 'active' : '' }}"
                    >
                        <i class="bi bi-image"></i>
                        <span>Fon for user</span>
                    </a>
                </div>
            </nav>

            <section class="project-settings-panel">
                @if($activeSection === 'printed-forms')
                    <form method="POST" action="{{ route('admin.project-settings.update') }}">
                        @csrf
                        <input type="hidden" name="settings_section" value="printed-forms">

                        <div class="project-settings-panel-header">
                            <h5>Printed Forms</h5>
                            <div class="text-muted small mt-1">Global options used by printable project forms.</div>
                        </div>
                        <div class="project-settings-panel-body">
                            <div class="setting-row">
                                <div class="setting-copy">
                                    <div class="fw-semibold">QR code mark</div>
                                    <div class="text-muted small">Add the short public verification QR code to printable forms.</div>
                                </div>

                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="print_forms_qr_enabled" value="0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        id="printFormsQrEnabled"
                                        name="print_forms_qr_enabled"
                                        value="1"
                                        @checked($qrEnabled)
                                    >
                                    <label class="visually-hidden" for="printFormsQrEnabled">QR code mark</label>
                                </div>
                            </div>
                        </div>
                        <div class="project-settings-panel-footer">
                            <button type="submit" class="btn btn-outline-primary">Save Printed Forms</button>
                        </div>
                    </form>
                @elseif($activeSection === 'marketing')
                    <form method="POST" action="{{ route('admin.project-settings.update') }}">
                        @csrf
                        <input type="hidden" name="settings_section" value="marketing">

                        <div class="project-settings-panel-header">
                            <h5>Marketing</h5>
                            <div class="text-muted small mt-1">Email reminders for WO Estimate Date.</div>
                        </div>
                        <div class="project-settings-panel-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="marketingWoEstimateEmailRecipients">
                                    WO Estimate Date email recipients
                                </label>
                                <textarea
                                    id="marketingWoEstimateEmailRecipients"
                                    name="marketing_wo_estimate_email_recipients"
                                    class="form-control @error('marketing_wo_estimate_email_recipients') is-invalid @enderror"
                                    placeholder="sales@example.com&#10;manager@example.com"
                                >{{ old('marketing_wo_estimate_email_recipients', $marketingWoEstimateEmailRecipientsText ?? '') }}</textarea>
                                <div class="form-text">One or more emails separated by line breaks, commas, semicolons, or spaces.</div>
                                @error('marketing_wo_estimate_email_recipients')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-3 align-items-end">
                                <div class="col-sm-4">
                                    <label class="form-label fw-semibold" for="marketingWoEstimateEmailDelayDays">Send after days</label>
                                    <input
                                        id="marketingWoEstimateEmailDelayDays"
                                        name="marketing_wo_estimate_email_delay_days"
                                        class="form-control @error('marketing_wo_estimate_email_delay_days') is-invalid @enderror"
                                        type="number"
                                        min="0"
                                        max="365"
                                        step="1"
                                        value="{{ old('marketing_wo_estimate_email_delay_days', $marketingWoEstimateEmailDelayDays ?? 5) }}"
                                    >
                                    @error('marketing_wo_estimate_email_delay_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-8 text-muted small">
                                    If the WO is still Waiting Approval, the reminder is sent this many calendar days after WO Estimate Date.
                                </div>
                            </div>
                        </div>
                        <div class="project-settings-panel-footer">
                            <button type="submit" class="btn btn-outline-primary">Save Marketing</button>
                        </div>
                    </form>
                @else
                    <div class="project-settings-panel-header">
                        <h5>Fon for user</h5>
                        <div class="text-muted small mt-1">Upload a shared project background for one selected user.</div>
                    </div>
                    <div class="project-settings-panel-body">
                        @if($selectedUser)
                            <div class="user-background-grid">
                                <div class="user-background-controls">
                                    <form method="GET" action="{{ route('admin.project-settings.index') }}" class="mb-4">
                                        <input type="hidden" name="section" value="user-background">
                                        <label class="form-label fw-semibold" for="backgroundUserSelect">User</label>
                                        <select
                                            id="backgroundUserSelect"
                                            name="user_id"
                                            class="form-select"
                                            onchange="this.form.submit()"
                                        >
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" @selected((int) $selectedUser->id === (int) $user->id)>
                                                    {{ $user->name }}{{ $user->role?->name ? ' — ' . $user->role->name : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">The background is visible only when this user is signed in.</div>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.project-settings.user-background.store') }}"
                                        enctype="multipart/form-data"
                                    >
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">

                                        <label class="form-label fw-semibold" for="userBackgroundImage">Background image</label>
                                        <input
                                            id="userBackgroundImage"
                                            name="background_image"
                                            type="file"
                                            class="form-control @error('background_image') is-invalid @enderror"
                                            accept="image/jpeg,image/png,image/webp"
                                            required
                                        >
                                        <div class="form-text">JPG, PNG or WEBP, up to 10 MB. A wide landscape image works best.</div>
                                        @error('background_image')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror

                                        <button type="submit" class="btn btn-outline-primary mt-3">
                                            <i class="bi bi-upload me-1"></i>
                                            {{ $userBackgroundUrl ? 'Replace background' : 'Upload background' }}
                                        </button>
                                    </form>

                                    @if($userBackgroundUrl)
                                        <form
                                            method="POST"
                                            action="{{ route('admin.project-settings.user-background.destroy', ['user' => $selectedUser->id]) }}"
                                            class="mt-2"
                                            onsubmit="return confirm('Remove the project background for this user?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="bi bi-trash me-1"></i>Remove background
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <div class="user-background-preview-wrap">
                                    <div class="d-flex justify-content-between gap-2 mb-2">
                                        <span class="fw-semibold">Preview</span>
                                        @if($userBackgroundUrl)
                                            <span class="text-muted small user-background-file-name">
                                                {{ data_get($userBackground, 'original_name', 'Background image') }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="user-background-preview">
                                        @if($userBackgroundUrl)
                                            <img src="{{ $userBackgroundUrl }}" alt="Project background for {{ $selectedUser->name }}">
                                        @else
                                            <div class="user-background-preview-empty">
                                                <i class="bi bi-image"></i>
                                                No personal project background for this user.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-muted">No active users found.</div>
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
