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

        .project-settings-page .settings-card + .settings-card {
            margin-top: 16px;
        }

        .project-settings-page .form-check-input {
            cursor: pointer;
            height: 1.35rem;
            width: 2.55rem;
        }

        .project-settings-page textarea {
            min-height: 98px;
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

        @if($errors->any())
            <div class="alert alert-danger py-2">
                {{ $errors->first() }}
            </div>
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

            <div class="card bg-gradient settings-card">
                <div class="card-header">
                    <strong>Marketing</strong>
                </div>
                <div class="card-body">
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
                        <div class="form-text">
                            One or more emails separated by line breaks, commas, semicolons, or spaces.
                        </div>
                        @error('marketing_wo_estimate_email_recipients')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold" for="marketingWoEstimateEmailDelayDays">
                                Send after days
                            </label>
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
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-outline-primary">
                        Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
