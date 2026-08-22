(function () {
    'use strict';

    function setRuleState(container, rule, passed) {
        const row = container.querySelector('[data-password-rule="' + rule + '"]');
        if (!row) return;

        row.classList.toggle('text-success', passed);
        row.classList.toggle('text-muted', !passed);
        const icon = row.querySelector('i');
        if (icon) {
            icon.className = passed ? 'bi bi-check-circle-fill me-1' : 'bi bi-circle me-1';
        }
    }

    function updateStrength(input) {
        const container = document.querySelector('[data-password-requirements="' + input.id + '"]');
        if (!container) return;

        const value = input.value || '';
        const lengthPassed = value.length >= Number(input.minLength || 8);
        const atPassed = value.includes('@');
        setRuleState(container, 'length', lengthPassed);
        setRuleState(container, 'at', atPassed);

        let score = 0;
        if (value.length > 0) score = 20;
        if (lengthPassed) score += 30;
        if (atPassed) score += 25;
        if (value.length >= 12) score += 15;
        if (/[a-z]/.test(value) && /[A-Z]/.test(value) && /\d/.test(value)) score += 10;
        score = Math.min(score, 100);

        const label = container.querySelector('[data-password-strength-label]');
        const bar = container.querySelector('[data-password-strength-bar]');
        const progress = container.querySelector('[role="progressbar"]');
        let text = 'Not entered';
        let color = 'bg-secondary';
        if (value.length > 0 && score < 55) {
            text = 'Weak';
            color = 'bg-danger';
        } else if (score < 85 && value.length > 0) {
            text = 'Fair';
            color = 'bg-warning';
        } else if (value.length > 0) {
            text = 'Strong';
            color = 'bg-success';
        }

        label.textContent = text;
        bar.style.width = score + '%';
        bar.className = 'progress-bar ' + color;
        progress.setAttribute('aria-valuenow', String(score));
    }

    function passwordSubmitOverlay() {
        let overlay = document.getElementById('password-submit-overlay');
        if (overlay) return overlay;

        overlay = document.createElement('div');
        overlay.id = 'password-submit-overlay';
        overlay.className = 'password-submit-overlay';
        overlay.hidden = true;
        overlay.setAttribute('role', 'status');
        overlay.setAttribute('aria-live', 'polite');
        overlay.setAttribute('aria-label', 'Changing password. Please wait.');
        overlay.innerHTML = '<div class="password-submit-dots" aria-hidden="true"><span></span><span></span><span></span></div>';
        document.body.appendChild(overlay);

        return overlay;
    }

    function hidePasswordSubmitProgress() {
        const overlay = document.getElementById('password-submit-overlay');
        if (overlay) overlay.hidden = true;
        document.querySelectorAll('form[data-password-submit-loading]').forEach(function (form) {
            delete form.dataset.passwordSubmitting;
            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
                button.disabled = false;
            });
        });
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-password-toggle]');
        if (!button) return;

        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;

        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        button.setAttribute('aria-pressed', showing ? 'false' : 'true');
        button.setAttribute(
            'aria-label',
            (showing ? 'Show ' : 'Hide ') + (button.dataset.passwordLabel || 'password')
        );
        const icon = button.querySelector('i');
        if (icon) icon.className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
        const hiddenLabel = button.querySelector('.visually-hidden');
        if (hiddenLabel) hiddenLabel.textContent = showing ? 'Show password' : 'Hide password';
        input.focus({ preventScroll: true });
    });

    document.addEventListener('input', function (event) {
        if (event.target.matches('[data-password-policy-input]')) {
            updateStrength(event.target);
        }
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form[data-password-submit-loading]');
        if (!form) return;

        event.preventDefault();

        if (form.dataset.passwordSubmitting === 'true') {
            return;
        }

        form.dataset.passwordSubmitting = 'true';
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
            button.disabled = true;
        });
        passwordSubmitOverlay().hidden = false;

        // Let the browser paint the overlay before the document starts
        // navigating away for the regular form submission.
        window.setTimeout(function () {
            HTMLFormElement.prototype.submit.call(form);
        }, 120);
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-password-policy-input]').forEach(updateStrength);
    });

    window.addEventListener('pageshow', hidePasswordSubmitProgress);
})();
