<div id="updatePasswordModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">

            <div class="modal-header py-2">
                <h6 class="modal-title text-primary mb-0">Change Password</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pb-0">
                <div class="form-group">
                    <label for="old_pass" class="small">Old Password</label>
                    <input id="old_pass" type="password" name="old_pass" class="form-control" required autofocus>
                    @error('old_pass')
                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="form-group mt-3">
                    <label for="new_pass" class="small">New Password</label>
                    <input id="new_pass" type="password" name="password" class="form-control" required autocomplete="new-password">
                    @error('password')
                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="form-group mt-3">
                    <label for="confirm_pass" class="small">Confirm Password</label>
                    <input id="confirm_pass" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                    @error('password_confirmation')
                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            </div>

            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary me-auto" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn_confirm_change_pass" onclick="showLoadingSpinner()">Verify</button>
            </div>
        </div>
    </div>
</div>
