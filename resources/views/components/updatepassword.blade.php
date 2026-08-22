<div id="updatePasswordModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">

            <div class="modal-header py-2">
                <h6 class="modal-title text-primary mb-0">Change Password</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pb-0">
                <div class="form-group">
                    <x-password-field id="legacy-current-password"
                                      name="old_pass"
                                      label="Current Password"
                                      autocomplete="current-password"
                                      label-class="small"
                                      :required="true" />
                </div>

                <div class="form-group mt-3">
                    <x-password-field id="legacy-new-password"
                                      name="password"
                                      label="New Password"
                                      autocomplete="new-password"
                                      label-class="small"
                                      :required="true"
                                      :policy="true" />
                </div>

                <div class="form-group mt-3">
                    <x-password-field id="legacy-password-confirmation"
                                      name="password_confirmation"
                                      label="Confirm New Password"
                                      autocomplete="new-password"
                                      label-class="small"
                                      :required="true" />
                </div>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary me-auto" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn_confirm_change_pass" onclick="showLoadingSpinner()">Change and sign out</button>
            </div>
        </div>
    </div>
</div>
