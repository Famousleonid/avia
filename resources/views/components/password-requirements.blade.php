@props(['inputId'])

<div id="{{ $inputId }}-requirements"
     class="mt-2 small"
     data-password-requirements="{{ $inputId }}">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
        <span>Password strength</span>
        <strong data-password-strength-label>Not entered</strong>
    </div>
    <div class="progress mb-2" style="height: 6px;" role="progressbar" aria-label="Password strength" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
        <div class="progress-bar" data-password-strength-bar style="width: 0"></div>
    </div>
    <ul class="list-unstyled mb-0" aria-label="Password requirements">
        <li class="text-muted" data-password-rule="length">
            <i class="bi bi-circle me-1" aria-hidden="true"></i>
            At least {{ \App\Support\UserPasswordPolicy::minimum() }} characters
        </li>
        <li class="text-muted" data-password-rule="at">
            <i class="bi bi-circle me-1" aria-hidden="true"></i>
            Includes the @ symbol
        </li>
        <li class="text-muted">
            <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
            Common passwords are not accepted
        </li>
    </ul>
</div>
