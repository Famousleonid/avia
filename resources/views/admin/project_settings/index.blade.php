@extends('admin.master')

@section('style')
    <style>
        .project-settings-page {
            max-width: 980px;
        }

        .project-settings-page .settings-card {
            border-radius: 8px;
        }

        .project-settings-page .setting-row {
            align-items: center;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            min-height: 74px;
        }

        .project-settings-page .setting-copy {
            min-width: 0;
        }

        .project-settings-page .form-check-input {
            cursor: pointer;
            height: 1.35rem;
            width: 2.55rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid project-settings-page py-3 pb-5">
        <div class="mb-3">
            <h4 class="mb-1">Project Settings</h4>
            <div class="text-muted small">Global settings for printed forms and shared project behavior.</div>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.project-settings.update') }}">
            @csrf

            <div class="card bg-gradient settings-card">
                <div class="card-header">
                    <strong>Printed Forms</strong>
                </div>
                <div class="card-body">
                    <div class="setting-row">
                        <div class="setting-copy">
                            <div class="fw-semibold">QR code mark</div>
                            <div class="text-muted small">
                                Add the short public verification QR code to printable forms.
                            </div>
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
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-outline-primary">
                        Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
