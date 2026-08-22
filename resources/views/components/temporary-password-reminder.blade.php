@if(!empty($temporaryPasswordReminder))
    <div class="modal fade"
         id="temporary-password-reminder"
         tabindex="-1"
         aria-labelledby="temporary-password-reminder-title"
         aria-describedby="temporary-password-reminder-description"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-warning shadow-lg">
                <div class="modal-header bg-warning-subtle">
                    <h2 class="modal-title fs-5" id="temporary-password-reminder-title">
                        Temporary password
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="temporary-password-reminder-description" class="mb-2">
                        An administrator assigned you a temporary password. Change it in your profile before
                        <strong>{{ format_project_date($temporaryPasswordReminder['expiresAt']) }}</strong>.
                    </p>
                    <p class="small text-body-secondary mb-0">
                        This reminder is shown once per day. After the deadline, only the password-change screen will remain available.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Remind me tomorrow</button>
                    <a href="{{ $temporaryPasswordReminder['profileUrl'] }}" class="btn btn-primary">
                        Change password in profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const reminder = document.getElementById('temporary-password-reminder');
            if (reminder && window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(reminder).show();
            }
        });
    </script>
@endif
