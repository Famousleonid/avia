<div id="updatePasswordModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">

        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary">office verification module</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group row">
                    <div class="col-md-12">
                        <label for="old_pass" class="col-form-label text-md-end text-sm text-gray">{{ __('Old password') }}</label>
                        <input id="old_pass" type="password" name="old_pass" class="form-control" required>
                        @error('password')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="col-md-12">
                        <label for="new_pass" class="col-form-label text-md-end text-sm">{{ __('New password') }}</label>
                        <input id="new_pass" type="password" name="password" class="form-control" required autocomplete="new-password">
                        @error('password')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="col-md-12">
                        <label for="confirm_pass" class="col-form-label text-md-end text-sm">{{ __('Confirm password') }}</label>
                        <input id="confirm_pass" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                        @error('password')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary mr-5" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn_confirm_change_pass">Verified</button>
            </div>
        </div>
    </div>
</div>





